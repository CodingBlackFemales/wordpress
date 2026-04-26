<?php
/**
 * PayPal Checkout Onboarding Access Token endpoint.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Endpoints\Onboarding;

use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use LearnDash\Core\App;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Client;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Whodat_Client;
use LearnDash\Core\Utilities\Cast;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

/**
 * PayPal Checkout Onboarding Access Token endpoint class.
 *
 * @since 4.25.0
 */
class Access_Token extends Endpoint {
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
	 * Validates a string parameter.
	 *
	 * @since 4.25.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return bool
	 */
	public function validate_string_parameter( $value ): bool {
		return ! empty( $value ) && is_string( $value );
	}

	/**
	 * Creates the access token.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function create_access_token( $request ): WP_REST_Response {
		$client = App::container()->get( Client::class );

		if ( ! $client instanceof Client ) {
			return $this->error_response( __( 'PayPal client not found.', 'learndash' ) );
		}

		if ( Cast::to_bool( $request->get_param( 'is_sandbox' ) ) ) {
			$client->use_sandbox();
		} else {
			$client->use_production();
		}

		$access_token = $client->get_access_token_from_authorization_code(
			Cast::to_string( $request->get_param( 'shared_id' ) ),
			Cast::to_string( $request->get_param( 'auth_code' ) ),
			Whodat_Client::get_transient_hash(),
		);

		if ( is_wp_error( $access_token ) ) {
			return $this->error_response( $access_token->get_error_message() );
		}

		return $this->success_response(
			[
				'access_token_created' => true,
			],
			__( 'PayPal access token created successfully.', 'learndash' ),
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
					'description' => __( 'PayPal onboarding access token endpoint response data.', 'learndash' ),
					'properties'  => [
						'access_token_created' => [
							'type'        => 'boolean',
							'description' => __( 'Indicates if the access token was created successfully.', 'learndash' ),
							'example'     => true,
						],
					],
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Success message.', 'learndash' ),
					'example'     => __( 'Access token created successfully.', 'learndash' ),
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
			'/access_token' => [
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_access_token' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Create PayPal Access Token', 'learndash' ),
				'description'         => __( 'Creates the PayPal Access Token using shared_id and auth_code.', 'learndash' ),
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
	 *     required?: bool,
	 *     validate_callback?: callable,
	 *     sanitize_callback?: callable,
	 *     description: string,
	 * }>
	 */
	protected function get_endpoint_args(): array {
		return [
			'shared_id'  => [
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => [ $this, 'validate_string_parameter' ],
				'description'       => __( 'The shared ID from PayPal onboarding.', 'learndash' ),
			],
			'auth_code'  => [
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => [ $this, 'validate_string_parameter' ],
				'description'       => __( 'The authorization code from PayPal onboarding.', 'learndash' ),
			],
			'is_sandbox' => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether to use the sandbox environment.', 'learndash' ),
			],
		];
	}
}
