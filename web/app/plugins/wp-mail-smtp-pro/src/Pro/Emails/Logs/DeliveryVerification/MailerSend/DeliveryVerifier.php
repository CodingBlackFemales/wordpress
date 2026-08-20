<?php

namespace WPMailSMTP\Pro\Emails\Logs\DeliveryVerification\MailerSend;

use WP_Error;
use WPMailSMTP\Pro\Emails\Logs\DeliveryVerification\AbstractDeliveryVerifier;
use WPMailSMTP\Pro\Emails\Logs\DeliveryVerification\DeliveryStatus;

/**
 * Class DeliveryVerifier.
 *
 * Delivery verifier for MailerSend Email.
 *
 * @since 4.5.0
 */
class DeliveryVerifier extends AbstractDeliveryVerifier {

	/**
	 * Get the DeliveryStatus of the email.
	 *
	 * @since 4.5.0
	 *
	 * @return DeliveryStatus
	 */
	protected function get_delivery_status(): DeliveryStatus {

		$delivery_status = new DeliveryStatus();
		$events          = $this->events;

		if ( ! is_array( $events ) || empty( $events[0] ) || ! is_array( $events[0] ) ) {
			return $delivery_status;
		}

		// Only consider first TO email.
		$message_data = $events[0];

		if ( empty( $message_data['status'] ) ) {
			return $delivery_status;
		}

		if ( $message_data['status'] === 'delivered' ) {
			$delivery_status->set_status( DeliveryStatus::STATUS_DELIVERED );
		}

		if ( $message_data['status'] === 'rejected' ) {
			$delivery_status->set_status( DeliveryStatus::STATUS_FAILED );
			$delivery_status->set_error_code( $message_data['status'] );
		}

		return $delivery_status;
	}

	/**
	 * Get events from the API response.
	 *
	 * @since 4.5.0
	 *
	 * @return mixed|WP_Error Returns `WP_Error` if unable to fetch a valid response from the API.
	 *                        Otherwise, returns an array containing the events.
	 */
	protected function get_events() {

		$mailer_options = $this->get_mailer_options();
		$message_id     = $this->get_email()->get_header( 'X-Msg-ID' );

		if ( empty( $message_id ) ) {
			return new WP_Error( 'mailersend_delivery_verifier_missing_message_id', esc_html__( 'Message ID is missing.', 'wp-mail-smtp-pro' ) );
		}

		$url = 'https://api.mailersend.com/v1/messages/' . $message_id;

		$response = wp_safe_remote_get(
			$url,
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $mailer_options['api_key'],
					'Accept'        => 'application/json',
					'Content-Type'  => 'application/json',
				],
			]
		);

		$validated_response = $this->validate_response( $response );

		if ( is_wp_error( $validated_response ) ) {
			return $validated_response;
		}

		// Validate response structure and extract emails array.
		if (
			empty( $validated_response['data'] ) ||
			! is_array( $validated_response['data'] ) ||
			empty( $validated_response['data']['emails'] ) ||
			! is_array( $validated_response['data']['emails'] )
		) {
			return new WP_Error(
				'mailersend_delivery_verifier_invalid_response_structure',
				esc_html__( 'Invalid response structure from MailerSend API.', 'wp-mail-smtp-pro' )
			);
		}

		return $validated_response['data']['emails'];
	}
}
