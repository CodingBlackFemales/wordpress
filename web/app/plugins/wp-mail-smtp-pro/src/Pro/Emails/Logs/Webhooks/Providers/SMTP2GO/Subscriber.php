<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\SMTP2GO;

use WP_Error;
use WPMailSMTP\Helpers\Helpers;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\AbstractSubscriber;
use WPMailSMTP\WP;

/**
 * Class Subscriber.
 *
 * @since 4.1.0
 */
class Subscriber extends AbstractSubscriber {

	/**
	 * Subscription events.
	 *
	 * @since 4.1.0
	 *
	 * @var array
	 */
	const EVENTS = [
		'delivered',
		'bounce',
		'reject',
	];

	/**
	 * Create webhook subscription.
	 *
	 * @since 4.1.0
	 *
	 * @return true|WP_Error
	 */
	public function subscribe() {

		$events = [];

		foreach ( self::EVENTS as $event ) {
			$subscription = $this->get_subscription( $event );

			if ( is_wp_error( $subscription ) ) {
				return $subscription;
			}

			// Already subscribed.
			if ( $subscription !== false ) {
				continue;
			}

			$events[] = $event;
		}

		// No events left to subscribe for.
		if ( empty( $events ) ) {
			return true;
		}

		$body = [
			'events' => $events,
			'url'    => $this->provider->get_url(),
		];

		// Create subscription.
		$response = $this->request( 'add', $body );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Remove webhook subscription.
	 *
	 * @since 4.1.0
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
				'remove',
				[
					'id' => $subscription['id'],
				]
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
	 * @since 4.1.0
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
	 * @since 4.1.0
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

		if ( empty( $response['data'] ) || empty( $response['data']['webhooks'] ) ) {
			return false;
		}

		$webhooks = array_filter(
			$response['data']['webhooks'],
			function ( $data ) use ( $event ) {
				return (
					$data['url'] === $this->provider->get_url() &&
					in_array( $event, $data['events'], true )
				);
			}
		);

		return ! empty( $webhooks ) ? array_values( $webhooks )[0] : false;
	}

	/**
	 * Performs webhooks API HTTP request.
	 *
	 * @since 4.1.0
	 *
	 * @param string $action Request action.
	 * @param array  $params Request params.
	 *
	 * @return mixed|WP_Error
	 */
	public function request( $action = 'view', $params = [] ) {

		$endpoint = "https://api.smtp2go.com/v3/webhook/$action";

		$params['api_key'] = $this->provider->get_option( 'api_key' );

		$args = [
			'method'  => 'POST',
			'headers' => [
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			],
			'body'    => wp_json_encode( $params ),
		];

		$response = wp_remote_request( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return $this->get_response_error( $response );
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Retrieve errors from API response.
	 *
	 * @since 4.1.0
	 *
	 * @param array $response Response array.
	 *
	 * @return WP_Error
	 */
	protected function get_response_error( $response ) {

		$body       = json_decode( wp_remote_retrieve_body( $response ) );
		$error_text = [];

		if ( ! empty( $body->data ) && ! empty( $body->data->error ) ) {
			$code = ! empty( $body->data->error_code ) ? $body->data->error_code : '';

			$error_text[] = Helpers::format_error_message( $body->data->error, $code );
		} else {
			$error_text[] = WP::wp_remote_get_response_error_message( $response );
		}

		return new WP_Error( wp_remote_retrieve_response_code( $response ), implode( WP::EOL, $error_text ) );
	}
}
