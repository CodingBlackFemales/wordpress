<?php
/**
 * User Course Progress Exam OpenAPI Documentation.
 *
 * Provides OpenAPI specification for user course progress exam endpoints.
 * Currently based on V2 REST API: https://developers.learndash.com/rest-api/v2/v2-user-course-progress/.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints\Users;

use LDLMS_Post_Types;
use LearnDash_Settings_Section;
use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Contracts\LDLMS_V2_Endpoint;

/**
 * User Course Progress Exam OpenAPI Documentation Endpoint.
 *
 * @since 5.0.0
 */
class Course_Progress_Exam extends LDLMS_V2_Endpoint {
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
		return [
			'$ref' => '#/components/schemas/LDLMS_v2_User_Course_Progress_Exam',
		];
	}

	/**
	 * Returns the routes configuration for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @return array<string,array<string,string|callable>>
	 */
	protected function get_routes(): array {
		$users_endpoint           = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'users_v2' );
		$course_progress_endpoint = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'users-course-progress_v2' );

		return $this->discover_routes(
			trailingslashit( $users_endpoint ) . '(?P<id>[\d]+)/' . $course_progress_endpoint . '/(?P<course>[\d]+)/exam',
			[ 'collection' ]
		);
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
				'GET' => sprintf(
					// translators: %1$s: singular exam label.
					__( 'Get %1$s progress for a user in a %2$s', 'learndash' ),
					learndash_get_custom_label_lower( LDLMS_Post_Types::EXAM ),
					learndash_get_custom_label_lower( LDLMS_Post_Types::COURSE )
				),
			],
		];

		return $summaries[ $route_type ][ $method ]
			?? sprintf(
				// translators: %1$s: singular exam label.
				__( 'User %1$s progress operation in a %2$s', 'learndash' ),
				learndash_get_custom_label_lower( LDLMS_Post_Types::EXAM ),
				learndash_get_custom_label_lower( LDLMS_Post_Types::COURSE )
			);
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
				'GET' => sprintf(
					// translators: %1$s: singular exam label.
					__( 'Retrieves the %1$s progress for a specific user in a specific course.', 'learndash' ),
					learndash_get_custom_label_lower( LDLMS_Post_Types::EXAM ),
				),
			],
		];

		return $descriptions[ $route_type ][ $method ] ?? sprintf(
			// translators: %1$s: singular exam label.
			__( 'Performs %1$s progress operations on user in a specific course.', 'learndash' ),
			learndash_get_custom_label_lower( LDLMS_Post_Types::EXAM ),
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
		return [
			sprintf(
				'user-%1$s-progress-%2$s',
				learndash_get_custom_label_lower( 'course' ),
				learndash_get_custom_label_lower( 'exam' )
			),
		];
	}
}
