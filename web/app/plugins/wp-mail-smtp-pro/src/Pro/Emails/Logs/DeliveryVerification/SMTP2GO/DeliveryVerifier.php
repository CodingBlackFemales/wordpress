<?php

namespace WPMailSMTP\Pro\Emails\Logs\DeliveryVerification\SMTP2GO;

use WP_Error;
use WPMailSMTP\Pro\Emails\Logs\DeliveryVerification\AbstractDeliveryVerifier;
use WPMailSMTP\Pro\Emails\Logs\DeliveryVerification\DeliveryStatus;
use WPMailSMTP\WP;

/**
 * Class DeliveryVerifier.
 *
 * Delivery verifier for SMTP2GO.
 *
 * @since 4.1.0
 */
class DeliveryVerifier extends AbstractDeliveryVerifier {

	/**
	 * Get the DeliveryStatus of the email.
	 *
	 * @since 4.1.0
	 *
	 * @return DeliveryStatus
	 */
	protected function get_delivery_status(): DeliveryStatus {

		$delivery_status           = new DeliveryStatus();
		$failed_delivery_events    = [
			'failed',
			'hardbounce',
			'refused',
			'softbounce',
			'returned',
			'rejected',
		];
		$succeeded_delivery_events = [
			'delivered',
			'ok',
			'sent',
		];

		// Only consider the first "TO" address.
		$to = $this->get_email()->get_people( 'to' );
		$to = is_array( $to ) ? $to[0] : $to;

		// Only consider events related to the first "TO" address.
		$events = array_filter(
			$this->events,
			function ( $event ) use ( $to ) {
				return $event['recipient'] === $to;
			}
		);

		foreach ( $events as $event ) {
			$status   = trim( strtolower( $event['status'] ) );
			$response = $event['response'];

			if ( in_array( $status, $succeeded_delivery_events, true ) ) {
				$delivery_status->set_status( DeliveryStatus::STATUS_DELIVERED );
				break;
			}

			if ( in_array( $status, $failed_delivery_events, true ) ) {
				$delivery_status->set_status( DeliveryStatus::STATUS_FAILED );
				$delivery_status->set_fail_reason( $response );
				$delivery_status->set_error_code( $status );
				break;
			}
		}

		return $delivery_status;
	}

	/**
	 * Get events from the API response.
	 *
	 * @since 4.1.0
	 *
	 * @return mixed|WP_Error Returns `WP_Error` if unable to fetch a valid response from the API.
	 *                        Otherwise, returns an array containing the events.
	 */
	protected function get_events() {

		$mailer_options = $this->get_mailer_options();
		$response       = wp_safe_remote_post(
			'https://api.smtp2go.com/v3/email/search',
			[
				'headers' => [
					'X-Smtp2go-Api-Key' => $mailer_options['api_key'],
					'Accept'            => 'application/json',
					'Content-Type'      => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'email_id' => [ $this->get_email()->get_message_id() ],
					]
				),
			]
		);

		$validated_response = $this->validate_response( $response );

		if ( is_wp_error( $validated_response ) ) {
			return $validated_response;
		}

		if ( empty( $validated_response['data'] ) || empty( $validated_response['data']['emails'] ) ) {
			return new WP_Error( 'smtp2go_delivery_verifier_missing_response', WP::wp_remote_get_response_error_message( $response ) );
		}

		return $validated_response['data']['emails'];
	}
}
