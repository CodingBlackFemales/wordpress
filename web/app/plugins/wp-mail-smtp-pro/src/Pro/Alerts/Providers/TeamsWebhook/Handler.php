<?php

namespace WPMailSMTP\Pro\Alerts\Providers\TeamsWebhook;

use WPMailSMTP\Admin\DebugEvents\DebugEvents;
use WPMailSMTP\Options;
use WPMailSMTP\Pro\Alerts\Alert;
use WPMailSMTP\Pro\Alerts\Alerts;
use WPMailSMTP\Pro\Alerts\Handlers\CanValidateWebhookUrlTrait;
use WPMailSMTP\Pro\Alerts\Handlers\HandlerInterface;
use WPMailSMTP\WP;

/**
 * Class Handler. Microsoft Teams Incoming Webhook alerts.
 *
 * @since 4.1.0
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
	 * @since 4.1.0
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
	 * Send alert notification via Microsoft Teams Incoming Webhook.
	 *
	 * @since 4.1.0
	 *
	 * @param Alert $alert Alert object.
	 *
	 * @return bool
	 */
	public function handle( Alert $alert ) {

		$connections = (array) Options::init()->get( 'alert_teams_webhook', 'connections' );

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
				'method'  => 'POST',
				'timeout' => MINUTE_IN_SECONDS,
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'type'        => 'message',
						'attachments' => [
							[
								'contentType' => 'application/vnd.microsoft.card.adaptive',
								'contentUrl'  => null,
								'content'     => [
									'$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
									'type'    => 'AdaptiveCard',
									'version' => '1.2',
									'body'    => $this->get_message( $alert ),
								],
							],
						],
					]
				),
			];

			/**
			 * Filters Microsoft Teams Incoming Webhook request arguments.
			 *
			 * @since 4.1.0
			 *
			 * @param array $connection Connection settings.
			 * @param Alert $alert      Alert object.
			 *
			 * @param array $args       Microsoft Teams Incoming Webhook request arguments.
			 */
			$args = apply_filters( 'wp_mail_smtp_pro_alerts_providers_teams_webhook_handler_handle_request_args', $args, $connection, $alert );

			$response = $this->safe_webhook_request( $webhook_url, $args );

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

		DebugEvents::add_debug( esc_html__( 'Microsoft Teams Webhook alert request was sent.', 'wp-mail-smtp-pro' ) );

		if ( ! empty( $errors ) ) {
			DebugEvents::add( esc_html__( 'Alert: Microsoft Teams Webhook.', 'wp-mail-smtp-pro' ) . WP::EOL . implode( WP::EOL, array_unique( $errors ) ) );
		}

		return $result;
	}

	/**
	 * Build message array.
	 *
	 * @since 4.1.0
	 *
	 * @param Alert $alert Alert object.
	 *
	 * @return array
	 * @link  https://api.slack.com/messaging/interactivity
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

		$message = [
			[
				'type'   => 'TextBlock',
				'text'   => "[$site_title] $alert_message",
				'weight' => 'bolder',
				'size'   => 'medium',
				'wrap'   => true,
			],
			[
				'type' => 'TextBlock',
				'text' => sprintf(
					"**%s**:\n\n %s",
					esc_html__( 'Website URL', 'wp-mail-smtp-pro' ),
					home_url()
				),
				'wrap' => true,
			],
			[
				'type' => 'TextBlock',
				'text' => sprintf(
					"**%s**:\n\n %s",
					esc_html__( 'To email addresses', 'wp-mail-smtp-pro' ),
					$data['to_email_addresses']
				),
				'wrap' => true,
			],
			[
				'type' => 'TextBlock',
				'text' => sprintf(
					"**%s**:\n\n %s",
					esc_html__( 'Subject', 'wp-mail-smtp-pro' ),
					$data['subject']
				),
				'wrap' => true,
			],
		];

		if ( ! empty( $data['error_message'] ) ) {
			$message[] = [
				'type' => 'TextBlock',
				'text' => sprintf(
					"**%s**:\n\n %s",
					esc_html__( 'Error message', 'wp-mail-smtp-pro' ),
					$data['error_message']
				),
				'wrap' => true,
			];
		}

		$quick_links = [];

		if ( ! empty( $data['log_link'] ) ) {
			$quick_links[] = $this->build_link(
				$data['log_link'],
				/* translators: %s - Email Log ID. */
				sprintf( esc_html__( 'Email Log [#%s]', 'wp-mail-smtp-pro' ), $data['log_id'] )
			);
		}

		if ( ! empty( $data['debug_event_link'] ) ) {
			$quick_links[] = $this->build_link(
				$data['debug_event_link'],
				/* translators: %s - Debug Event ID. */
				sprintf( esc_html__( 'Debug Event [#%s]', 'wp-mail-smtp-pro' ), $data['debug_event_id'] )
			);
		}

		if ( ! empty( $quick_links ) ) {
			$message[] = [
				'type' => 'TextBlock',
				'text' => implode( ' | ', $quick_links ),
				'wrap' => true,
			];
		}

		$message[] = [
			'type' => 'TextBlock',
			'text' => $this->build_link(
				$settings_link,
				esc_html__( 'WP Mail SMTP settings', 'wp-mail-smtp-pro' )
			),
			'wrap' => true,
		];

		$message[] = [
			'type' => 'TextBlock',
			'text' => sprintf(
			/* translators: %s - Troubleshooting guide link. */
				esc_html__( "Need more help?\n %s.", 'wp-mail-smtp-pro' ),
				$this->build_link(
					wp_mail_smtp()->get_utm_url(
						'https://wpmailsmtp.com/docs/how-to-troubleshoot-wp-mail-smtp',
						[
							'medium'  => 'Microsoft Teams Alerts Notification',
							'content' => 'Read Our Troubleshooting Guide',
						]
					),
					esc_html__( 'Read our troubleshooting guide', 'wp-mail-smtp-pro' )
				)
			),
			'wrap' => true,
		];

		return $message;
	}

	/**
	 * Build link.
	 *
	 * @since 4.1.0
	 *
	 * @param string $url  Link URL.
	 * @param string $text Link text.
	 *
	 * @return string
	 */
	private function build_link( $url, $text ) {

		return "[$text]($url)";
	}
}
