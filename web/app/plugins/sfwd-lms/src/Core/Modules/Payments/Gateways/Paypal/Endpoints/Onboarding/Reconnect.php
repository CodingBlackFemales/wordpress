<?php
/**
 * PayPal Checkout Onboarding Reconnect endpoint.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Endpoints\Onboarding;

use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use LearnDash\Core\App;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Client;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Webhook_Client;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash\Core\Utilities\Cast;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use LearnDash_Settings_Section;

/**
 * PayPal Checkout Onboarding Reconnect endpoint class.
 *
 * @since 4.25.0
 */
class Reconnect extends Endpoint {
	/**
	 * The base route for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $base_route = '/commerce/paypal/onboarding';

	/**
	 * The permission required to access this endpoint.
	 *
	 * This endpoint is only accessible to admins.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $permission_required = 'manage_options';

	/**
	 * Handles the reconnect request.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function handle_reconnect( $request ): WP_REST_Response {
		$client = App::container()->get( Client::class );

		if ( ! $client instanceof Client ) {
			return $this->error_response( __( 'PayPal client not found.', 'learndash' ) );
		}

		$webhook_client = App::container()->get( Webhook_Client::class );

		if ( ! $webhook_client instanceof Webhook_Client ) {
			return $this->error_response( __( 'PayPal webhook client not found.', 'learndash' ) );
		}

		if ( Cast::to_bool( $request->get_param( 'is_sandbox' ) ) ) {
			$client->use_sandbox();
			$webhook_client->use_sandbox();
		} else {
			$client->use_production();
			$webhook_client->use_production();
		}

		$client_details = $client->get_client_data();

		// Refresh the access token.
		$access_token = $client->get_access_token_from_client_credentials(
			Cast::to_string( Arr::get( $client_details, 'client_id', '' ) ),
			Cast::to_string( Arr::get( $client_details, 'client_secret', '' ) ),
			Cast::to_string( Arr::get( $client_details, 'merchant_id', '' ) ),
		);

		if ( is_wp_error( $access_token ) ) {
			return $this->error_response(
				$access_token->get_error_message(),
				'rest_error',
				502,
				[
					'reconnect_successful' => false,
					'webhooks_updated'     => false,
				],
			);
		}

		// Reset the account status to force a new verification.
		LearnDash_Settings_Section::set_section_settings_all(
			'LearnDash_Settings_Section_PayPal_Checkout',
			[
				'merchant_account_is_ready' => '',
				'merchant_account_verified' => '',
			]
		);

		// Create or update webhooks.
		$webhook_client->create_or_update_existing_webhooks( true );

		return $this->success_response(
			[
				'reconnect_successful' => true,
				'webhooks_updated'     => true,
			],
			__( 'PayPal connection reconnected successfully.', 'learndash' ),
		);
	}

	/**
	 * Returns the request schema for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array{
	 *     type: string,
	 *     properties: array<string,array<string,mixed>>,
	 * }
	 */
	public function get_request_schema( string $path, string $method ): array {
		return $this->convert_endpoint_args_to_schema();
	}

	/**
	 * Returns the schema for response data.
	 *
	 * @since 4.25.0
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array{
	 *     type: string,
	 *     properties: array{
	 *         success: array<string,string|bool>,
	 *         data: array{
	 *             type: string,
	 *             description: string,
	 *             properties: array<string,array<string,string|bool>>,
	 *         },
	 *         message: array<string,string>,
	 *     },
	 *     required: string[],
	 * }
	 */
	public function get_response_schema( string $path, string $method ): array {
		return [
			'type'       => 'object',
			'properties' => [
				'success' => [
					'type'        => 'boolean',
					'description' => __( 'Indicates if the request was successful.', 'learndash' ),
					'example'     => true,
				],
				'data'    => [
					'type'        => 'object',
					'description' => __( 'PayPal onboarding reconnect endpoint response data.', 'learndash' ),
					'properties'  => [
						'reconnect_successful' => [
							'type'        => 'boolean',
							'description' => __( 'Indicates if the reconnect was successful.', 'learndash' ),
							'example'     => true,
						],
						'webhooks_updated'     => [
							'type'        => 'boolean',
							'description' => __( 'Indicates if the webhooks were updated.', 'learndash' ),
							'example'     => true,
						],
					],
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Success message.', 'learndash' ),
					'example'     => __( 'PayPal connection reconnected successfully.', 'learndash' ),
				],
			],
			'required'   => [ 'success', 'data' ],
		];
	}

	/**
	 * Returns the routes configuration for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string,array{
	 *     methods: string,
	 *     callback: callable,
	 *     args: array<string,array<string,mixed>>,
	 *     permission_callback: callable,
	 *     summary: string,
	 *     description: string,
	 * }>
	 */
	protected function get_routes(): array {
		return [
			'/reconnect' => [
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_reconnect' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Reconnect PayPal Connection', 'learndash' ),
				'description'         => __( 'Reconnects and refreshes the PayPal payment connection and webhooks.', 'learndash' ),
			],
		];
	}

	/**
	 * Returns the endpoint arguments.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string,array{
	 *     type: string,
	 *     default?: mixed,
	 *     validate_callback?: callable,
	 *     sanitize_callback?: callable,
	 *     description: string,
	 * }>
	 */
	protected function get_endpoint_args(): array {
		return [
			'is_sandbox' => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether to use the sandbox environment.', 'learndash' ),
			],
		];
	}
}
