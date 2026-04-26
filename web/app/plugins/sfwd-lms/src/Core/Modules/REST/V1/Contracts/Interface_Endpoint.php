<?php
/**
 * Interface that defines the contract for all REST endpoints.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\V1\Contracts;

use WP_REST_Request;

/**
 * Interface that defines the contract for all REST endpoints.
 *
 * @since 4.25.0
 */
interface Interface_Endpoint {
	/**
	 * Registers the routes for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function register_routes(): void;

	/**
	 * Returns the base route for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_base_route(): string;

	/**
	 * Returns the permission required to access this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_permission_required(): string;

	/**
	 * Checks if the current user has permission to access this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The REST request object.
	 *
	 * @return bool|\WP_Error
	 */
	public function check_permission( $request );

	/**
	 * Validates the request parameters.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The REST request object.
	 *
	 * @return bool|\WP_Error
	 */
	public function validate_request( $request );

	/**
	 * Sanitizes the request parameters.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The REST request object.
	 *
	 * @return mixed[]
	 */
	public function sanitize_request( $request ): array;

	/**
	 * Returns the OpenAPI schema for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @return mixed[]
	 */
	public function get_openapi_schema(): array;

	/**
	 * Returns the schema for request parameters.
	 *
	 * @since 4.25.0
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return mixed[]
	 */
	public function get_request_schema( string $path, string $method ): array;

	/**
	 * Returns the schema for response data.
	 *
	 * @since 4.25.0
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return mixed[]
	 */
	public function get_response_schema( string $path, string $method ): array;
}
