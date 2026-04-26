<?php
/**
 * Course Users OpenAPI Documentation.
 *
 * Provides OpenAPI specification for courses endpoints.
 * Currently based on V2 REST API: https://developers.learndash.com/rest-api/v2/v2-course-users/.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints\Courses;

use LearnDash_Settings_Section;
use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Contracts\LDLMS_V2_Endpoint;
use stdClass;
use WP_REST_Server;

/**
 * Course Users OpenAPI Documentation Endpoint.
 *
 * @since 4.25.2
 */
class Users extends LDLMS_V2_Endpoint {
	/**
	 * Returns the response schema for this endpoint.
	 *
	 * @since 4.25.2
	 *
	 * @param string $path   The path of the route. Defaults to empty string.
	 * @param string $method The HTTP method. Defaults to empty string.
	 *
	 * @return array<string,array<string,mixed>|stdClass|string>
	 */
	public function get_response_schema( string $path = '', string $method = '' ): array {
		if ( $method !== WP_REST_Server::READABLE ) {
			return [
				'type'        => 'array',
				'description' => __( 'Empty array indicating successful operation.', 'learndash' ),
				'items'       => new stdClass(),
				'example'     => new stdClass(),
			];
		}

		return [
			'type'  => 'array',
			'items' => [
				'$ref' => '#/components/schemas/LDLMS_v2_User',
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
		$courses_endpoint = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'courses_v2' );
		$users_endpoint   = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'courses-users_v2' );

		return $this->discover_routes(
			trailingslashit( $courses_endpoint ) . '(?P<id>[\d]+)/' . $users_endpoint,
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
					// translators: %s: singular course label.
					__( 'Get associated users for a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'POST'   => sprintf(
					// translators: %s: singular course label.
					__( 'Update associated users for a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'PUT'    => sprintf(
					// translators: %s: singular course label.
					__( 'Update associated users for a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'PATCH'  => sprintf(
					// translators: %s: singular course label.
					__( 'Update associated users for a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'DELETE' => sprintf(
					// translators: %s: singular course label.
					__( 'Delete associated users for a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
			],
		];

		return $summaries[ $route_type ][ $method ]
			?? sprintf(
				// translators: %s: singular course label.
				__( '%s user operation', 'learndash' ),
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
				'GET'    => sprintf(
					// translators: %s: singular course label.
					__( 'Retrieves the users for a specific %s.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'POST'   => sprintf(
					// translators: %s: singular course label.
					__( 'Adds users to a specific %s.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'PUT'    => sprintf(
					// translators: %s: singular course label.
					__( 'Adds users to a specific %s.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'PATCH'  => sprintf(
					// translators: %s: singular course label.
					__( 'Adds users to a specific %s.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'DELETE' => sprintf(
					// translators: %s: singular course label.
					__( 'Removes users from a specific %s.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
			],
		];

		return $descriptions[ $route_type ][ $method ] ?? sprintf(
			// translators: %s: singular course label.
			__( 'Performs user operations on %s.', 'learndash' ),
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
		return [ sprintf( '%s-users', learndash_get_custom_label_lower( 'course' ) ) ];
	}
}
