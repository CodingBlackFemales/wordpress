<?php
/**
 * User Course Update OpenAPI Documentation.
 *
 * Provides OpenAPI specification for individual user course update endpoint.
 * Currently based on V2 REST API: https://developers.learndash.com/rest-api/v2/v2-user-courses/.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints\Users;

use LearnDash_Settings_Section;
use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Contracts\LDLMS_V2_Endpoint;

/**
 * User Course Update OpenAPI Documentation Endpoint.
 *
 * @since 5.0.0
 */
class Course_Update extends LDLMS_V2_Endpoint {
	/**
	 * Returns the response schema for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @param string $path   The path of the route. Defaults to empty string.
	 * @param string $method The HTTP method. Defaults to empty string.
	 *
	 * @return array<string,array<string|int,mixed>|string>
	 */
	public function get_response_schema( string $path = '', string $method = '' ): array {
		return [
			'type'       => 'object',
			'properties' => [
				'course_id'       => [
					'type'        => 'integer',
					'description' => sprintf(
						// translators: %s: singular course label.
						__( 'The ID of the %s being updated.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'example'     => 123,
				],
				'user_id'         => [
					'type'        => 'integer',
					'description' => __( 'The ID of the user being updated.', 'learndash' ),
					'example'     => 456,
				],
				'status'          => [
					'type'        => 'string',
					'description' => __( 'The status of the operation.', 'learndash' ),
					'enum'        => [ 'success', 'failed' ],
					'example'     => 'success',
				],
				'code'            => [
					'type'        => 'string',
					'description' => __( 'The response code indicating the result.', 'learndash' ),
					'enum'        => [
						'learndash_rest_course_not_found',
						'learndash_rest_empty_enrollment_date',
						'learndash_rest_enrollment_date_same',
						'learndash_rest_enrollment_date_update_failed',
						'learndash_rest_enrollment_date_updated',
						'learndash_rest_invalid_course_id',
						'learndash_rest_invalid_date',
						'learndash_rest_invalid_user_id',
						'learndash_rest_user_not_enrolled',

					],
					'example'     => 'learndash_rest_enrollment_date_updated',
				],
				'message'         => [
					'type'        => 'string',
					'description' => __( 'A human-readable message describing the result.', 'learndash' ),
					'example'     => sprintf(
						// translators: %s: singular course label.
						__( 'User enrollment date for %s updated successfully.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
				],
				'enrolled_at'     => [
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => sprintf(
						// translators: %s: singular course label.
						__( 'The enrollment date for the %s in the site timezone.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'example'     => '2024-01-15T10:30:00',
				],
				'enrolled_at_gmt' => [
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => sprintf(
						// translators: %s: singular course label.
						__( 'The enrollment date for the %s in GMT.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'example'     => '2024-01-15T15:30:00Z',
				],
			],
			'required'   => [ 'course_id', 'user_id', 'status', 'code', 'message' ],
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
		$users_endpoint   = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'users_v2' );
		$courses_endpoint = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'users-courses_v2' );

		return $this->discover_routes(
			trailingslashit( $users_endpoint ) . '(?P<id>[\d]+)/' . $courses_endpoint . '/(?P<course>[\d]+)',
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
				'POST'  => sprintf(
					// translators: %1$s: singular course label.
					__( 'Update the %1$s enrollment date for a user', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
				),
				'PUT'   => sprintf(
					// translators: %1$s: singular course label.
					__( 'Update the %1$s enrollment date for a user', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
				),
				'PATCH' => sprintf(
					// translators: %1$s: singular course label.
					__( 'Update the %1$s enrollment date for a user', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
				),
			],
		];

		return $summaries[ $route_type ][ $method ]
			?? sprintf(
				// translators: %1$s: singular course label.
				__( 'User %1$s update operation', 'learndash' ),
				learndash_get_custom_label_lower( 'course' )
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
				'POST'  => sprintf(
					// translators: %1$s: singular course label.
					__( 'Updates the enrollment date for a specific %1$s for a user.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
				),
				'PUT'   => sprintf(
					// translators: %1$s: singular course label.
					__( 'Updates the enrollment date for a specific %1$s for a user.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
				),
				'PATCH' => sprintf(
					// translators: %1$s: singular course label.
					__( 'Updates the enrollment date for a specific %1$s for a user.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' ),
				),
			],
		];

		return $descriptions[ $route_type ][ $method ] ?? sprintf(
			// translators: %1$s: singular course label.
			__( 'Performs %1$s update operations for a user.', 'learndash' ),
			learndash_get_custom_label_lower( 'course' ),
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
		return [ sprintf( 'user-%1$s-update', learndash_get_custom_label_lower( 'course' ) ) ];
	}
}
