<?php
/**
 * PayPal Checkout Onboarding endpoint.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Endpoints\Onboarding;

use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use LearnDash\Core\App;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Whodat_Client;
use LearnDash\Core\Utilities\Countries;
use LearnDash\Core\Utilities\Cast;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

/**
 * PayPal Checkout Onboarding endpoint class.
 *
 * @since 4.25.0
 */
class Signup_Url extends Endpoint {
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
	 * Validates the account country.
	 *
	 * @since 4.25.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return bool
	 */
	public function validate_account_country( $value ): bool {
		return in_array(
			Cast::to_string( $value ),
			array_keys( Countries::get_all() ),
			true
		);
	}

	/**
	 * Handles the signup URL request.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function handle_signup_url( $request ): WP_REST_Response {
		$client = App::container()->get( Whodat_Client::class );

		if ( ! $client instanceof Whodat_Client ) {
			return $this->error_response( __( 'PayPal client not found.', 'learndash' ) );
		}

		$signup_url = $client->get_signup_url(
			Cast::to_string( $request->get_param( 'account_country' ) ),
			Cast::to_bool( $request->get_param( 'is_sandbox' ) ),
			Cast::to_bool( $request->get_param( 'is_setup_wizard' ) )
		);

		if ( is_wp_error( $signup_url ) ) {
			return $this->error_response( $signup_url->get_error_message() );
		}

		return $this->success_response(
			[
				'signup_url' => $signup_url,
			],
			__( 'PayPal signup URL retrieved successfully.', 'learndash' ),
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
	 *             properties: array<string,array<string,string>>,
	 *         },
	 *         message: array<string,string>,
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
					'description' => __( 'PayPal onboarding signup URL endpoint response data.', 'learndash' ),
					'properties'  => [
						'signup_url' => [
							'type'        => 'string',
							'description' => __( 'The URL to sign up for PayPal.', 'learndash' ),
							'example'     => 'https://www.paypal.com/bizsignup/partner/entry?referralToken={referralToken}&displayMode=minibrowser',
						],
					],
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Success message.', 'learndash' ),
					'example'     => __( 'Signup URL retrieved successfully.', 'learndash' ),
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
			'/signup_url' => [
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle_signup_url' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Retrieve PayPal Seller signup URL', 'learndash' ),
				'description'         => __( 'Retrieves the PayPal Seller signup URL for the current user.', 'learndash' ),
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
	 *     enum?: array<string>,
	 * }>
	 */
	protected function get_endpoint_args(): array {
		return [
			'account_country' => [
				'type'              => 'string',
				'default'           => 'US',
				'validate_callback' => [ $this, 'validate_account_country' ],
				'description'       => __( 'The country code of the account to sign up.', 'learndash' ),
				'enum'              => array_keys( Countries::get_all() ),
			],
			'is_sandbox'      => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether to use the sandbox environment.', 'learndash' ),
			],
			'is_setup_wizard' => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether the request is from the setup wizard.', 'learndash' ),
			],
		];
	}
}
