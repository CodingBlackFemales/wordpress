<?php
/**
 * PayPal Standard Migration Setup Token endpoint.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Endpoints\Migration;

use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use LearnDash\Core\App;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Client;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Setup_Token_Data;
use LearnDash\Core\Utilities\Cast;
use WP_User;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

/**
 * PayPal Standard Migration Setup Token endpoint class.
 *
 * This endpoint is used to create a setup token for a user and schedule the migration of their subscriptions from PayPal Standard to PayPal Checkout.
 *
 * @since 4.25.3
 */
class Setup_Token extends Endpoint {
	/**
	 * The base route for this endpoint.
	 *
	 * @since 4.25.3
	 *
	 * @var string
	 */
	protected string $base_route = '/commerce/paypal-standard/migration';

	/**
	 * The permission required to access this endpoint.
	 *
	 * This endpoint is public.
	 *
	 * @since 4.25.3
	 *
	 * @var string
	 */
	protected string $permission_required = '';

	/**
	 * Validates the user ID.
	 *
	 * @since 4.25.3
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return bool
	 */
	public function validate_user_id( $value ): bool {
		return is_numeric( $value ) && (int) $value > 0;
	}

	/**
	 * Handles the setup token creation request for migration.
	 *
	 * @since 4.25.3
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function handle_create_setup_token( $request ): WP_REST_Response {
		$client = App::get( Client::class );

		if ( ! $client instanceof Client ) {
			return $this->error_response( __( 'PayPal client not found.', 'learndash' ) );
		}

		$user = new WP_User( Cast::to_int( $request->get_param( 'user_id' ) ) );

		$data_builder = App::get( Setup_Token_Data::class );

		if ( ! $data_builder instanceof Setup_Token_Data ) {
			return $this->error_response( __( 'Setup token data builder not found.', 'learndash' ) );
		}

		if ( Cast::to_bool( $request->get_param( 'is_sandbox' ) ) ) {
			$client->use_sandbox();
		} else {
			$client->use_production();
		}

		// Create setup token data without any specific product (product_id = 0).
		$setup_token_data = $data_builder->build(
			0, // No specific product - just for vaulting payment method.
			$user,
			true
		);

		// If no setup token data is returned, return error.
		if ( empty( $setup_token_data['payment_source'] ) ) {
			return $this->error_response( __( 'Failed to create setup token data.', 'learndash' ) );
		}

		$setup_token = $client->create_setup_token( $setup_token_data );

		if ( is_wp_error( $setup_token ) ) {
			return $this->error_response( $setup_token->get_error_message() );
		}

		return $this->success_response(
			[
				'setup_token' => $setup_token,
			],
			__( 'PayPal setup token created successfully for migration.', 'learndash' ),
		);
	}

	/**
	 * Returns the request schema for this endpoint.
	 *
	 * @since 4.25.3
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array{
	 *     type: string,
	 *     properties: array<string, array<string, mixed>>|object,
	 *     required?: array<string>,
	 * }
	 */
	public function get_request_schema( string $path, string $method ): array {
		return $this->convert_endpoint_args_to_schema();
	}

	/**
	 * Returns the schema for response data.
	 *
	 * @since 4.25.3
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array{
	 *     type: string,
	 *     properties: array{
	 *         success: array<string,mixed>,
	 *         data: array{
	 *             type: string,
	 *             description: string,
	 *             properties: array<string,array<string,mixed>>,
	 *         },
	 *         message: array{
	 *             type: string,
	 *             description: string,
	 *             example: string,
	 *         },
	 *     },
	 *     required: array<string>,
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
					'description' => __( 'PayPal setup token creation endpoint response data for migration.', 'learndash' ),
					'properties'  => [
						'setup_token' => [
							'type'        => 'object',
							'description' => __( 'The created PayPal setup token for migration.', 'learndash' ),
							'properties'  => [
								'id'     => [
									'type'        => 'string',
									'description' => __( 'The setup token ID.', 'learndash' ),
									'example'     => 'SETUP_TOKEN_1234567890ABCDEFG',
								],
								'status' => [
									'type'        => 'string',
									'description' => __( 'The setup token status.', 'learndash' ),
									'example'     => 'CREATED',
								],
								'links'  => [
									'type'        => 'array',
									'description' => __( 'The setup token links for approval.', 'learndash' ),
									'items'       => [
										'type'       => 'object',
										'properties' => [
											'href'   => [
												'type'    => 'string',
												'description' => __( 'The link URL.', 'learndash' ),
												'example' => 'https://api.sandbox.paypal.com/v3/vault/setup-tokens/1234567890ABCDEFG',
											],
											'rel'    => [
												'type'    => 'string',
												'description' => __( 'The link relationship.', 'learndash' ),
												'example' => 'self',
											],
											'method' => [
												'type'    => 'string',
												'description' => __( 'The HTTP method.', 'learndash' ),
												'example' => 'GET',
											],
										],
									],
								],
							],
						],
					],
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Success message.', 'learndash' ),
					'example'     => __( 'PayPal setup token created successfully for migration.', 'learndash' ),
				],
			],
			'required'   => [ 'success', 'data' ],
		];
	}

	/**
	 * Returns the routes configuration for this endpoint.
	 *
	 * @since 4.25.3
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
			'/setup-token' => [
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_create_setup_token' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Create PayPal Setup Token for Migration', 'learndash' ),
				'description'         => __( 'Creates a new PayPal setup token for migrating subscriptions from PayPal Standard to PayPal Checkout.', 'learndash' ),
			],
		];
	}

	/**
	 * Returns the endpoint arguments.
	 *
	 * @since 4.25.3
	 *
	 * @return array<string,array{
	 *     type: string,
	 *     default?: mixed,
	 *     validate_callback?: callable,
	 *     sanitize_callback?: callable,
	 *     description: string,
	 *     required?: bool,
	 * }>
	 */
	protected function get_endpoint_args(): array {
		return [
			'user_id'    => [
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => [ $this, 'validate_user_id' ],
				'description'       => __( 'User ID of the customer.', 'learndash' ),
			],
			'is_sandbox' => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether to use the sandbox environment.', 'learndash' ),
			],
		];
	}
}
