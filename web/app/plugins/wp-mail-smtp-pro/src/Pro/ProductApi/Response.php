<?php

namespace WPMailSMTP\Pro\ProductApi;

use WP_Error;

/**
 * Product API Response.
 *
 * @since 4.4.0
 */
class Response {

	/**
	 * Response returned by `wp_remote_request` function.
	 *
	 * @since 4.4.0
	 *
	 * @var array|WP_Error
	 */
	private $response;

	/**
	 * Constructor.
	 *
	 * @since 4.4.0
	 *
	 * @param array|WP_Error $response Response array or error.
	 */
	public function __construct( $response ) {

		$this->response = $response;
	}

	/**
	 * Get response body.
	 *
	 * @since 4.4.0
	 *
	 * @return string|array
	 */
	public function get_body() {

		$body = wp_remote_retrieve_body( $this->response );

		if ( ! empty( $body ) && is_string( $body ) ) {
			$decoded_body = json_decode( $body, true );

			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_body ) ) {
				$body = $decoded_body;
			}
		}

		return $body;
	}

	/**
	 * Get response status code.
	 *
	 * @since 4.4.0
	 *
	 * @return int
	 */
	public function get_status_code() {

		return wp_remote_retrieve_response_code( $this->response );
	}

	/**
	 * Get response errors.
	 *
	 * @since 4.4.0
	 *
	 * @return false|WP_Error
	 */
	public function get_errors() {

		if ( $this->is_successful() ) {
			return false;
		}

		if ( is_wp_error( $this->response ) ) {
			return $this->response;
		}

		$body = $this->get_body();

		if ( ! empty( $body['errors'] ) && is_array( $body['errors'] ) ) {
			$errors = new WP_Error();

			foreach ( array_values( $body['errors'] ) as $key => $error ) {
				if ( empty( $error['message'] ) ) {
					continue;
				}

				$message = $error['message'];

				unset( $error['message'] );

				$errors->add( 'error:' . $key, $message, $error );
			}

			return $errors;
		}

		return new WP_Error( 'unknown', wp_remote_retrieve_body( $this->response ) );
	}

	/**
	 * Whether response is successful.
	 *
	 * @since 4.4.0
	 *
	 * @return bool
	 */
	public function is_successful() {

		return $this->get_status_code() >= 200 && $this->get_status_code() <= 299;
	}

	/**
	 * Get response header.
	 *
	 * @since 4.4.0
	 *
	 * @param string $header Header name.
	 *
	 * @return string
	 */
	public function get_header( $header ) {

		return wp_remote_retrieve_header( $this->response, $header );
	}
}
