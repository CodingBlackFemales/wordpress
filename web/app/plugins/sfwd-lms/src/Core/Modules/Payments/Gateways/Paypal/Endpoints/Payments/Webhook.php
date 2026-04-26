<?php
/**
 * PayPal Checkout Webhook endpoint.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Endpoints\Payments;

use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use LearnDash\Core\App;
use LearnDash\Core\Enums\Commerce\Cancellation_Reason;
use LearnDash\Core\Models\Commerce\Subscription;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Webhook_Client;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Token;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

/**
 * PayPal Checkout Webhook endpoint class.
 *
 * @since 4.25.0
 */
class Webhook extends Endpoint {
	/**
	 * The base route for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $base_route = '/commerce/paypal';

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
	 * Whether the endpoint is experimental.
	 *
	 * PayPal can't send the special LD header. So we need to disable the experimental flag.
	 *
	 * @since 4.25.0
	 *
	 * @var bool
	 */
	protected bool $experimental = false;

	/**
	 * Handles the webhook request.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function handle_webhook( $request ): WP_REST_Response {
		$gateway = App::get( Payment_Gateway::class );

		if ( ! $gateway instanceof Payment_Gateway ) {
			// Return success response to avoid PayPal retries.
			return $this->success_response(
				[ 'processed_successfully' => false ],
				__( 'Payment gateway not found.', 'learndash' )
			);
		}

		$gateway->log_info( 'Webhook received: ' . Cast::to_string( $request->get_param( 'event_type' ) ) );

		$webhook_client = App::get( Webhook_Client::class );

		if ( ! $webhook_client instanceof Webhook_Client ) {
			$message = __( 'Webhook client not found.', 'learndash' );

			$gateway->log_error( $message );

			// Return success response to avoid PayPal retries.
			return $this->success_response(
				[ 'processed_successfully' => false ],
				$message
			);
		}

		if ( $gateway->is_sandbox_enabled() ) {
			$webhook_client->use_sandbox();
			$gateway->log_info( 'Using sandbox mode.' );
		} else {
			$webhook_client->use_production();
			$gateway->log_info( 'Using production mode.' );
		}

		// Webhook validation.

		$validation_result = $this->validate_webhook( $webhook_client, $gateway, $request );

		if ( is_wp_error( $validation_result ) ) {
			// Return success response to avoid PayPal retries.
			return $this->success_response(
				[ 'processed_successfully' => false ],
				$validation_result->get_error_message()
			);
		}

		$event_type = Cast::to_string( $request->get_param( 'event_type' ) );
		$event_data = (array) $request->get_param( 'resource' );

		switch ( $event_type ) {
			case 'CHECKOUT.ORDER.APPROVED':
			case 'CHECKOUT.ORDER.COMPLETED':
				$custom_data = $this->get_webhook_custom_data( $event_data, 'purchase_units.0.custom_id' );

				if ( is_wp_error( $custom_data ) ) {
					$gateway->log_error( $custom_data->get_error_message() );

					return $this->success_response(
						[ 'processed_successfully' => false ],
						$custom_data->get_error_message()
					);
				}

				$gateway->process_successful_single_payment( $custom_data['user_id'], $event_data );
				break;

			case 'PAYMENT.ORDER.CANCELLED':
			case 'PAYMENT.CAPTURE.DENIED':
			case 'PAYMENT.CAPTURE.REFUNDED':
			case 'PAYMENT.CAPTURE.REVERSED':
				$gateway->log_info( 'Event received: ' . $event_type );

				$custom_data = $this->get_webhook_custom_data( $event_data, 'custom_id' );

				if ( is_wp_error( $custom_data ) ) {
					$gateway->log_error( $custom_data->get_error_message() );

					return $this->success_response(
						[ 'processed_successfully' => false ],
						$custom_data->get_error_message()
					);
				}

				$gateway->process_failed_payment(
					$custom_data['user_id'],
					$custom_data['product_ids'],
					Cancellation_Reason::REFUNDED()->getValue()
				);

				break;

			case 'VAULT.PAYMENT-TOKEN.CREATED':
			case 'VAULT.PAYMENT-TOKEN.DELETED':
				$payment_token = App::get( Payment_Token::class );

				if ( ! $payment_token instanceof Payment_Token ) {
					$message = __( 'Payment token not found.', 'learndash' );

					$gateway->log_error( $message );

					return $this->success_response(
						[ 'processed_successfully' => false ],
						$message
					);
				}

				if ( $gateway->is_sandbox_enabled() ) {
					$payment_token->use_sandbox();
				} else {
					$payment_token->use_production();
				}

				$payment_token->process_webhook_event( $request->get_params() );
				break;

			default:
		}

		$message = __( 'Webhook processed successfully.', 'learndash' );

		$gateway->log_info( $message );

		return $this->success_response(
			[ 'processed_successfully' => true ],
			$message
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
	 *     properties: array<string,array<string,mixed>>|object,
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
					'description' => __( 'Webhook response data.', 'learndash' ),
					'properties'  => [
						'processed_successfully' => [
							'type'        => 'boolean',
							'description' => __( 'Indicates if the webhook was processed successfully.', 'learndash' ),
							'example'     => true,
						],
					],
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Processing status message.', 'learndash' ),
					'example'     => __( 'Webhook processed successfully.', 'learndash' ),
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
			'/webhook' => [
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_webhook' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Handle PayPal webhook event', 'learndash' ),
				'description'         => __( 'Handles PayPal webhook notifications for payment events.', 'learndash' ),
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
		return [];
	}

	/**
	 * Parses the headers from the PayPal webhook request.
	 *
	 * Used in the signature verification.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,string> $paypal_headers The headers from the PayPal webhook request.
	 *
	 * @return array<string,string>|WP_Error
	 */
	protected function parse_headers( array $paypal_headers ) {
		$header_keys = [
			'transmission_id'   => 'PAYPAL-TRANSMISSION-ID',
			'transmission_time' => 'PAYPAL-TRANSMISSION-TIME',
			'transmission_sig'  => 'PAYPAL-TRANSMISSION-SIG',
			'cert_url'          => 'PAYPAL-CERT-URL',
			'auth_algo'         => 'PAYPAL-AUTH-ALGO',
			'debug_id'          => 'PAYPAL-DEBUG-ID',
		];

		$headers      = [];
		$missing_keys = [];

		foreach ( $header_keys as $property => $key ) {
			// Headers are inconsistent between sandbox and live.

			if ( ! isset( $paypal_headers[ $key ] ) ) {
				$key = str_replace( '-', '_', $key );
				$key = strtoupper( $key );

				if ( ! isset( $paypal_headers[ $key ] ) ) {
					$key = strtolower( $key );
				}
			}

			$value = Cast::to_string( Arr::get( $paypal_headers, "$key.0", '' ) );

			if ( ! empty( $value ) ) {
				$headers[ $property ] = $value;
			} else {
				$missing_keys[] = $property;
			}
		}

		// Remove the debug_id from the missing keys.
		if ( ! empty( $missing_keys ) ) {
			$missing_keys = array_diff( $missing_keys, [ 'debug_id' ] );
		}

		if ( ! empty( $missing_keys ) ) {
			return new WP_Error(
				'learndash-gateway-paypal-checkout-webhook-missing-headers',
				sprintf(
					// translators: %s: List of missing headers.
					__( 'Missing headers from the PayPal webhook request: %s', 'learndash' ),
					implode( ', ', $missing_keys )
				)
			);
		}

		return $headers;
	}

