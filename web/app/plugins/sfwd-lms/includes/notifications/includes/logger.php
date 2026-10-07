<?php
/**
 * Notification log entries.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action(
	'learndash_notification_after_send_notification',
	function ( $data ) {
		// translators: %s: Notification data.
		learndash_notifications_log_action( sprintf( __( 'Notification with these data: %s was sent.', 'learndash' ), learndash_notifications_parse_variable( $data ) ) );
	}
);

add_action(
	'learndash_notifications_after_send_delayed_email',
	function ( $data ) {
		// translators: %s: Notification data.
		learndash_notifications_log_action( sprintf( __( 'Scheduled notification with these data: %s was sent.', 'learndash' ), learndash_notifications_parse_variable( $data ) ) );
	}
);

add_action(
	'learndash_notifications_insert_delayed_email',
	function ( $data ) {
		// translators: %1$d: Timestamp the notification is scheduled for, %2$s: Notification data.
		learndash_notifications_log_action( sprintf( __( 'Scheduled notification for the timestamp: %1$d with these data: %2$s was stored in database.', 'learndash' ), $data['sent_on'], learndash_notifications_parse_variable( $data['shortcode_data'] ) ) );
	}
);

add_action(
	'learndash_notifications_update_delayed_email',
	function ( $data, $where, $count ) {
		// translators: %1$s: Notification data before the update, %2$s: Notification data after the update, %3$d: Number of affected rows, %4$s: Row or rows.
		learndash_notifications_log_action( sprintf( __( 'Scheduled notification with these data: %1$s was updated in database with these new data: %2$s. %3$d %4$s affected.', 'learndash' ), learndash_notifications_parse_variable( $where ), learndash_notifications_parse_variable( $data['shortcode_data'] ), $count, _n( 'row was', 'rows were', $count, 'learndash' ) ) );
	},
	10,
	3
);

add_action(
	'learndash_notifications_empty_delayed_emails_table',
	function ( $count ) {
		// translators: %1$d: Number of deleted rows, %2$s: Row or rows.
		learndash_notifications_log_action( sprintf( __( 'Empty delayed emails table process was run. %1$d %2$s deleted from database.', 'learndash' ), $count, _n( 'row was', 'rows were', $count, 'learndash' ) ) );
	}
);

add_action(
	'learndash_notifications_delete_delayed_emails',
	function ( $count ) {
		if (
			is_numeric( $count )
			&& $count > 0
		) {
			// translators: %1$d: Number of deleted rows, %2$s: Row or rows.
			learndash_notifications_log_action( sprintf( __( 'Expired scheduled notifications were deleted. %1$d %2$s deleted from database.', 'learndash' ), $count, _n( 'row was', 'rows were', $count, 'learndash' ) ) );
		}
	}
);
