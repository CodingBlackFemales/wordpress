<?php

namespace WPMailSMTP\Pro;

use WPMailSMTP\EmailSendingDebug;
use WPMailSMTP\Pro\Alerts\Alerts;
use WPMailSMTP\WP;
use Exception;

/**
 * Trait MailCatcherTrait.
 *
 * @since 3.7.0
 */
trait MailCatcherTrait {

	/**
	 * Whether current mail connection is backup connection.
	 *
	 * @since 3.7.0
	 *
	 * @var bool
	 */
	private static $is_backup_connection = false;

	/**
	 * Send email.
	 *
	 * @since 3.7.0
	 *
	 * @throws Exception When sending via PHPMailer fails for some reason.
	 *
	 * @return bool
	 */
	public function send() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.MaxExceeded

		$connections_manager = wp_mail_smtp()->get_connections_manager();
		$connection          = $connections_manager->get_mail_connection();

		if ( ! $connection->is_primary() ) {
			$this->addCustomHeader( 'X-WP-Mail-SMTP-Connection', $connection->get_id() );
		}

		if ( self::$is_backup_connection ) {
			$this->addCustomHeader( 'X-WP-Mail-SMTP-Connection-Type', 'backup' );
		}

		$exception = false;

		try {
			$is_sent = parent::send();
		} catch ( Exception $e ) {
			$is_sent   = false;
			$exception = $e;
		}

		$is_backup_sent = false;

		if (
			! $is_sent &&
			! self::$is_backup_connection &&
			! $this->is_test_email() &&
			! $this->is_setup_wizard_test_email() &&
			! $this->is_emailing_blocked()
		) {
			$backup_connections = wp_mail_smtp()->get_pro()->get_backup_connections();

			$alert_type    = Alerts::FAILED_EMAIL;
			$error_message = $this->latest_error;

			// Capture before the backup re-entry overwrites Logs::current_email_id.
			$email_logs     = wp_mail_smtp()->get_pro()->get_logs();
			$primary_log_id = $email_logs->get_current_email_id();

			$primary_debug = [
				'email_log_id' => $primary_log_id,
			];
			$backup_debug = [];

			if ( $backup_connections->is_ready() ) {
				self::$is_backup_connection = true;
				$is_backup_sent             = $backup_connections->send_email();
				self::$is_backup_connection = false;

				if ( $is_backup_sent ) {
					$alert_type = Alerts::FAILED_PRIMARY_EMAIL;
				} else {
					$alert_type           = Alerts::FAILED_BACKUP_EMAIL;
					$backup_error_message = $this->latest_error;

					if ( $error_message !== $backup_error_message ) {
						$error_message  = esc_html(
							__( 'Primary connection', 'wp-mail-smtp-pro' ) . WP::EOL . $error_message
						);
						$error_message .= WP::EOL . WP::EOL . esc_html(
							__( 'Backup connection', 'wp-mail-smtp-pro' ) . WP::EOL . $backup_error_message
						);
					}
				}

				// Primary record: 'sent' when the backup was rescued.
				if ( $is_backup_sent ) {
					$primary_debug['status'] = 'sent';
				} else {
					$backup_debug['email_log_id'] = $email_logs->get_current_email_id();
				}
			}

			$backup_connection = $connections_manager->get_mail_backup_connection();

			EmailSendingDebug::merge( $connection->get_id(), $primary_debug );

			if ( ! empty( $backup_debug ) && $backup_connection ) {
				EmailSendingDebug::merge( $backup_connection->get_id(), $backup_debug );
			}

			// Process alerts.
			( new Alerts() )->handle_failed_email( $error_message, $this, $connection->get_mailer_slug(), $alert_type, $primary_log_id );
		}

		$connections_manager->reset_mail_connection();

		if ( $exception && ! $is_backup_sent ) {
			throw $e;
		}

		return $is_sent || $is_backup_sent;
	}
}
