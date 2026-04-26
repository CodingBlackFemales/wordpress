<?php
/**
 * Trait for PayPal requests.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Traits;

use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash\Core\Utilities\Cast;
use DateTime;
use DateInterval;
use WP_Error;
use JsonException;

/**
 * Trait for PayPal requests.
 *
 * @since 4.25.0
 */
trait Request {
	/**
	 * PayPal request debug ID.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $debug_id = '';

	/**
	 * PayPal access token key.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $access_token_key = 'learndash_paypal_checkout_access_token';

	/**
	 * PayPal access token data key.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $access_token_data_key = 'learndash_paypal_checkout_access_token_data';

	/**
	 * Current environment setting.
	 *
	 * @since 4.25.0
	 *
	 * @var bool
	 */
	protected bool $is_sandbox = false;

	/**
	 * Sets the environment to use sandbox for API calls.
	 *
	 * @since 4.25.0
	 *
	 * @return self
	 */
	public function use_sandbox(): self {
		return $this->set_environment( true );
	}

	/**
	 * Sets the environment to use production for API calls.
	 *
	 * @since 4.25.0
	 *
	 * @return self
	 */
	public function use_production(): self {
		return $this->set_environment( false );
	}

	/**
	 * Returns the current environment.
	 *
	 * @since 4.25.0
	 *
	 * @return bool
	 */
	public function is_sandbox(): bool {
		return $this->is_sandbox;
	}

	/**
	 * Returns the last stored debug ID from PayPal.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_debug_id(): string {
		return $this->debug_id;
	}

	/**
	 * Returns the client data.
	 *
	 * @since 4.25.0
	 *
	 * @return array{
	 *     client_id: string,
	 *     client_secret: string,
	 * }
	 */
	public function get_client_data(): array {
		/**
		 * Filters the client details.
		 *
		 * @since 4.25.0
		 *
		 * @param array{
		 *     client_id: string,
		 *     client_secret: string,
		 *     merchant_id: string,
		 * } $client_details The client details.
		 *
		 * @return array{
		 *     client_id: string,
		 *     client_secret: string,
		 *     merchant_id: string,
		 * }
		 */
		return apply_filters(
			'learndash_paypal_checkout_client_data',
			[
				'client_id'     => '',
				'client_secret' => '',
				'merchant_id'   => '',
			]
		);
	}

	/**
	 * Saves the access token data.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,mixed> $data The data to save.
	 *
	 * @return bool
	 */
	public function save_access_token_data( array $data ): bool {
		if ( empty( $data['access_token'] ) ) {
			return false;
		}

		update_option( $this->access_token_key, $data['access_token'] );

		if ( ! empty( $data['expires_in'] ) ) {
			$expires_in = new DateInterval( 'PT' . $data['expires_in'] . 'S' );

			// Store date related data in readable formats.
			$data['token_retrieval_time']  = ( new DateTime() )->format( 'Y-m-d H:i:s' );
			$data['token_expiration_time'] = ( new DateTime() )->add( $expires_in )->format( 'Y-m-d H:i:s' );
		}

		update_option( $this->access_token_data_key, $data );

		return true;
	}

	/**
	 * Fetches an access token from the PayPal API using the client credentials.
	 *
	 * @since 4.25.0
	 *
	 * @param string $client_id     The client ID to use.
	 * @param string $client_secret The client secret to use.
	 * @param string $merchant_id   The merchant ID to use.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_access_token_from_client_credentials(
		string $client_id,
		string $client_secret,
		string $merchant_id
	) {
		$auth = base64_encode( "$client_id:$client_secret" ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- We need to encode the client ID and secret.

		$response = $this->client_post(
			'v1/oauth2/token',
			[],
			[
				'headers' => [
					'Authorization'         => sprintf( 'Basic %s', $auth ),
					'Content-Type'          => 'application/x-www-form-urlencoded',
					'PayPal-Auth-Assertion' => $this->generate_auth_assertion( $client_id, $merchant_id ),
				],
				'body'    => [
					'grant_type' => 'client_credentials',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( Arr::has( $response, 'error_description' ) ) {
			return new WP_Error(
				'ld-paypal-checkout-api-request-error',
				Cast::to_string( Arr::get( $response, 'error_description', '' ) ),
				$response
			);
		}

		// Update the access token data for later use.
		$this->save_access_token_data( $response );

		return $response;
	}

	/**
	 * Returns the access token.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_access_token(): string {
		return Cast::to_string(
			get_option( $this->access_token_key, '' )
		);
	}

	/**
	 * Stores the debug ID from a given PayPal request, which allows for us to store it with the gateway payload.
	 *
	 * @since 4.25.0
	 *
	 * @param string $debug_id The debug header to store.
	 *
	 * @return void
	 */
	protected function set_debug_id( string $debug_id ): void {
		$this->debug_id = $debug_id;
	}

