<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\Mailjet;

use WP_Error;
use WPMailSMTP\Helpers\Helpers;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\AbstractSubscriber;
use WPMailSMTP\WP;

/**
 * Class Subscriber.
 *
 * @since 4.2.0
 */
class Subscriber extends AbstractSubscriber {

	/**
	 * Subscription events.
	 *
	 * @since 4.2.0
	 *
	 * @var array
	 */
	const EVENTS = [
		'sent',
		'bounce',
		'blocked',
	];

	/**
	 * Create webhook subscription.
	 *
	 * @since 4.2.0
	 *
	 * @return true|WP_Error
	 */
	public function subscribe() {

		foreach ( self::EVENTS as $event ) {
			$subscription = $this->get_subscription( $event );

			if ( is_wp_error( $subscription ) ) {
				return $subscription;
			}

			// Already subscribed.
			if ( $subscription !== false ) {
				continue;
			}

			$body = [
				'EventType' => $event,
				'Url'       => $this->provider->get_url(),
			];

			// Create subscription.
			$response = $this->request( 'POST', $body );

			if ( is_wp_error( $response ) ) {
				return $response;
			}
		}

		return true;
	}

	/**
	 * Remove webhook subscription.
	 *
	 * @since 4.2.0
	 *
	 * @return true|WP_Error
	 */
	public function unsubscribe() {

		foreach ( self::EVENTS as $event ) {
			$subscription = $this->get_subscription( $event );

			if ( is_wp_error( $subscription ) ) {
				return $subscription;
			}

			// Already unsubscribed.
			if ( $subscription === false ) {
				continue;
			}

			$response = $this->request(
				'DELETE',
				[],
				$subscription['ID']
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}
		}

		return true;
	}

	/**
	 * Check webhook subscription.
	 *
	 * @since 4.2.0
	 *
	 * @return bool|WP_Error
	 */
	public function is_subscribed() {

		foreach ( self::EVENTS as $event ) {
			$subscription = $this->get_subscription( $event );

			if ( is_wp_error( $subscription ) ) {
				return $subscription;
			}

			if ( $subscription === false ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get subscription if available.
	 *
	 * @since 4.2.0
	 *
	 * @param static $event Event name.
	 *
	 * @return array|false|WP_Error
	 */
	protected function get_subscription( $event ) {

		$response = $this->request();

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$webhooks = array_filter(
			$response['Data'],
			function ( $data ) use ( $event ) {
				return $data['Url'] === $this->provider->get_url() && $data['EventType'] === $event;
			}
		);

		return ! empty( $webhooks ) ? array_values( $webhooks )[0] : false;
	}

	/**
	 * Performs webhooks API HTTP request.
	 *
	 * @since 4.2.0
	 *
	 * @param string $method     Request method.
	 * @param array  $params     Request params.
	 * @param array  $webhook_id SendLayer webhooks ID.
	 *
	 * @return mixed|WP_Error
	 */
	public function request( $method = 'GET', $params = [], $webhook_id = false ) {

		$endpoint = 'https://api.mailjet.com/v3/REST/eventcallbackurl';
		$user     = $this->provider->get_option( 'api_key' );
		$pass     = $this->provider->get_option( 'secret_key' );

		$args = [
			'method'  => $method,
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( "$user:$pass" ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
			],
		];

		if ( $method === 'GET' ) {
			$endpoint = add_query_arg( $params, $endpoint );
		} else {
			$args['body'] = wp_json_encode( $params );
		}

		if ( $webhook_id !== false ) {
			$endpoint .= '/' . $webhook_id;
		}

		$response = wp_remote_request( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		if (
			( $method === 'GET' && $response_code !== 200 ) ||
			( $method === 'POST' && $response_code !== 201 ) ||
			( $method === 'DELETE' && $response_code !== 204 )
		) {
			return $this->get_response_error( $response );
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Retrieve errors from API response.
	 *
	 * @since 4.2.0
	 *
	 * @param array $response Response array.
	 *
	 * @return WP_Error
	 */
	protected function get_response_error( $response ) {

		$body       = json_decode( wp_remote_retrieve_body( $response ) );
		$error_text = [];

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( ! empty( $body->ErrorMessage ) ) {
			$code          = ! empty( $body->StatusCode ) ? $body->StatusCode : '';
			$error_message = $this->get_error_message( $body->ErrorMessage );

			$error_text[] = Helpers::format_error_message( $error_message, $code );
		} else {
			$error_text[] = WP::wp_remote_get_response_error_message( $response );
		}
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		return new WP_Error( wp_remote_retrieve_response_code( $response ), implode( WP::EOL, $error_text ) );
	}

	/**
	 * Append an explanation to specific error messages.
	 *
	 * @since 4.2.0
	 *
	 * @param string $message Error message.
	 *
	 * @return string
	 */
	private function get_error_message( $message ) {

		// Multiple sites are trying to register webhooks for the same event.
		if ( strpos( strtolower( $message ), 'mj18' ) !== false ) {
			$message = sprintf(
				'%1$s %2$s',
				esc_html__( 'Mailjet webhook registration limit reached.', 'wp-mail-smtp-pro' ),
				$message
			);
		}

		return $message;
	}
}
