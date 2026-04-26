<?php
/**
 * PayPal Checkout Cancel endpoint.
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
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

/**
 * PayPal Checkout Cancel endpoint class.
 *
 * @since 4.25.0
 */
class Cancel extends Endpoint {
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
	 * Handles the order cancellation request.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function handle_cancel_order( $request ): WP_REST_Response {
		$client = App::get( Client::class );

		if ( ! $client instanceof Client ) {
			return $this->error_response( __( 'PayPal client not found.', 'learndash' ) );
		}

		$gateway = App::get( Payment_Gateway::class );

		if ( ! $gateway instanceof Payment_Gateway ) {
			return $this->error_response( __( 'Payment gateway not found.', 'learndash' ) );
		}

		$order_id = Cast::to_string( $request->get_param( 'order_id' ) );

		if ( Cast::to_bool( $request->get_param( 'is_sandbox' ) ) ) {
			$client->use_sandbox();
		} else {
			$client->use_production();
		}

		$order = $client->get_order( $order_id );

		if ( is_wp_error( $order ) ) {
			return $this->error_response( $order->get_error_message() );
		}

		$gateway->delete_reference_id_data(
			Cast::to_int( $request->get_param( 'user_id' ) ),
			Cast::to_string( Arr::get( $order, 'purchase_units.0.reference_id' ) )
		);

		return $this->success_response(
			[
				'canceled' => true,
			],
			sprintf(
				// translators: %s: order label.
				__( 'PayPal %s canceled successfully.', 'learndash' ),
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
						__( 'Returns the %s cancellation data.', 'learndash' ),
						learndash_get_custom_label_lower( 'order' )
					),
					'properties'  => [
						'canceled' => [
							'type'        => 'boolean',
							'description' => sprintf(
								// translators: %s: order label.
								__( 'Indicates if the PayPal %s was canceled.', 'learndash' ),
								learndash_get_custom_label_lower( 'order' )
							),
							'example'     => true,
						],
					],
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Success message.', 'learndash' ),
					'example'     => sprintf(
						// translators: %s: order label.
						__( 'PayPal %s canceled successfully.', 'learndash' ),
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
			'/cancel' => [
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_cancel_order' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => sprintf(
					// translators: %s: order label.
					__( 'Cancels a PayPal %s', 'learndash' ),
					learndash_get_custom_label_lower( 'order' )
				),
				'description'         => sprintf(
					// translators: %s: order label.
					__( 'Cancels a PayPal %s.', 'learndash' ),
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
					__( 'The PayPal %s ID to cancel.', 'learndash' ),
					learndash_get_custom_label_lower( 'order' )
				),
			],
			'user_id'    => [
				'type'        => 'integer',
				'required'    => true,
				'description' => sprintf(
					// translators: %s: order label.
					__( 'The user ID to cancel the PayPal %s for.', 'learndash' ),
					learndash_get_custom_label_lower( 'order' )
				),
			],
			'is_sandbox' => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether to use the sandbox environment.', 'learndash' ),
			],
		];
	}
}
