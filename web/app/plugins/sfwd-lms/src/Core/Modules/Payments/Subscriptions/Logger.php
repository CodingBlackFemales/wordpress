<?php
/**
 * Subscription Logger.
 *
 * Handles logging for subscription payment processing.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Subscriptions;

use Learndash_Logger;

/**
 * Subscription logger class.
 *
 * @since 4.25.0
 */
class Logger extends Learndash_Logger {
	/**
	 * Returns the label.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return esc_html__( 'Subscription Scheduler', 'learndash' );
	}

	/**
	 * Returns the name.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'subscription_scheduler';
	}
}
