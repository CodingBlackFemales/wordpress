<?php
/**
 * Subscription Retry Scheduler.
 *
 * Handles scheduling of payment retries for failed subscriptions.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Subscriptions;

use LearnDash\Core\Models\Commerce\Subscription;

/**
 * Retry Scheduler class for subscriptions.
 *
 * @since 4.25.3
 */
class Retry_Scheduler {
	/**
	 * Action hook for subscription payment retries.
	 *
	 * @since 4.25.3
	 *
	 * @var string
	 */
	public static string $retry_hook = 'learndash_subscription_payment_retry';

	/**
	 * Action group for subscription retry actions.
	 *
	 * @since 4.25.3
	 *
	 * @var string
	 */
	public static string $action_group = 'learndash-subscription-retries';

	/**
	 * Schedules a payment retry for the subscription.
	 *
	 * @since 4.25.3
	 *
	 * @param Subscription $subscription The subscription.
	 *
	 * @return bool True if scheduled successfully, false otherwise.
	 */
	public static function schedule( Subscription $subscription ): bool {
		$next_retry_timestamp = $subscription->get_next_retry_timestamp();

		if ( $next_retry_timestamp <= 0 ) {
			return false;
		}

		if ( self::is_scheduled( $subscription ) ) {
			return false;
		}

		$action_id = as_schedule_single_action(
			$next_retry_timestamp,
			self::$retry_hook,
			[
				$subscription->get_id(),
			],
			self::$action_group
		);

		return $action_id > 0;
	}

	/**
	 * Cancels any scheduled retries for the subscription.
	 *
	 * @since 4.25.3
	 *
	 * @param Subscription $subscription The subscription.
	 *
	 * @return bool True if cancelled successfully, false otherwise.
	 */
	public static function cancel_retry( Subscription $subscription ): bool {
		return ! is_null(
			as_unschedule_action(
				self::$retry_hook,
				[
					$subscription->get_id(),
				],
				self::$action_group
			)
		);
	}

	/**
	 * Checks if a retry is scheduled for the subscription.
	 *
	 * @since 4.25.3
	 *
	 * @param Subscription $subscription The subscription.
	 *
	 * @return bool True if a retry is scheduled, false otherwise.
	 */
	public static function is_scheduled( Subscription $subscription ): bool {
		return as_next_scheduled_action(
			self::$retry_hook,
			[ $subscription->get_id() ],
			self::$action_group
		) !== false;
	}
}
