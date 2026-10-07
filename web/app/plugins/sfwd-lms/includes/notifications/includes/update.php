<?php
/**
 * LD Notifications Update class.
 *
 * @package LearnDash\Notifications
 *
 * cspell:ignore leanrdash shcedule
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * LD Notifications Update class
 */
class LD_Notifications_Update {
	public function __construct() {
		add_action( 'init', [ $this, 'update_plugin_cron_shcedule' ] );
	}

	/**
	 * Update plugin cron schedule for each plugin update
	 */
	public function update_plugin_cron_shcedule() {
		$saved_version   = get_option( 'ld_notifications_version' );
		$current_version = LEARNDASH_VERSION;

		if ( $saved_version === false || version_compare( $saved_version, $current_version, '<' ) ) {
			wp_clear_scheduled_hook( 'learndash_notifications_cron' );
			wp_clear_scheduled_hook( 'learndash_notifications_cron_hourly' );

			if ( ! wp_next_scheduled( 'learndash_notifications_cron' ) ) {
				wp_schedule_event( time(), 'twicedaily', 'learndash_notifications_cron' );
			}
			// queue this for kick start the check
			if ( ! wp_next_scheduled( 'leanrdash_notifications_send_delayed_email' ) ) {
				wp_schedule_single_event( time(), 'leanrdash_notifications_send_delayed_email' );
			}
			update_option( 'learndash_notifications_drips_check', true );
			update_option( 'ld_notifications_version', $current_version );
			update_option( 'ld_notifications_init', time() );
		}
	}
}

new LD_Notifications_Update();
