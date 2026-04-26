<?php
/**
 * PayPal Checkout Start Trial endpoint.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Endpoints\Payments;

use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use LearnDash\Core\App;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Token;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Models\Product;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use WP_User;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

/**
 * PayPal Checkout Start Trial endpoint class.
 *
 * @since 4.25.0
 */
class Start_Trial extends Endpoint {
	/**
	 * The base route for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $base_route = '/commerce/paypal/payments';

	/**
	 * The permission required to access this endpoint.
	 *
	 * This endpoint is public.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $permission_required = '';

	/**
	 * Validates the user ID.
	 *
	 * @since 4.25.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return bool
	 */
	public function validate_user_id( $value ): bool {
		return is_numeric( $value ) && (int) $value > 0;
	}

	/**
	 * Validates the token ID.
	 *
	 * @since 4.25.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return bool
	 */
	public function validate_token_id( $value ): bool {
		return is_string( $value ) && ! empty( $value );
	}

	/**
	 * Validates the product ID.
	 *
	 * @since 4.25.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return bool
	 */
	public function validate_product_id( $value ): bool {
		return is_numeric( $value ) && (int) $value > 0;
	}

	/**
	 * Validates the customer ID.
	 *
	 * @since 4.25.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return bool
	 */
	public function validate_customer_id( $value ): bool {
		return is_string( $value ) && ! empty( $value );
	}

	/**
	 * Handles the start trial request.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function handle_start_trial( $request ): WP_REST_Response {
		$payment_gateway = App::get( Payment_Gateway::class );

		if ( ! $payment_gateway instanceof Payment_Gateway ) {
			return $this->error_response( __( 'PayPal payment gateway not found.', 'learndash' ) );
		}

		$user = new WP_User( Cast::to_int( $request->get_param( 'user_id' ) ) );

		if ( ! $user->exists() ) {
			return $this->error_response( __( 'User not found.', 'learndash' ) );
		}

		$product = Product::find( Cast::to_int( $request->get_param( 'product_id' ) ) );

		if ( ! $product ) {
			return $this->error_response( __( 'Product not found.', 'learndash' ) );
		}

		$token_id = Cast::to_string( $request->get_param( 'token_id' ) );
		$type     = Cast::to_string( $request->get_param( 'type' ) );

		// If paying with a saved PayPal button method, we need to get the token ID from the payment token data.
		if (
			$token_id === 'paypal'
			&& $type === 'paypal'
		) {
			$payment_token = App::get( Payment_Token::class );

			if ( ! $payment_token instanceof Payment_Token ) {
				return $this->error_response( __( 'PayPal payment token handler not found.', 'learndash' ) );
			}

			if ( Cast::to_bool( $request->get_param( 'is_sandbox' ) ) ) {
				$payment_token->use_sandbox();
			} else {
				$payment_token->use_production();
			}

			$token_data = $payment_token->get_user_payment_token( $user->ID, $token_id );

			$token_id = Cast::to_string( Arr::get( $token_data, 'id', '' ) );
		}

		$payment_gateway->process_free_trial_start(
			$user->ID,
			$product,
			[
				'payment_token' => [
					'gateway'     => 'paypal_checkout',
					'token'       => $token_id,
					'customer_id' => Cast::to_string( $request->get_param( 'customer_id' ) ),
					'type'        => $type,
				],
			]
		);

		$settings    = Payment_Gateway::get_settings();
		$success_url = Payment_Gateway::get_url_success(
			[ $product ],
			Cast::to_string( Arr::get( $settings, 'return_url', '' ) )
		);

		return $this->success_response(
			[ 'success_url' => $success_url ],
			__( 'Trial started successfully.', 'learndash' )
		);
	}

	/**
	 * Returns the schema for request parameters.
	 *
	 * @since 4.25.0
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array{
	 *     type: string,
	 *     properties: array<string,array<string,mixed>>,
	 *     required?: string[],
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
					'description' => __( 'PayPal start trial endpoint response data.', 'learndash' ),
					'properties'  => [
						'success_url' => [
							'type'        => 'string',
							'description' => __( 'The URL to redirect the user after successful payment.', 'learndash' ),
							'example'     => 'https://example.com/success',
						],
					],
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Success message.', 'learndash' ),
					'example'     => __( 'Trial started successfully.', 'learndash' ),
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
			'/start-trial' => [
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_start_trial' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Start a free trial', 'learndash' ),
				'description'         => __( 'Starts a free trial for a user.', 'learndash' ),
			],
		];
	}

	/**
	 * Returns the endpoint arguments.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string,array<string,mixed>>
	 */
	protected function get_endpoint_args(): array {
		return [
			'user_id'     => [
				'type'              => 'integer',
				'description'       => __( 'The user ID for the trial.', 'learndash' ),
				'required'          => true,
				'validate_callback' => [ $this, 'validate_user_id' ],
				'sanitize_callback' => 'absint',
			],
			'product_id'  => [
				'type'              => 'integer',
				'description'       => __( 'The product ID for the trial.', 'learndash' ),
				'required'          => true,
				'validate_callback' => [ $this, 'validate_product_id' ],
				'sanitize_callback' => 'absint',
			],
			'token_id'    => [
				'type'              => 'string',
				'description'       => __( 'The PayPal token ID.', 'learndash' ),
				'required'          => true,
				'validate_callback' => [ $this, 'validate_token_id' ],
				'sanitize_callback' => 'sanitize_text_field',
			],
			'customer_id' => [
				'type'              => 'string',
				'description'       => __( 'The PayPal customer ID.', 'learndash' ),
				'required'          => true,
				'validate_callback' => [ $this, 'validate_customer_id' ],
				'sanitize_callback' => 'sanitize_text_field',
			],
			'type'        => [
				'type'              => 'string',
				'description'       => __( 'The payment type.', 'learndash' ),
				'required'          => true,
				'enum'              => [
					'paypal',
					'card',
				],
				'sanitize_callback' => 'sanitize_text_field',
			],
			'is_sandbox'  => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether to use the sandbox environment.', 'learndash' ),
			],
		];
	}
}
