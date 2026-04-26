<?php
/**
 * PayPal Checkout Payment Token endpoint.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Endpoints\Payments;

use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use LearnDash\Core\App;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Client;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Token as Payment_Token_Handler;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Models\Product;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

/**
 * PayPal Checkout Payment Token endpoint class.
 *
 * @since 4.25.0
 */
class Payment_Token extends Endpoint {
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
	 * Validates the product ID.
	 *
	 * @since 4.25.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return bool
	 */
	public function validate_product_id( $value ): bool {
		return is_numeric( $value ) && (int) $value >= 0;
	}

	/**
	 * Validates the numeric ID.
	 *
	 * @since 4.25.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return bool
	 */
	public function validate_numeric_id( $value ): bool {
		return is_numeric( $value ) && (int) $value > 0;
	}

	/**
	 * Handles the payment token creation request.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function handle_create_payment_token( $request ): WP_REST_Response {
		$client = App::get( Client::class );

		if ( ! $client instanceof Client ) {
			return $this->error_response( __( 'PayPal client not found.', 'learndash' ) );
		}

		$payment_token_handler = App::get( Payment_Token_Handler::class );

		if ( ! $payment_token_handler instanceof Payment_Token_Handler ) {
			return $this->error_response( __( 'PayPal payment token handler not found.', 'learndash' ) );
		}

		$gateway = App::get( Payment_Gateway::class );

		if ( ! $gateway instanceof Payment_Gateway ) {
			return $this->error_response( __( 'PayPal payment gateway not found.', 'learndash' ) );
		}

		// Set the environment for the client and payment token handler.
		if ( Cast::to_bool( $request->get_param( 'is_sandbox' ) ) ) {
			$client->use_sandbox();
			$payment_token_handler->use_sandbox();
		} else {
			$client->use_production();
			$payment_token_handler->use_production();
		}

		$result = $client->create_payment_token( Cast::to_string( $request->get_param( 'token_id' ) ) );

		if ( is_wp_error( $result ) ) {
			return $this->error_response( $result->get_error_message() );
		}

		// Save the payment token for the user.
		$payment_token_data = [
			'id'       => Cast::to_string( Arr::get( $result, 'id', '' ) ),
			'customer' => [
				'id' => Cast::to_string( Arr::get( $result, 'customer.id', '' ) ),
			],
		];

		if ( Arr::has( $result, 'payment_source.card' ) ) {
			$payment_token_data['card'] = [
				'holder_name'   => Cast::to_string( Arr::get( $result, 'payment_source.card.name', '' ) ),
				'brand'         => Cast::to_string( Arr::get( $result, 'payment_source.card.brand', '' ) ),
				'last_4_digits' => Cast::to_string( Arr::get( $result, 'payment_source.card.last_digits', '' ) ),
				'expiry_date'   => Cast::to_string( Arr::get( $result, 'payment_source.card.expiry', '' ) ),
			];
		}

		$user_id = Cast::to_int( $request->get_param( 'user_id' ) );

		// Save the customer ID for the user.
		$payment_token_handler->save_user_customer_id(
			$user_id,
			Cast::to_string( Arr::get( $result, 'customer.id', '' ) )
		);

		// Save the payment token for the user.
		$saved = $payment_token_handler->save_user_payment_token(
			$user_id,
			$payment_token_data
		);

		if ( ! $saved ) {
			return $this->error_response( __( 'Failed to save payment token for user.', 'learndash' ) );
		}

		$product_id = Cast::to_int( $request->get_param( 'product_id' ) );
		$settings   = Payment_Gateway::get_settings();

		if ( $product_id > 0 ) {
			$product = Product::find( $product_id );
			if ( ! $product ) {
				return $this->error_response( __( 'Product not found.', 'learndash' ) );
			}

			// Process the free trial start using the gateway helper method.
			$gateway->process_free_trial_start( $user_id, $product, $result );

			$success_url = Payment_Gateway::get_url_success(
				[ $product ],
				Cast::to_string( Arr::get( $settings, 'return_url', '' ) )
			);
		} else {
			$success_url = Payment_Gateway::get_url_success(
				[],
				Cast::to_string( Arr::get( $settings, 'return_url', '' ) )
			);
		}

		return $this->success_response(
			[ 'success_url' => $success_url ],
			__( 'PayPal payment token created and free trial started successfully.', 'learndash' )
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
	 * @return array<string,mixed>
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
	 * @return array<string,mixed>
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
					'description' => __( 'PayPal payment token creation endpoint response data.', 'learndash' ),
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
					'example'     => __( 'PayPal payment token created and saved successfully.', 'learndash' ),
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
			'/payment-token' => [
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_create_payment_token' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Create PayPal Payment Token', 'learndash' ),
				'description'         => __( 'Creates a new PayPal payment token for vaulting payment methods.', 'learndash' ),
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
	 *     description: string,
	 * }>
	 */
	protected function get_endpoint_args(): array {
		return [
			'token_id'   => [
				'type'              => 'string',
				'required'          => true,
				'description'       => __( 'The PayPal setup token ID.', 'learndash' ),
				'validate_callback' => [ $this, 'validate_token_id' ],
			],
			'user_id'    => [
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => [ $this, 'validate_user_id' ],
				'description'       => __( 'User ID to associate the payment token with.', 'learndash' ),
			],
			'product_id' => [
				'type'              => 'integer',
				'default'           => 0,
				'required'          => false,
				'validate_callback' => [ $this, 'validate_product_id' ],
				'description'       => __( 'Product ID for the payment.', 'learndash' ),
			],
			'is_sandbox' => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether to use the sandbox environment.', 'learndash' ),
			],
		];
	}
}
