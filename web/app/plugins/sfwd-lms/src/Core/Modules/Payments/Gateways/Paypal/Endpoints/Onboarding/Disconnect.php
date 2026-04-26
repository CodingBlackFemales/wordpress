<?php
/**
 * PayPal Checkout Onboarding Disconnect endpoint.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Endpoints\Onboarding;

use LearnDash\Core\App;
use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Client;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Webhook_Client;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Whodat_Client;
use LearnDash\Core\Utilities\Cast;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use LearnDash_Settings_Section;

/**
 * PayPal Checkout Onboarding Disconnect endpoint class.
 *
 * @since 4.25.0
 */
class Disconnect extends Endpoint {
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
	 * Handles the disconnect request.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function handle_disconnect( $request ): WP_REST_Response {
		$client = App::get( Client::class );

		if ( ! $client instanceof Client ) {
			return $this->error_response( __( 'PayPal client not found.', 'learndash' ) );
		}

		$webhook_client = App::get( Webhook_Client::class );

		if ( ! $webhook_client instanceof Webhook_Client ) {
			return $this->error_response( __( 'PayPal webhook client not found.', 'learndash' ) );
		}

		if ( ! Cast::to_bool( $request->get_param( 'confirm_disconnect' ) ) ) {
			return $this->error_response( __( 'Please confirm the disconnect by setting the confirm_disconnect parameter to true.', 'learndash' ) );
		}

		// Remove access token data.
		$client->delete_access_token_data();

		// Remove client access token.
		$client->delete_client_token();

		// Clear the settings.
		LearnDash_Settings_Section::set_section_settings_all(
			'LearnDash_Settings_Section_PayPal_Checkout',
			[
				'signup_hash'               => '',
				'merchant_id'               => '',
				'merchant_id_in_paypal'     => '',
				'api_granted_scopes'        => '',
				'webhooks'                  => '',
				'supports_custom_payments'  => '',
				'client_id'                 => '',
				'client_secret'             => '',
				'account_id'                => '',
				'merchant_account_is_ready' => '',
				'merchant_account_verified' => '',
			]
		);

		// Remove webhook data.
		$webhook_client->delete_webhook_data();

		// Delete all transients.
		Whodat_Client::delete_all_transients();

		return $this->success_response(
			[
				'disconnected' => true,
			],
			__( 'PayPal Checkout disconnected successfully.', 'learndash' ),
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
					'description' => __( 'PayPal onboarding disconnect endpoint response data.', 'learndash' ),
					'properties'  => [
						'disconnected' => [
							'type'        => 'boolean',
							'description' => __( 'Indicates if PayPal Checkout was disconnected successfully.', 'learndash' ),
							'example'     => true,
						],
					],
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Success message.', 'learndash' ),
					'example'     => __( 'PayPal Checkout disconnected successfully.', 'learndash' ),
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
			'/disconnect' => [
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'handle_disconnect' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Disconnect PayPal Checkout', 'learndash' ),
				'description'         => __( 'Disconnects PayPal Checkout by clearing all stored tokens and data.', 'learndash' ),
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
	 *     description: string,
	 * }>
	 */
	protected function get_endpoint_args(): array {
		return [
			'confirm_disconnect' => [
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Whether to confirm the disconnect.', 'learndash' ),
			],
		];
	}
}
