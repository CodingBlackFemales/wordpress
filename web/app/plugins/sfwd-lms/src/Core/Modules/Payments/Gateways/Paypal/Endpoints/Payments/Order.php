<?php
/**
 * PayPal Checkout Order endpoint.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Endpoints\Payments;

use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use LearnDash\Core\App;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Client;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Order_Data;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Token;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use WP_User;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

/**
 * PayPal Checkout Order endpoint class.
 *
 * @since 4.25.0
 */
class Order extends Endpoint {
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
	 * Validates the products array.
	 *
	 * @since 4.25.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return bool
	 */
	public function validate_products( $value ): bool {
		if (
			! is_array( $value )
			|| empty( $value )
		) {
			return false;
		}

		foreach ( $value as $product_id ) {
			if (
				! is_numeric( $product_id )
				|| (int) $product_id <= 0
			) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Handles the order creation request.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function handle_create_order( $request ): WP_REST_Response {
		$client = App::get( Client::class );

		if ( ! $client instanceof Client ) {
			return $this->error_response( __( 'PayPal client not found.', 'learndash' ) );
		}

		$payment_gateway = App::get( Payment_Gateway::class );

		if ( ! $payment_gateway instanceof Payment_Gateway ) {
			return $this->error_response( __( 'PayPal payment gateway not found.', 'learndash' ) );
		}

		$user_id = Cast::to_int( $request->get_param( 'user_id' ) );
		$user    = new WP_User( $user_id );

		$product_ids = array_map(
			[ Cast::class, 'to_int' ],
			Arr::wrap( $request->get_param( 'products' ) )
		);

		$use_card_fields = Cast::to_bool( $request->get_param( 'use_card_fields' ) );
		$data_builder    = App::get( Order_Data::class );

		if ( ! $data_builder instanceof Order_Data ) {
			return $this->error_response( __( 'Data builder not found.', 'learndash' ) );
		}

		$order_data = $data_builder->build( $product_ids, $user, $use_card_fields );

		if ( $use_card_fields ) {
			$order_data['use_card_fields'] = true;

			if ( Cast::to_bool( $request->get_param( 'save_payment_method' ) ) ) {
				$order_data['save_payment_method'] = true;
			}

			$vault_id = Cast::to_string( $request->get_param( 'vault_id' ) );

			if ( ! empty( $vault_id ) ) {
				$order_data['vault_id'] = $vault_id;
			}
		}

		$customer_id = Cast::to_string( $request->get_param( 'customer_id' ) );

		if ( ! empty( $customer_id ) ) {
			$order_data['customer_id'] = $customer_id;
		}

		if ( Cast::to_bool( $request->get_param( 'is_sandbox' ) ) ) {
			$client->use_sandbox();
		} else {
			$client->use_production();
		}

		$order = $client->create_order( $order_data );

		if ( is_wp_error( $order ) ) {
			return $this->error_response( $order->get_error_message() );
		}

		// Save the payment token to the reference ID for checkout with saved payment method.
		Payment_Token::maybe_save_payment_token_to_reference_id( $user_id, $order_data, $order );

		return $this->success_response(
			[
				'order' => $order,
			],
			sprintf(
				// translators: %s: order label.
				__( 'PayPal %s created successfully.', 'learndash' ),
				learndash_get_custom_label_lower( 'order' )
			),
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
					'description' => sprintf(
						// translators: %s: order label.
						__( 'PayPal %s creation endpoint response data.', 'learndash' ),
						learndash_get_custom_label_lower( 'order' )
					),
					'properties'  => [
						'order' => [
							'type'        => 'object',
							'description' => sprintf(
								// translators: %s: order label.
								__( 'The created PayPal %s.', 'learndash' ),
								learndash_get_custom_label_lower( 'order' )
							),
							'properties'  => [
								'id'             => [
									'type'        => 'string',
									'description' => sprintf(
										// translators: %s: order label.
										__( 'The PayPal %s ID.', 'learndash' ),
										learndash_get_custom_label_lower( 'order' )
									),
									'example'     => '1234567890ABCDEFG',
								],
								'intent'         => [
									'type'        => 'string',
									'description' => sprintf(
										// translators: %s: order label.
										__( 'The %s intent.', 'learndash' ),
										learndash_get_custom_label_lower( 'order' )
									),
									'example'     => 'CAPTURE',
								],
								'status'         => [
									'type'        => 'string',
									'description' => sprintf(
										// translators: %s: order label.
										__( 'The %s status.', 'learndash' ),
										learndash_get_custom_label_lower( 'order' )
									),
									'example'     => 'PAYER_ACTION_REQUIRED',
								],
								'payment_source' => [
									'type'        => 'object',
									'description' => __( 'The payment source information.', 'learndash' ),
									'properties'  => [
										'paypal' => [
											'type'        => 'object',
											'description' => __( 'PayPal payment source details.', 'learndash' ),
										],
									],
								],
								'purchase_units' => [
									'type'        => 'array',
									'description' => __( 'The purchase units.', 'learndash' ),
									'items'       => [
										'type'       => 'object',
										'properties' => [
											'reference_id' => [
												'type'    => 'string',
												'description' => __( 'The reference ID for the purchase unit.', 'learndash' ),
												'example' => 'ld_1_6_1751332136',
											],
											'amount'       => [
												'type' => 'object',
												'description' => __( 'The amount details.', 'learndash' ),
												'properties' => [
													'currency_code' => [
														'type'        => 'string',
														'description' => __( 'The currency code.', 'learndash' ),
														'example'     => 'USD',
													],
													'value'         => [
														'type'        => 'string',
														'description' => __( 'The amount value.', 'learndash' ),
														'example'     => '10.00',
													],
													'breakdown'     => [
														'type'        => 'object',
														'description' => __( 'The amount breakdown.', 'learndash' ),
														'properties'  => [
															'item_total' => [
																'type'        => 'object',
																'description' => __( 'The item total.', 'learndash' ),
																'properties'  => [
																	'currency_code' => [
																		'type'        => 'string',
																		'description' => __( 'The currency code.', 'learndash' ),
																		'example'     => 'USD',
																	],
																	'value'         => [
																		'type'        => 'string',
																		'description' => __( 'The item total value.', 'learndash' ),
																		'example'     => '10.00',
																	],
																],
															],
														],
													],
												],
											],
											'payee'        => [
												'type' => 'object',
												'description' => __( 'The payee information.', 'learndash' ),
												'properties' => [
													'merchant_id' => [
														'type'        => 'string',
														'description' => __( 'The merchant ID.', 'learndash' ),
														'example'     => 'ABC123DEF456',
													],
												],
											],
											'description'  => [
												'type'    => 'string',
												'description' => __( 'The purchase unit description.', 'learndash' ),
												'example' => 'Test',
											],
											'items'        => [
												'type'  => 'array',
												'description' => __( 'The purchase unit items.', 'learndash' ),
												'items' => [
													'type' => 'object',
													'properties' => [
														'name'        => [
															'type'        => 'string',
															'description' => __( 'The item name.', 'learndash' ),
															'example'     => 'Test',
														],
														'quantity'    => [
															'type'        => 'string',
															'description' => __( 'The item quantity.', 'learndash' ),
															'example'     => '1',
														],
														'unit_amount' => [
															'type'        => 'object',
															'description' => __( 'The unit amount.', 'learndash' ),
															'properties'  => [
																'currency_code' => [
																	'type'        => 'string',
																	'description' => __( 'The currency code.', 'learndash' ),
																	'example'     => 'USD',
																],
																'value'         => [
																	'type'        => 'string',
																	'description' => __( 'The unit amount value.', 'learndash' ),
																	'example'     => '10.00',
																],
															],
														],
													],
												],
											],
										],
									],
								],
								'links'          => [
									'type'        => 'array',
									'description' => sprintf(
										// translators: %s: order label.
										__( 'The %s links for approval and capture.', 'learndash' ),
										learndash_get_custom_label_lower( 'order' )
									),
									'items'       => [
										'type'       => 'object',
										'properties' => [
											'href'   => [
												'type'    => 'string',
												'description' => __( 'The link URL.', 'learndash' ),
												'example' => 'https://api.sandbox.paypal.com/v2/checkout/orders/1234567890ABCDEFG',
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
					'example'     => sprintf(
						// translators: %s: order label.
						__( 'PayPal %s created successfully.', 'learndash' ),
						learndash_get_custom_label_lower( 'order' )
					),
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
			'/order' => [
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_create_order' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => sprintf(
					// translators: %s: order label.
					__( 'Create PayPal %s', 'learndash' ),
					learndash_get_custom_label_lower( 'order' )
				),
				'description'         => sprintf(
					// translators: %s: order label.
					__( 'Creates a new PayPal %s for payment processing.', 'learndash' ),
					learndash_get_custom_label_lower( 'order' )
				),
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
	 *     items?: array{
	 *         type: string,
	 *     },
	 * }>
	 */
	protected function get_endpoint_args(): array {
		return [
			'products'            => [
				'type'              => 'array',
				'required'          => true,
				'validate_callback' => [ $this, 'validate_products' ],
				'description'       => __( 'Array of product IDs to purchase.', 'learndash' ),
				'items'             => [
					'type' => 'integer',
				],
			],
			'user_id'             => [
				'type'        => 'integer',
				'required'    => true,
				'description' => __( 'User ID of the customer.', 'learndash' ),
			],
			'use_card_fields'     => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether to use the card fields.', 'learndash' ),
			],
			'save_payment_method' => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether to save the payment method for future use.', 'learndash' ),
			],
			'customer_id'         => [
				'type'        => 'string',
				'default'     => '',
				'description' => __( 'The PayPal customer ID to use for the payment.', 'learndash' ),
			],
			'vault_id'            => [
				'type'        => 'string',
				'default'     => '',
				'description' => __( 'The PayPal vault ID to use for the payment.', 'learndash' ),
			],
			'is_sandbox'          => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether to use the sandbox environment.', 'learndash' ),
			],
		];
	}
}
