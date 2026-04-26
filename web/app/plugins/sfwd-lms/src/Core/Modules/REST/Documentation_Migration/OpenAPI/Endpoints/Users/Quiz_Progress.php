<?php
/**
 * User Quiz Progress OpenAPI Documentation.
 *
 * Provides OpenAPI specification for individual user quiz progress endpoint.
 * Currently based on V2 REST API: https://developers.learndash.com/rest-api/v2/v2-user-quiz-progress/.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints\Users;

use LearnDash_Settings_Section;
use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Contracts\LDLMS_V2_Endpoint;

/**
 * User Quiz Progress OpenAPI Documentation Endpoint.
 *
 * @since 5.0.0
 */
class Quiz_Progress extends LDLMS_V2_Endpoint {
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
			'type'  => 'array',
			'items' => [
				'type'       => 'object',
				'properties' => [
					'id'            => [
						'type'        => 'string',
						'description' => sprintf(
							// translators: placeholder: Quiz label.
							_x( 'Unique identifier for the %s progress entry', 'Unique identifier for the quiz progress entry for User Quiz Progress OpenAPI Documentation', 'learndash' ),
							learndash_get_custom_label( 'quiz' )
						),
						'readOnly'    => true,
					],
					'quiz'          => [
						'description' => sprintf(
							// translators: placeholder: Quiz.
							_x(
								'%s ID',
								'Quiz ID Label for User Quiz Progress OpenAPI Documentation',
								'learndash'
							),
							learndash_get_custom_label( 'quiz' )
						),
						'type'        => 'integer',
						'readOnly'    => true,
					],
					'course'        => [
						'description' => sprintf(
							// translators: placeholder: Course.
							_x(
								'%s ID',
								'Course ID Label for User Quiz Progress OpenAPI Documentation',
								'learndash'
							),
							learndash_get_custom_label( 'course' )
						),
						'type'        => 'integer',
						'readOnly'    => true,
					],
					'lesson'        => [
						'description' => sprintf(
							// translators: placeholder: Lesson.
							_x(
								'%s ID',
								'Lesson ID Label for User Quiz Progress OpenAPI Documentation',
								'learndash'
							),
							learndash_get_custom_label( 'lesson' )
						),
						'type'        => 'integer',
						'readOnly'    => true,
					],
					'topic'         => [
						'description' => sprintf(
							// translators: placeholder: Topic.
							_x(
								'%s ID',
								'Topic ID Label for User Quiz Progress OpenAPI Documentation',
								'learndash'
							),
							learndash_get_custom_label( 'topic' )
						),
						'type'        => 'integer',
						'readOnly'    => true,
					],
					'user'          => [
						'description' => _x( 'User ID', 'User ID Label for User Quiz Progress OpenAPI Documentation', 'learndash' ),
						'type'        => 'integer',
						'readOnly'    => true,
					],
					'percentage'    => [
						'description' => _x( 'Percentage passed', 'Percentage passed Label for User Quiz Progress OpenAPI Documentation', 'learndash' ),
						'type'        => 'number',
						'format'      => 'float',
						'readOnly'    => true,
					],
					'timespent'     => [
						'description' => _x( 'Time spent', 'Timespent Label for User Quiz Progress OpenAPI Documentation', 'learndash' ),
						'type'        => 'number',
						'format'      => 'float',
						'readOnly'    => true,
					],
					'has_graded'    => [
						'description' => sprintf(
							// translators: placeholder: %1$s: Quiz, %2$s: Questions.
							_x( 'Has ungraded %1$s %2$s', 'Has ungraded quiz questions Label for User Quiz Progress OpenAPI Documentation', 'learndash' ),
							learndash_get_custom_label( 'quiz' ),
							learndash_get_custom_label( 'questions' )
						),
						'type'        => 'boolean',
						'readOnly'    => true,
					],
					'started'       => [
						'description' => _x( 'Started timestamp', 'Started timestamp Label for User Quiz Progress OpenAPI Documentation', 'learndash' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'nullable'    => true,
						'readOnly'    => true,
					],
					'completed'     => [
						'description' => _x( 'Completed timestamp', 'Completed timestamp Label for User Quiz Progress OpenAPI Documentation', 'learndash' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'nullable'    => true,
						'readOnly'    => true,
					],
					'points_scored' => [
						'description' => _x( 'Points scored', 'Points scored Label for User Quiz Progress OpenAPI Documentation', 'learndash' ),
						'type'        => 'integer',
						'readOnly'    => true,
					],
					'points_total'  => [
						'description' => _x( 'Points total', 'Points total Label for User Quiz Progress OpenAPI Documentation', 'learndash' ),
						'type'        => 'integer',
						'readOnly'    => true,
					],
					'statistic'     => [
						'description' => _x( 'Statistic ID', 'Statistic ID Label for User Quiz Progress OpenAPI Documentation', 'learndash' ),
						'type'        => 'integer',
						'readOnly'    => true,
					],
					'_links'        => [
						'type'                 => 'object',
						'description'          => _x( 'Links to related resources. Only included when corresponding resource IDs are present.', 'Links to related resources for User Quiz Progress OpenAPI Documentation', 'learndash' ),
						'properties'           => [
							// As we have not defined `required`, all link types are optional.
							'quiz'             => [
								'type'  => 'array',
								'items' => [
									'type'       => 'object',
									'properties' => [
										'href'       => [
											'type'        => 'string',
											'format'      => 'uri',
											'description' => sprintf(
												// translators: placeholder: Quiz.
												_x( 'URL to the %s resource', 'URL to the quiz resource for User Quiz Progress OpenAPI Documentation', 'learndash' ),
												learndash_get_custom_label_lower( 'quiz' )
											),
										],
										'embeddable' => [
											'type'        => 'boolean',
											'description' => _x( 'Whether the resource can be embedded', 'Whether the resource can be embedded for User Quiz Progress OpenAPI Documentation', 'learndash' ),
										],
									],
								],
							],
							'course'           => [
								'type'  => 'array',
								'items' => [
									'type'       => 'object',
									'properties' => [
										'href'       => [
											'type'        => 'string',
											'format'      => 'uri',
											'description' => sprintf(
												// translators: placeholder: Course.
												_x( 'URL to the %s resource', 'URL to the course resource for User Quiz Progress OpenAPI Documentation', 'learndash' ),
												learndash_get_custom_label_lower( 'course' )
											),
										],
										'embeddable' => [
											'type'        => 'boolean',
											'description' => _x( 'Whether the resource can be embedded', 'Whether the resource can be embedded for User Quiz Progress OpenAPI Documentation', 'learndash' ),
										],
									],
								],
							],
							'lesson'           => [
								'type'  => 'array',
								'items' => [
									'type'       => 'object',
									'properties' => [
										'href'       => [
											'type'        => 'string',
											'format'      => 'uri',
											'description' => sprintf(
												// translators: placeholder: Lesson.
												_x( 'URL to the %s resource', 'URL to the lesson resource for User Quiz Progress OpenAPI Documentation', 'learndash' ),
												learndash_get_custom_label_lower( 'lesson' )
											),
										],
										'embeddable' => [
											'type'        => 'boolean',
											'description' => _x( 'Whether the resource can be embedded', 'Whether the resource can be embedded for User Quiz Progress OpenAPI Documentation', 'learndash' ),
										],
									],
								],
							],
							'topic'            => [
								'type'  => 'array',
								'items' => [
									'type'       => 'object',
									'properties' => [
										'href'       => [
											'type'        => 'string',
											'format'      => 'uri',
											'description' => sprintf(
												// translators: placeholder: Topic.
												_x( 'URL to the %s resource', 'URL to the topic resource for User Quiz Progress OpenAPI Documentation', 'learndash' ),
												learndash_get_custom_label_lower( 'topic' )
											),

										],
										'embeddable' => [
											'type'        => 'boolean',
											'description' => _x( 'Whether the resource can be embedded', 'Whether the resource can be embedded for User Quiz Progress OpenAPI Documentation', 'learndash' ),
										],
									],
								],
							],
							'statistic_ref_id' => [
								'type'  => 'array',
								'items' => [
									'type'       => 'object',
									'properties' => [
										'href'       => [
											'type'        => 'string',
											'format'      => 'uri',
											'description' => sprintf(
												// translators: placeholder: Quiz.
												_x( 'URL to the %s statistics resource', 'URL to the quiz statistics resource for User Quiz Progress OpenAPI Documentation', 'learndash' ),
												learndash_get_custom_label_lower( 'quiz' )
											),
										],
										'embeddable' => [
											'type'        => 'boolean',
											'description' => _x( 'Whether the resource can be embedded', 'Whether the resource can be embedded for User Quiz Progress OpenAPI Documentation', 'learndash' ),
										],
									],
								],
							],
						],
						'additionalProperties' => false, // Only defined link types are valid.
						'readOnly'             => true,
					],
				],
				'required'   => [
					'id',
					'quiz',
					'course',
					'lesson',
					'topic',
					'user',
					'percentage',
					'timespent',
					'has_graded',
					'started',
					'completed',
					'points_scored',
					'points_total',
					'statistic',
					// _links is not always returned.
				],
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
		$users_endpoint         = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'users_v2' );
		$quiz_progress_endpoint = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', 'users-quiz-progress_v2' );

		return $this->discover_routes(
			trailingslashit( $users_endpoint ) . '(?P<id>[\d]+)/' . $quiz_progress_endpoint,
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
					// translators: placeholder: singular quiz label.
					__( 'Get the %s progress for a user', 'learndash' ),
					learndash_get_custom_label_lower( 'quiz' ),
				),
			],
		];

		return $summaries[ $route_type ][ $method ]
			?? sprintf(
				// translators: placeholder: singular quiz label.
				__( 'User %s progress operation', 'learndash' ),
				learndash_get_custom_label_lower( 'quiz' )
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
					// translators: placeholder: singular quiz label.
					__( 'Get the %s progress for a user.', 'learndash' ),
					learndash_get_custom_label_lower( 'quiz' ),
				),
			],
		];

		return $descriptions[ $route_type ][ $method ] ?? sprintf(
			// translators: placeholder: singular quiz label.
			__( 'Performs %s progress operations for a user.', 'learndash' ),
			learndash_get_custom_label_lower( 'quiz' ),
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
		return [ sprintf( 'user-%1$s-progress', learndash_get_custom_label_lower( 'quiz' ) ) ];
	}
}
