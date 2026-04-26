<?php
/**
 * Subscription handler class.
 *
 * Handles subscription-related actions.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Subscriptions;

use LearnDash\Core\Enums\Commerce\Cancellation_Reason;
use LearnDash\Core\Models\Commerce\Subscription as Subscription_Model;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;

/**
 * Subscription handler class.
 *
 * @since 4.25.0
 */
class Handler {
	/**
	 * Handles subscription cancellation.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function handle_cancellation(): void {
		// Check if this is a cancellation request.

		$action = SuperGlobals::get_get_var( 'ld_action' );

		if ( $action !== 'cancel_subscription' ) {
			return;
		}

		$nonce           = Cast::to_string( SuperGlobals::get_get_var( 'ld_nonce' ) );
		$subscription_id = Cast::to_int( SuperGlobals::get_get_var( 'ld_subscription_id' ) );

		if (
			! $subscription_id
			|| ! wp_verify_nonce( $nonce, 'ld_subscription_cancel_' . $subscription_id )
		) {
			wp_die( esc_html__( 'Invalid request.', 'learndash' ) );
		}

		// Get the subscription.

		$subscription = Subscription_Model::find( $subscription_id );

		if ( ! $subscription ) {
			wp_die( esc_html__( 'Subscription not found.', 'learndash' ) );
		}

		// Check if the subscription can be canceled.

		if ( ! $subscription->can_be_cancelled() ) {
			wp_die( esc_html__( 'You cannot cancel this subscription.', 'learndash' ) );
		}

		// Cancel the subscription.

		$cancellation_result = $subscription->cancel(
			learndash_is_admin_user()
				? Cancellation_Reason::CANCELED_BY_ADMIN()->getValue()
				: Cancellation_Reason::CANCELED_BY_STUDENT()->getValue()
		);

		// Add a transient to display the result of the cancellation.

		set_transient( 'ld_subscription_canceled_user_' . get_current_user_id(), $cancellation_result, MINUTE_IN_SECONDS );

		// Redirect to the called page, removing the query arguments.

		wp_safe_redirect(
			remove_query_arg(
				[
					'ld_action',
					'ld_subscription_id',
					'ld_nonce',
				]
			)
		);
		exit;
	}
}
