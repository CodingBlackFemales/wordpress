<?php

namespace WPMailSMTP\Pro\AdditionalConnections\Admin;

use WPMailSMTP\ConnectionInterface;
use WPMailSMTP\Pro\AdditionalConnections\AdditionalConnections;

/**
 * Class TestTab.
 *
 * @since 3.7.0
 */
class TestTab {

	/**
	 * Register hooks.
	 *
	 * @since 3.7.0
	 */
	public function hooks() {

		add_action(
			'wp_mail_smtp_admin_pages_test_tab_display_form_send_to_after',
			[ $this, 'display_connection_selector' ]
		);

		add_filter(
			'wp_mail_smtp_admin_pages_test_tab_process_post_connection',
			[ $this, 'set_test_email_connection' ],
			10,
			2
		);
	}

	/**
	 * Display connection selector.
	 *
	 * @since 3.7.0
	 */
	public function display_connection_selector() {

		$connections = ( new AdditionalConnections() )->get_configured_connections();

		if ( empty( $connections ) ) {
			return;
		}

		$primary_connection = wp_mail_smtp()->get_connections_manager()->get_primary_connection();

		// Bail if the primary connection is not configured.
		if ( ! $primary_connection->get_mailer()->is_mailer_complete() ) {
			return;
		}

		// Add a primary connection to the list as a first item.
		array_unshift( $connections, $primary_connection );

		// Pre-select the connection from (in priority order):
		// 1. The just-submitted POST value, so the page reload after a test keeps
		// the connection the user picked.
		// 2. ?connection_id=<id> from the URL — set by the EmailSendingErrors
		// banner's "Send Test Email" CTA so the test runs through the
		// connection that had the failure.
		// Falls back to primary in every other case. Each candidate is validated
		// against the list of configured connections before being honored.
		// Read-only sticky-form lookahead during form render. Nonce verification fires on submit in TestTab::process_post().
		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
		$posted_id = isset( $_POST['wp-mail-smtp']['test']['connection'] ) ? sanitize_key( wp_unslash( $_POST['wp-mail-smtp']['test']['connection'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$requested_id = isset( $_GET['connection_id'] ) ? sanitize_key( wp_unslash( $_GET['connection_id'] ) ) : '';
		$selected_id  = $primary_connection->get_id();

		foreach ( [ $posted_id, $requested_id ] as $candidate ) {
			if ( $candidate === '' ) {
				continue;
			}

			foreach ( $connections as $connection ) {
				if ( $connection->get_id() === $candidate ) {
					$selected_id = $candidate;

					break 2;
				}
			}
		}
		?>
		<div id="wp-mail-smtp-setting-row-test_email_connection" class="wp-mail-smtp-setting-row wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-test_email_html">
					<?php esc_html_e( 'Connection', 'wp-mail-smtp-pro' ); ?>
				</label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<div class="wp-mail-smtp-connection-selector">
					<?php foreach ( $connections as $connection ) : ?>
						<label for="wp-mail-smtp-setting-test_email_connection_<?php echo esc_attr( $connection->get_id() ); ?>">
							<input type="radio" id="wp-mail-smtp-setting-test_email_connection_<?php echo esc_attr( $connection->get_id() ); ?>"
										 name="wp-mail-smtp[test][connection]" value="<?php echo esc_attr( $connection->get_id() ); ?>"
										 <?php checked( $selected_id, $connection->get_id() ); ?>
							/>
							<span><?php echo esc_html( $connection->get_title() ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
				<p class="desc">
					<?php esc_html_e( 'Choose which connection you\'d like to use to send a test email.', 'wp-mail-smtp-pro' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Set test email connection.
	 *
	 * @since 3.7.0
	 *
	 * @param ConnectionInterface $connection The Connection object.
	 * @param array               $data       Post data.
	 */
	public function set_test_email_connection( $connection, $data ) {

		if ( ! empty( $data['test']['connection'] ) ) {
			$connections_manager = wp_mail_smtp()->get_connections_manager();
			$connection_id       = sanitize_key( $data['test']['connection'] );
			$connection          = $connections_manager->get_connection( $connection_id );

			$connections_manager->set_mail_connection( $connection );
		}

		return $connection;
	}
}