	/**
	 * Verifies the identity of the Webhook request, to avoid any security problems.
	 *
	 * @since 4.25.0
	 *
	 * @param Payment_Gateway      $gateway        The payment gateway instance.
	 * @param Webhook_Client       $webhook_client The webhook client instance.
	 * @param array<string,mixed>  $event          The Event received by the endpoint from PayPal.
	 * @param array<string,string> $headers        Headers from the PayPal request that we use to verify the signature.
	 *
	 * @return bool
	 */
	protected function verify_webhook_signature(
		Payment_Gateway $gateway,
		Webhook_Client $webhook_client,
		array $event,
		array $headers
	): bool {
		$validation_fields = [
			'transmission_id'   => Cast::to_string( Arr::get( $headers, 'transmission_id' ) ),
			'transmission_time' => Cast::to_string( Arr::get( $headers, 'transmission_time' ) ),
			'transmission_sig'  => Cast::to_string( Arr::get( $headers, 'transmission_sig' ) ),
			'cert_url'          => Cast::to_string( Arr::get( $headers, 'cert_url' ) ),
			'auth_algo'         => Cast::to_string( Arr::get( $headers, 'auth_algo' ) ),
			'webhook_id'        => $webhook_client->get_webhook_data( 'id' ),
			'webhook_event'     => $event,
		];

		$response = $webhook_client->verify_webhook_signature( $validation_fields );

		if ( is_wp_error( $response ) ) {
			$gateway->log_error( $response->get_error_message() );

			return false;
		}

		return 'SUCCESS' === Arr::get( $response, 'verification_status', false );
	}

