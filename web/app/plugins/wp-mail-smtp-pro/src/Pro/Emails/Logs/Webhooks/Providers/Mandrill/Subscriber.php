<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\Mandrill;

use WP_Error;
use WPMailSMTP\Helpers\Helpers;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\AbstractSubscriber;
use WPMailSMTP\WP;

/**
 * Class Subscriber.
 *
 * @since 4.6.0
 */
class Subscriber extends AbstractSubscriber {

	/**
	 * API base URL.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	const API_BASE = 'https://mandrillapp.com/api/1.0';

	/**
	 * Subscription events.
	 *
	 * @since 4.6.0
	 *
	 * @var array
	 */
	const EVENTS = [
		'delivered',
		'hard_bounce',
		'reject',
	];

	/**
	 * Create webhook subscription.
	 *
	 * @since 4.6.0
	 *
	 * @return true|WP_Error
	 */
	public function subscribe() {

		$subscription = $this->get_subscription();

		if ( is_wp_error( $subscription ) ) {
			return $subscription;
		}

		// Already subscribed.
		if ( $subscription !== false && empty( array_diff( self::EVENTS, $subscription['events'] ) ) ) {
			return true;
		}

		$params = [
			'url'         => $this->provider->get_url(),
			'description' => 'WP Mail SMTP',
			'events'      => self::EVENTS,
		];

		if ( ! $subscription ) {
			// Create subscription.
			$response = $this->request( '/webhooks/add', $params );
		} else {
			$params['id'] = $subscription['id'];
			$response     = $this->request( '/webhooks/update', $params );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Remove webhook subscription.
	 *
	 * @since 4.6.0
	 *
	 * @return true|WP_Error
	 */
	public function unsubscribe() {

		$subscription = $this->get_subscription();

		if ( is_wp_error( $subscription ) ) {
			return $subscription;
		}

		// Already unsubscribed.
		if ( $subscription === false ) {
			return true;
		}

		// Delete subscription.
		$response = $this->request( '/webhooks/delete', [ 'id' => $subscription['id'] ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Check webhook subscription.
	 *
	 * @since 4.6.0
	 *
	 * @return bool|WP_Error
	 */
	public function is_subscribed() {

		$subscription = $this->get_subscription();

		if ( is_wp_error( $subscription ) ) {
			return $subscription;
		}

		// Subscription does not exist.
		if ( $subscription === false ) {
			return false;
		}

		return empty( array_diff( self::EVENTS, $subscription['events'] ) );
	}

	/**
	 * Get subscription if available.
	 *
	 * @since 4.6.0
	 *
	 * @return array|false|WP_Error
	 */
	protected function get_subscription() {

		$response = $this->request( '/webhooks/list' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$webhooks = array_filter(
			$response,
			function ( $data ) {
				return $data['url'] === $this->provider->get_url();
			}
		);

		return ! empty( $webhooks ) ? array_values( $webhooks )[0] : false;
	}

	/**
	 * Performs Mandrill webhooks API HTTP request.
	 *
	 * @since 4.6.0
	 *
	 * @param string $endpoint API endpoint path.
	 * @param array  $params   Request params.
	 *
	 * @return mixed|WP_Error
	 */
	protected function request( $endpoint, $params = [] ) {

		$params = array_merge(
			[
				'key' => $this->provider->get_option( 'api_key' ),
			],
			$params
		);

		$args = [
			'method'  => 'POST',
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body'    => wp_json_encode( $params ),
		];

		$response = wp_remote_request( self::API_BASE . $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return $this->get_response_error( $response );
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Retrieve errors from Mandrill API response.
	 *
	 * @since 4.6.0
	 *
	 * @param array $response Response array.
	 *
	 * @return WP_Error
	 */
	protected function get_response_error( $response ) {

		$body       = json_decode( wp_remote_retrieve_body( $response ), true );
		$error_text = WP::wp_remote_get_response_error_message( $response );

		if ( ! empty( $body['name'] ) && ! empty( $body['message'] ) ) {
			$error_text = Helpers::format_error_message( $body['message'], $body['name'] );
		}

		return new WP_Error( wp_remote_retrieve_response_code( $response ), $error_text );
	}
}
