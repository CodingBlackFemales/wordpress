<?php
/**
 * Main logger class file.
 *
 * @package LearnDash\Notifications
 */

namespace LearnDash\Notifications;

use Learndash_Logger;

/**
 * Main logger class.
 *
 * @since 5.2.0
 */
class Logger extends Learndash_Logger {
	/**
	 * Get the Logger admin label.
	 *
	 * @inheritDoc
	 */
	public function get_label(): string {
		return __( 'Notifications', 'learndash' );
	}

	/**
	 * Get the Logger name.
	 *
	 * @inheritDoc
	 */
	public function get_name(): string {
		return 'notifications';
	}
}
