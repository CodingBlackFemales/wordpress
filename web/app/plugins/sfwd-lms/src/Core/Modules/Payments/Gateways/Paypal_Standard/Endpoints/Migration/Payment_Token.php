<?php
/**
 * PayPal Standard Migration Payment Token endpoint.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Endpoints\Migration;

use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use LearnDash\Core\App;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Client;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Token as Payment_Token_Handler;
use LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Migration\User_Data;
use LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Migration\Scheduler;
use LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Migration\Subscriptions;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

/**
 * PayPal Standard Migration Payment Token endpoint class.
 *
 * This endpoint is used to create a payment token for a user and schedule the migration of their subscriptions from PayPal Standard to PayPal Checkout.
 *
 * @since 4.25.3
 */
class Payment_Token extends Endpoint {
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
	 * Validates the token ID.
	 *
	 * @since 4.25.3
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
	 * Handles the payment token creation request for migration.
	 *
	 * @since 4.25.3
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

		$scheduler = App::get( Scheduler::class );

		if ( ! $scheduler instanceof Scheduler ) {
			return $this->error_response( __( 'PayPal Standard scheduler not found.', 'learndash' ) );
		}

		$subscriptions = App::get( Subscriptions::class );

		if ( ! $subscriptions instanceof Subscriptions ) {
			return $this->error_response( __( 'PayPal Standard subscriptions helper not found.', 'learndash' ) );
		}

		$is_sandbox = Cast::to_bool( $request->get_param( 'is_sandbox' ) );

		// Set the environment for the client and payment token handler.
		if ( $is_sandbox ) {
			$client->use_sandbox();
			$payment_token_handler->use_sandbox();
		} else {
			$client->use_production();
			$payment_token_handler->use_production();
		}

		// Get the payment token from PayPal.
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
			'card'     => [
				'holder_name'   => Cast::to_string( Arr::get( $result, 'payment_source.card.name', '' ) ),
				'brand'         => Cast::to_string( Arr::get( $result, 'payment_source.card.brand', '' ) ),
				'last_4_digits' => Cast::to_string( Arr::get( $result, 'payment_source.card.last_digits', '' ) ),
				'expiry_date'   => Cast::to_string( Arr::get( $result, 'payment_source.card.expiry', '' ) ),
			],
		];

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

		$product_ids = $subscriptions->get_user_subscribed_product_ids( $user_id );

		// Schedule migrations for each product.
		$migrations_scheduled = 0;
		foreach ( $product_ids as $product_id ) {
			$migration_scheduled = $scheduler->schedule_migration(
				$product_id,
				$user_id,
				Cast::to_string( Arr::get( $payment_token_data, 'id', '' ) )
			);

			if ( $migration_scheduled ) {
				++$migrations_scheduled;
			}
		}

		if ( $migrations_scheduled !== count( $product_ids ) ) {
			return $this->error_response(
				__( 'Not all migrations were scheduled, please try again later.', 'learndash' ),
				'not_all_migrations_scheduled',
				500,
			);
		}

		// Update the migration data for the user.
		User_Data::update_migration_data(
			$user_id,
			[
				'products' => $product_ids,
				'status'   => 'pending',
			],
			$is_sandbox
		);

		return $this->success_response(
			[
				'migrations_scheduled' => $migrations_scheduled,
				'total_products'       => count( $product_ids ),
			],
			sprintf(
				// translators: %1$d: number of migrations scheduled, %2$d: total products.
				__( '%1$d of %2$d migrations scheduled successfully.', 'learndash' ),
				$migrations_scheduled,
				count( $product_ids )
			),
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
	 *     required?: array<string>
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
					'description' => __( 'PayPal payment token creation endpoint response data for migration.', 'learndash' ),
					'properties'  => [
						'migrations_scheduled' => [
							'type'        => 'integer',
							'description' => __( 'Number of migrations successfully scheduled.', 'learndash' ),
							'example'     => 3,
						],
						'total_products'       => [
							'type'        => 'integer',
							'description' => __( 'Total number of products provided.', 'learndash' ),
							'example'     => 5,
						],
					],
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Success message.', 'learndash' ),
					'example'     => __( 'PayPal payment token created successfully and migration scheduled.', 'learndash' ),
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
			'/payment-token' => [
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle_create_payment_token' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Create PayPal Payment Token for Migration', 'learndash' ),
				'description'         => __( 'Creates a new PayPal payment token and schedules migration from PayPal Standard to PayPal Checkout.', 'learndash' ),
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
			'is_sandbox' => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether to use the sandbox environment.', 'learndash' ),
			],
		];
	}
}
