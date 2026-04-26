<?php
/**
 * Subscription Scheduler.
 *
 * Handles scheduling and checking for subscriptions.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Subscriptions;

use DateTime;
use DateTimeZone;
use LearnDash\Core\Repositories\Subscription as Subscription_Repository;
use LearnDash\Core\Utilities\Cast;

/**
 * Scheduler class for subscriptions.
 *
 * @since 4.25.0
 */
class Scheduler {
	/**
	 * Action hook for the daily subscription payment check.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static string $daily_check_hook = 'learndash_payment_subscription_check_due_payments';

	/**
	 * Action hook for processing individual subscription payments.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static string $payment_process_hook = 'learndash_payment_subscription_process_due_payment';

	/**
	 * Action group for subscription payment actions.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static string $action_group = 'learndash-subscription-payments';

	/**
	 * Register the daily subscription payment check action if not already scheduled.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function register_daily_check() {
		if ( as_next_scheduled_action( self::$daily_check_hook, [], self::$action_group ) ) {
			return;
		}

		/**
		 * Filter the start timestamp for the daily subscription check.
		 *
		 * @since 4.25.0
		 *
		 * @param int $start_timestamp The timestamp when the daily check should start. Default is today at midnight.
		 *
		 * @return int The start timestamp.
		 */
		$start_timestamp = Cast::to_int( apply_filters( 'learndash_payment_subscription_daily_check_start_timestamp', strtotime( 'today midnight' ) ) );

		as_schedule_recurring_action(
			$start_timestamp,
			DAY_IN_SECONDS,
			self::$daily_check_hook,
			[],
			self::$action_group
		);
	}

	/**
	 * Checks for subscriptions due for payment up to the current time and schedule payment actions.
	 * This ensures we catch any subscriptions that were missed in previous days.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function check_due_payments() {
		$today = new DateTime( 'today', new DateTimeZone( 'UTC' ) );

		// Start from the beginning of time to catch all missed subscriptions.
		$start_timestamp = Cast::to_int(
			/**
			 * Filters the start timestamp for checking due payments.
			 *
			 * @since 4.25.0
			 *
			 * @param int $start_timestamp The timestamp when the daily check should start. Default is 0 (beginning of time).
			 *
			 * @return int The start timestamp.
			 */
			apply_filters(
				'learndash_payment_subscription_check_start_timestamp',
				0
			)
		);

		$end_timestamp = Cast::to_int(
			/**
			 * Filters the end timestamp for checking due payments.
			 *
			 * @since 4.25.0
			 *
			 * @param int $end_timestamp The timestamp when the daily check should end. Default is today at 23:59:59.
			 *
			 * @return int The end timestamp.
			 */
			apply_filters(
				'learndash_payment_subscription_check_end_timestamp',
				$today->setTime( 23, 59, 59 )->getTimestamp()
			)
		);

		/**
		 * Filters the batch size for processing subscription payments.
		 *
		 * @since 4.25.0
		 *
		 * @param int $batch_size The number of subscriptions to process per batch. Default 50.
		 *
		 * @return int The batch size.
		 */
		$batch_size = Cast::to_int( apply_filters( 'learndash_payment_subscription_batch_size', 50 ) );

		/**
		 * Filters the delay in seconds between each scheduled payment action.
		 * Spreading actions over time prevents overwhelming the payment gateway
		 * when a large number of subscriptions are due at once (e.g. on first run
		 * after a plugin upgrade).
		 *
		 * @since 5.0.4
		 *
		 * @param int $delay_between_actions Delay in seconds between each action. Default 5.
		 *
		 * @return int The delay in seconds.
		 */
		$delay_between_actions = max( 0, Cast::to_int( apply_filters( 'learndash_payment_subscription_delay_between_actions', 5 ) ) );

		$offset          = 0;
		$scheduled_count = 0;

		do {
			$subscriptions = Subscription_Repository::find_due_for_payment(
				$start_timestamp,
				$end_timestamp,
				$batch_size,
				$offset
			);

			if ( empty( $subscriptions ) ) {
				break;
			}

			foreach ( $subscriptions as $subscription_data ) {
				$args = [
					'subscription_id' => $subscription_data['subscription_id'],
					'user_id'         => $subscription_data['user_id'],
				];

				if ( as_next_scheduled_action( self::$payment_process_hook, $args, self::$action_group ) ) {
					continue;
				}

				as_schedule_single_action(
					time() + ( $scheduled_count * $delay_between_actions ),
					self::$payment_process_hook,
					$args,
					self::$action_group
				);

				++$scheduled_count;
			}

			$subscription_count = count( $subscriptions );

			$offset += $batch_size;
		} while ( $subscription_count === $batch_size );
	}
}
