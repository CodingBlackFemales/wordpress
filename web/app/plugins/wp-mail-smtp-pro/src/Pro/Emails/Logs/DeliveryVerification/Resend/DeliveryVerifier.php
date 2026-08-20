<?php

namespace WPMailSMTP\Pro\Emails\Logs\DeliveryVerification\Resend;

use WP_Error;
use WPMailSMTP\Pro\Emails\Logs\DeliveryVerification\AbstractDeliveryVerifier;
use WPMailSMTP\Pro\Emails\Logs\DeliveryVerification\DeliveryStatus;
use WPMailSMTP\WP;

/**
 * Class DeliveryVerifier.
 *
 * @since 4.7.0
 */
class DeliveryVerifier extends AbstractDeliveryVerifier {

	/**
	 * Get the DeliveryStatus of the email.
	 *
	 * @since 4.7.0
	 *
	 * @return DeliveryStatus
	 */
	protected function get_delivery_status(): DeliveryStatus {

		$delivery_status           = new DeliveryStatus();
		$failed_delivery_events    = [
			'bounced',
			'failed',
		];
		$succeeded_delivery_events = [
			'delivered',
			'opened',
		];

		foreach ( $this->events as $event ) {
			$status = strtolower( $event['last_event'] );

			if ( in_array( $status, $succeeded_delivery_events, true ) ) {
				$delivery_status->set_status( DeliveryStatus::STATUS_DELIVERED );

				break;
			}

			if ( in_array( $status, $failed_delivery_events, true ) ) {
				$delivery_status->set_status( DeliveryStatus::STATUS_FAILED );
				$delivery_status->set_error_code( $status );

				break;
			}
		}

		return $delivery_status;
	}

	/**
	 * Get events from the API response.
	 *
	 * @since 4.7.0
	 *
	 * @return mixed|WP_Error Returns `WP_Error` if unable to fetch a valid response from the API.
	 *                        Otherwise, returns an array containing the events.
	 */
	protected function get_events() {

		$mailer_options = $this->get_mailer_options();
		$api_key        = $mailer_options['api_key'];
		$message_id     = $this->get_email()->get_message_id();

		$response = wp_safe_remote_get(
			"https://api.resend.com/emails/$message_id",
			[
				'headers' => [
					'Authorization' => "Bearer $api_key", // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					'Accept'        => 'application/json',
					'Content-Type'  => 'application/json',
				],
			]
		);

		$validated_response = $this->validate_response( $response );

		if ( is_wp_error( $validated_response ) ) {
			return $validated_response;
		}

		if ( empty( $validated_response ) ) {
			return new WP_Error( 'resend_delivery_verifier_missing_response', WP::wp_remote_get_response_error_message( $response ) );
		}

		return [ $validated_response ];
	}
}
