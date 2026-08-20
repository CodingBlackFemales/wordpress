<?php

namespace WPMailSMTP\Pro\Emails\Logs\DeliveryVerification\ElasticEmail;

use WP_Error;
use WPMailSMTP\Pro\Emails\Logs\DeliveryVerification\AbstractDeliveryVerifier;
use WPMailSMTP\Pro\Emails\Logs\DeliveryVerification\DeliveryStatus;
use WPMailSMTP\WP;

/**
 * Class DeliveryVerifier.
 *
 * Delivery verifier for Elastic Email.
 *
 * @since 4.3.0
 */
class DeliveryVerifier extends AbstractDeliveryVerifier {

	/**
	 * Get the DeliveryStatus of the email.
	 *
	 * @since 4.3.0
	 *
	 * @return DeliveryStatus
	 */
	protected function get_delivery_status(): DeliveryStatus {  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		$delivery_status = new DeliveryStatus();
		// Delivered is an array of email addresses.
		$delivered = ! empty( $this->events['Delivered'] ) ? $this->events['Delivered'] : [];
		// Failed is an array of arrays.
		$failed = ! empty( $this->events['Failed'] ) ? $this->events['Failed'] : [];

		// Only consider the first "TO" address.
		$to = $this->get_email()->get_people( 'to' );
		$to = is_array( $to ) ? $to[0] : $to;

		foreach ( $delivered as $event ) {
			if ( $event === $to ) {
				$delivery_status->set_status( DeliveryStatus::STATUS_DELIVERED );

				return $delivery_status;
			}
		}

		foreach ( $failed as $event ) {
			if ( ! empty( $event['Address'] ) && $event['Address'] === $to ) {
				$delivery_status->set_status( DeliveryStatus::STATUS_FAILED );

				if ( ! empty( $event['Error'] ) ) {
					$delivery_status->set_fail_reason( $event['Error'] );
				}

				if ( ! empty( $event['ErrorCode'] ) ) {
					$error_code = ! empty( $event['Category'] ) ? $event['ErrorCode'] . '_' . $event['Category'] : $event['ErrorCode'];

					$delivery_status->set_error_code( $error_code );
				}

				return $delivery_status;
			}
		}

		return $delivery_status;
	}

	/**
	 * Get events from the API response.
	 *
	 * @since 4.3.0
	 *
	 * @return mixed|WP_Error Returns `WP_Error` if unable to fetch a valid response from the API.
	 *                        Otherwise, returns an array containing the events.
	 */
	protected function get_events() {

		$mailer_options = $this->get_mailer_options();
		$transaction_id = $this->get_email()->get_message_id();
		$url            = add_query_arg(
			[
				'showDelivered' => true,
				'showFailed'    => true,
			],
			"https://api.elasticemail.com/v4/emails/{$transaction_id}/status"
		);
		$response       = wp_safe_remote_get(
			$url,
			[
				'headers' => [
					'X-ElasticEmail-ApiKey' => $mailer_options['api_key'],
					'Accept'                => 'application/json',
					'Content-Type'          => 'application/json',
				],
			]
		);

		$validated_response = $this->validate_response( $response );

		if ( is_wp_error( $validated_response ) ) {
			return $validated_response;
		}

		if (
			! isset( $validated_response['Delivered'] ) ||
			! isset( $validated_response['Failed'] )
		) {
			return new WP_Error( 'elasticemail_delivery_verifier_missing_response', WP::wp_remote_get_response_error_message( $response ) );
		}

		return $validated_response;
	}
}
