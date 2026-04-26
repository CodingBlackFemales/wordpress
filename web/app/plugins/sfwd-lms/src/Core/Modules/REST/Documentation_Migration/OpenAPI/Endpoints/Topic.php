<?php
/**
 * Topics OpenAPI Documentation.
 *
 * Provides OpenAPI specification for topics endpoints.
 * Currently based on V2 REST API: https://developers.learndash.com/rest-api/v2/v2-topics/.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints;

use LearnDash_Settings_Section;
use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Contracts\LDLMS_V2_Endpoint;

/**
 * Topics OpenAPI Documentation Endpoint.
 *
 * @since 4.25.2
 */
class Topic extends LDLMS_V2_Endpoint {
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
				'$ref' => '#/components/schemas/LDLMS_v2_Topic',
			];
		}

		return [
			'type'  => 'array',
			'items' => [
				'$ref' => '#/components/schemas/LDLMS_v2_Topic',
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
		$endpoint = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'topics_v2' );

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
					// translators: %s: plural topics label.
					__( 'Get %s', 'learndash' ),
					learndash_get_custom_label_lower( 'topics' )
				),
				'POST' => sprintf(
					// translators: %s: singular topic label.
					__( 'Create a new %s', 'learndash' ),
					learndash_get_custom_label_lower( 'topic' )
				),
			],
			'singular'   => [
				'GET'    => sprintf(
					// translators: %s: singular topic label.
					__( 'Get a specific %s', 'learndash' ),
					learndash_get_custom_label_lower( 'topic' )
				),
				'POST'   => sprintf(
					// translators: %s: singular topic label.
					__( 'Update a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'topic' )
				),
				'PUT'    => sprintf(
					// translators: %s: singular topic label.
					__( 'Update a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'topic' )
				),
				'PATCH'  => sprintf(
					// translators: %s: singular topic label.
					__( 'Update a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'topic' )
				),
				'DELETE' => sprintf(
					// translators: %s: singular topic label.
					__( 'Delete a %s', 'learndash' ),
					learndash_get_custom_label_lower( 'topic' )
				),
			],
		];

		return $summaries[ $route_type ][ $method ]
			?? sprintf(
				// translators: %s: plural topics label.
				__( '%s operation', 'learndash' ),
				learndash_get_custom_label( 'topics' )
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
					// translators: %s: plural topics label.
					__( 'Returns a list of %s. You can filter the results using query parameters such as per_page, page, search, and orderby.', 'learndash' ),
					learndash_get_custom_label_lower( 'topics' )
				),
				'POST' => sprintf(
					// translators: %s: singular topic label.
					__( 'Creates a new %1$s. Requires %2$s data in the request body including title, content, and other %3$s-specific fields.', 'learndash' ),
					learndash_get_custom_label_lower( 'topic' ),
					learndash_get_custom_label_lower( 'topic' ),
					learndash_get_custom_label_lower( 'topic' )
				),
			],
			'singular'   => [
				'GET'    => sprintf(
					// translators: %s: singular topic label.
					__( 'Returns a specific %1$s by ID. Returns the complete %2$s data including all fields and metadata.', 'learndash' ),
					learndash_get_custom_label_lower( 'topic' ),
					learndash_get_custom_label_lower( 'topic' )
				),
				'POST'   => sprintf(
					// translators: %s: singular topic label.
					__( 'Partially updates an existing %1$s. Only the provided fields will be updated, leaving other fields unchanged.', 'learndash' ),
					learndash_get_custom_label_lower( 'topic' )
				),
				'PUT'    => sprintf(
					// translators: %s: singular topic label.
					__( 'Updates an existing %1$s. Requires the complete %2$s data in the request body. All fields will be replaced with the provided values.', 'learndash' ),
					learndash_get_custom_label_lower( 'topic' ),
					learndash_get_custom_label_lower( 'topic' )
				),
				'PATCH'  => sprintf(
					// translators: %s: singular topic label.
					__( 'Partially updates an existing %1$s. Only the provided fields will be updated, leaving other fields unchanged.', 'learndash' ),
					learndash_get_custom_label_lower( 'topic' )
				),
				'DELETE' => sprintf(
					// translators: %s: singular topic label.
					__( 'Deletes a %s permanently. This action cannot be undone.', 'learndash' ),
					learndash_get_custom_label_lower( 'topic' )
				),
			],
		];

		return $descriptions[ $route_type ][ $method ] ?? sprintf(
			// translators: %s: plural topics label.
			__( 'Performs operations on %s.', 'learndash' ),
			learndash_get_custom_label_lower( 'topics' )
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
		return [ learndash_get_custom_label_lower( 'topics' ) ];
	}
}
