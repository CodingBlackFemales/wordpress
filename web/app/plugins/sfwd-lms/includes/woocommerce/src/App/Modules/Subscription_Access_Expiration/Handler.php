<?php
/**
 * Subscription access expiration class file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

namespace LearnDash\WooCommerce\Modules\Subscription_Access_Expiration;

use LearnDash\WooCommerce\Settings\Status_Access;
use Learndash_WooCommerce;
use WC_Subscription;

/**
 * Withdraws course access when the paid-up term of the subscription that granted it runs out.
 *
 * Access is normally withdrawn by the subscription status change handlers, which depend on a single
 * scheduled job running at the end of the prepaid term. When that job does not run the subscription
 * stays in `pending-cancel` and the enrollment is never withdrawn. Recording the deadline instead
 * lets LearnDash expire the access on the next request, whether or not the status change ever
 * arrives.
 *
 * @since 5.2.0
 */
class Handler {
	/**
	 * The hook the deferred cancellation runs on.
	 *
	 * @since 5.2.0
	 *
	 * @var string
	 */
	private const CANCEL_ACTION_KEY = 'learndash_woocommerce_cancel_lapsed_subscription';

	/**
	 * Caps course access at the end of the paid-up term of the subscriptions that granted it.
	 *
	 * Only access granted by this plugin is affected. Enrollments made by hand, by a group, or by
	 * another add-on are left alone, as is access backed by a one-off order. An access extension
	 * granted to the user by hand also stands, for as long as it runs.
	 *
	 * @since 5.2.0
	 *
	 * @param int $expires_on Timestamp the access currently expires on. 0 when it does not expire.
	 * @param int $course_id  Course ID.
	 * @param int $user_id    User ID.
	 *
	 * @return int Timestamp the access expires on. 0 when it does not expire.
	 */
	public function expire_course_access( $expires_on, $course_id, $user_id ) {
		if ( ! function_exists( 'wcs_get_subscription' ) ) {
			return $expires_on;
		}

		$granting_orders = $this->get_granting_orders( $course_id, $user_id );

		if ( empty( $granting_orders ) ) {
			return $expires_on;
		}

		// A group grants this course independently of any order, so the term does not bound it.
		if ( learndash_user_group_enrolled_to_course( $user_id, $course_id ) ) {
			return $expires_on;
		}

		$grants = $this->sort_grants_by_type( $granting_orders );

		if ( empty( $grants['subscriptions'] ) ) {
			return $expires_on;
		}

		/*
		 * A subscription bought as a product variation records its parent order here as well as
		 * the subscription itself, and that order is not a separate purchase. Only an order that
		 * belongs to none of these subscriptions stands on its own, and grants access no
		 * subscription term can withdraw.
		 */
		if ( ! empty( array_diff( $grants['loose_orders'], $grants['related_orders'] ) ) ) {
			return $expires_on;
		}

		$term_ends_on = $this->get_term_end( $grants['subscriptions'] );

		// No term bounds the access, so whatever expiration is already in place stands.
		if ( is_null( $term_ends_on ) ) {
			return $expires_on;
		}

		$term_ends_on = $this->apply_access_extension( $term_ends_on, $course_id, $user_id );

		// Never push the expiration back beyond one the course already sets for itself.
		return empty( $expires_on )
			? $term_ends_on
			: min( $expires_on, $term_ends_on );
	}