	/**
	 * Validates the webhook request.
	 *
	 * @since 4.25.0
	 *
	 * @param Webhook_Client                       $webhook_client The webhook client instance.
	 * @param Payment_Gateway                      $gateway        The payment gateway instance.
	 * @param WP_REST_Request<array<string,mixed>> $request        The request object.
	 *
	 * @return bool|WP_Error
	 */
	private function validate_webhook( Webhook_Client $webhook_client, Payment_Gateway $gateway, $request ) {
		$gateway->log_info( 'Validating webhook...' );

		if ( ! $gateway->is_ready() ) {
			$message = __( 'Payment gateway not ready.', 'learndash' );

			$gateway->log_error( $message );

			return new WP_Error(
				'learndash-gateway-paypal-checkout-webhook-gateway-not-ready',
				$message
			);
		}

		if ( ! $webhook_client->is_event_processable( Cast::to_string( $request->get_param( 'event_type' ) ) ) ) {
			$message = __( 'Event not processed by LearnDash.', 'learndash' );

			$gateway->log_error( $message );

			return new WP_Error(
				'learndash-gateway-paypal-checkout-webhook-event-not-processable',
				$message
			);
		}

		$headers = $this->parse_headers( $request->get_headers() );

		if ( is_wp_error( $headers ) ) {
			$gateway->log_error( $headers->get_error_message() );

			return new WP_Error(
				'learndash-gateway-paypal-checkout-webhook-headers-validation-failed',
				$headers->get_error_message()
			);
		}

		if ( ! $this->verify_webhook_signature(
			$gateway,
			$webhook_client,
			$request->get_params(),
			$headers
		) ) {
			$message = __( 'Invalid webhook signature.', 'learndash' );

			$gateway->log_error( $message );

			return new WP_Error(
				'learndash-gateway-paypal-checkout-webhook-signature-verification-failed',
				$message
			);
		}

		// All checks passed.

		$gateway->log_info( 'Webhook validated successfully.' );

		return true;
	}

	/**
	 * Returns the custom data from the webhook event data.
	 *
	 * @since 4.25.0
	 *
	 * @param array<mixed> $event_data       The event data.
	 * @param string       $custom_field_key The custom ID field key in the event data.
	 *
	 * @return array{
	 *     user_id: int,
	 *     product_ids: int[],
	 *     ld_version: string,
	 * }|WP_Error
	 */
	private function get_webhook_custom_data( array $event_data, string $custom_field_key ) {
		$custom_data = json_decode(
			Cast::to_string(
				Arr::get( $event_data, $custom_field_key, '' )
			),
			true
		);

		if ( empty( $custom_data ) ) {
			return new WP_Error(
				'learndash-gateway-paypal-checkout-webhook-custom-data-not-found',
				__( 'Custom data not set.', 'learndash' )
			);
		}

		$user_id = Cast::to_int( Arr::get( $custom_data, 'user_id', 0 ) );

		if ( $user_id <= 0 ) {
			return new WP_Error(
				'learndash-gateway-paypal-checkout-webhook-user-id-not-found',
				__( 'User ID not set.', 'learndash' )
			);
		}

		$product_ids = (array) Arr::get( $custom_data, 'product_ids', [] );

		if (
			! is_array( $product_ids )
			|| empty( $product_ids )
		) {
			return new WP_Error(
				'learndash-gateway-paypal-checkout-webhook-product-ids-not-found',
				__( 'Product IDs not set.', 'learndash' )
			);
		}

		$ld_version = Cast::to_string( Arr::get( $custom_data, 'ld_version', '' ) );

		if ( empty( $ld_version ) ) {
			return new WP_Error(
				'learndash-gateway-paypal-checkout-webhook-ld-version-not-found',
				__( 'LD version not set.', 'learndash' )
			);
		}

		return [
			'user_id'     => $user_id,
			'product_ids' => $product_ids,
			'ld_version'  => $ld_version,
		];
	}
}
