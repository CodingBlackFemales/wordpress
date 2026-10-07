<?php
/**
 * Activation functions.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Notifications
 *
 * cspell:ignore leanrdash
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Schedules the add-on's cron events and flags the drip check for its first run.
 *
 * Runs on `learndash_activated`, putting back what the deactivation before it cleared. Every step is guarded, so a site
 * that is already set up is unchanged.
 *
 * @since 5.2.0
 *
 * @return void
 */
function learndash_notifications_activate() {
	if ( ! wp_next_scheduled( 'learndash_notifications_cron' ) ) {
		wp_schedule_event( time(), 'twicedaily', 'learndash_notifications_cron' );
	}
	if ( ! wp_next_scheduled( 'leanrdash_notifications_send_delayed_email' ) ) {
		wp_schedule_single_event( time(), 'leanrdash_notifications_send_delayed_email' );
	}
	update_option( 'learndash_notifications_drips_check', true );
}

/**
 * Activates the add-on when the site has none of the scheduled work that produces.
 *
 * Deactivating a standalone copy clears that work, and a site that then starts loading the bundled copy has no
 * activation to put it back.
 *
 * The recurring event is the signal because it is the one piece a set-up site always has. The other two are absent for
 * ordinary reasons, so watching them would recover on every request.
 *
 * @since 5.2.0
 *
 * @return void
 */
function learndash_notifications_ensure_scheduled() {
	if ( wp_next_scheduled( 'learndash_notifications_cron' ) ) {
		return;
	}

	learndash_notifications_activate();
}