	/**
	 * Cancels a subscription whose paid-up term ran out, once its course access is withdrawn.
	 *
	 * The end of the paid-up term is normally applied by a scheduled job that moves the
	 * subscription from `pending-cancel` to `cancelled`. Where that job never ran, the access
	 * expiration is the point at which the term is known to be over, so the subscription is
	 * brought into line here rather than left reporting as live in WooCommerce.
	 *
	 * @since 5.2.0
	 *
	 * @param int $user_id   User ID.
	 * @param int $course_id Course ID.
	 *
	 * @return void
	 */
	public function cancel_on_expiration( $user_id, $course_id ): void {
		if ( ! function_exists( 'wcs_get_subscription' ) ) {
			return;
		}

		$granting_orders = $this->get_granting_orders( $course_id, $user_id );

		if ( empty( $granting_orders ) ) {
			return;
		}

		/*
		 * Reports and exports check access for many users in one pass. Cancelling inline there
		 * would write to WooCommerce and email every affected customer mid-request, so hand those
		 * off to the queue and keep the immediate path for the subscriber's own visit.
		 */
		$subscriber_is_here = get_current_user_id() === (int) $user_id;

		foreach ( $granting_orders as $order_id ) {
			$subscription = wcs_get_subscription( $order_id );

			if (
				! $subscription instanceof WC_Subscription
				|| ! $this->is_term_over( $subscription )
			) {
				continue;
			}

			if ( $subscriber_is_here ) {
				$this->cancel_lapsed( $subscription->get_id() );

				continue;
			}

			$this->defer_cancellation( $subscription->get_id() );
		}
	}

	/**
	 * Cancels a subscription whose paid-up term has run out.
	 *
	 * Also runs from the queue, so the subscription is checked again here rather than trusting the
	 * state it was in when the work was handed off.
	 *
	 * WooCommerce Subscriptions applies the end of a paid-up term itself, and handing the work back
	 * to it would be the shorter route. It is done here instead so the store owner gets an order
	 * note saying why the subscription was cancelled, and so the cancellation can be filtered.
	 *
	 * @since 5.2.0
	 *
	 * @param int $subscription_id Subscription ID.
	 *
	 * @return void
	 */
	public function cancel_lapsed( $subscription_id ): void {
		if ( ! function_exists( 'wcs_get_subscription' ) ) {
			return;
		}

		$subscription = wcs_get_subscription( $subscription_id );

		if (
			! $subscription instanceof WC_Subscription
			|| ! $this->is_term_over( $subscription )
		) {
			return;
		}

		/**
		 * Filters whether to cancel a subscription once its paid-up term has run out.
		 *
		 * @since 5.2.0
		 *
		 * @param bool            $cancel       True to cancel the subscription, false otherwise.
		 * @param WC_Subscription $subscription WC_Subscription object.
		 *
		 * @return bool True to cancel the subscription, false otherwise.
		 */
		if ( ! apply_filters( 'learndash_woocommerce_cancel_subscription_on_course_access_expiration', true, $subscription ) ) {
			return;
		}

		/*
		 * Cancelling is refused when the gateway that took the payment is no longer available, and
		 * asking for it anyway throws. The course access has already been withdrawn by this point,
		 * so leaving the subscription as it stands is the safe outcome.
		 */
		if ( ! $subscription->can_be_updated_to( 'cancelled' ) ) {
			return;
		}

		$subscription->update_status(
			'cancelled',
			sprintf(
			// translators: LearnDash course.
				_x(
					'The paid-up term ended and %s access was withdrawn.',
					'LearnDash course',
					'learndash'
				),
				learndash_get_custom_label_lower( 'course' )
			)
		);
	}

	/**
	 * Gets the orders this plugin recorded as granting the course to the user.
	 *
	 * @since 5.2.0
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 *
	 * @return int[] Order IDs, empty when this plugin did not grant the course.
	 */
	private function get_granting_orders( $course_id, $user_id ): array {
		// The cheapest way to rule out a course this plugin never granted.
		$granted_by = Learndash_WooCommerce::get_courses_access_counter( $user_id );

		if (
			empty( $granted_by[ $course_id ] )
			|| ! is_array( $granted_by[ $course_id ] )
		) {
			return [];
		}

		return $granted_by[ $course_id ];
	}

