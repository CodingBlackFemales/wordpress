<?php
/**
 * Course Steps OpenAPI Documentation.
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
use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Schemas\Course_Steps;
use WP_REST_Server;

/**
 * Course Steps OpenAPI Documentation Endpoint.
 *
 * @since 4.25.2
 */
class Steps extends LDLMS_V2_Endpoint {
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
		if ( $method === WP_REST_Server::READABLE ) {
			return Course_Steps::get_schema();
		}

		return [
			'type'        => 'object',
			'description' => sprintf(
				// translators: %s: singular course label.
				__( 'Updated hierarchical view of %s steps.', 'learndash' ),
				learndash_get_custom_label_lower( 'course' )
			),
			'properties'  => Course_Steps::get_hierarchical_properties(),
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
		return $this->discover_routes(
			$this->get_base_endpoint(),
			[ 'collection' ]
		);
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
				'GET'    => sprintf(
					// translators: 1: singular course label, 2: singular lesson label, 3: singular topic label, 4: singular quiz label.
					__( 'Get %1$s structure: returns %2$s/%3$s/%4$s hierarchy and order', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
					learndash_get_custom_label_lower( 'lesson' ),
					learndash_get_custom_label_lower( 'topic' ),
					learndash_get_custom_label_lower( 'quiz' )
				),
				'POST'   => sprintf(
					// translators: 1: singular course label, 2: plural lessons label, 3: plural topics label, 4: plural quizzes label.
					__( 'Set the %1$s structure: assign and organize %2$s, %3$s, and %4$s', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
					learndash_get_custom_label_lower( 'lessons' ),
					learndash_get_custom_label_lower( 'topics' ),
					learndash_get_custom_label_lower( 'quizzes' ),
				),
				'PUT'    => sprintf(
					// translators: %s: singular course label.
					__( 'Update associated steps for a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'PATCH'  => sprintf(
					// translators: %s: singular course label.
					__( 'Update associated steps for a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'DELETE' => sprintf(
					// translators: %s: singular course label.
					__( 'Delete associated steps for a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
			],
		];

		return $summaries[ $route_type ][ $method ]
			?? sprintf(
				// translators: %s: singular course label.
				__( '%s step operation', 'learndash' ),
				learndash_get_custom_label( 'course' )
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
				'GET'   => sprintf(
					// translators: 1: singular course label, 2: plural lessons label, 3: plural topics label, 4: plural quizzes label.
					__( 'Returns the current %1$s structure showing the hierarchy and ordering of %2$s, %3$s, and %4$s. Parameter \'type\' controls response format (hierarchy, flat lists, sequential order, etc).', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
					learndash_get_custom_label_lower( 'lessons' ),
					learndash_get_custom_label_lower( 'topics' ),
					learndash_get_custom_label_lower( 'quizzes' ),
				),
				'POST'  => sprintf(
					// translators: 1: singular course label, 2: plural lessons label, 3: plural topics label, 4: plural quizzes label.
					__( 'Establishes the complete %1$s hierarchy by assigning %2$s, %3$s, and %4$s with their parent-child relationships and ordering. Must be called after creating %1$s content to make the %1$s functional.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
					learndash_get_custom_label_lower( 'lessons' ),
					learndash_get_custom_label_lower( 'topics' ),
					learndash_get_custom_label_lower( 'quizzes' ),
				),
				'PUT'   => sprintf(
					// translators: %s: singular course label.
					__( 'Updates the %1$s step association for an existing %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
					learndash_get_custom_label_lower( 'course' ),
				),
				'PATCH' => sprintf(
					// translators: 1: singular course label, 2: singular course label, 3: plural lessons label, 4: plural topics label, 5: plural quizzes label.
					__( 'Partially updates the %1$s step association for an existing %2$s. Only the provided fields will be updated, leaving other fields unchanged. Use this to assign %3$s, %4$s and %5$s to a specific %1$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
					learndash_get_custom_label_lower( 'course' ),
					learndash_get_custom_label_lower( 'lessons' ),
					learndash_get_custom_label_lower( 'topics' ),
					learndash_get_custom_label_lower( 'quizzes' ),
					learndash_get_custom_label_lower( 'course' )
				),
			],
		];

		return $descriptions[ $route_type ][ $method ] ?? sprintf(
			// translators: %s: singular course label.
			__( 'Performs %1$s step operations on %2$s.', 'learndash' ),
			learndash_get_custom_label_lower( 'course' ),
			learndash_get_custom_label_lower( 'course' )
		);
	}

	/**
	 * Returns the base endpoint for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	protected function get_base_endpoint(): string {
		$courses_endpoint = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'courses_v2' );
		$steps_endpoint   = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'courses-steps_v2' );

		return trailingslashit( $courses_endpoint ) . '(?P<id>[\d]+)/' . $steps_endpoint;
	}

	/**
	 * Returns the tags for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @return string[]
	 */
	protected function get_tags(): array {
		return [ sprintf( '%s-steps', learndash_get_custom_label_lower( 'course' ) ) ];
	}
}
