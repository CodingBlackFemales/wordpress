<?php
/**
 * Cron functions.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Notifications
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

function learndash_notifications_lock_cron_process( $key = '' ) {
	$lock_file = WP_CONTENT_DIR . '/uploads/learndash/ld-notifications/process_lock_' . $key . '.txt';
	$dirname   = dirname( $lock_file );

	if ( ! is_dir( $dirname ) ) {
		wp_mkdir_p( $dirname );
	}

	$lock_fp = fopen( $lock_file, 'c+' );

	return flock( $lock_fp, LOCK_EX | LOCK_NB );
}

function learndash_notifications_cron() {
	// Now try to get exclusive lock on the file.
	if ( ! learndash_notifications_lock_cron_process( 'minute' ) ) {
		// If you can't lock then abort because another process is already running
		exit();
	}

	ignore_user_abort( true );
	set_time_limit( 0 );

	// error_log( 'Cron fired by LearnDash Notifications at: ' . date( 'Y-m-d h:i:sa' ) );

	learndash_notifications_send_delayed_emails();
	learndash_notifications_send_enroll_course_via_group_queue();

	// update cron status
	learndash_notifications_update_cron_status();

	// error_log( 'LearnDash Notifications cron finished at: ' . date( 'Y-m-d h:i:sa' ) );
}

// add_action( 'learndash_notifications_cron', 'learndash_notifications_cron' );

function learndash_notifications_cron_hourly() {
	// error_log( 'Hourly cron fired by LearnDash Notifications at: ' . date( 'Y-m-d h:i:sa' ) );

	// Now try to get exclusive lock on the file.
	if ( ! learndash_notifications_lock_cron_process( 'hourly' ) ) {
		// If you can't lock then abort because another process is already running
		exit();
	}

	ignore_user_abort( true );
	set_time_limit( 0 );

	learndash_notifications_resend_missed_delayed_emails();
	learndash_notifications_not_logged_in();
	learndash_notifications_course_expires();
	learndash_notifications_course_expires_after();
	learndash_notifications_update_scheduled_lesson_available_notifications_cron();
	learndash_notifications_delete_learndash_user_data_cron();

	// update cron status
	learndash_notifications_update_cron_status();

	// error_log( 'LearnDash Notifications hourly cron finished at: ' . date( 'Y-m-d h:i:sa' ) );
}

// add_action( 'learndash_notifications_cron_hourly', 'learndash_notifications_cron_hourly' );

/**
 * Adds the every minute interval to the WordPress cron schedules.
 *
 * @since 5.2.0
 *
 * @param array<string,array<string,mixed>> $schedules Registered cron schedules.
 *
 * @return array<string,array<string,mixed>> Cron schedules with the every minute interval added.
 */
function learndash_notifications_cron_schedules( $schedules ) {
	$schedules['every_minute'] = [
		'interval' => 60,
		'display'  => __( 'Every Minute', 'learndash' ),
	];

	return $schedules;
}

// add_filter( 'cron_schedules', 'learndash_notifications_cron_schedules' );

function learndash_notifications_update_cron_status() {
	if ( isset( $_GET['cron'] ) ) {
		$status               = get_option( 'learndash_notifications_status', [] );
		$status['cron_setup'] = 'true';
		$status['last_run']   = time();
		update_option( 'learndash_notifications_status', $status );
	}
}
