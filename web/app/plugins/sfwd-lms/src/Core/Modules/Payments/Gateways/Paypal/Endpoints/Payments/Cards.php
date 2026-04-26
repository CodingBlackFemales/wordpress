<?php
/**
 * PayPal Checkout Cards endpoint.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Endpoints\Payments;

use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use LearnDash\Core\App;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Client;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Token;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

/**
 * PayPal Checkout Cards endpoint class.
 *
 * @since 4.25.0
 */
class Cards extends Endpoint {
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
	 * Requires at least read permission to access the endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $permission_required = 'read';

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
	 * Handles the cards listing request.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function handle_list_cards( $request ): WP_REST_Response {
		$payment_token = App::get( Payment_Token::class );

		if ( ! $payment_token instanceof Payment_Token ) {
			return $this->error_response( __( 'PayPal payment token handler not found.', 'learndash' ) );
		}

		$user_id     = Cast::to_int( $request->get_param( 'user_id' ) );
		$customer_id = Cast::to_string( $request->get_param( 'customer_id' ) );
		$is_sandbox  = Cast::to_bool( $request->get_param( 'is_sandbox' ) );

		// Check if the user exists.
		if ( ! get_user_by( 'ID', $user_id ) ) {
			return $this->error_response( __( 'User not found.', 'learndash' ) );
		}

		if ( $is_sandbox ) {
			$payment_token->use_sandbox();
		} else {
			$payment_token->use_production();
		}

		// Validate that the customer ID belongs to the user.
		$user_customer_id = $payment_token->get_user_customer_id( $user_id );
		if (
			empty( $user_customer_id )
			|| $user_customer_id !== $customer_id
		) {
			return $this->error_response( __( 'Invalid customer ID for this user.', 'learndash' ) );
		}

		$cards = $this->get_cards( $customer_id, $is_sandbox );

		return $this->success_response(
			[
				'cards' => $cards,
			],
			sprintf(
				// translators: %d: number of cards.
				_n(
					'%d card retrieved successfully.',
					'%d cards retrieved successfully.',
					count( $cards ),
					'learndash'
				),
				count( $cards )
			)
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
	 *         success: array<string,mixed>,
	 *         data: array<string,mixed>,
	 *         message: array<string,mixed>,
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
					'description' => __( 'PayPal cards endpoint response data.', 'learndash' ),
					'properties'  => [
						'cards' => [
							'type'        => 'array',
							'description' => __( 'Array of saved payment cards.', 'learndash' ),
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'token_id'    => [
										'type'        => 'string',
										'description' => __( 'PayPal payment token ID.', 'learndash' ),
										'example'     => 'PAYMENT_TOKEN_1234567890',
									],
									'name'        => [
										'type'        => 'string',
										'description' => __( 'Cardholder name.', 'learndash' ),
										'example'     => 'John Doe',
									],
									'brand'       => [
										'type'        => 'string',
										'description' => __( 'Card brand (e.g., VISA, MASTERCARD).', 'learndash' ),
										'example'     => 'VISA',
									],
									'last_digits' => [
										'type'        => 'string',
										'description' => __( 'Last four digits of the card.', 'learndash' ),
										'example'     => '1234',
									],
									'expiry'      => [
										'type'        => 'string',
										'description' => __( 'Card expiration date.', 'learndash' ),
										'example'     => '2025-12',
									],
								],
							],
						],
					],
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Success message.', 'learndash' ),
					'example'     => __( '1 card retrieved successfully.', 'learndash' ),
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
	 * @return array<string,array<string,mixed>>
	 */
	protected function get_routes(): array {
		return [
			'/cards' => [
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle_list_cards' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'List PayPal payment cards', 'learndash' ),
				'description'         => __( 'Retrieves the list of saved payment cards for a user.', 'learndash' ),
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
	 *     required?: bool,
	 * }>
	 */
	protected function get_endpoint_args(): array {
		return [
			'user_id'     => [
				'type'              => 'integer',
				'required'          => true,
				'validate_callback' => [ $this, 'validate_user_id' ],
				'description'       => __( 'User ID to retrieve cards for.', 'learndash' ),
			],
			'customer_id' => [
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => [ $this, 'validate_customer_id' ],
				'description'       => __( 'PayPal customer ID to validate against the user.', 'learndash' ),
			],
			'is_sandbox'  => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether to use the sandbox environment.', 'learndash' ),
			],
		];
	}

	/**
	 * Returns the cards for a user.
	 *
	 * @since 4.25.0
	 *
	 * @param string $customer_id Customer ID.
	 * @param bool   $is_sandbox  Whether to use sandbox mode.
	 *
	 * @return array<int,array{
	 *     token_id: string,
	 *     name: string,
	 *     brand: string,
	 *     last_digits: string,
	 *     expiry: string,
	 * }> Cards data.
	 */
	private function get_cards( string $customer_id, bool $is_sandbox ): array {
		$client = App::get( Client::class );

		if ( ! $client instanceof Client ) {
			return [];
		}

		$payment_token = App::get( Payment_Token::class );

		if ( ! $payment_token instanceof Payment_Token ) {
			return [];
		}

		if ( $is_sandbox ) {
			$client->use_sandbox();
			$payment_token->use_sandbox();
		} else {
			$client->use_production();
			$payment_token->use_production();
		}

		$payment_tokens = $client->list_payment_tokens( $customer_id );

		if ( is_wp_error( $payment_tokens ) ) {
			return [];
		}

		$payment_tokens = (array) Arr::get( $payment_tokens, 'payment_tokens', [] );

		$cards = [];

		foreach ( $payment_tokens as $payment_token ) {
			$card_data = Arr::get( $payment_token, 'payment_source.card', [] );

			if ( empty( $card_data ) ) {
				continue;
			}

			$cards[] = [
				'token_id'    => Cast::to_string( Arr::get( $payment_token, 'id', '' ) ),
				'name'        => Cast::to_string( Arr::get( $card_data, 'name', '' ) ),
				'brand'       => Cast::to_string( Arr::get( $card_data, 'brand', '' ) ),
				'last_digits' => Cast::to_string( Arr::get( $card_data, 'last_digits', '' ) ),
				'expiry'      => Cast::to_string( Arr::get( $card_data, 'expiry', '' ) ),
			];
		}

		return $cards;
	}
}
