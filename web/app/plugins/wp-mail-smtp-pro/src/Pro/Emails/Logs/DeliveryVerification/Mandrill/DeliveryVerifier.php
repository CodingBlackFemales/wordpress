<?php

namespace WPMailSMTP\Pro\Emails\Logs\DeliveryVerification\Mandrill;

use WP_Error;
use WPMailSMTP\Pro\Emails\Logs\DeliveryVerification\AbstractDeliveryVerifier;
use WPMailSMTP\Pro\Emails\Logs\DeliveryVerification\DeliveryStatus;
use WPMailSMTP\WP;

/**
 * Class DeliveryVerifier.
 *
 * Delivery verifier for Mandrill.
 *
 * @since 4.6.0
 */
class DeliveryVerifier extends AbstractDeliveryVerifier {

	/**
	 * Get the DeliveryStatus of the email.
	 *
	 * @since 4.6.0
	 *
	 * @return DeliveryStatus
	 */
	protected function get_delivery_status(): DeliveryStatus { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$delivery_status = new DeliveryStatus();
		$failed_states   = [
			'rejected',
			'bounced',
		];

		foreach ( $this->events as $event ) {
			if ( empty( $event['state'] ) ) {
				continue;
			}

			if ( $event['state'] === 'sent' ) {
				$delivery_status->set_status( DeliveryStatus::STATUS_DELIVERED );
				break;
			}

			if ( in_array( $event['state'], $failed_states, true ) ) {
				$delivery_status->set_status( DeliveryStatus::STATUS_FAILED );
				$delivery_status->set_error_code( $event['state'] );

				if ( ! empty( $event['reject'] ) ) {
					$delivery_status->set_fail_reason( $event['reject']['reason'] );
				} elseif ( ! empty( $event['bounce_description'] ) ) {
					$delivery_status->set_fail_reason( $event['bounce_description'] );
				}
				break;
			}
		}

		return $delivery_status;
	}

	/**
	 * Get events from the API response.
	 *
	 * @since 4.6.0
	 *
	 * @return mixed|WP_Error Returns `WP_Error` if unable to fetch a valid response from the API.
	 *                        Otherwise, returns an array containing the events.
	 */
	protected function get_events() {

		$mailer_options = $this->get_mailer_options();
		$message_id     = $this->get_email()->get_header( 'X-Msg-ID' );

		if ( empty( $message_id ) ) {
			return new WP_Error( 'mandrill_delivery_verifier_missing_message_id', esc_html__( 'Message ID is missing.', 'wp-mail-smtp-pro' ) );
		}

		$response = wp_safe_remote_post(
			'https://mandrillapp.com/api/1.0/messages/info',
			[
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'key' => $mailer_options['api_key'],
						'id'  => $message_id,
					]
				),
			]
		);

		$validated_response = $this->validate_response( $response );

		if ( is_wp_error( $validated_response ) ) {
			return $validated_response;
		}

		if ( empty( $validated_response['_id'] ) ) {
			return new WP_Error( 'mandrill_delivery_verifier_missing_response', WP::wp_remote_get_response_error_message( $response ) );
		}

		return [ $validated_response ];
	}
}
