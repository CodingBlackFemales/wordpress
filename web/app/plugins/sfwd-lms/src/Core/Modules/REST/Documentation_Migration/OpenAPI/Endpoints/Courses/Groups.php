<?php
/**
 * Course Groups OpenAPI Documentation.
 *
 * Provides OpenAPI specification for course groups endpoints.
 * Currently based on V2 REST API: https://developers.learndash.com/rest-api/v2/v2-course-groups/.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints\Courses;

use LearnDash_Settings_Section;
use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Contracts\LDLMS_V2_Endpoint;
use WP_REST_Server;

/**
 * Course Groups OpenAPI Documentation Endpoint.
 *
 * @since 4.25.2
 */
class Groups extends LDLMS_V2_Endpoint {
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
			return [
				'type'  => 'array',
				'items' => [
					'$ref' => '#/components/schemas/LDLMS_v2_Group',
				],
			];
		}

		// For POST, PUT, PATCH, DELETE operations.
		return [
			'type'  => 'array',
			'items' => [
				'type'       => 'object',
				'properties' => [
					'group_id' => [
						'type'        => 'integer',
						'description' => sprintf(
							// translators: %s: singular group label.
							__( 'The ID of the %s being processed.', 'learndash' ),
							learndash_get_custom_label_lower( 'group' )
						),
						'example'     => 123,
					],
					'status'   => [
						'type'        => 'string',
						'description' => __( 'The status of the operation.', 'learndash' ),
						'enum'        => [ 'success', 'failed' ],
						'example'     => 'success',
					],
					'code'     => [
						'type'        => 'string',
						'description' => __( 'The response code indicating the result.', 'learndash' ),
						'enum'        => $method === WP_REST_Server::DELETABLE
							? [
								'learndash_rest_invalid_id',
								'learndash_rest_unenroll_failed',
								'learndash_rest_unenroll_success',
							]
							: [
								'learndash_rest_enroll_failed',
								'learndash_rest_enroll_success',
								'learndash_rest_invalid_id',
							],
						'example'     => $method === WP_REST_Server::DELETABLE
							? 'learndash_rest_unenroll_success'
							: 'learndash_rest_enroll_success',
					],
					'message'  => [
						'type'        => 'string',
						'description' => __( 'A human-readable message describing the result.', 'learndash' ),
						'example'     => $method === WP_REST_Server::DELETABLE
							? sprintf(
								// translators: %1$s: singular course label, %2$s: singular group label.
								__( '%1$s enrolled from %2$s success.', 'learndash' ),
								learndash_get_custom_label( 'course' ),
								learndash_get_custom_label_lower( 'group' )
							)
							: sprintf(
								// translators: %1$s: singular course label, %2$s: singular group label.
								__( '%1$s already enrolled in %2$s.', 'learndash' ),
								learndash_get_custom_label( 'course' ),
								learndash_get_custom_label_lower( 'group' )
							),
					],
				],
				'required'   => [ 'group_id', 'status', 'code', 'message' ],
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
		$groups_endpoint  = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'courses-groups_v2' );

		return $this->discover_routes(
			trailingslashit( $courses_endpoint ) . '(?P<id>[\d]+)/' . $groups_endpoint,
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
					// translators: %1$s: plural groups label, %2$s: singular course label.
					__( 'Get associated %1$s for a %2$s', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'POST'   => sprintf(
					// translators: %1$s: plural groups label, %2$s: singular course label.
					__( 'Update associated %1$s for a %2$s', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'PUT'    => sprintf(
					// translators: %1$s: plural groups label, %2$s: singular course label.
					__( 'Update associated %1$s for a %2$s', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'PATCH'  => sprintf(
					// translators: %1$s: plural groups label, %2$s: singular course label.
					__( 'Update associated %1$s for a %2$s', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'DELETE' => sprintf(
					// translators: %1$s: plural groups label, %2$s: singular course label.
					__( 'Delete associated %1$s for a %2$s', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
					learndash_get_custom_label_lower( 'course' )
				),
			],
		];

		return $summaries[ $route_type ][ $method ]
			?? sprintf(
				// translators: %1$s: singular course label, %2$s: singular group label.
				__( '%1$s %2$s operation', 'learndash' ),
				learndash_get_custom_label( 'course' ),
				learndash_get_custom_label_lower( 'group' )
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
					// translators: %1$s: plural groups label, %2$s: singular course label.
					__( 'Retrieves the %1$s for a specific %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'POST'   => sprintf(
					// translators: %1$s: plural groups label, %2$s: singular course label.
					__( 'Adds %1$s to a specific %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'PUT'    => sprintf(
					// translators: %1$s: plural groups label, %2$s: singular course label.
					__( 'Adds %1$s to a specific %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'PATCH'  => sprintf(
					// translators: %1$s: plural groups label, %2$s: singular course label.
					__( 'Adds %1$s to a specific %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'DELETE' => sprintf(
					// translators: %1$s: plural groups label, %2$s: singular course label.
					__( 'Removes %1$s from a specific %2$s.', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
					learndash_get_custom_label_lower( 'course' )
				),
			],
		];

		return $descriptions[ $route_type ][ $method ] ?? sprintf(
			// translators: %1$s: singular group label, %2$s: singular course label.
			__( 'Performs %1$s operations on %2$s.', 'learndash' ),
			learndash_get_custom_label_lower( 'group' ),
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
		return [
			sprintf(
				'%s-%s',
				learndash_get_custom_label_lower( 'course' ),
				learndash_get_custom_label_lower( 'groups' )
			),
		];
	}
}
