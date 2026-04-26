<?php
/**
 * Progress Status Nested endpoint for LearnDash REST API.
 *
 * Provides access to a specific progress status for a given post type.
 * Returns the display name for the requested status slug.
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
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash\Core\Utilities\Cast;

/**
 * Progress Status Nested endpoint for LearnDash REST API.
 *
 * Handles requests to retrieve a specific progress status for a given post type.
 * This endpoint accepts both post type and status slug parameters and returns
 * the display name for the requested status.
 *
 * @since 5.0.0
 */
class Nested extends Endpoint {
	/**
	 * The base route for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	protected string $base_route = '/progress-status/(?P<post_type>[\w-]+)/';

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
	 * Retrieves a specific progress status for a given post type by looking up
	 * the status slug in the post type's available statuses.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The REST request object.
	 *
	 * @return WP_REST_Response Response containing the specific status display name.
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

		// Find this specific status by the 'slug' parameter.
		$slug = Cast::to_string( $request->get_param( 'slug' ) );

		$status_value = str_replace( '-', '_', $slug );

		$status_label = Arr::get( $statuses, $status_value );

		// Check if status exists and if the slug format is correct (hyphens, not underscores).
		if (
			! $status_label
			|| $slug !== str_replace( '_', '-', $status_value ) // It's needed to check if the slug format is correct (hyphens, not underscores).
		) {
			return $this->error_response(
				__( 'The specified status slug was not found for this post type. Please check that the status slug is valid.', 'learndash' ),
				'rest_progress_status_item_retrieval_failed',
				404
			);
		}

		return $this->success_response(
			[
				'label' => $status_label,
				'slug'  => $slug,
				'value' => $status_value,
			],
			__( 'Progress status retrieved successfully.', 'learndash' ),
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
				'slug'      => [
					'in'          => 'path',
					'name'        => 'slug',
					'schema'      => [
						'type' => 'string',
					],
					'required'    => true,
					'description' => __( 'The status slug to retrieve the status display name for.', 'learndash' ),
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
					'type'        => 'object',
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
					'description' => __( 'The specific progress status display name.', 'learndash' ),
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Response message.', 'learndash' ),
					'example'     => __( 'Progress status retrieved successfully.', 'learndash' ),
				],
			],
			'required'   => [ 'success', 'data', 'message' ],
		];
	}

	/**
	 * Returns the routes configuration for this endpoint.
	 *
	 * Defines the REST API route for retrieving a specific progress status.
	 * This endpoint accepts both post type and status slug parameters and is publicly accessible.
	 * Note: OpenAPI documentation may show {type} and {slug} instead of WordPress regex patterns.
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
			'(?P<slug>[\w-]+)' => [
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'args'                => $this->get_endpoint_args(),
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Retrieve a specific progress status', 'learndash' ),
				'description'         => __( 'Retrieves a specific progress status.', 'learndash' ),
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
