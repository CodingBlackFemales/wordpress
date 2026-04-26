<?php
/**
 * PayPal Checkout Capture endpoint.
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
 * PayPal Checkout Capture endpoint class.
 *
 * @since 4.25.0
 */
class Capture extends Endpoint {
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
	 * Handles the order capture request.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function handle_capture_order( $request ): WP_REST_Response {
		$client = App::get( Client::class );

		if ( ! $client instanceof Client ) {
			return $this->error_response( __( 'PayPal client not found.', 'learndash' ) );
		}

		$payment_token = App::get( Payment_Token::class );

		if ( ! $payment_token instanceof Payment_Token ) {
			return $this->error_response( __( 'PayPal payment token not found.', 'learndash' ) );
		}

		$order_id = Cast::to_string( $request->get_param( 'order_id' ) );

		if ( Cast::to_bool( $request->get_param( 'is_sandbox' ) ) ) {
			$client->use_sandbox();
			$payment_token->use_sandbox();
		} else {
			$client->use_production();
			$payment_token->use_production();
		}

		$capture = $client->capture_order(
			$order_id,
			Cast::to_string( $request->get_param( 'payer_id' ) )
		);

		if ( is_wp_error( $capture ) ) {
			return $this->error_response( $capture->get_error_message() );
		}

		// Maybe save the payment token.
		$custom_id = json_decode( Cast::to_string( Arr::get( $capture, 'purchase_units.0.custom_id', '' ) ), true );
		$user_id   = is_array( $custom_id ) ? Cast::to_int( Arr::get( $custom_id, 'user_id', 0 ) ) : 0;

		if ( 0 !== $user_id ) {
			$payment_token->save_user_payment_token_from_order( $user_id, $capture );
		}

		$status = Cast::to_string( Arr::get( $capture, 'status', '' ) );

		if ( 'COMPLETED' !== $status ) {
			return $this->error_response(
				__( 'Your payment was declined.', 'learndash' ),
				'rest_error_paypal_capture_failed',
				400,
				$capture
			);
		}

		return $this->success_response(
			[
				'order_id' => $order_id,
			],
			sprintf(
				// translators: %s: order label.
				__( 'PayPal %s captured successfully.', 'learndash' ),
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
						__( 'PayPal %s capture endpoint response data.', 'learndash' ),
						learndash_get_custom_label_lower( 'order' )
					),
					'properties'  => [
						'order_id' => [
							'type'        => 'string',
							'description' => sprintf(
								// translators: %s: order label.
								__( 'The PayPal %s ID that was captured.', 'learndash' ),
								learndash_get_custom_label_lower( 'order' )
							),
							'example'     => '1234567890ABCDEFG',
						],
					],
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Success message.', 'learndash' ),
					'example'     => sprintf(
						// translators: %s: order label.
						__( 'PayPal %s captured successfully.', 'learndash' ),
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
			'/capture' => [
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_capture_order' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => sprintf(
					// translators: %s: order label.
					__( 'Capture a PayPal %s', 'learndash' ),
					learndash_get_custom_label_lower( 'order' )
				),
				'description'         => sprintf(
					// translators: %s: order label.
					__( 'Captures a PayPal %s to complete the payment.', 'learndash' ),
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
					__( 'The PayPal %s ID to capture.', 'learndash' ),
					learndash_get_custom_label_lower( 'order' )
				),
			],
			'payer_id'   => [
				'type'        => 'string',
				'required'    => false,
				'default'     => '',
				'description' => __( 'The PayPal payer ID.', 'learndash' ),
			],
			'is_sandbox' => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether to use the sandbox environment.', 'learndash' ),
			],
		];
	}
}
