<?php
/**
 * User Groups OpenAPI Documentation.
 *
 * Provides OpenAPI specification for user groups endpoints.
 * Currently based on V2 REST API: https://developers.learndash.com/rest-api/v2/v2-user-groups/.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints\Users;

use LearnDash_Settings_Section;
use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Contracts\LDLMS_V2_Endpoint;
use WP_REST_Server;

/**
 * User Groups OpenAPI Documentation Endpoint.
 *
 * @since 5.0.0
 */
class Groups extends LDLMS_V2_Endpoint {
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
								'learndash_rest_invalid_user_id',
								'learndash_rest_invalid_group_id',
								'learndash_rest_unenroll_failed',
								'learndash_rest_unenroll_success',
							]
							: [
								'learndash_rest_invalid_user_id',
								'learndash_rest_invalid_group_id',
								'learndash_rest_enroll_failed',
								'learndash_rest_enroll_success',
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
								// translators: %1$s: singular user label, %2$s: singular group label.
								__( '%1$s unenrolled from %2$s success.', 'learndash' ),
								learndash_get_custom_label( 'user' ),
								learndash_get_custom_label_lower( 'group' )
							)
							: sprintf(
								// translators: %1$s: singular user label, %2$s: singular group label.
								__( '%1$s already enrolled in %2$s.', 'learndash' ),
								learndash_get_custom_label( 'user' ),
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
	 * @since 5.0.0
	 *
	 * @return array<string,array<string,string|callable>>
	 */
	protected function get_routes(): array {
		$users_endpoint  = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'users_v2' );
		$groups_endpoint = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'users-groups_v2' );

		return $this->discover_routes(
			trailingslashit( $users_endpoint ) . '(?P<id>[\d]+)/' . $groups_endpoint,
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
					// translators: %1$s: plural groups label.
					__( 'Get associated %1$s for a user', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
				),
				'POST'   => sprintf(
					// translators: %1$s: plural groups label.
					__( 'Update associated %1$s for a user', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
				),
				'PUT'    => sprintf(
					// translators: %1$s: plural groups label.
					__( 'Update associated %1$s for a user', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
				),
				'PATCH'  => sprintf(
					// translators: %1$s: plural groups label.
					__( 'Update associated %1$s for a user', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
				),
				'DELETE' => sprintf(
					// translators: %1$s: plural groups label.
					__( 'Delete associated %1$s for a user', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
				),
			],
		];

		return $summaries[ $route_type ][ $method ]
			?? sprintf(
				// translators: %1$s: singular group label.
				__( 'User %1$s operation', 'learndash' ),
				learndash_get_custom_label_lower( 'group' )
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
					// translators: %1$s: plural groups label.
					__( 'Retrieves the %1$s for a specific user.', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
				),
				'POST'   => sprintf(
					// translators: %1$s: plural groups label.
					__( 'Adds %1$s to a specific user.', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
				),
				'PUT'    => sprintf(
					// translators: %1$s: plural groups label.
					__( 'Update associated %1$s for a user.', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
				),
				'PATCH'  => sprintf(
					// translators: %1$s: plural groups label.
					__( 'Update associated %1$s for a user.', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
				),
				'DELETE' => sprintf(
					// translators: %1$s: plural groups label.
					__( 'Removes %1$s from a specific user.', 'learndash' ),
					learndash_get_custom_label_lower( 'groups' ),
				),
			],
		];

		return $descriptions[ $route_type ][ $method ] ?? sprintf(
			// translators: %1$s: singular group label.
			__( 'Performs %1$s operations on user.', 'learndash' ),
			learndash_get_custom_label_lower( 'group' ),
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
		return [ sprintf( 'user-%1$s', learndash_get_custom_label_lower( 'groups' ) ) ];
	}
}
