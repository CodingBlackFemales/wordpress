<?php
/**
 * Progress Status Singular endpoint for LearnDash REST API.
 *
 * Provides access to progress status data for a specific LearnDash post type.
 * Returns the available statuses for the requested post type.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\V1\Endpoints\Progress_Status;

use LearnDash\Core\Mappers\Progress\Post_Type_Status;
use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use LearnDash\Core\Utilities\Cast;

/**
 * Progress Status Singular endpoint for LearnDash REST API.
 *
 * Handles requests to retrieve progress status data for a specific LearnDash post type.
 * This endpoint accepts a post type parameter and returns the corresponding status options.
 *
 * @since 5.0.0
 */
class Singular extends Endpoint {
	/**
	 * The base route for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	protected string $base_route = '/progress-status/';

	/**
	 * The permission required to access this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	protected string $permission_required = '';

	/**
	 * Whether the endpoint is experimental.
	 *
	 * @since 5.0.0
	 *
	 * @var bool
	 */
	protected bool $experimental = true;

	/**
	 * Handles the get item request.
	 *
	 * Retrieves progress status data for a specific LearnDash post type.
	 * The post type is specified via the 'post_type' parameter in the request.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The REST request object.
	 *
	 * @return WP_REST_Response Response containing statuses for the specified post type.
	 */
	public function get_item( $request ): WP_REST_Response {
		$post_type = Cast::to_string( $request->get_param( 'post_type' ) );
		$statuses  = Post_Type_Status::get_statuses( $post_type );

		if ( empty( $statuses ) ) {
			return $this->error_response(
				__( 'Unable to retrieve progress statuses for the specified post type. Please check that the post type is valid.', 'learndash' ),
				'rest_progress_status_item_retrieval_failed',
				404
			);
		}

		$result = [];

		foreach ( $statuses as $status_value => $status_label ) {
			$result[ $status_value ] = [
				'label' => $status_label,
				'slug'  => str_replace( '_', '-', $status_value ),
				'value' => $status_value,
			];
		}

		return $this->success_response(
			$result,
			__( 'Progress statuses retrieved successfully.', 'learndash' ),
			200
		);
	}

	/**
	 * Returns the request schema for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array<string,mixed>
	 */
	public function get_request_schema( string $path, string $method ): array {
		return [
			'properties' => [
				'post_type' => [
					'in'          => 'path',
					'name'        => 'post_type',
					'schema'      => [
						'type' => 'string',
					],
					'required'    => true,
					'description' => __( 'The post type slug to retrieve statuses for.', 'learndash' ),
				],
			],
		];
	}

	/**
	 * Returns the schema for response data.
	 *
	 * @since 5.0.0
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array<string,mixed>
	 */
	public function get_response_schema( string $path, string $method ): array {
		return [
			'type'       => 'object',
			'properties' => [
				'success' => [
					'type'        => 'boolean',
					'description' => __( 'Whether the request was successful.', 'learndash' ),
				],
				'data'    => [
					'type'                 => 'object',
					'description'          => __( 'Progress statuses for the specified post type. The key is the status value.', 'learndash' ),
					'additionalProperties' => [
						'type'        => 'object',
						'description' => __( 'Status key-value pairs for a progress status slug. The key is the status value.', 'learndash' ),
						'properties'  => [
							'label' => [
								'type'        => 'string',
								'description' => __( 'Status display name.', 'learndash' ),
							],
							'slug'  => [
								'type'        => 'string',
								'description' => __( 'Status slug.', 'learndash' ),
							],
							'value' => [
								'type'        => 'string',
								'description' => __( 'Status value. This is the actual value of the status that will be referenced in other endpoints.', 'learndash' ),
							],
						],
						'required'    => [ 'label', 'slug', 'value' ],
					],
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Response message.', 'learndash' ),
					'example'     => __( 'Progress statuses retrieved successfully.', 'learndash' ),
				],
			],
			'required'   => [ 'success', 'data', 'message' ],
		];
	}

	/**
	 * Returns the routes configuration for this endpoint.
	 *
	 * Defines the REST API route for retrieving progress statuses for a specific post type.
	 * This endpoint accepts a post type parameter and is publicly accessible.
	 * Note: OpenAPI documentation may show {type} instead of the WordPress regex pattern.
	 *
	 * @since 5.0.0
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
			'(?P<post_type>[\w-]+)' => [
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Retrieve progress statuses for a given type', 'learndash' ),
				'description'         => __( 'Retrieves the progress statuses for a given type.', 'learndash' ),
			],
		];
	}

	/**
	 * Returns the endpoint arguments.
	 *
	 * @since 5.0.0
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
		return [];
	}

	/**
	 * Returns the tags for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @return string[]
	 */
	protected function get_tags(): array {
		return [ 'progress' ];
	}
}
