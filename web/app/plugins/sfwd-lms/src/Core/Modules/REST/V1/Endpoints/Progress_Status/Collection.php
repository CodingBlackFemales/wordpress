<?php
/**
 * Progress Status Collection endpoint for LearnDash REST API.
 *
 * Provides access to progress status data for all LearnDash post types.
 * Returns a collection of post type slugs mapped to their available statuses.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\V1\Endpoints\Progress_Status;

use LearnDash\Core\Mappers\Progress\Post_Type_Status;
use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use LDLMS_Post_Types;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Progress Status Collection endpoint for LearnDash REST API.
 *
 * Handles requests to retrieve progress status data for all LearnDash post types.
 * This endpoint returns a comprehensive collection of all available post types
 * and their corresponding progress status options.
 *
 * @since 5.0.0
 */
class Collection extends Endpoint {
	/**
	 * The base route for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	protected string $base_route = '';

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
	 * Handles the get items request.
	 *
	 * Retrieves progress status data for all LearnDash post types by looping through
	 * the available post types and fetching their corresponding status options.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The REST request object.
	 *
	 * @return WP_REST_Response Response containing all post types and their statuses.
	 */
	public function get_items( $request ): WP_REST_Response {
		$post_types = LDLMS_Post_Types::get_all_post_types_set();
		$result     = [];

		// Loop through all post types and fetch their statuses.
		foreach ( $post_types as $post_type ) {
			$statuses = Post_Type_Status::get_statuses( $post_type );

			if ( empty( $statuses ) ) {
				continue;
			}

			foreach ( $statuses as $status_value => $status_label ) {
				if ( ! isset( $result[ $post_type ] ) ) {
					$result[ $post_type ] = [];
				}

				$result[ $post_type ][ $status_value ] = [
					'label' => $status_label,
					'slug'  => str_replace( '_', '-', $status_value ),
					'value' => $status_value,
				];
			}
		}

		return $this->success_response(
			$result,
			__( 'Progress status types retrieved successfully.', 'learndash' ),
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
		return $this->convert_endpoint_args_to_schema();
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
					'description'          => __( 'Progress status data for all post types. Each key is a post type.', 'learndash' ),
					'additionalProperties' => [
						'type'                 => 'object',
						'description'          => __( 'Status key-value pairs for a post type. The key is the status value.', 'learndash' ),
						'additionalProperties' => [
							'type'        => 'object',
							'description' => __( 'Status object with label, slug, and value.', 'learndash' ),
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
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Response message.', 'learndash' ),
					'example'     => __( 'Progress status types retrieved successfully.', 'learndash' ),
				],
			],
			'required'   => [ 'success', 'data', 'message' ],
		];
	}

	/**
	 * Returns the routes configuration for this endpoint.
	 *
	 * Defines the REST API route for retrieving all progress status types.
	 * This endpoint is publicly accessible and returns comprehensive status data.
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
			'/progress-status' => [
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Retrieve progress status types', 'learndash' ),
				'description'         => __( 'Retrieves the different progress status types.', 'learndash' ),
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
