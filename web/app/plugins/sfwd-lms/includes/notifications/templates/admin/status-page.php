<?php
/**
 * Admin status page template.
 *
 * @since 5.2.0
 * @version 5.2.0
 *
 * @package LearnDash\Notifications
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<div id="learndash-settings-status" class="learndash-status">
	<h2><?php esc_html_e( 'Status', 'learndash' ); ?></h2>

	<table cellspacing="0" class="learndash-support-settings">
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Server Cron Setup', 'learndash' ); ?></th>
				<td>
					<?php echo isset( $values['cron_setup'] ) && $values['cron_setup'] == 'true' ? esc_html__( 'Yes', 'learndash' ) : '<span style="color:red;">' . esc_html__( 'Not yet detected', 'learndash' ) . '</span>'; ?>
					<?php
					echo ! isset( $values['cron_setup'] ) || $values['cron_setup'] == 'false'
						? wp_kses(
							sprintf(
								// translators: %s: URL of the cron setup documentation.
								__( ', <a href="%s" target="_blank" rel="noreferrer">click here</a> for cron setup instruction (it may take some times for this value to be updated)', 'learndash' ),
								'https://www.learndash.com/support/docs/faqs/email-notifications-send-time/'
							),
							[
								'a' => [
									'href'   => [],
									'target' => [],
									'rel'    => [],
								],
							]
						)
						: '';
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Queued Emails in DB', 'learndash' ); ?></th>
				<td>
					<?php $emails_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ld_notifications_delayed_emails" ); ?>
					<?php $emails_count = $emails_count > 0 ? $emails_count : 0; ?>
					<?php echo $emails_count; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Last Run', 'learndash' ); ?></th>
				<td>
					<?php $learndash_notifications_last_run = ! empty( $values['last_run'] ) ? gmdate( 'Y-m-d H:i:s', $values['last_run'] ) : __( 'Not yet detected', 'learndash' ); ?>
					<?php echo esc_html( $learndash_notifications_last_run ); ?>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<div id="learndash-settings-tools" class="learndash-status">
	<h2><?php esc_html_e( 'Tools', 'learndash' ); ?></h2>

	<table cellspacing="0" class="learndash-support-settings">
		<thead>
			<tr>
				<th scope="col" class="learndash-support-settings-left"></th>
				<th scope="col" class="learndash-support-settings-right"></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Empty DB Table', 'learndash' ); ?></th>
				<td>
					<?php
					$url = add_query_arg(
						[
							'page'  => 'ld-notifications-status',
							'tool'  => 'empty-table',
							'nonce' => wp_create_nonce( 'ld_notifications_empty_db_table' ),
						],
						admin_url( '/admin.php' )
					);
					?>
					<a href="<?php echo esc_url( $url ); ?>" class="empty-db-table button button-secondary"><?php esc_html_e( 'Run', 'learndash' ); ?></a>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Fix Scheduled Notification Recipients', 'learndash' ); ?></th>
				<td>
					<a href="#" id="ld-fix-recipient-button" class="button button-secondary"><?php esc_html_e( 'Run', 'learndash' ); ?></a>
				</td>
			</tr>
		</tbody>
	</table>
</div>
