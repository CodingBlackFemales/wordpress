<?php

namespace WPMailSMTP\Pro\Alerts\Providers\Push;

use WPMailSMTP\Admin\DebugEvents\DebugEvents;
use WPMailSMTP\Pro\Alerts\Alert;
use WPMailSMTP\Pro\Alerts\Alerts;
use WPMailSMTP\Pro\Alerts\Handlers\HandlerInterface;
use WPMailSMTP\WP;

/**
 * Class Handler. Push notifications alerts.
 *
 * @since 4.4.0
 */
class Handler implements HandlerInterface {

	/**
	 * The maximum rate at which alerts can be handled by this handler.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	const RATE_LIMIT = MINUTE_IN_SECONDS * 30;

	/**
	 * The last time an alert was handled by this handler.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	const LAST_EXECUTED_TRANSIENT = 'wp_mail_smtp_push_notifications_alert_handler_timestamp';

	/**
	 * Whether current handler can handle provided alert.
	 *
	 * @since 4.4.0
	 *
	 * @param Alert $alert Alert object.
	 *
	 * @return bool
	 */
	public function can_handle( Alert $alert ) {

		return in_array(
			$alert->get_type(),
			[
				Alerts::FAILED_EMAIL,
				Alerts::FAILED_PRIMARY_EMAIL,
				Alerts::FAILED_BACKUP_EMAIL,
				Alerts::HARD_BOUNCED_EMAIL,
			],
			true
		);
	}

	/**
	 * Handle alert.
	 * Send alert notification via push notifications.
	 *
	 * @since 4.4.0
	 *
	 * @param Alert $alert Alert object.
	 *
	 * @return bool
	 */
	public function handle( Alert $alert ) {

		$provider = new Provider();
		$result   = false;
		$errors   = [];

		$args = [
			'timeout' => MINUTE_IN_SECONDS,
			'json'    => [
				'urgency' => 'high',
				'payload' => wp_json_encode( $this->get_message( $alert ) ),
			],
		];

		/**
		 * Filters push notifications alerts handler request arguments.
		 *
		 * @since 4.4.0
		 *
		 * @param array $args  Request arguments.
		 * @param Alert $alert Alert object.
		 */
		$args = apply_filters( 'wp_mail_smtp_pro_alerts_providers_push_handler_handle_request_args', $args, $alert );

		$response = $provider->request( 'POST', 'push/v1/notify', $args );

		if ( ! is_wp_error( $response ) ) {
			$result = true;
		} else {
			$errors[] = $response->get_error_message();
		}

		DebugEvents::add_debug( esc_html__( 'Push notifications alert request was sent.', 'wp-mail-smtp-pro' ) );

		if ( ! empty( $errors ) && DebugEvents::is_debug_enabled() ) {
			DebugEvents::add( esc_html__( 'Alert: Push notifications.', 'wp-mail-smtp-pro' ) . WP::EOL . implode( WP::EOL, array_unique( $errors ) ) );
		}

		// Start a new rate limit period, if previous one expired.
		if ( self::get_remaining_rate_limit_seconds() === 0 ) {
			set_transient( self::LAST_EXECUTED_TRANSIENT, time(), self::RATE_LIMIT );
		}

		return $result;
	}

	/**
	 * Build message array.
	 *
	 * @since 4.4.0
	 *
	 * @param Alert $alert Alert object.
	 *
	 * @return array
	 */
	private function get_message( Alert $alert ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$data          = $alert->get_data();
		$alert_title   = esc_html__( 'Email sending failed', 'wp-mail-smtp-pro' );
		$alert_message = '';

		switch ( $alert->get_type() ) {
			case Alerts::FAILED_EMAIL:
				$alert_message = esc_html__( 'Your Site Failed to Send an Email', 'wp-mail-smtp-pro' );
				break;

			case Alerts::FAILED_PRIMARY_EMAIL:
				$alert_message = esc_html__( 'Your Site failed to send an email via the Primary connection, but the email was sent successfully via the Backup connection', 'wp-mail-smtp-pro' );
				break;

			case Alerts::FAILED_BACKUP_EMAIL:
				$alert_message = esc_html__( 'Your Site failed to send an email via Primary and Backup connection', 'wp-mail-smtp-pro' );
				break;

			case Alerts::HARD_BOUNCED_EMAIL:
				$alert_message = esc_html__( 'An email failed to be delivered', 'wp-mail-smtp-pro' );
				break;
		}

		// Check if quick links are available.
		$quick_link = null;

		if ( ! empty( $data['log_link'] ) ) {
			$quick_link = $data['log_link'];
		} elseif ( ! empty( $data['debug_event_link'] ) ) {
			$quick_link = $data['debug_event_link'];
		}

		$message = [
			'title' => $alert_title,
			'body'  => $alert_message,
		];

		// Add quick links if available.
		if ( ! empty( $quick_link ) ) {
			$message['data'] = [
				'url' => $quick_link,
			];
		}

		return $message;
	}

	/**
	 * Return the number of seconds until rate limit expires.
	 *
	 * @since 4.4.0
	 *
	 * @return int Number of seconds until rate limit expires.
	 */
	public static function get_remaining_rate_limit_seconds() {

		$last_executed_time = get_transient( self::LAST_EXECUTED_TRANSIENT );

		// Bail early if rate limit already expired.
		if ( $last_executed_time === false ) {
			return 0;
		}

		$remaining_time = self::RATE_LIMIT - ( time() - $last_executed_time );

		// Bail early if rate limit just expired.
		if ( $remaining_time <= 0 ) {
			return 0;
		}

		return $remaining_time;
	}
}