	/**
	 * Returns the PayPal API URL.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	protected function get_environment_url(): string {
		return sprintf(
			'https://api.%spaypal.com',
			$this->is_sandbox() ? 'sandbox.' : ''
		);
	}

	/**
	 * Returns the API URL.
	 *
	 * @since 4.25.0
	 *
	 * @param string              $endpoint   The endpoint to connect to.
	 * @param array<string,mixed> $query_args The query arguments to pass to the endpoint.
	 *
	 * @return string
	 */
	protected function get_api_url(
		string $endpoint,
		array $query_args = []
	): string {
		$base_url = $this->get_environment_url();
		$endpoint = ltrim( $endpoint, '/' );

		return add_query_arg( $query_args, trailingslashit( $base_url ) . $endpoint );
	}

	/**
	 * Builds a request URL.
	 *
	 * @since 4.25.0
	 *
	 * @param string              $url        The URL to connect to.
	 * @param array<string,mixed> $query_args The query arguments to pass to the endpoint.
	 *
	 * @return string
	 */
	protected function build_request_url(
		string $url,
		array $query_args = []
	): string {
		return 0 !== strpos( $url, $this->get_environment_url() )
			? $this->get_api_url( $url, $query_args )
			: add_query_arg( $query_args, $url );
	}

	/**
	 * Builds request arguments.
	 *
	 * @since 4.25.0
	 *
	 * @param string              $method The method to use for the request.
	 * @param array<string,mixed> $args   The arguments to pass to the endpoint.
	 *
	 * @return array<string,mixed>
	 */
	protected function build_request_args( string $method, array $args ): array {
		$default_args = [
			'headers' => [
				'Accept'        => 'application/json',
				'Authorization' => sprintf( 'Bearer %s', $this->get_access_token() ),
				'Content-Type'  => 'application/json',
			],
			'timeout' => MINUTE_IN_SECONDS,
		];

		if ( 'GET' !== $method ) {
			$default_args['body'] = [];
		}

		$args = Arr::merge_recursive(
			$default_args,
			$args
		);

		if ( 'GET' !== $method ) {
			$content_type = Cast::to_string(
				Arr::get( $args, 'headers.Content-Type', '' )
			);

			$body = Arr::get( $args, 'body', [] );

			if (
				! empty( $body )
				&& 'application/json' === strtolower( $content_type )
			) {
				$args['body'] = is_string( $body )
					? $body
					: wp_json_encode( $body );
			}
		}

		return $args;
	}

