<?php
/**
 * Base class for all LearnDash Hub classes.
 *
 * @package LearnDash\Hub
 */

declare( strict_types=1 );

namespace LearnDash\Hub\Framework;

use LearnDash\Hub\Traits\License;

defined( 'ABSPATH' ) || exit;

/**
 * This is the base class, every object should extend this.
 *
 * Class Base
 *
 * @package LearnDash\Hub
 */
class Base {
	use License;

	/**
	 * Export the class properties as array format.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return get_object_vars( $this );
	}

	/**
	 * Trigger a request to API server.
	 *
	 * @param string $endpoint The endpoint, as relative URL.
	 * @param string $method   The method, GET OR POST.
	 * @param array  $args     The body args.
	 *
	 * @return array|mixed|\WP_Error
	 */
	protected function do_api_request( string $endpoint, string $method = 'GET', array $args = array() ) {
		$base = LICENSING_SITE . '/wp-json/' . BASE_REST;

		$response = wp_remote_request(
			$base . $endpoint,
			array(
				'method'  => $method,
				'headers' => $this->get_auth_headers(),
				'body'    => $args,
				'timeout' => 30,
			)
		);

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $body ) ) {
				$body = [
					'code'    => 'unknown_error',
					'message' => __( 'An unknown error occurred', 'learndash' ),
				];
			}

			if ( 'rest_forbidden' === $body['code'] ) {
				$body['message'] = __( 'Your license is invalid', 'learndash' );
			}

			return new \WP_Error( $body['code'], $body['message'] );
		}

		$body = wp_remote_retrieve_body( $response );

		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			// fail-safe.
			$data = array();
		}

		return $data;
	}
}
