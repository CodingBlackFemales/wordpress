<?php

namespace WPMailSMTP\Pro\Alerts\Providers\DiscordWebhook;

use WPMailSMTP\Admin\DebugEvents\DebugEvents;
use WPMailSMTP\Options;
use WPMailSMTP\Pro\Alerts\Alert;
use WPMailSMTP\Pro\Alerts\Alerts;
use WPMailSMTP\Pro\Alerts\Handlers\CanValidateWebhookUrlTrait;
use WPMailSMTP\Pro\Alerts\Handlers\HandlerInterface;
use WPMailSMTP\WP;

/**
 * Class Handler. Discord Incoming Webhook alerts.
 *
 * @since 4.2.0
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
	 * @since 4.2.0
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
	 * Send alert notification via Discord Incoming Webhook.
	 *
	 * @since 4.2.0
	 *
	 * @param Alert $alert Alert object.
	 *
	 * @return bool
	 */
	public function handle( Alert $alert ) {

		$connections = (array) Options::init()->get( 'alert_discord_webhook', 'connections' );

		$connections = array_unique(
			array_filter(
				$connections,
				function( $connection ) {
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
				'method'  => 'POST',
				'timeout' => MINUTE_IN_SECONDS,
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'embeds' => $this->get_message( $alert ),
					]
				),
			];

			/**
			 * Filters Discord Incoming Webhook request arguments.
			 *
			 * @since 4.2.0
			 *
			 * @param array $args       Discord Incoming Webhook request arguments.
			 * @param array $connection Connection settings.
			 * @param Alert $alert      Alert object.
			 */
			$args = apply_filters( 'wp_mail_smtp_pro_alerts_providers_discord_webhook_handler_handle_request_args', $args, $connection, $alert );

			$response = $this->safe_webhook_request( $webhook_url, $args );

			if ( $response === false ) {
				$errors[] = esc_html__( 'Webhook URL points to an internal-network address and was rejected.', 'wp-mail-smtp-pro' );

				continue;
			}

			$response_code = wp_remote_retrieve_response_code( $response );

			if ( in_array( $response_code, [ 200, 204 ], true ) ) {
				$result = true;
			} else {
				$errors[] = WP::wp_remote_get_response_error_message( $response );
			}
		}

		DebugEvents::add_debug( esc_html__( 'Discord Webhook alert request was sent.', 'wp-mail-smtp-pro' ) );

		if ( ! empty( $errors ) ) {
			DebugEvents::add( esc_html__( 'Alert: Discord Webhook.', 'wp-mail-smtp-pro' ) . WP::EOL . implode( WP::EOL, array_unique( $errors ) ) );
		}

		return $result;
	}

	/**
	 * Build message array.
	 *
	 * @since 4.2.0
	 *
	 * @link https://discord.com/developers/docs/resources/webhook#execute-webhook
	 *
	 * @param Alert $alert Alert object.
	 *
	 * @return array
	 */
	private function get_message( Alert $alert ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$data          = $alert->get_data();
		$site_title    = get_bloginfo( 'name' );
		$settings_link = wp_mail_smtp()->get_admin()->get_admin_page_url();
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

		// Compose Discord payload fields.
		$message = [];

		$message_group = [
			'title'  => $alert_message,
			'fields' => [],
		];

		$message_group_fields = [
			[
				'name'   => esc_html__( 'Website', 'wp-mail-smtp-pro' ),
				'value'  => $site_title,
				'inline' => false,
			],
			[
				'name'   => esc_html__( 'Website URL', 'wp-mail-smtp-pro' ),
				'value'  => home_url(),
				'inline' => false,
			],
			[
				'name'   => esc_html__( 'To email addresses', 'wp-mail-smtp-pro' ),
				'value'  => $data['to_email_addresses'],
				'inline' => false,
			],
			[
				'name'   => esc_html__( 'Subject', 'wp-mail-smtp-pro' ),
				'value'  => $data['subject'],
				'inline' => false,
			],
		];

		if ( ! empty( $data['error_message'] ) ) {
			$message_group_fields[] = [
				'name'   => esc_html__( 'Error Message', 'wp-mail-smtp-pro' ),
				'value'  => $data['error_message'],
				'inline' => false,
			];
		}

		// Check if quick links are available.
		$quick_links = [];

		if ( ! empty( $data['log_link'] ) ) {
			$quick_links[] = sprintf(
				'[Email Log [#%s]](%s)',
				$data['log_id'],
				$data['log_link']
			);
		}

		if ( ! empty( $data['debug_event_link'] ) ) {
			$quick_links[] = sprintf(
				'[Debug Event [#%s]](%s)',
				$data['debug_event_id'],
				$data['debug_event_link']
			);
		}

		// Add quick links if available.
		if ( ! empty( $quick_links ) ) {
			$message_group_fields[] = [
				'name'   => '',
				'value'  => implode( ' | ', $quick_links ),
				'inline' => false,
			];
		}

		// Add link to admin settings.
		$message_group_fields[] = [
			'name'   => '',
			'value'  => sprintf(
				'[WP Mail SMTP Settings](%s)',
				$settings_link
			),
			'inline' => false,
		];

		// Add link to troubleshooting guide.
		$message_group_fields[] = [
			'name'   => '',
			'value'  => sprintf(
				"%s\n[%s](%s)",
				esc_html__( 'Need more help?', 'wp-mail-smtp-pro' ),
				esc_html__( 'Read our troubleshooting guide', 'wp-mail-smtp-pro' ),
				wp_mail_smtp()->get_utm_url(
					'https://wpmailsmtp.com/docs/how-to-troubleshoot-wp-mail-smtp',
					[
						'medium'  => 'Discord Alerts Notification',
						'content' => 'Read Our Troubleshooting Guide',
					]
				)
			),
			'inline' => false,
		];

		// Add fields to payload.
		$message_group['fields'] = $message_group_fields;

		$message[] = $message_group;

		return $message;
	}
}
