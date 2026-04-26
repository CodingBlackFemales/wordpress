<?php
/**
 * Price Types OpenAPI Documentation.
 *
 * Provides OpenAPI specification for price types endpoints.
 * Currently based on V2 REST API: https://developers.learndash.com/rest-api/v2/v2-price-types/.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints;

use LearnDash_Settings_Section;
use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Contracts\LDLMS_V2_Endpoint;

/**
 * Price Types OpenAPI Documentation Endpoint.
 *
 * @since 4.25.2
 */
class Price_Types extends LDLMS_V2_Endpoint {
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

		$price_type_schema = [
			'type'       => 'object',
			'properties' => [
				'slug'        => [
					'type'        => 'string',
					'description' => __( 'An alphanumeric identifier for the price type.', 'learndash' ),
					'example'     => 'open',
				],
				'name'        => [
					'type'        => 'string',
					'description' => __( 'The name of the price type.', 'learndash' ),
					'example'     => __( 'Open', 'learndash' ),
				],
				'description' => [
					'type'        => 'string',
					'description' => __( 'The description for the price type.', 'learndash' ),
					'example'     => sprintf(
						// translators: %s: singular course label.
						__( 'The %s is not protected. Any user can access its content without the need to be logged-in or enrolled.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
				],
			],
		];

		if ( $this->determine_route_type( $route_path ) === 'singular' ) {
			// Singular endpoint - returns a single price type object.
			return $price_type_schema;
		}

		return [
			'type'                 => 'object',
			'additionalProperties' => $price_type_schema,
			'example'              => [
				'open' => [
					'slug'        => 'open',
					'name'        => __( 'Open', 'learndash' ),
					'description' => sprintf(
						// translators: %s: singular course label.
						__( 'The %s is not protected. Any user can access its content without the need to be logged-in or enrolled.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
				],
			],
		];
	}

	/**
	 * Returns the security schemes for this endpoint.
	 *
	 * @since 4.25.2
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array<int,array<string,string[]>>
	 */
	public function get_security_schemes( string $path, string $method ): array {
		// No security schemes are required for this endpoint.
		return [];
	}

	/**
	 * Returns the routes configuration for this endpoint.
	 *
	 * @since 4.25.2
	 *
	 * @return array<string,array<string,string|callable>>
	 */
	protected function get_routes(): array {
		$endpoint = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'price-types_v2' );

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
				'GET' => __( 'Get price types', 'learndash' ),
			],
			'singular'   => [
				'GET' => __( 'Get a specific price type', 'learndash' ),
			],
		];

		return $summaries[ $route_type ][ $method ]
			?? __( 'Price types operation.', 'learndash' );
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
				'GET' => __( 'Returns a list of price types. A provided context will determine the fields present in the response.', 'learndash' ),
			],
			'singular'   => [
				'GET' => __( 'Returns a specific price type by its slug. A provided context will determine the fields present in the response.', 'learndash' ),
			],
		];

		return $descriptions[ $route_type ][ $method ] ?? __( 'Price type operation.', 'learndash' );
	}

	/**
	 * Returns the tags for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @return string[]
	 */
	protected function get_tags(): array {
		return [ 'price-types' ];
	}
}