	/**
	 * Splits the granting orders into the subscriptions and the plain orders behind them.
	 *
	 * @since 5.2.0
	 *
	 * @param int[] $granting_orders Order IDs that granted the course.
	 *
	 * @return array{subscriptions: WC_Subscription[], related_orders: int[], loose_orders: int[]} The grants by type.
	 */
	private function sort_grants_by_type( array $granting_orders ): array {
		$subscriptions  = [];
		$related_orders = [];
		$loose_orders   = [];

		foreach ( $granting_orders as $order_id ) {
			$subscription = wcs_get_subscription( $order_id );

			if ( ! $subscription instanceof WC_Subscription ) {
				$loose_orders[] = (int) $order_id;

				continue;
			}

			$subscriptions[] = $subscription;

			/*
			 * Every order type, rather than the default set, so that an order the customer
			 * resubscribed with counts as belonging to the subscription too.
			 */
			$related_orders = array_merge(
				$related_orders,
				array_map( 'intval', $subscription->get_related_orders( 'ids', 'any' ) )
			);
		}

		return [
			'subscriptions'  => $subscriptions,
			'related_orders' => $related_orders,
			'loose_orders'   => $loose_orders,
		];
	}

	/**
	 * Gets the timestamp the last of the paid-up terms runs out on.
	 *
	 * @since 5.2.0
	 *
	 * @param WC_Subscription[] $subscriptions Subscriptions that granted the course.
	 *
	 * @return int|null Timestamp the last term ends on, or null when no term bounds the access.
	 */
	private function get_term_end( array $subscriptions ): ?int {
		$term_ends_on = 0;

		foreach ( $subscriptions as $subscription ) {
			/*
			 * Keeping access when a subscription expires is a documented choice, and a separate
			 * one from cancelling, so only expiration is exempt here.
			 *
			 * The statuses a store has picked out as denying access are deliberately not consulted.
			 * Those govern what happens the moment a status changes, whereas a paid-up term running
			 * out is a deadline the customer already paid for and agreed to.
			 */
			if (
				$subscription->has_status( 'expired' )
				&& Status_Access::is_access_removal_disabled_on_expiration()
			) {
				return null;
			}

			$end_date = $subscription->get_time( 'end' );

			// An open-ended subscription keeps the access for as long as it exists.
			if ( empty( $end_date ) ) {
				return null;
			}

			// Where several subscriptions grant the same course, the last one to lapse wins.
			$term_ends_on = max( $term_ends_on, $end_date );
		}

		return $term_ends_on;
	}

	/**
	 * Extends the deadline to cover an access extension granted to the user by hand.
	 *
	 * An access extension is set by hand, one user at a time, and LearnDash already lets one
	 * outrank the expiration a course sets for itself. A term running out does not get to undo it
	 * either, so the extension stands wherever it reaches furthest.
	 *
	 * @since 5.2.0
	 *
	 * @param int $term_ends_on Timestamp the paid-up term ends on.
	 * @param int $course_id    Course ID.
	 * @param int $user_id      User ID.
	 *
	 * @return int Timestamp the access expires on.
	 */
	private function apply_access_extension( int $term_ends_on, $course_id, $user_id ): int {
		$extended_access = learndash_course_get_extended_access_timestamp( (int) $course_id, $user_id );

		if ( empty( $extended_access ) ) {
			return $term_ends_on;
		}

		return max( $term_ends_on, $extended_access );
	}

	/**
	 * Hands the cancellation off to the queue.
	 *
	 * @since 5.2.0
	 *
	 * @param int $subscription_id Subscription ID.
	 *
	 * @return void
	 */
	private function defer_cancellation( int $subscription_id ): void {
		if ( ! function_exists( 'as_enqueue_async_action' ) ) {
			return;
		}

		$action_args = [ 'subscription_id' => $subscription_id ];

		if ( as_next_scheduled_action( self::CANCEL_ACTION_KEY, $action_args ) ) {
			return;
		}

		as_enqueue_async_action( self::CANCEL_ACTION_KEY, $action_args );
	}

	/**
	 * Checks whether a subscription is pending cancellation with its paid-up term already over.
	 *
	 * @since 5.2.0
	 *
	 * @param WC_Subscription $subscription WC_Subscription object.
	 *
	 * @return bool True when the term has run out, false otherwise.
	 */
	private function is_term_over( WC_Subscription $subscription ): bool {
		if ( ! $subscription->has_status( 'pending-cancel' ) ) {
			return false;
		}

		$end_date = $subscription->get_time( 'end' );

		// Still inside the paid-up term, so the scheduled job is simply not due yet.
		return ! empty( $end_date ) && $end_date <= time();
	}
}
