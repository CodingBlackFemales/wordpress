<?php
/**
 * LearnDash Quiz Statistic Question OpenAPI Schema Class.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Schemas;

use LDLMS_Post_Types;
use LearnDash\Core\Enums\Models\Question_Type;

/**
 * Class that provides LearnDash Quiz Statistic Question OpenAPI schema.
 *
 * @since 5.0.0
 */
class Quiz_Statistic_Question {
	/**
	 * Returns the OpenAPI response schema for a LearnDash Quiz Statistic Question.
	 *
	 * @since 5.0.0
	 *
	 * @return array{
	 *     type: string,
	 *     properties: array<string,array<string,mixed>>,
	 *     required: array<string>,
	 * }
	 */
	public static function get_schema(): array {
		$quiz_singular               = learndash_get_custom_label( LDLMS_Post_Types::QUIZ );
		$quiz_singular_lowercase     = learndash_get_custom_label_lower( LDLMS_Post_Types::QUIZ );
		$question_singular           = learndash_get_custom_label( LDLMS_Post_Types::QUESTION );
		$question_singular_lowercase = learndash_get_custom_label_lower( LDLMS_Post_Types::QUESTION );

		return [
			'type'       => 'object',
			'properties' => [
				'id'            => [
					'description' => sprintf(
						// translators: %1$s: question label. %2$s: quiz label.
						esc_html_x(
							'Unique ID for %1$s statistics for a specific %2$s in format "{statistic_id}_{question_pro_id}".',
							'placeholder: question label, placeholder: quiz label',
							'learndash'
						),
						$question_singular_lowercase,
						$quiz_singular_lowercase,
					),
					'type'        => 'string',
					'example'     => '456_11',
				],
				'statistic'     => [
					'description' => esc_html__( 'Statistics ID.', 'learndash' ),
					'type'        => 'integer',
					'example'     => 456,
				],
				'quiz'          => [
					'description' => sprintf(
						// translators: %s: quiz label.
						esc_html_x(
							'%s ID',
							'placeholder: Quiz label',
							'learndash'
						),
						$quiz_singular
					),
					'type'        => 'integer',
					'example'     => 789,
				],
				'question'      => [
					'description' => sprintf(
						// translators: %s: question label.
						esc_html_x(
							'%s ID',
							'placeholder: Question label',
							'learndash'
						),
						$question_singular
					),
					'type'        => 'integer',
					'example'     => 101,
				],
				'question_type' => [
					'type'        => 'string',
					'description' => sprintf(
						/* translators: %1$s: Question label (lowercase), %2$s: Question types. */
						__( 'The type of %1$s. Options include: %2$s.', 'learndash' ),
						$question_singular_lowercase,
						implode(
							', ',
							array_map(
								function ( $type ) {
									return "'{$type->getValue()}' ({$type->get_label()})";
								},
								Question_Type::values()
							)
						)
					),
					'example'     => Question_Type::SINGLE_CHOICE()->getValue(),
					'enum'        => array_values(
						array_map(
							function ( $type ) {
								return $type->getValue();
							},
							Question_Type::values()
						)
					),
				],
				'points_scored' => [
					'description' => esc_html__( 'Points scored.', 'learndash' ),
					'type'        => 'integer',
					'example'     => 5,
				],
				'points_total'  => [
					'description' => esc_html__( 'Points total.', 'learndash' ),
					'type'        => 'integer',
					'example'     => 10,
				],
				'answers'       => [
					'description'          => sprintf(
						// translators: placeholder: Question label.
						esc_html_x(
							'The collection of %1$s answers. Structure varies by %1$s type.',
							'placeholder: Question label',
							'learndash'
						),
						$question_singular_lowercase
					),
					'type'                 => 'object',
					'additionalProperties' => [
						'type'        => 'object',
						'description' => sprintf(
							// translators: %1$s: question label.
							esc_html__( 'Answer object with structure depending on %1$s type.', 'learndash' ),
							$question_singular_lowercase
						),
						'properties'  => [
							'label'         => [
								'type'        => 'string',
								'description' => esc_html__( 'Answer label text.', 'learndash' ),
							],
							'correct'       => [
								'type'        => 'boolean',
								'description' => esc_html__( 'Whether this answer is correct (for multiple/single choice).', 'learndash' ),
							],
							'points'        => [
								'type'        => 'integer',
								'description' => esc_html__( 'Points for this answer (for assessment questions).', 'learndash' ),
							],
							'values'        => [
								'type'                 => 'object',
								'description'          => esc_html__( 'Nested answer values (for free_answer, cloze_answer, matrix_sort_answer).', 'learndash' ),
								'additionalProperties' => [
									'type'       => 'object',
									'properties' => [
										'label' => [
											'type'        => 'string',
											'description' => esc_html__( 'Value label text.', 'learndash' ),
										],
									],
								],
							],
							'essay_type'    => [
								'type'        => 'string',
								'description' => esc_html__( 'Essay type (for essay questions).', 'learndash' ),
							],
							'essay_grading' => [
								'type'        => 'string',
								'description' => esc_html__( 'Essay grading method (for essay questions).', 'learndash' ),
							],
						],
					],
					'example'              => [
						'12-0' => [
							'label'   => 'Correct Answer 1',
							'correct' => true,
						],
						'12-1' => [
							'label'   => 'Correct Answer 2',
							'correct' => true,
						],
						'12-2' => [
							'label'   => 'Incorrect Answer',
							'correct' => false,
						],
					],
				],
				'student'       => [
					'description' => sprintf(
						// translators: placeholder: Question label.
						esc_html_x(
							'The collection of student submitted %1$s answers. Structure varies by %1$s type. Uses numeric string keys for array-like access for most %1$s types, or single object for essay and assessment %1$s.',
							'placeholder: Question label',
							'learndash'
						),
						$question_singular_lowercase
					),
					'oneOf'       => [
						[
							'type'                 => 'object',
							'description'          => sprintf(
								// translators: placeholder: Question label.
								esc_html_x(
									'Object with numeric string keys containing student %1$s answers (multiple, single, free_answer, sort_answer, matrix_sort_answer, cloze_answer).',
									'placeholder: Question label',
									'learndash'
								),
								$question_singular_lowercase
							),
							'additionalProperties' => [
								'type'       => 'object',
								'properties' => [
									'answer_key' => [
										'type'        => 'string',
										'description' => esc_html__( 'Key referencing the answer option.', 'learndash' ),
									],
									'correct'    => [
										'type'        => 'boolean',
										'description' => esc_html__( 'Whether the student answer is correct.', 'learndash' ),
									],
									'answer'     => [
										'oneOf'       => [
											[ 'type' => 'boolean' ],
											[ 'type' => 'string' ],
											[ 'type' => 'integer' ],
										],
										'description' => esc_html__( 'Student answer value (boolean for multiple/single choice, string for text answers, integer for essay ID).', 'learndash' ),
									],
									'value_key'  => [
										'type'        => 'string',
										'description' => esc_html__( 'Value key for matrix sort answers.', 'learndash' ),
									],
								],
							],
						],
						[
							'type'        => 'object',
							'description' => sprintf(
								// translators: placeholder: Question label.
								esc_html_x(
									'Single answer object for essay %1$s.',
									'placeholder: Question label',
									'learndash'
								),
								$question_singular_lowercase
							),
							'properties'  => [
								'answer_key' => [
									'type'        => 'string',
									'description' => esc_html__( 'Key referencing the answer option.', 'learndash' ),
								],
								'essay'      => [
									'type'        => 'integer',
									'description' => esc_html__( 'Essay post ID (for essay questions).', 'learndash' ),
								],
								'status'     => [
									'type'        => 'string',
									'description' => esc_html__( 'Essay grading status (for essay questions).', 'learndash' ),
								],
							],
						],
						[
							'type'        => 'object',
							'description' => sprintf(
								// translators: placeholder: Question label.
								esc_html_x(
									'Single answer object for assessment %1$s.',
									'placeholder: Question label',
									'learndash'
								),
								$question_singular_lowercase
							),
							'properties'  => [
								'answer_key' => [
									'type'        => 'string',
									'description' => esc_html__( 'Key referencing the answer option.', 'learndash' ),
								],
								'points'     => [
									'type'        => 'integer',
									'description' => esc_html__( 'Points awarded (for assessment questions).', 'learndash' ),
								],
							],
						],
					],
					'example'     => [
						'0' => [
							'answer_key' => '12-0',
							'correct'    => true,
							'answer'     => true,
						],
						'1' => [
							'answer_key' => '12-1',
							'correct'    => false,
							'answer'     => false,
						],
					],
				],
				'_links'        => [
					'description' => esc_html__( 'REST API links for this resource.', 'learndash' ),
					'type'        => 'object',
					'properties'  => [
						'collection'     => [
							'type'        => 'array',
							'description' => esc_html__( 'Link to the collection endpoint.', 'learndash' ),
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'href' => [
										'type'        => 'string',
										'format'      => 'uri',
										'description' => esc_html__( 'Collection URL.', 'learndash' ),
									],
								],
							],
						],
						'self'           => [
							'type'        => 'array',
							'description' => esc_html__( 'Link to this specific resource.', 'learndash' ),
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'href' => [
										'type'        => 'string',
										'format'      => 'uri',
										'description' => esc_html__( 'Self URL.', 'learndash' ),
									],
								],
							],
						],
						'statistic'      => [
							'type'        => 'array',
							'description' => esc_html__( 'Link to the parent statistic.', 'learndash' ),
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'href' => [
										'type'        => 'string',
										'format'      => 'uri',
										'description' => esc_html__( 'Statistic URL.', 'learndash' ),
									],
								],
							],
						],
						'question-types' => [
							'type'        => 'array',
							'description' => sprintf(
								// translators: placeholder: Question label.
								esc_html_x(
									'Link to the %1$s type resource.',
									'placeholder: Question label',
									'learndash'
								),
								$question_singular_lowercase
							),
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'href'       => [
										'type'        => 'string',
										'format'      => 'uri',
										'description' => sprintf(
											// translators: placeholder: Question label.
											esc_html_x(
												'%1$s type URL.',
												'placeholder: Question label',
												'learndash'
											),
											$question_singular
										),
									],
									'embeddable' => [
										'type'        => 'boolean',
										'description' => esc_html__( 'Whether this resource can be embedded.', 'learndash' ),
									],
								],
							],
						],
						'quiz'           => [
							'type'        => 'array',
							'description' => sprintf(
								// translators: placeholder: Quiz label.
								esc_html_x(
									'Link to the %1$s resource.',
									'placeholder: Quiz label',
									'learndash'
								),
								$quiz_singular_lowercase
							),
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'href'       => [
										'type'        => 'string',
										'format'      => 'uri',
										'description' => sprintf(
											// translators: placeholder: Quiz label.
											esc_html_x(
												'%1$s URL.',
												'placeholder: Quiz label',
												'learndash'
											),
											$quiz_singular
										),
									],
									'embeddable' => [
										'type'        => 'boolean',
										'description' => esc_html__( 'Whether this resource can be embedded.', 'learndash' ),
									],
								],
							],
						],
						'question'       => [
							'type'        => 'array',
							'description' => sprintf(
								// translators: placeholder: Question label.
								esc_html_x(
									'Link to the %1$s resource.',
									'placeholder: Question label',
									'learndash'
								),
								$question_singular_lowercase
							),
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'href'       => [
										'type'        => 'string',
										'format'      => 'uri',
										'description' => sprintf(
											// translators: placeholder: Question label.
											esc_html_x(
												'%1$s URL.',
												'placeholder: Question label',
												'learndash'
											),
											$question_singular
										),
									],
									'embeddable' => [
										'type'        => 'boolean',
										'description' => esc_html__( 'Whether this resource can be embedded.', 'learndash' ),
									],
								],
							],
						],
					],
					'example'     => [
						'collection'     => [
							[ 'href' => 'https://localhost/wp-json/ldlms/v2/sfwd-quiz/789/statistics/456/questions' ],
						],
						'self'           => [
							[ 'href' => 'https://localhost/wp-json/ldlms/v2/sfwd-quiz/789/statistics/456/questions/456_101' ],
						],
						'statistic'      => [
							[ 'href' => 'https://localhost/wp-json/ldlms/v2/sfwd-quiz/789/statistics/456' ],
						],
						'question-types' => [
							[
								'href'       => 'https://localhost/wp-json/ldlms/v2/question-types/single',
								'embeddable' => true,
							],
						],
						'quiz'           => [
							[
								'href'       => 'https://localhost/wp-json/ldlms/v2/sfwd-quiz/789',
								'embeddable' => true,
							],
						],
						'question'       => [
							[
								'href'       => 'https://localhost/wp-json/ldlms/v2/sfwd-question/101',
								'embeddable' => true,
							],
						],
					],
				],
			],
			'required'   => [
				'id',
				'statistic',
				'quiz',
				'question',
				'question_type',
				'points_scored',
				'points_total',
				'answers',
				'student',
				'_links',
			],
		];
	}
}
