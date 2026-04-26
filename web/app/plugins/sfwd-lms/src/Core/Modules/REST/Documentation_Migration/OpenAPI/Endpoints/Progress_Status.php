<?php
/**
 * Progress Status OpenAPI Documentation.
 *
 * Provides OpenAPI specification for progress status endpoints.
 * Currently based on V2 REST API: https://developers.learndash.com/rest-api/v2/v2-progress-status/.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints;

use LearnDash_Settings_Section;
use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Contracts\LDLMS_V2_Endpoint;

/**
 * Progress Status OpenAPI Documentation Endpoint.
 *
 * @since 5.0.0
 */
class Progress_Status extends LDLMS_V2_Endpoint {
	/**
	 * Returns the response schema for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @param string $path   The path of the route. Defaults to empty string.
	 * @param string $method The HTTP method. Defaults to empty string.
	 *
	 * @return array<string,array<string,mixed>|string>
	 */
	public function get_response_schema( string $path = '', string $method = '' ): array {
		$route_path = '/' . trim( $this->get_namespace(), '/' ) . '/' . ltrim( $path, '/' );

		$progress_status_schema = [
			'type'       => 'object',
			'properties' => [
				'name'        => [
					'type'        => 'string',
					'description' => __( 'The name of the progress status.', 'learndash' ),
					'example'     => __( 'Not Started', 'learndash' ),
				],
				'slug'        => [
					'type'        => 'string',
					'description' => __( 'The slug for the progress status that can be used to retrieve the progress status.', 'learndash' ),
					'example'     => 'not-started',
				],
				'value'       => [
					'type'        => 'string',
					'description' => __( 'The value for the progress status. This is the actual value that will be referenced in other endpoints.', 'learndash' ),
					'example'     => 'not_started',
				],
			],
		];

		if ( $this->determine_route_type( $route_path ) === 'singular' ) {
			// Singular endpoint - returns a single progress status object.
			return $progress_status_schema;
		}

		return [
			'type'                 => 'object',
			'additionalProperties' => $progress_status_schema,
			'example'              => [
				'not-started' => [
					'name'  => __( 'Not Started', 'learndash' ),
					'slug'  => 'not-started',
					'value' => 'not_started',
				],
			],
		];
	}

	/**
	 * Returns the security schemes for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array<int,array<string,string[]>>
	 */
	public function get_security_schemes( string $path, string $method ): array {
		// No security schemes are required for this endpoint.
		return [];
	}

	/**
	 * Returns the routes configuration for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @return array<string,array<string,string|callable>>
	 */
	protected function get_routes(): array {
		$endpoint = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'progress-status_v2' );

		return $this->discover_routes( $endpoint, [ 'collection', 'singular' ] );
	}

	/**
	 * Returns the summary for a specific HTTP method.
	 *
	 * @since 5.0.0
	 *
	 * @param string $method The HTTP method.
	 * @param string $route_type The route type ('collection', 'singular', or 'nested').
	 *
	 * @return string
	 */
	protected function get_method_summary( string $method, string $route_type = 'collection' ): string {
		$summaries = [
			'collection' => [
				'GET' => __( 'Retrieve progress statuses', 'learndash' ),
			],
			'singular'   => [
				'GET' => __( 'Retrieve progress status', 'learndash' ),
			],
		];

		return $summaries[ $route_type ][ $method ]
			?? __( 'Progress statuses operation', 'learndash' );
	}

	/**
	 * Returns the description for a specific HTTP method.
	 *
	 * @since 5.0.0
	 *
	 * @param string $method The HTTP method.
	 * @param string $route_type The route type ('collection', 'singular', or 'nested').
	 *
	 * @return string
	 */
	protected function get_method_description( string $method, string $route_type = 'collection' ): string {
		$descriptions = [
			'collection' => [
				'GET' => __( 'Retrieves the progress statuses.', 'learndash' ),
			],
			'singular'   => [
				'GET' => __( 'Retrieves the progress status.', 'learndash' ),
			],
		];

		return $descriptions[ $route_type ][ $method ] ?? __( 'Progress status operation.', 'learndash' );
	}

	/**
	 * Returns the tags for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @return string[]
	 */
	protected function get_tags(): array {
		return [ 'progress-statuses' ];
	}
}
