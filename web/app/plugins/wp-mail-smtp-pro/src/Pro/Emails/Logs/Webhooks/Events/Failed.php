<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Events;

use WPMailSMTP\Pro\Alerts\Alerts;
use WPMailSMTP\Pro\Emails\Logs\Email;

/**
 * Class Failed. Failed event.
 *
 * @since 3.3.0
 */
class Failed implements EventInterface {

	/**
	 * Handle event.
	 *
	 * @since 3.3.0
	 *
	 * @param Email $email Email object.
	 * @param array $data  Event data.
	 *
	 * @return bool
	 */
	public function handle( $email, $data ) {

		$raw_reason = $this->get_error_message( $data );
		$error_code = $this->get_error_code( $email, $data );

		// Build display text with translatable wrapper.
		if ( ! empty( $raw_reason ) ) {
			/* translators: %s - The reason the email was rejected. */
			$error_text = sprintf( esc_html__( 'The email failed to be delivered. Reason: %s', 'wp-mail-smtp-pro' ), $raw_reason );
		} else {
			$error_text = esc_html__( 'The email failed to be delivered. No specific reason was provided by the API.', 'wp-mail-smtp-pro' );
		}

		$email->set_status( Email::STATUS_UNSENT );
		$email->set_error_text( $error_text );
		$email->save();

		// Trigger alerts.
		( new Alerts() )->handle_hard_bounced_email( $error_text, $email );

		/**
		 * Fires when an email delivery failure is detected via webhook.
		 *
		 * @since 4.8.0
		 *
		 * @param string $mailer_slug   Current mailer name.
		 * @param string $error_code    Error code.
		 * @param string $error_message Raw error message (not translated).
		 */
		do_action( 'wp_mail_smtp_email_delivery_failed', $email->get_mailer(), $error_code, $raw_reason ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName

		return true;
	}

	/**
	 * Get error message from event data.
	 *
	 * @since 3.3.0
	 *
	 * @param array $data Event data.
	 *
	 * @return string
	 */
	protected function get_error_message( $data ) {

		return '';
	}

	/**
	 * Get error code from event data.
	 *
	 * @since 4.8.0
	 *
	 * @param Email $email Email object.
	 * @param array $data  Event data.
	 *
	 * @return string
	 */
	protected function get_error_code( $email, $data ) {

		return 'unknown';
	}
}
