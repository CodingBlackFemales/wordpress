<?php
/**
 * Courses OpenAPI Documentation.
 *
 * Provides OpenAPI specification for courses endpoints.
 * Currently based on V2 REST API: https://developers.learndash.com/rest-api/v2/v2-courses/.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints\Courses;

use LearnDash_Settings_Section;
use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Contracts\LDLMS_V2_Endpoint;

/**
 * Courses OpenAPI Documentation Endpoint.
 *
 * @since 4.25.2
 */
class Course extends LDLMS_V2_Endpoint {
	/**
	 * Returns the response schema for this endpoint.
	 *
	 * @since 4.25.2
	 *
	 * @param string $path   The path of the route. Defaults to empty string.
	 * @param string $method The HTTP method. Defaults to empty string.
	 *
	 * @return array<string,array<string,mixed>|string>
	 */
	public function get_response_schema( string $path = '', string $method = '' ): array {
		$route_path = '/' . ltrim( $path, '/' );

		if ( $this->determine_route_type( $route_path ) === 'singular' ) {
			return [
				'$ref' => '#/components/schemas/LDLMS_v2_Course',
			];
		}

		return [
			'type'  => 'array',
			'items' => [
				'$ref' => '#/components/schemas/LDLMS_v2_Course',
			],
		];
	}

	/**
	 * Returns the routes configuration for this endpoint.
	 *
	 * @since 4.25.2
	 *
	 * @return array<string,array<string,string|callable>>
	 */
	protected function get_routes(): array {
		$endpoint = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'courses_v2' );

		return $this->discover_routes( $endpoint, [ 'collection', 'singular' ] );
	}

	/**
	 * Returns the summary for a specific HTTP method.
	 *
	 * @since 4.25.2
	 *
	 * @param string $method The HTTP method.
	 * @param string $route_type The route type ('collection', 'singular', or 'nested').
	 *
	 * @return string
	 */
	protected function get_method_summary( string $method, string $route_type = 'collection' ): string {
		$summaries = [
			'collection' => [
				'GET'  => sprintf(
					// translators: 1: plural courses label.
					__( 'List %1$s: retrieve all %1$s with filtering and pagination', 'learndash' ),
					learndash_get_custom_label_lower( 'courses' )
				),
				'POST' => sprintf(
					// translators: 1: singular course label, 2: plural lessons label.
					__( 'Create %1$s container: requires %2$s to be functional', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
					learndash_get_custom_label_lower( 'lessons' ),
				),
			],
			'singular'   => [
				'GET'    => sprintf(
					// translators: %s: singular course label.
					__( 'Get a specific %s', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'POST'   => sprintf(
					// translators: %s: singular course label.
					__( 'Update a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'PUT'    => sprintf(
					// translators: %s: singular course label.
					__( 'Update a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'PATCH'  => sprintf(
					// translators: %s: singular course label.
					__( 'Update a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'DELETE' => sprintf(
					// translators: %s: singular course label.
					__( 'Delete a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
			],
		];

		return $summaries[ $route_type ][ $method ]
			?? sprintf(
				// translators: %s: plural courses label.
				__( '%s operation', 'learndash' ),
				learndash_get_custom_label( 'courses' )
			);
	}

	/**
	 * Returns the description for a specific HTTP method.
	 *
	 * @since 4.25.2
	 *
	 * @param string $method The HTTP method.
	 * @param string $route_type The route type ('collection', 'singular', or 'nested').
	 *
	 * @return string
	 */
	protected function get_method_description( string $method, string $route_type = 'collection' ): string {
		$descriptions = [
			'collection' => [
				'GET'  => sprintf(
					// translators: 1: plural courses label, 2: singular course label.
					__( 'Returns a paginated collection of %1$s. Supports filtering by author, category, tag, status, and search. Use per_page (max 100) and page for pagination. Does not include %2$s steps/content structure.', 'learndash' ),
					learndash_get_custom_label_lower( 'courses' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'POST' => sprintf(
					// translators: 1: singular course label, 2: plural lessons label, 3: plural topics label, 4: plural quizzes label.
					__( 'Creates an empty %1$s with metadata (title, pricing, settings). Warning: A %1$s without %2$s/%3$s assigned via /sfwd-courses/{id}/steps is non-functional and cannot be taken by students. Always follow with: 1) Create %2$s/%3$s/%4$s, 2) Assign them using the /steps endpoint.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
					learndash_get_custom_label_lower( 'lessons' ),
					learndash_get_custom_label_lower( 'topics' ),
					learndash_get_custom_label_lower( 'quizzes' )
				),
			],
			'singular'   => [
				'GET'    => sprintf(
					// translators: %s: singular course label.
					__( 'Returns a specific %1$s by ID. Returns the complete %2$s data including all fields and metadata.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'POST'   => sprintf(
					// translators: %s: singular course label.
					__( 'Partially updates an existing %1$s. Only the provided fields will be updated, leaving other fields unchanged.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'PUT'    => sprintf(
					// translators: %s: singular course label.
					__( 'Updates an existing %1$s. Requires the complete %2$s data in the request body. All fields will be replaced with the provided values.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'PATCH'  => sprintf(
					// translators: %s: singular course label.
					__( 'Partially updates an existing %1$s. Only the provided fields will be updated, leaving other fields unchanged.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'DELETE' => sprintf(
					// translators: %s: singular course label.
					__( 'Deletes a %s permanently. This action cannot be undone.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
			],
		];

		return $descriptions[ $route_type ][ $method ] ?? sprintf(
			// translators: %s: plural courses label.
			__( 'Performs operations on %s.', 'learndash' ),
			learndash_get_custom_label_lower( 'courses' )
		);
	}

	/**
	 * Returns the tags for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @return string[]
	 */
	protected function get_tags(): array {
		return [ learndash_get_custom_label_lower( 'courses' ) ];
	}
}