	/**
	 * Performs an API request to the PayPal API.
	 *
	 * @since 4.25.0
	 *
	 * @param string              $method             The method to use for the request.
	 * @param string              $url                The URL to connect to.
	 * @param array<string,mixed> $query_args         The query arguments to pass to the endpoint.
	 * @param array<string,mixed> $request_arguments  The request arguments to pass to the endpoint.
	 * @param int                 $retries            The number of retries to attempt.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	protected function client_request(
		string $method,
		string $url,
		array $query_args = [],
		array $request_arguments = [],
		int $retries = 0
	) {
		$method            = strtoupper( $method );
		$url               = $this->build_request_url( $url, $query_args );
		$request_arguments = $this->build_request_args( $method, $request_arguments );

		if ( 'GET' === $method ) {
			// @phpstan-ignore-next-line -- We are using the correct array structure.
			$response = wp_remote_get( $url, $request_arguments );
		} elseif ( 'POST' === $method ) {
			// @phpstan-ignore-next-line -- We are using the correct array structure.
			$response = wp_remote_post( $url, $request_arguments );
		} else {
			$request_arguments['method'] = $method;

			// @phpstan-ignore-next-line -- We are using the correct array structure.
			$response = wp_remote_request( $url, $request_arguments );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		// If the debug header was set we pass it or reset it.
		$this->set_debug_id( '' );

		$debug_id = Cast::to_string(
			Arr::get( $response, 'headers.Paypal-Debug-Id', '' )
		);

		if ( ! empty( $debug_id ) ) {
			$this->set_debug_id( $debug_id );
		}

		// When we get specifically a 401 and we are not trying to generate a token we try once more.
		if (
			401 === $response_code
			&& 2 >= $retries
			&& false === strpos( $url, 'v1/oauth2/token' )
		) {
			$client_details = $this->get_client_data();

			$token_data = $this->get_access_token_from_client_credentials(
				Cast::to_string( Arr::get( $client_details, 'client_id', '' ) ),
				Cast::to_string( Arr::get( $client_details, 'client_secret', '' ) ),
				Cast::to_string( Arr::get( $client_details, 'merchant_id', '' ) )
			);

			// If properly saved, re-try the request.
			if ( ! is_wp_error( $token_data ) ) {
				$arguments = func_get_args(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.Changed -- Method is converted to uppercase.

				// Set the Authorization header with the new token to avoid multiple retries.
				$arguments = Arr::set(
					$arguments,
					'3.headers.Authorization',
					sprintf(
						'Bearer %s',
						Cast::to_string( $token_data['access_token'] )
					)
				);

				// Increase the number of retries.
				$arguments = Arr::set(
					$arguments,
					'4',
					$retries + 1
				);

				// @phpstan-ignore-next-line -- We are calling the same method.
				return call_user_func_array( [ $this, 'client_request' ], $arguments );
			}
		}

		$response_body = wp_remote_retrieve_body( $response );
		$response_body = $this->json_decode( $response_body );

		if ( empty( $response_body ) ) {
			return $response;
		}

		if ( ! is_array( $response_body ) ) {
			return new WP_Error(
				'ld-paypal-checkout-unexpected-response',
				'',
				[
					'method'            => $method,
					'url'               => $url,
					'query_args'        => $query_args,
					'request_arguments' => $request_arguments,
					'response'          => $response,
				]
			);
		}

		return $response_body;
	}

	/**
	 * Performs a GET request to the PayPal API.
	 *
	 * @since 4.25.0
	 *
	 * @param string              $endpoint          The endpoint to connect to.
	 * @param array<string,mixed> $query_args        The query arguments to pass to the endpoint.
	 * @param array<string,mixed> $request_arguments The request arguments to pass to the endpoint.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	protected function client_get(
		string $endpoint,
		array $query_args = [],
		array $request_arguments = []
	) {
		return $this->client_request( 'GET', $endpoint, $query_args, $request_arguments );
	}

	/**
	 * Performs a POST request to the PayPal API.
	 *
	 * @since 4.25.0
	 *
	 * @param string              $endpoint          The endpoint to connect to.
	 * @param array<string,mixed> $query_args        The query arguments to pass to the endpoint.
	 * @param array<string,mixed> $request_arguments The request arguments to pass to the endpoint.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	protected function client_post(
		string $endpoint,
		array $query_args = [],
		array $request_arguments = []
	) {
		return $this->client_request( 'POST', $endpoint, $query_args, $request_arguments );
	}

	/**
	 * Performs a PATCH request to the PayPal API.
	 *
	 * @since 4.25.0
	 *
	 * @param string              $endpoint          The endpoint to connect to.
	 * @param array<string,mixed> $query_args        The query arguments to pass to the endpoint.
	 * @param array<string,mixed> $request_arguments The request arguments to pass to the endpoint.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	protected function client_patch(
		string $endpoint,
		array $query_args = [],
		array $request_arguments = []
	) {
		return $this->client_request( 'PATCH', $endpoint, $query_args, $request_arguments );
	}

	/**
	 * Performs a DELETE request to the PayPal API.
	 *
	 * @since 4.25.0
	 *
	 * @param string              $endpoint          The endpoint to connect to.
	 * @param array<string,mixed> $query_args        The query arguments to pass to the endpoint.
	 * @param array<string,mixed> $request_arguments The request arguments to pass to the endpoint.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	protected function client_delete(
		string $endpoint,
		array $query_args = [],
		array $request_arguments = []
	) {
		return $this->client_request( 'DELETE', $endpoint, $query_args, $request_arguments );
	}

	/**
	 * Decodes a JSON string into an array.
	 *
	 * If the JSON string is invalid, an empty array is returned.
	 *
	 * @since 4.25.0
	 *
	 * @param string $json The JSON string to decode.
	 *
	 * @return array<string,mixed>
	 */
	protected function json_decode( string $json ): array {
		if ( empty( $json ) ) {
			return [];
		}

		try {
			$data = json_decode( $json, true, 512, JSON_THROW_ON_ERROR );

			return Arr::wrap( $data );
		} catch ( JsonException $e ) {
			return [];
		}
	}

	/**
	 * Generates a PayPal Auth Assertion JWT token.
	 *
	 * @since 4.25.0
	 *
	 * @param string $client_id   The client ID to use.
	 * @param string $merchant_id The merchant ID to use.
	 *
	 * @return string
	 */
	protected function generate_auth_assertion( string $client_id, string $merchant_id ): string {
		$header = [
			'alg' => 'none',
		];

		$payload = [
			'iss'      => $client_id,
			'payer_id' => $merchant_id,
		];

		$header_encoded  = base64_encode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- We need to encode the header.
			Cast::to_string( wp_json_encode( $header, JSON_HEX_APOS ) )
		);
		$payload_encoded = base64_encode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- We need to encode the payload.
			Cast::to_string( wp_json_encode( $payload, JSON_HEX_APOS ) )
		);

		return sprintf(
			'%s.%s.',
			$header_encoded,
			$payload_encoded
		);
	}

	/**
	 * Sets the environment for API calls.
	 *
	 * @since 4.25.0
	 *
	 * @param bool $is_sandbox Whether to use the sandbox environment. Defaults to false.
	 *
	 * @return self
	 */
	private function set_environment( bool $is_sandbox = false ): self {
		$this->is_sandbox = $is_sandbox;

		return $this;
	}
}
