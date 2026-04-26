<?php
/**
 * Course Prerequisites OpenAPI Documentation.
 *
 * Provides OpenAPI specification for courses prerequisites endpoints.
 * Currently based on V2 REST API: https://developers.learndash.com/rest-api/v2/v2-course-prerequisites/.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints\Courses;

use LearnDash_Settings_Section;
use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Contracts\LDLMS_V2_Endpoint;

/**
 * Course Prerequisites OpenAPI Documentation Endpoint.
 *
 * @since 4.25.2
 */
class Prerequisites extends LDLMS_V2_Endpoint {
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
		$courses_endpoint       = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'courses_v2' );
		$prerequisites_endpoint = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'courses-prerequisites_v2' );

		return $this->discover_routes(
			trailingslashit( $courses_endpoint ) . '(?P<id>[\d]+)/' . $prerequisites_endpoint,
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
				'GET' => sprintf(
					// translators: %s: singular course label.
					__( 'Get associated prerequisites for a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
			],
		];

		return $summaries[ $route_type ][ $method ]
			?? sprintf(
				// translators: %s: singular course label.
				__( '%s prerequisite operation', 'learndash' ),
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
				'GET' => sprintf(
					// translators: %s: singular course label.
					__( 'Retrieves the %1$s prerequisites for a specific %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
					learndash_get_custom_label_lower( 'course' )
				),
			],
		];

		return $descriptions[ $route_type ][ $method ] ?? sprintf(
			// translators: %s: singular course label.
			__( 'Performs %1$s prerequisite operations on the %2$s.', 'learndash' ),
			learndash_get_custom_label_lower( 'course' ),
			learndash_get_custom_label_lower( 'course' )
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
		return [ sprintf( '%s-prerequisites', learndash_get_custom_label_lower( 'course' ) ) ];
	}
}
