<?php
/**
 * PayPal Checkout Confirm Order endpoint.
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
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Order_Status;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

/**
 * PayPal Checkout Confirm Order endpoint class.
 *
 * @since 4.25.0
 */
class Confirm_Order extends Endpoint {
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
	 * Validates the order ID.
	 *
	 * @since 4.25.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return bool
	 */
	public function validate_order_id( $value ): bool {
		return is_string( $value ) && ! empty( $value );
	}

	/**
	 * Handles the order confirmation request.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function handle_confirm_order( $request ): WP_REST_Response {
		$gateway = App::get( Payment_Gateway::class );

		if ( ! $gateway instanceof Payment_Gateway ) {
			return $this->error_response( __( 'Payment gateway not found.', 'learndash' ) );
		}

		$gateway->log_info( 'Confirming order: ' . $request->get_param( 'order_id' ) );

		$client = App::get( Client::class );

		if ( ! $client instanceof Client ) {
			$message = __( 'PayPal client not found.', 'learndash' );

			$gateway->log_error( $message );

			return $this->error_response( $message );
		}

		$order_id = Cast::to_string( $request->get_param( 'order_id' ) );
		$user_id  = Cast::to_int( $request->get_param( 'user_id' ) );

		if ( Cast::to_bool( $request->get_param( 'is_sandbox' ) ) ) {
			$client->use_sandbox();
			$gateway->log_info( 'Using sandbox mode.' );
		} else {
			$client->use_production();
			$gateway->log_info( 'Using production mode.' );
		}

		// Handle initial order confirmation.
		$order = $client->get_order( $order_id );

		if ( is_wp_error( $order ) ) {
			$gateway->log_error( 'Error getting order: ' . $order->get_error_message() );

			return $this->error_response( $order->get_error_message() );
		}

		$reference_id = Cast::to_string( Arr::get( $order, 'purchase_units.0.reference_id' ) );

		$has_transaction = $gateway->has_transaction_from_reference_id( $user_id, $reference_id );

		// If we have a transaction, it means the order is already processed. So we don't need to do anything.

		if ( $has_transaction ) {
			$gateway->log_info( 'Order has already been processed.' );

			return $this->success_response(
				[
					'order_id'     => $order_id,
					'redirect_url' => $gateway->get_success_url_from_reference_id( $user_id, $reference_id, $order_id ),
				],
				sprintf(
					// translators: %s: order label.
					__( 'PayPal %s has already been processed.', 'learndash' ),
					learndash_get_custom_label_lower( 'order' )
				)
			);
		}

		$order_status = App::get( Order_Status::class );

		if ( ! $order_status instanceof Order_Status ) {
			$gateway->log_error( 'Order status helper not found.' );

			return $this->error_response( __( 'Status helper not found.', 'learndash' ) );
		}

		$status = $order_status->get_latest_payment_status( $order );

		$gateway->log_info( 'Latest payment status: ' . $status );

		if ( $order_status->is_failed_payment( $status ) ) {
			/**
			 * If the payment is declined, we don't need to do anything.
			 * We only create a transaction if the payment is successful.
			 */

			$gateway->log_info( 'Payment was declined.' );

			return $this->error_response( __( 'Your payment was declined.', 'learndash' ) );
		}

		if ( $order_status->is_successful_payment( $status ) ) {
			$gateway->log_info( 'Processing successful single payment.' );

			$gateway->process_successful_single_payment( $user_id, $order );

			$gateway->log_info( 'Successful single payment processed.' );

			return $this->success_response(
				[
					'order_id'     => $order_id,
					'redirect_url' => $gateway->get_success_url_from_reference_id( $user_id, $reference_id, $order_id ),
				],
				sprintf(
					// translators: %s: order label.
					__( 'PayPal %s confirmed successfully.', 'learndash' ),
					learndash_get_custom_label_lower( 'order' )
				)
			);
		}

		$gateway->log_info( 'Order is not in a valid state to confirm.' );

		return $this->error_response(
			sprintf(
				// translators: %s: order label.
				__( 'PayPal %s is not in a valid state to confirm.', 'learndash' ),
				learndash_get_custom_label_lower( 'order' )
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
						__( 'PayPal %s confirmation response data.', 'learndash' ),
						learndash_get_custom_label_lower( 'order' )
					),
					'properties'  => [
						'order_id'     => [
							'type'        => 'string',
							'description' => sprintf(
								// translators: %s: order label.
								__( 'The PayPal %s ID.', 'learndash' ),
								learndash_get_custom_label_lower( 'order' )
							),
							'example'     => '1234567890ABCDEFG',
						],
						'redirect_url' => [
							'type'        => 'string',
							'description' => __( 'The URL to redirect the user to after confirmation.', 'learndash' ),
							'example'     => 'https://example.com/success',
						],
					],
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Success message.', 'learndash' ),
					'example'     => sprintf(
						// translators: %s: order label.
						__( 'PayPal %s confirmed successfully.', 'learndash' ),
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
			'/confirm' => [
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_confirm_order' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => sprintf(
					// translators: %s: order label.
					__( 'Confirm a PayPal %s', 'learndash' ),
					learndash_get_custom_label_lower( 'order' )
				),
				'description'         => sprintf(
					// translators: %s: order label.
					__( 'Confirms a PayPal %s and returns the redirect URL.', 'learndash' ),
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
	 * }>
	 */
	protected function get_endpoint_args(): array {
		return [
			'order_id'   => [
				'type'              => 'string',
				'required'          => true,
				'validate_callback' => [ $this, 'validate_order_id' ],
				'description'       => sprintf(
					// translators: %s: order label.
					__( 'The PayPal %s ID to confirm.', 'learndash' ),
					learndash_get_custom_label_lower( 'order' )
				),
			],
			'user_id'    => [
				'type'        => 'integer',
				'required'    => true,
				'description' => __( 'The PayPal user ID for the payment confirmation.', 'learndash' ),
			],

			'is_sandbox' => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether to use the sandbox environment.', 'learndash' ),
			],
		];
	}
}
