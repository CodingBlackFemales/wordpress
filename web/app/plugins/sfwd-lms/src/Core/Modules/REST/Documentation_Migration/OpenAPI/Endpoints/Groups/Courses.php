<?php
/**
 * Group Courses OpenAPI Documentation.
 *
 * Provides OpenAPI specification for group courses endpoints.
 * Currently based on V2 REST API: https://developers.learndash.com/rest-api/v2/v2-group-courses/.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints\Groups;

use LearnDash_Settings_Section;
use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Contracts\LDLMS_V2_Endpoint;
use stdClass;
use WP_REST_Server;

/**
 * Group Courses OpenAPI Documentation Endpoint.
 *
 * @since 5.0.0
 */
class Courses extends LDLMS_V2_Endpoint {
	/**
	 * Returns the response schema for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @param string $path   The path of the route. Defaults to empty string.
	 * @param string $method The HTTP method. Defaults to empty string.
	 *
	 * @return array<string,array<int|string,mixed>|stdClass|string>
	 */
	public function get_response_schema( string $path = '', string $method = '' ): array {
		if ( $method !== WP_REST_Server::READABLE ) {
			return [
				'type'        => 'array',
				'description' => sprintf(
					// translators: %s: singular course label.
					__( 'An array of objects for each processed %s.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'items'       => [
					'properties' => [
						'course_id' => [
							'type'        => 'integer',
							'description' => sprintf(
								// translators: %s: singular course label.
								__( 'The ID of the processed %s.', 'learndash' ),
								learndash_get_custom_label_lower( 'course' )
							),
							'example'     => 123,
						],
						'status'    => [
							'type'        => 'string',
							'description' => __( 'The status of the operation.', 'learndash' ),
							'enum'        => [ 'success', 'failed' ],
							'example'     => 'success',
						],
						'code'      => [
							'type'        => 'string',
							'description' => __( 'The response code indicating the result.', 'learndash' ),
							'enum'        => $method === WP_REST_Server::DELETABLE ? [
								'rest_post_invalid_id',
								'learndash_rest_invalid_id',
								'learndash_rest_unenroll_failed',
								'learndash_rest_unenroll_success',
							] : [
								'rest_post_invalid_id',
								'learndash_rest_invalid_id',
								'learndash_rest_enroll_failed',
								'learndash_rest_enroll_success',
							],
							'example'     => $method === WP_REST_Server::DELETABLE
								? 'learndash_rest_unenroll_success'
								: 'learndash_rest_enroll_success',
						],
						'message'   => [
							'type'        => 'string',
							'description' => __( 'The message indicating the result.', 'learndash' ),
							'example'     => $method === WP_REST_Server::DELETABLE
								? sprintf(
									// translators: %1$s: singular course label, %2$s: singular group label.
									__( '%1$s unenrolled from %2$s success.', 'learndash' ),
									learndash_get_custom_label( 'course' ),
									learndash_get_custom_label_lower( 'group' )
								)
								: sprintf(
									// translators: %1$s: singular course label, %2$s: singular group label.
									__( '%1$s enrolled in %2$s success.', 'learndash' ),
									learndash_get_custom_label( 'course' ),
									learndash_get_custom_label_lower( 'group' )
								),
						],
					],
				],
				'example'     => [
					[
						'course_id' => 123,
						'status'    => 'success',
						'code'      => $method === WP_REST_Server::DELETABLE
							? 'learndash_rest_unenroll_success'
							: 'learndash_rest_enroll_success',
						'message'   => $method === WP_REST_Server::DELETABLE
							? sprintf(
								// translators: %1$s: singular course label, %2$s: singular group label.
								__( '%1$s unenrolled from %2$s success.', 'learndash' ),
								learndash_get_custom_label( 'course' ),
								learndash_get_custom_label_lower( 'group' )
							)
							: sprintf(
								// translators: %1$s: singular course label, %2$s: singular group label.
								__( '%1$s enrolled in %2$s success.', 'learndash' ),
								learndash_get_custom_label( 'course' ),
								learndash_get_custom_label_lower( 'group' )
							),
					],
				],
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
	 * @since 5.0.0
	 *
	 * @return array<string,array<string,string|callable>>
	 */
	protected function get_routes(): array {
		$groups_endpoint  = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'groups_v2' );
		$courses_endpoint = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'groups-courses_v2' );

		return $this->discover_routes(
			trailingslashit( $groups_endpoint ) . '(?P<id>[\d]+)/' . $courses_endpoint,
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
				'GET'    => sprintf(
					// translators: %1$s: plural courses label, %2$s: singular group label.
					__( 'Get associated %1$s for a %2$s', 'learndash' ),
					learndash_get_custom_label_lower( 'courses' ),
					learndash_get_custom_label_lower( 'group' )
				),
				'POST'   => sprintf(
					// translators: %1$s: plural courses label, %2$s: singular group label.
					__( 'Update associated %1$s for a %2$s', 'learndash' ),
					learndash_get_custom_label_lower( 'courses' ),
					learndash_get_custom_label_lower( 'group' )
				),
				'PUT'    => sprintf(
					// translators: %1$s: plural courses label, %2$s: singular group label.
					__( 'Update associated %1$s for a %2$s', 'learndash' ),
					learndash_get_custom_label_lower( 'courses' ),
					learndash_get_custom_label_lower( 'group' )
				),
				'PATCH'  => sprintf(
					// translators: %1$s: plural courses label, %2$s: singular group label.
					__( 'Update associated %1$s for a %2$s', 'learndash' ),
					learndash_get_custom_label_lower( 'courses' ),
					learndash_get_custom_label_lower( 'group' )
				),
				'DELETE' => sprintf(
					// translators: %1$s: plural courses label, %2$s: singular group label.
					__( 'Delete associated %1$s for a %2$s', 'learndash' ),
					learndash_get_custom_label_lower( 'courses' ),
					learndash_get_custom_label_lower( 'group' )
				),
			],
		];

		return $summaries[ $route_type ][ $method ]
			?? sprintf(
				// translators: %1$s: singular group label, %2$s: plural courses label.
				__( '%1$s %2$s operation', 'learndash' ),
				learndash_get_custom_label( 'group' ),
				learndash_get_custom_label( 'courses' ),
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
				'GET'    => sprintf(
					// translators: %1$s: plural courses label, %2$s: singular group label.
					__( 'Retrieves the %1$s for a specific %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'courses' ),
					learndash_get_custom_label_lower( 'group' )
				),
				'POST'   => sprintf(
					// translators: %1$s: plural courses label, %2$s: singular group label.
					__( 'Adds %1$s to a specific %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'courses' ),
					learndash_get_custom_label_lower( 'group' )
				),
				'PUT'    => sprintf(
					// translators: %1$s: plural courses label, %2$s: singular group label.
					__( 'Adds %1$s to a specific %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'courses' ),
					learndash_get_custom_label_lower( 'group' )
				),
				'PATCH'  => sprintf(
					// translators: %1$s: plural courses label, %2$s: singular group label.
					__( 'Adds %1$s to a specific %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'courses' ),
					learndash_get_custom_label_lower( 'group' )
				),
				'DELETE' => sprintf(
					// translators: %1$s: plural courses label, %2$s: singular group label.
					__( 'Removes users from a specific %s.', 'learndash' ),
					learndash_get_custom_label_lower( 'courses' ),
					learndash_get_custom_label_lower( 'group' )
				),
			],
		];

		return $descriptions[ $route_type ][ $method ] ?? sprintf(
			// translators: %1$s: singular group label, %2$s: plural courses label.
			__( 'Performs operations on %1$s %2$s.', 'learndash' ),
			learndash_get_custom_label_lower( 'group' ),
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
		return [
			sprintf(
				'%s-%s',
				learndash_get_custom_label_lower( 'group' ),
				learndash_get_custom_label_lower( 'courses' )
			),
		];
	}
}
