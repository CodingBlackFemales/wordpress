<?php
/**
 * Remove Card endpoint for LearnDash REST API.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\V1\Endpoints\Profile;

use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use LearnDash\Core\Utilities\Cast;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Remove Card endpoint for LearnDash REST API.
 *
 * @since 4.25.0
 */
class Remove_Card extends Endpoint {
	/**
	 * The base route for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $base_route = '/profile';

	/**
	 * The permission required to access this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $permission_required = 'read';

	/**
	 * Whether the endpoint is experimental.
	 *
	 * @since 4.25.0
	 *
	 * @var bool
	 */
	protected bool $experimental = true;

	/**
	 * Validates the ID parameters.
	 *
	 * @since 4.25.0
	 *
	 * @param mixed $value The value to validate.
	 *
	 * @return bool
	 */
	public function validate_id( $value ): bool {
		return is_string( $value ) && ! empty( $value );
	}

	/**
	 * Handles the remove card request.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The REST request object.
	 *
	 * @return WP_REST_Response
	 */
	public function handle_remove_card( $request ): WP_REST_Response {
		$user_id    = get_current_user_id();
		$card_id    = Cast::to_string( $request->get_param( 'card_id' ) );
		$gateway_id = Cast::to_string( $request->get_param( 'gateway_id' ) );

		if ( ! $user_id ) {
			return $this->error_response(
				__( 'Current user not found.', 'learndash' ),
				'rest_user_not_found',
				401
			);
		}

		/**
		 * Filters the result of removing a payment card.
		 *
		 * @since 4.25.0
		 *
		 * @param bool|WP_Error $result     The result of the card removal operation.
		 * @param string        $card_id    The ID of the card to remove.
		 * @param string        $gateway_id The ID of the payment gateway.
		 * @param int           $user_id    The ID of the user.
		 */
		$result = apply_filters(
			"learndash_handle_remove_card_{$gateway_id}",
			new WP_Error(
				'rest_card_removal_not_supported',
				__( 'It is not possible to remove this card.', 'learndash' ),
				[ 'status' => 400 ]
			),
			$card_id,
			$gateway_id,
			$user_id
		);

		if ( is_wp_error( $result ) ) {
			return $this->error_response(
				$result->get_error_message(),
				Cast::to_string( $result->get_error_code() ),
				Cast::to_int( $result->get_error_data( 'status' ) ?? 400 )
			);
		}

		if ( ! $result ) {
			return $this->error_response(
				__( 'Card removal failed.', 'learndash' ),
				'rest_card_removal_failed',
				400
			);
		}

		return $this->success_response(
			[
				'removed' => true,
			],
			__( 'Card removed successfully.', 'learndash' ),
			200
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
	 *     required?: array<string>,
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
					'description' => __( 'Returns the card removal success status.', 'learndash' ),
					'properties'  => [
						'removed' => [
							'type'        => 'boolean',
							'description' => __( 'Indicates if the card was removed.', 'learndash' ),
							'example'     => true,
						],
					],
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Success message.', 'learndash' ),
					'example'     => sprintf(
						__( 'Card removed successfully.', 'learndash' )
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
			'/remove-card' => [
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'handle_remove_card' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Remove a payment card', 'learndash' ),
				'description'         => __( 'Removes a payment card for the current user.', 'learndash' ),
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
			'card_id'    => [
				'type'              => 'string',
				'validate_callback' => [ $this, 'validate_id' ],
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'The ID of the card to remove.', 'learndash' ),
				'required'          => true,
			],
			'gateway_id' => [
				'type'              => 'string',
				'validate_callback' => [ $this, 'validate_id' ],
				'sanitize_callback' => 'sanitize_text_field',
				'description'       => __( 'The ID of the payment gateway.', 'learndash' ),
				'required'          => true,
			],
		];
	}
}
