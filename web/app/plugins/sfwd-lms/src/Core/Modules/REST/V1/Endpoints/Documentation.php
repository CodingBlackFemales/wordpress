<?php
/**
 * Documentation endpoint for LearnDash REST API.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\V1\Endpoints;

use LearnDash\Core\App;
use LearnDash\Core\Modules\REST\V1\Controller;
use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use LearnDash\Core\Modules\REST\V1\OpenAPI;
use LearnDash\Core\Utilities\Sanitize;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Documentation endpoint for LearnDash REST API.
 *
 * @since 4.25.0
 */
class Documentation extends Endpoint {
	/**
	 * The base route for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $base_route = '/docs';

	/**
	 * The permission required to access this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $permission_required = '';

	/**
	 * Whether the endpoint is experimental.
	 *
	 * @since 4.25.0
	 *
	 * @var bool
	 */
	protected bool $experimental = false;

	/**
	 * Returns the routes configuration for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string,array{
	 *     methods: string,
	 *     callback: callable,
	 *     permission_callback: callable,
	 *     summary: string,
	 *     description: string,
	 * }>
	 */
	protected function get_routes(): array {
		return [
			'/openapi' => [
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_openapi_spec' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Get OpenAPI specification', 'learndash' ),
				'description'         => __( 'Returns the OpenAPI 3.0 specification in JSON format.', 'learndash' ),
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
	 * Returns the OpenAPI specification.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_openapi_spec( WP_REST_Request $request ): WP_REST_Response {
		$controller = App::get( Controller::class );

		if ( ! $controller instanceof Controller ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'data'    => [],
					'message' => __( 'Controller not found.', 'learndash' ),
				],
				500
			);
		}

		// Get the trim parameter from the request.
		$trim               = $request->get_param( 'trim' );
		$default_trim_value = true;

		if (
			$trim !== null
			&& ! is_scalar( $trim )
		) {
			$trim = $default_trim_value;
		}

		// Sanitize the trim parameter.
		$trim = Sanitize::bool( $trim, $default_trim_value );

		// Pass the trim context to the controller.
		$spec = $controller->get_openapi_documentation( $trim );

		// Add tags to the specification.
		$spec['tags'] = OpenAPI::get_tags();

		// Return raw OpenAPI spec without wrapper.
		return new WP_REST_Response( $spec );
	}

	/**
	 * Returns the schema for request parameters.
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
		return [
			'type'       => 'object',
			'properties' => [
				'trim' => [
					'description' => __( 'Whether to trim documentation from the OpenAPI specification. This is used in cases where an older REST API endpoints may expose unnecessary documentation.', 'learndash' ),
					'type'        => 'boolean',
					'default'     => true,
					'required'    => false,
				],
			],
		];
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
	 *             properties: array<string,array<string,string>>,
	 *         },
	 *         message: array<string,string>,
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
					'description' => __( 'The OpenAPI specification.', 'learndash' ),
					'properties'  => [
						'openapi' => [
							'type'        => 'string',
							'description' => __( 'OpenAPI version.', 'learndash' ),
							'example'     => '3.0.0',
						],
						'info'    => [
							'type'        => 'object',
							'description' => __( 'API information.', 'learndash' ),
						],
						'paths'   => [
							'type'        => 'object',
							'description' => __( 'API paths and operations.', 'learndash' ),
						],
					],
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Success message.', 'learndash' ),
					'example'     => __( 'API documentation retrieved successfully.', 'learndash' ),
				],
			],
			'required'   => [ 'success', 'data' ],
		];
	}
}
