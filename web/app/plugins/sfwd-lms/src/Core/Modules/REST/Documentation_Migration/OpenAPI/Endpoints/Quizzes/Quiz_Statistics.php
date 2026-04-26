<?php
/**
 * Quiz Statistics OpenAPI Documentation.
 *
 * Provides OpenAPI specification for quiz statistics endpoints.
 * Currently based on V2 REST API: https://developers.learndash.com/rest-api/v2/v2-quiz-statistics/.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints\Quizzes;

use LDLMS_Post_Types;
use LearnDash_Settings_Section;
use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Contracts\LDLMS_V2_Endpoint;

/**
 * Quiz Statistics OpenAPI Documentation Endpoint.
 *
 * @since 5.0.0
 */
class Quiz_Statistics extends LDLMS_V2_Endpoint {
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
		$route_path = '/' . trim( $this->get_namespace(), '/' ) . '/' . ltrim( $path, '/' );

		if ( $this->determine_route_type( $route_path, 'sfwd-quiz/{quiz}/statistics' ) === 'singular' ) {
			return [
				'$ref' => '#/components/schemas/LDLMS_v2_Quiz_Statistic',
			];
		}

		return [
			'type'  => 'array',
			'items' => [
				'$ref' => '#/components/schemas/LDLMS_v2_Quiz_Statistic',
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
		$quizzes_endpoint    = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'quizzes_v2' );
		$statistics_endpoint = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'quizzes-statistics_v2' );

		return $this->discover_routes(
			trailingslashit( $quizzes_endpoint ) . '(?P<quiz>[\d]+)/' . $statistics_endpoint,
			[ 'collection', 'singular' ]
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
					// translators: %1$s: quiz label.
					__( 'Get statistics for a %1$s', 'learndash' ),
					learndash_get_custom_label_lower( LDLMS_Post_Types::QUIZ ),
				),
			],
			'singular'   => [
				'GET' => sprintf(
					// translators: %1$s: quiz label.
					__( 'Get a specific statistic for a %1$s', 'learndash' ),
					learndash_get_custom_label_lower( LDLMS_Post_Types::QUIZ ),
				),
			],
		];

		return $summaries[ $route_type ][ $method ]
			?? sprintf(
				// translators: %1$s: quiz label.
				__( '%1$s statistic operation', 'learndash' ),
				learndash_get_custom_label( LDLMS_Post_Types::QUIZ )
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
					// translators: %1$s: quiz label.
					__( 'Retrieves the statistics for a specific %1$s.', 'learndash' ),
					learndash_get_custom_label_lower( LDLMS_Post_Types::QUIZ ),
				),
			],
			'singular'   => [
				'GET' => sprintf(
					// translators: %1$s: quiz label.
					__( 'Retrieves a specific statistic for a %1$s.', 'learndash' ),
					learndash_get_custom_label_lower( LDLMS_Post_Types::QUIZ ),
				),
			],
		];

		return $descriptions[ $route_type ][ $method ] ?? sprintf(
			// translators: %1$s: quiz label.
			__( 'Performs statistics operations on %s.', 'learndash' ),
			learndash_get_custom_label_lower( LDLMS_Post_Types::QUIZ ),
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
		return [ sprintf( '%s-statistics', learndash_get_custom_label_lower( 'quiz' ) ) ];
	}
}
