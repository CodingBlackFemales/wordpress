<?php

namespace WPMailSMTP\Pro\Alerts\Providers\CustomWebhook;

use WPMailSMTP\Admin\DebugEvents\DebugEvents;
use WPMailSMTP\Options;
use WPMailSMTP\WP;
use WPMailSMTP\Pro\Alerts\Alert;
use WPMailSMTP\Pro\Alerts\Alerts;
use WPMailSMTP\Pro\Alerts\Handlers\CanValidateWebhookUrlTrait;
use WPMailSMTP\Pro\Alerts\Handlers\HandlerInterface;

/**
 * Class Handler. Custom webhook alerts.
 *
 * @since 3.5.0
 */
class Handler implements HandlerInterface {

	/**
	 * Webhook URL validation trait.
	 *
	 * @since 4.9.0
	 */
	use CanValidateWebhookUrlTrait;

	/**
	 * Whether current handler can handle provided alert.
	 *
	 * @since 3.5.0
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
	 * Send alert notification via custom webhook.
	 *
	 * @since 3.5.0
	 *
	 * @param Alert $alert Alert object.
	 *
	 * @return bool
	 */
	public function handle( Alert $alert ) { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks, Generic.Metrics.CyclomaticComplexity.TooHigh

		$connections = (array) Options::init()->get( 'alert_custom_webhook', 'connections' );

		$connections = array_unique(
			array_filter(
				$connections,
				function ( $connection ) {
					return isset( $connection['webhook_url'] ) && filter_var( $connection['webhook_url'], FILTER_VALIDATE_URL );
				}
			),
			SORT_REGULAR
		);

		if ( empty( $connections ) ) {
			return false;
		}

		$result = false;
		$errors = [];

		foreach ( $connections as $connection ) {
			$webhook_url = $connection['webhook_url'];

			$args = [
				'method'      => 'POST',
				'timeout'     => MINUTE_IN_SECONDS,
				'redirection' => 0,
				'user-agent'  => sprintf( 'wp-mail-smtp-alerts-webhooks/%s', WPMS_PLUGIN_VER ),
				'headers'     => [
					'Content-Type' => 'application/json',
				],
				'body'        => wp_json_encode( $this->get_message( $alert ) ),
			];

			/**
			 * Filters custom webhook request arguments.
			 *
			 * @since 3.5.0
			 *
			 * @param array $args       Custom webhook request arguments.
			 * @param array $connection Connection settings.
			 * @param Alert $alert      Alert object.
			 */
			$args = apply_filters( 'wp_mail_smtp_pro_alerts_providers_custom_webhook_handler_handle_request_args', $args, $connection, $alert );

			$allow_internal_host = static function ( $is_external, $host, $url ) use ( $webhook_url ) {
				return $url === $webhook_url ? true : $is_external;
			};

			$allow_configured_port = static function ( $ports, $host, $url ) use ( $webhook_url ) {
				if ( $url !== $webhook_url ) {
					return $ports;
				}

				$port = wp_parse_url( $webhook_url, PHP_URL_PORT );

				return $port ? array_merge( (array) $ports, [ (int) $port ] ) : $ports;
			};

			add_filter( 'http_request_host_is_external', $allow_internal_host, 10, 3 );
			add_filter( 'http_allowed_safe_ports', $allow_configured_port, 10, 3 );

			$response = $this->safe_webhook_request( $webhook_url, $args );

			remove_filter( 'http_request_host_is_external', $allow_internal_host, 10 );
			remove_filter( 'http_allowed_safe_ports', $allow_configured_port, 10 );

			if ( $response === false ) {
				$errors[] = esc_html__( 'Webhook URL points to an internal-network address and was rejected.', 'wp-mail-smtp-pro' );

				continue;
			}

			$response_code = wp_remote_retrieve_response_code( $response );

			if ( $response_code === 200 ) {
				$result = true;
			} else {
				$errors[] = WP::wp_remote_get_response_error_message( $response );
			}
		}

		DebugEvents::add_debug( esc_html__( 'Custom Webhook alert request was sent.', 'wp-mail-smtp-pro' ) );

		if ( ! empty( $errors ) ) {
			DebugEvents::add( esc_html__( 'Alert: Custom Webhook.', 'wp-mail-smtp-pro' ) . WP::EOL . implode( WP::EOL, array_unique( $errors ) ) );
		}

		return $result;
	}

	/**
	 * Build message array.
	 *
	 * @since 3.5.0
	 *
	 * @param Alert $alert Alert object.
	 *
	 * @return array
	 */
	private function get_message( Alert $alert ) {

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

		return [
			'event'           => $alert->get_type(),
			'site_title'      => get_bloginfo( 'name' ),
			'site_url'        => home_url(),
			'general_message' => $alert_message,
			'email_data'      => $alert->get_data(),
		];
	}
}
