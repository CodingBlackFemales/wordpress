<?php
/**
 * PayPal Standard Migration Scheduler.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Migration;

/**
 * PayPal Standard Migration Scheduler.
 *
 * This class is used to schedule the migration of PayPal Standard subscriptions to PayPal Checkout.
 *
 * @since 4.25.3
 */
class Scheduler {
	/**
	 * The action hook for the subscription migration.
	 *
	 * @since 4.25.3
	 *
	 * @var string
	 */
	public static string $schedule_migration_hook = 'learndash_paypal_standard_subscription_migration';

	/**
	 * Schedule the migration with Action Scheduler.
	 *
	 * @since 4.25.3
	 *
	 * @param int    $product_id       The product ID.
	 * @param int    $user_id          The user ID.
	 * @param string $payment_token_id The payment token ID.
	 *
	 * @return bool True if event successfully scheduled. False on failure.
	 */
	public function schedule_migration( int $product_id, int $user_id, string $payment_token_id ) {
		return as_schedule_single_action(
			time(), // Schedule the migration immediately.
			self::$schedule_migration_hook,
			[
				'product_id'       => $product_id,
				'user_id'          => $user_id,
				'payment_token_id' => $payment_token_id,
			]
		) > 0;
	}
}
