<?php
/**
 * Notifications admin tools.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

add_action( 'admin_init', 'learndash_notifications_tool_empty_db_table' );
function learndash_notifications_tool_empty_db_table() {
	if ( ! isset( $_GET['page'] ) || ! isset( $_GET['tool'] ) || ! isset( $_GET['nonce'] )
		|| $_GET['page'] != 'ld-notifications-status' || $_GET['tool'] != 'empty-table'
	) {
		return;
	}

	if ( ! wp_verify_nonce( $_GET['nonce'], 'ld_notifications_empty_db_table' )
		|| ! current_user_can( 'manage_options' ) ) {
		return;
	}

	learndash_notifications_empty_db_table();

	add_action( 'admin_notices', 'learndash_notifications_admin_notice_empty_db_table' );
}

/**
 * Prints the admin notice shown after the delayed emails table is emptied.
 *
 * @since 5.2.0
 *
 * @return void
 */
function learndash_notifications_admin_notice_empty_db_table() {
	?>

	<div class="notice notice-success is-dismissible">
		<p><?php esc_html_e( 'Your ld_notifications_delayed_emails DB table has been successfully emptied.', 'learndash' ); ?></p>
	</div>

	<?php
}

// Fix recipient ajax tool.
add_action( 'wp_ajax_ld_notifications_fix_recipients', 'learndash_notifications_ajax_fix_recipients' );

/**
 * Recalculates recipients for queued delayed notification emails, in batches.
 *
 * @since 5.2.0
 *
 * @return void
 */
function learndash_notifications_ajax_fix_recipients() {
	if ( ! isset( $_POST['nonce'] ) ) {
		wp_die();
	}

	if ( ! wp_verify_nonce( $_POST['nonce'], 'ld_notifications_fix_recipients' ) ) {
		wp_die();
	}

	global $wpdb;

	$checked_emails = [];

	if (
		isset( $_POST['checked_emails'] )
		&& is_array( $_POST['checked_emails'] )
	) {
		$sanitized_emails = map_deep( wp_unslash( $_POST['checked_emails'] ), 'sanitize_text_field' );

		if ( is_array( $sanitized_emails ) ) {
			$checked_emails = $sanitized_emails;
		}
	}

	$step      = intval( $_POST['step'] );
	$per_batch = 100;
	$offset    = ( $step - 1 ) * $per_batch;
	if ( isset( $_POST['total'] ) && $_POST['total'] > 0 ) {
		$total = intval( $_POST['total'] );
	} else {
		$total_query = "SELECT COUNT(*) FROM {$wpdb->prefix}ld_notifications_delayed_emails";
		$total       = $wpdb->get_var( $total_query );
	}
	$percentage = $total > 0 ? number_format( ( ( $offset + $per_batch ) / $total ) * 100, 2 ) : 100;
	$percentage = $percentage >= 100 ? 100 : $percentage;

	$emails_query = "SELECT * FROM {$wpdb->prefix}ld_notifications_delayed_emails LIMIT {$per_batch} OFFSET {$offset}";

	$emails = $wpdb->get_results( $emails_query );

	foreach ( $emails as $email ) {
		$recipients     = maybe_unserialize( $email->recipient );
		$shortcode_data = maybe_unserialize( $email->shortcode_data );

		if ( isset( $shortcode_data['user_id'] ) && $shortcode_data['user_id'] > 0 &&
			isset( $shortcode_data['notification_id'] ) && $shortcode_data['notification_id'] > 0 &&
			isset( $shortcode_data['course_id'] ) && $shortcode_data['course_id'] > 0
		) {
			$notification     = get_post( $shortcode_data['notification_id'] );
			$recipients       = learndash_notifications_get_recipients( $shortcode_data['notification_id'] );
			$recipient_emails = learndash_notifications_get_recipients_emails( $recipients, $shortcode_data['user_id'], $shortcode_data['course_id'], $notification );
			$bcc              = learndash_notifications_get_bcc( $shortcode_data['notification_id'] );
			if ( ! empty( $bcc ) ) {
				$recipient_emails = array_merge( $recipient_emails, $bcc );
			}
			$recipient_emails = array_filter(
				$recipient_emails,
				function ( $value ) {
					if ( ! empty( $value ) ) {
						return true;
					} else {
						return false;
					}
				}
			);
			$recipient_emails = array_unique( $recipient_emails );

			$current_email = [
				'recipients'     => $recipient_emails,
				'shortcode_data' => $shortcode_data,
			];

			if ( ! empty( $recipient_emails ) && ! in_array( $current_email, $checked_emails, true ) ) {
				$wpdb->update(
					"{$wpdb->prefix}ld_notifications_delayed_emails",
					[
						'recipient' => maybe_serialize( $recipient_emails ),
					],
					[
						'id' => $email->id,
					],
					[ '%s' ],
					[ '%d' ]
				);
			} else {
				$wpdb->delete(
					"{$wpdb->prefix}ld_notifications_delayed_emails",
					[
						'id' => $email->id,
					],
					[ '%d' ]
				);
			}

			$checked_emails[] = $current_email;
		}
	}

	if ( ! empty( $emails ) ) {
		$return = [
			'step'           => intval( $step + 1 ),
			'total'          => intval( $total ),
			'percentage'     => $percentage,
			'checked_emails' => $checked_emails,
		];
	} else {
		$return = [
			'step' => 'complete',
		];
	}

	echo json_encode( $return );

	wp_die();
}
