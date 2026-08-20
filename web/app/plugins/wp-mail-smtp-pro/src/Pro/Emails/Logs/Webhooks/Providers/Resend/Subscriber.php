<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\Resend;

use WP_Error;
use WPMailSMTP\Helpers\Helpers;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\AbstractSubscriber;
use WPMailSMTP\WP;

/**
 * Class Subscriber.
 *
 * @since 4.7.0
 */
class Subscriber extends AbstractSubscriber {

	/**
	 * Delivered event.
	 *
	 * @since 4.7.0
	 *
	 * @var string
	 */
	const EVENT_DELIVERED = 'email.delivered';

	/**
	 * Failed event.
	 *
	 * @since 4.7.0
	 *
	 * @var string
	 */
	const EVENT_FAILED = 'email.failed';

	/**
	 * Bounced event.
	 *
	 * @since 4.7.0
	 *
	 * @var string
	 */
	const EVENT_BOUNCED = 'email.bounced';

	/**
	 * Subscription events.
	 *
	 * @since 4.7.0
	 *
	 * @var array
	 */
	const EVENTS = [
		self::EVENT_DELIVERED,
		self::EVENT_FAILED,
		self::EVENT_BOUNCED,
	];

	/**
	 * Create webhook subscription.
	 *
	 * @since 4.7.0
	 *
	 * @return true|WP_Error
	 */
	public function subscribe() {

		$subscription = $this->get_subscription( static::EVENTS );

		if ( is_wp_error( $subscription ) ) {
			return $subscription;
		}

		// Already subscribed.
		if ( $subscription !== false ) {
			return true;
		}

		$body = [
			'events'   => static::EVENTS,
			'endpoint' => $this->provider->get_url(),
		];

		// Create subscription.
		$response = $this->request( 'POST', $body );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Remove webhook subscription.
	 *
	 * @since 4.7.0
	 *
	 * @return true|WP_Error
	 */
	public function unsubscribe() {

		$subscription = $this->get_subscription( static::EVENTS );

		if ( is_wp_error( $subscription ) ) {
			return $subscription;
		}

		// Already unsubscribed.
		if ( $subscription === false ) {
			return true;
		}

		$response = $this->request( 'DELETE', [], $subscription['id'] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Check webhook subscription.
	 *
	 * @since 4.7.0
	 *
	 * @return bool|WP_Error
	 */
	public function is_subscribed() {

		$subscription = $this->get_subscription( static::EVENTS );

		if ( is_wp_error( $subscription ) ) {
			return $subscription;
		}

		if ( $subscription === false ) {
			return false;
		}

		return true;
	}

	/**
	 * Get subscription if available.
	 *
	 * @since 4.7.0
	 *
	 * @param array $events Event names.
	 *
	 * @return array|false|WP_Error
	 */
	protected function get_subscription( $events ) {

		$response = $this->request();

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$webhooks = array_filter(
			$response['data'],
			fn( $data ) => (
				$data['endpoint'] === $this->provider->get_url() &&
				empty( array_diff( $events, $data['events'] ) )
			)
		);

		return ! empty( $webhooks ) ? array_values( $webhooks )[0] : false;
	}

	/**
	 * Performs Resend webhooks API HTTP request.
	 *
	 * @since 4.7.0
	 *
	 * @param string $method     Request method.
	 * @param array  $params     Request params.
	 * @param array  $webhook_id Resend webhooks ID.
	 *
	 * @return mixed|WP_Error
	 */
	protected function request( $method = 'GET', $params = [], $webhook_id = false ) {

		$endpoint = 'https://api.resend.com/webhooks';

		$args = [
			'method'  => $method,
			'headers' => [
				'Authorization' => 'Bearer ' . $this->provider->get_option( 'api_key' ),
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

		$response_code    = wp_remote_retrieve_response_code( $response );
		$success_statuses = [
			200,
			201,
		];

		if ( ! in_array( $response_code, $success_statuses, true ) ) {
			return $this->get_response_error( $response );
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Retrieve errors from Resend API response.
	 *
	 * @since 4.7.0
	 *
	 * @param array $response Response array.
	 *
	 * @return WP_Error
	 */
	protected function get_response_error( $response ) {

		$body       = json_decode( wp_remote_retrieve_body( $response ) );
		$error_text = [];

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( ! empty( $body->message ) ) {
			$error_code   = ! empty( $body->statusCode ) ? $body->statusCode : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$error_name   = ! empty( $body->name ) ? $body->name : '';
			$error_text[] = Helpers::format_error_message( $error_name, $error_code, $body->message );
		} else {
			$error_text[] = WP::wp_remote_get_response_error_message( $response );
		}

		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		return new WP_Error( wp_remote_retrieve_response_code( $response ), implode( WP::EOL, $error_text ) );
	}
}
