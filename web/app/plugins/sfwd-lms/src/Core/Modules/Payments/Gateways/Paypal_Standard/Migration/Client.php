<?php
/**
 * PayPal Standard Migration Request Client helper.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Migration;

use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use WP_Error;

/**
 * PayPal Standard Migration Request Client helper.
 *
 * Helper class to make PayPal Standard NVP API requests.
 *
 * @since 4.25.3
 */
class Client {
	/**
	 * PayPal Standard NVP API endpoint for sandbox.
	 *
	 * @since 4.25.3
	 *
	 * @var string
	 */
	private const NVP_SANDBOX_ENDPOINT = 'https://api-3t.sandbox.paypal.com/nvp';

	/**
	 * PayPal Standard NVP API endpoint for production.
	 *
	 * @since 4.25.3
	 *
	 * @var string
	 */
	private const NVP_PRODUCTION_ENDPOINT = 'https://api-3t.paypal.com/nvp';

	/**
	 * PayPal Standard NVP API version.
	 *
	 * @since 4.25.3
	 *
	 * @var string
	 */
	private const NVP_API_VERSION = '124.0';

	/**
	 * Current environment setting.
	 *
	 * @since 4.25.3
	 *
	 * @var bool
	 */
	protected bool $is_sandbox = false;

	/**
	 * Sets the environment to use sandbox for API calls.
	 *
	 * @since 4.25.3
	 *
	 * @return self
	 */
	public function use_sandbox(): self {
		return $this->set_environment( true );
	}

	/**
	 * Sets the environment to use production for API calls.
	 *
	 * @since 4.25.3
	 *
	 * @return self
	 */
	public function use_production(): self {
		return $this->set_environment( false );
	}

	/**
	 * Returns the current environment.
	 *
	 * @since 4.25.3
	 *
	 * @return bool
	 */
	public function is_sandbox(): bool {
		return $this->is_sandbox;
	}

	/**
	 * Cancels a PayPal Standard subscription.
	 *
	 * @since 4.25.3
	 *
	 * @param string $subscription_id The subscription ID.
	 * @param string $api_username    The API username.
	 * @param string $api_password    The API password.
	 * @param string $api_signature   The API signature.
	 *
	 * @return bool True if the subscription was cancelled successfully, false otherwise.
	 */
	public function cancel_subscription(
		string $subscription_id,
		string $api_username,
		string $api_password,
		string $api_signature
	): bool {
		$response = $this->client_request(
			$this->get_environment_url(),
			[
				'METHOD'    => 'ManageRecurringPaymentsProfileStatus',
				'PROFILEID' => $subscription_id,
				'ACTION'    => 'Cancel',
				'VERSION'   => self::NVP_API_VERSION,
				'USER'      => $api_username,
				'PWD'       => $api_password,
				'SIGNATURE' => $api_signature,
			]
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		// Check if the cancellation was successful by checking the ACK response.
		if ( mb_strtolower( Cast::to_string( Arr::get( $response, 'ACK', '' ) ) ) === 'success' ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the PayPal Standard NVP API URL.
	 *
	 * @since 4.25.3
	 *
	 * @return string
	 */
	protected function get_environment_url(): string {
		return $this->is_sandbox()
			? self::NVP_SANDBOX_ENDPOINT
			: self::NVP_PRODUCTION_ENDPOINT;
	}

	/**
	 * Makes a PayPal Standard NVP API request.
	 *
	 * @since 4.25.3
	 *
	 * @param string              $endpoint The endpoint to make the request to.
	 * @param array<string,mixed> $data     The data to send with the request.
	 *
	 * @return array<int|string,mixed>|WP_Error The response from the request.
	 */
	protected function client_request( string $endpoint, array $data ) {
		$response = wp_remote_post(
			$endpoint,
			[
				'body'    => http_build_query( $data ),
				'timeout' => 30,
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return new WP_Error(
				'api_request_failed',
				__( 'PayPal Standard NVP API request failed.', 'learndash' )
			);
		}

		// Parse the NVP response.
		$result = [];
		parse_str( $body, $result );

		return $result;
	}

	/**
	 * Sets the environment for API calls.
	 *
	 * @since 4.25.3
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
