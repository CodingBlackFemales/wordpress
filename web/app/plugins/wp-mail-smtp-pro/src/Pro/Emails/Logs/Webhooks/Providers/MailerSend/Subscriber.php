<?php

namespace WPMailSMTP\Pro\Emails\Logs\Webhooks\Providers\MailerSend;

use WP_Error;
use WPMailSMTP\Helpers\Helpers;
use WPMailSMTP\Options;
use WPMailSMTP\Pro\Emails\Logs\Webhooks\AbstractSubscriber;
use WPMailSMTP\WP;

/**
 * Class Subscriber.
 *
 * @since 4.5.0
 */
class Subscriber extends AbstractSubscriber {

	/**
	 * Subscription events.
	 *
	 * @since 4.5.0
	 *
	 * @var array
	 */
	const EVENTS = [
		'activity.delivered',
		'activity.hard_bounced',
	];

	/**
	 * Create webhook subscription.
	 *
	 * @since 4.5.0
	 *
	 * @return true|WP_Error
	 */
	public function subscribe() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		$subscription = $this->get_subscription();

		if ( is_wp_error( $subscription ) ) {
			return $subscription;
		}

		// Already subscribed.
		if ( $subscription !== false && empty( array_diff( self::EVENTS, $subscription['events'] ) ) ) {
			return true;
		}

		$domain_id = $this->get_domain_id();

		if ( is_wp_error( $domain_id ) ) {
			return $domain_id;
		}

		$existing_events = ! empty( $subscription['events'] ) ? $subscription['events'] : [];
		$events          = array_unique( array_merge( $existing_events, self::EVENTS ) );
		$body            = [
			'url'    => $this->provider->get_url(),
			'name'   => 'WP Mail SMTP',
			'events' => array_values( $events ),
		];

		if ( ! $subscription ) {
			$body['domain_id'] = $domain_id;

			$response = $this->request( 'POST', $body );
		} else {
			$response = $this->request( 'PUT', $body, $subscription['id'] );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Remove webhook subscription.
	 *
	 * @since 4.5.0
	 *
	 * @return true|WP_Error
	 */
	public function unsubscribe() {

		$subscription = $this->get_subscription();

		if ( is_wp_error( $subscription ) || empty( $subscription['id'] ) ) {
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
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public function is_subscribed() {

		$subscription = $this->get_subscription();

		if ( is_wp_error( $subscription ) ) {
			return false;
		}

		return ! empty( $subscription['id'] ) && ! empty( $subscription['enabled'] ) && empty( array_diff( self::EVENTS, $subscription['events'] ) );
	}

	/**
	 * Get domain ID from API.
	 *
	 * @since 4.5.0
	 *
	 * @return string|WP_Error Domain ID or WP_Error on failure.
	 */
	protected function get_domain_id() {

		$domain_name = $this->get_domain_name_from_email();

		if ( is_wp_error( $domain_name ) ) {
			return $domain_name;
		}

		$domains = $this->get_domains_from_api();

		if ( is_wp_error( $domains ) ) {
			return $domains;
		}

		// Find matching domain and return its ID.
		foreach ( $domains as $domain ) {
			if ( ! empty( $domain['name'] ) && $domain['name'] === $domain_name ) {
				return $domain['id'];
			}
		}

		return new WP_Error(
			'mailersend_webhook_api_error',
			sprintf(
				/* translators: %s - domain name. */
				esc_html__( 'Domain "%s" not found.', 'wp-mail-smtp-pro' ),
				esc_html( $domain_name )
			)
		);
	}

	/**
	 * Get domain name from From Email setting.
	 *
	 * @since 4.5.0
	 *
	 * @return string|WP_Error Domain name or WP_Error on failure.
	 */
	private function get_domain_name_from_email() {

		$options     = Options::init();
		$from_email  = $options->get( 'mail', 'from_email' );
		$domain_name = WP::get_email_domain( $from_email );

		if ( empty( $domain_name ) ) {
			return new WP_Error(
				'mailersend_webhook_api_error',
				esc_html__( 'Unable to extract domain from "From Email".', 'wp-mail-smtp-pro' )
			);
		}

		return $domain_name;
	}

	/**
	 * Get domains from MailerSend API.
	 *
	 * @since 4.5.0
	 *
	 * @return array|WP_Error Response data array or WP_Error on failure.
	 */
	protected function get_domains_from_api() {

		$endpoint = 'https://api.mailersend.com/v1/domains';
		$args     = [
			'method'  => 'GET',
			'headers' => [
				'Authorization' => 'Bearer ' . $this->provider->get_option( 'api_key' ),
				'Accept'        => 'application/json',
			],
		];

		$response = wp_remote_request( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $response_code !== 200 ) {
			return $this->get_response_error( $response );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
			return new WP_Error(
				'mailersend_webhook_api_error',
				esc_html__( 'No domains found.', 'wp-mail-smtp-pro' )
			);
		}

		return $body['data'];
	}

	/**
	 * Get subscription if available.
	 *
	 * @since 4.5.0
	 *
	 * @return array|false|WP_Error
	 */
	protected function get_subscription() {

		$response = $this->request();

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['data'] ) ) {
			return false;
		}

		$webhooks = array_filter(
			$response['data'],
			function ( $data ) {
				return ! empty( $data['url'] ) && $data['url'] === $this->provider->get_url();
			}
		);

		return ! empty( $webhooks ) ? array_values( $webhooks )[0] : false;
	}

	/**
	 * Make API request.
	 *
	 * @since 4.5.0
	 *
	 * @param string $method     Request method.
	 * @param array  $params     Request body.
	 * @param string $webhook_id Webhook ID.
	 *
	 * @return mixed|WP_Error
	 */
	protected function request( $method = 'GET', $params = [], $webhook_id = '' ) {

		$endpoint = 'https://api.mailersend.com/v1/webhooks';

		if ( ! empty( $webhook_id ) ) {
			$endpoint .= '/' . $webhook_id;
		}

		$args = [
			'method'  => $method,
			'headers' => [
				'Authorization' => 'Bearer ' . $this->provider->get_option( 'api_key' ),
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
			],
		];

		if ( $method === 'GET' ) {
			$endpoint                  = add_query_arg( $params, $endpoint );
			$args['body']['domain_id'] = $this->get_domain_id();
		} else {
			$args['body'] = wp_json_encode( $params );
		}

		$response = wp_remote_request( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! in_array( wp_remote_retrieve_response_code( $response ), [ 200, 201, 202, 204 ], true ) ) {
			return $this->get_response_error( $response );
		}

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	/**
	 * Retrieve errors from MailerSend API response.
	 *
	 * @since 4.5.0
	 *
	 * @param array $response Response array.
	 *
	 * @return WP_Error
	 */
	protected function get_response_error( $response ) {

		$body       = json_decode( wp_remote_retrieve_body( $response ) );
		$error_code = wp_remote_retrieve_response_code( $response );

		if ( ! empty( $body->message ) ) {
			$message = $body->message;

			$error_text = Helpers::format_error_message( $message, $error_code );
		} else {
			$error_text = WP::wp_remote_get_response_error_message( $response );
		}

		return new WP_Error( $error_code, $error_text );
	}
}
