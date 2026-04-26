<?php
/**
 * Question Types OpenAPI Documentation.
 *
 * Provides OpenAPI specification for question types endpoints.
 * Currently based on V2 REST API: https://developers.learndash.com/rest-api/v2/v2-question-types/.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints;

use LearnDash_Settings_Section;
use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Contracts\LDLMS_V2_Endpoint;
use LearnDash\Core\Enums\Models\Question_Type;

/**
 * Question Types OpenAPI Documentation Endpoint.
 *
 * @since 5.0.0
 */
class Question_Types extends LDLMS_V2_Endpoint {
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

		$question_type_schema = [
			'type'       => 'object',
			'properties' => [
				'name'  => [
					'type'        => 'string',
					'description' => sprintf(
						// translators: placeholder: question.
						__( 'The label for the %s type.', 'learndash' ),
						learndash_get_custom_label_lower( 'question' )
					),
					'example'     => __( 'Single choice', 'learndash' ),
				],
				'slug'  => [
					'type'        => 'string',
					'description' => sprintf(
						// translators: placeholders: question, question.
						__( 'The slug for the %1$s type that can be used to retrieve the %2$s type.', 'learndash' ),
						learndash_get_custom_label_lower( 'question' ),
						learndash_get_custom_label_lower( 'question' )
					),
					'example'     => 'single-choice',
				],
				'value' => [
					'type'        => 'string',
					'description' => sprintf(
						// translators: placeholder: question.
						__( 'The value for the %s type. This is the actual value that will be referenced in other endpoints.', 'learndash' ),
						learndash_get_custom_label_lower( 'question' )
					),
					'example'     => 'single_choice',
				],
			],
		];

		if ( $this->determine_route_type( $route_path ) === 'singular' ) {
			// Singular endpoint - returns a single question type object.
			return $question_type_schema;
		}

		return [
			'type'                 => 'object',
			'additionalProperties' => $question_type_schema,
			'example'              => [
				Question_Type::SINGLE_CHOICE()->getValue() => [
					'name'  => Question_Type::SINGLE_CHOICE()->get_label(),
					'slug'  => str_replace( '_', '-', Question_Type::SINGLE_CHOICE()->getValue() ),
					'value' => Question_Type::SINGLE_CHOICE()->getValue(),
				],
				Question_Type::MULTIPLE_CHOICE()->getValue() => [
					'name'  => Question_Type::MULTIPLE_CHOICE()->get_label(),
					'slug'  => str_replace( '_', '-', Question_Type::MULTIPLE_CHOICE()->getValue() ),
					'value' => Question_Type::MULTIPLE_CHOICE()->getValue(),
				],
			],
		];
	}

	/**
	 * Returns the security schemes for this endpoint.
	 *
	 * @since 5.0.0
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
	 * @since 5.0.0
	 *
	 * @return array<string,array<string,string|callable>>
	 */
	protected function get_routes(): array {
		$endpoint = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'question-types_v2' );

		return $this->discover_routes( $endpoint, [ 'collection', 'singular' ] );
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
					// translators: placeholder: question.
					__( 'Retrieve %s types', 'learndash' ),
					learndash_get_custom_label_lower( 'question' )
				),
			],
			'singular'   => [
				'GET' => sprintf(
					// translators: placeholder: question.
					__( 'Retrieve %s type', 'learndash' ),
					learndash_get_custom_label_lower( 'question' )
				),
			],
		];

		return $summaries[ $route_type ][ $method ]
			?? sprintf(
				// translators: placeholder: question.
				__( '%s types operation', 'learndash' ),
				learndash_get_custom_label_lower( 'question' )
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
					// translators: placeholder: question.
					__( 'Retrieves the %s types.', 'learndash' ),
					learndash_get_custom_label_lower( 'question' )
				),
			],
			'singular'   => [
				'GET' => sprintf(
					// translators: placeholder: question.
					__( 'Retrieves the %s type.', 'learndash' ),
					learndash_get_custom_label_lower( 'question' )
				),
			],
		];

		return $descriptions[ $route_type ][ $method ] ?? sprintf(
			// translators: placeholder: question.
			__( '%s type operation.', 'learndash' ),
			learndash_get_custom_label_lower( 'question' )
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
		return [ sprintf( '%1$s-types', learndash_get_custom_label_lower( 'question' ) ) ];
	}
}
