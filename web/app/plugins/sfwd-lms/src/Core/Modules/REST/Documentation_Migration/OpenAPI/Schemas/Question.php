<?php
/**
 * LearnDash Question OpenAPI Schema Class.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Schemas;

use LearnDash\Core\Enums\Models\Question_Type;

/**
 * Class that provides LearnDash Question OpenAPI schema.
 *
 * @since 5.0.0
 */
class Question extends WP_Post {
	/**
	 * Returns the OpenAPI response schema for a LearnDash Question.
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
		// Get the base WP_Post schema.
		$base_schema = parent::get_schema();

		$question_singular_lowercase = learndash_get_custom_label_lower( 'question' );
		$quiz_singular_lowercase     = learndash_get_custom_label_lower( 'quiz' );

		// Add LearnDash Question specific properties based on actual API response.
		$question_properties = [
			// Question associations.
			'quiz'                   => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %s: Quiz label (lowercase) */
					__( 'The ID of the parent %s.', 'learndash' ),
					$quiz_singular_lowercase
				),
				'example'     => 523,
			],

			// Question type and answers.
			'question_type'          => [
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

			// Points configuration.
			'points_total'           => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %s: Question label (lowercase) */
					__( 'Total points awarded for answering the %s correctly.', 'learndash' ),
					$question_singular_lowercase
				),
				'example'     => 1,
			],
			'points_per_answer'      => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Question label (lowercase) */
					__( 'Whether different points are awarded for each answer option in the %s.', 'learndash' ),
					$question_singular_lowercase
				),
				'example'     => false,
			],
			'points_show_in_message' => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Question label (lowercase) */
					__( 'Whether to show the points reached in the correct/incorrect message for the %s. Requires "Different points for each answer" to be enabled.', 'learndash' ),
					$question_singular_lowercase
				),
				'example'     => false,
			],
			'points_diff_modus'      => [
				'type'        => 'boolean',
				'description' => sprintf(
								// translators: placeholder: %1$s - question label, %2$s - question type value.
					__( 'Whether different points can be awarded for each answer. Requires "Different points for each answer" to be enabled and for the %1$s Type to be "%2$s".', 'learndash' ),
					$question_singular_lowercase,
					Question_Type::SINGLE_CHOICE()->getValue()
				),
				'example'     => false,
			],

			// Answer correctness settings.
			'disable_correct'        => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Question label (lowercase) */
					__( 'Disable the distinction between correct and incorrect answers. Requires "points_diff_modus" to be enabled.', 'learndash' ),
					$question_singular_lowercase
				),
				'example'     => false,
			],
			'correct_message'        => [
				'type'        => 'object',
				'description' => sprintf(
					/* translators: %s: Question label (lowercase) */
					__( 'Message displayed when the %s is answered correctly.', 'learndash' ),
					$question_singular_lowercase
				),
				'properties'  => [
					'raw'      => [
						'type'        => 'string',
						'description' => __( 'The raw correct message content.', 'learndash' ),
						'example'     => __( 'Correct!', 'learndash' ),
					],
					'rendered' => [
						'type'        => 'string',
						'description' => __( 'The rendered correct message content.', 'learndash' ),
						'example'     => '<p>' . __( 'Correct!', 'learndash' ) . '</p>',
					],
				],
				'required'    => [ 'rendered' ],
			],
			'incorrect_message'      => [
				'type'        => 'object',
				'description' => sprintf(
					/* translators: %s: Question label (lowercase) */
					__( 'Message shown when %s is incorrect. Cannot be used when the "Same correct and incorrect message text" setting is enabled.', 'learndash' ),
					$question_singular_lowercase
				),
				'properties'  => [
					'raw'      => [
						'type'        => 'string',
						'description' => __( 'The raw incorrect message content.', 'learndash' ),
						'example'     => __( 'Incorrect.', 'learndash' ),
					],
					'rendered' => [
						'type'        => 'string',
						'description' => __( 'The rendered incorrect message content.', 'learndash' ),
						'example'     => '<p>' . __( 'Incorrect.', 'learndash' ) . '</p>',
					],
				],
				'required'    => [ 'rendered' ],
			],
			'correct_same'           => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Question label (lowercase) */
					__( 'Whether to use the same message for both correct and incorrect answers for this %s.', 'learndash' ),
					$question_singular_lowercase
				),
				'example'     => false,
			],

			// Hints configuration.
			'hints_enabled'          => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Question label (lowercase) */
					__( 'Whether hints are enabled for the %s.', 'learndash' ),
					$question_singular_lowercase
				),
				'example'     => false,
			],
			'hints_message'          => [
				'type'        => 'object',
				'description' => sprintf(
					/* translators: %s: Question label (lowercase) */
					__( 'Hint message for the %s.', 'learndash' ),
					$question_singular_lowercase
				),
				'properties'  => [
					'raw'      => [
						'type'        => 'string',
						'description' => __( 'The raw hint message content.', 'learndash' ),
						'example'     => __( 'Here is a hint...', 'learndash' ),
					],
					'rendered' => [
						'type'        => 'string',
						'description' => __( 'The rendered hint message content.', 'learndash' ),
						'example'     => '<p>' . __( 'Here is a hint...', 'learndash' ) . '</p>',
					],
				],
				'required'    => [ 'rendered' ],
			],

			// Answer data.
			'answers'                => [
				'type'        => 'array',
				'description' => sprintf(
					/* translators: %s: Question label (lowercase) */
					__( 'Answer data, always null.', 'learndash' ),
					$question_singular_lowercase
				),
				'items'       => [
					'type'       => 'object',
					'properties' => [
						'_answer'             => [
							'type'        => 'string',
							'description' => __( 'The answer text.', 'learndash' ),
						],
						'_html'               => [
							'type'        => 'boolean',
							'description' => __( 'Whether the answer is HTML.', 'learndash' ),
						],
						'_points'             => [
							'type'        => 'integer',
							'description' => sprintf(
								/* translators: %s: Question label (lowercase) */
								__( 'The number of points that can be obtained from the answer. Only used if "points_per_answer" is enabled for the %s.', 'learndash' ),
								$question_singular_lowercase
							),
						],
						'_correct'            => [
							'type'        => 'boolean',
							'description' => __( 'Whether the answer is correct.', 'learndash' ),
						],
						'_sortString'         => [
							'type'        => 'string',
							'description' => sprintf(
								/* translators: %1$s: Matrix sort answer question type, %2$s: Question label (lowercase) */
								__( 'The sort string Only used for the "%1$s" %2$s type. This is the draggable element that you match with the "_answer" field.', 'learndash' ),
								Question_Type::MATRIX_SORTING_CHOICE()->getValue(),
								$question_singular_lowercase
							),
						],
						'_sortStringHtml'     => [
							'type'        => 'boolean',
							'description' => sprintf(
										// translators: placeholder: %1$s - matrix sort answer question type, %2$s - question label.
								__( 'Whether HTML is enabled for _sortString. Only used for the "%1$s" %2$s type.', 'learndash' ),
								Question_Type::MATRIX_SORTING_CHOICE()->getValue(),
								$question_singular_lowercase
							),
						],
						'_graded'             => [
							'type'        => 'boolean',
							'description' => __( 'Whether the answer can be graded.', 'learndash' ),
						],
						'_gradingProgression' => [
							'type'        => 'string',
							'description' => sprintf(
										// translators: placeholder: %1$s - question label, %2$s - essay question type.
								__( 'Determines how should the answer to this %1$s be marked and graded upon submission. Only applies to the "%2$s" %1$s type', 'learndash' ),
								$question_singular_lowercase,
								Question_Type::ESSAY()->getValue()
							),
							'enum'        => [
								'not-graded-none',
								'not-graded-full',
								'graded-full',
							],
						],
						'_gradedType'         => [
							'type'        => 'string',
							'description' => sprintf(
										// translators: placeholder: %1$s - essay question type, %2$s - question label.
								__( 'Determines how a user can submit answer. Only applies to the "%1$s" %2$s type', 'learndash' ),
								Question_Type::ESSAY()->getValue(),
								$question_singular_lowercase
							),
							'enum'        => [
								'text',
								'upload',
							],
						],

					],
				],

			],

			// Question links (extending WP_Post _links).
			'_links'                 => [
				'type'        => 'object',
				'description' => sprintf(
					/* translators: %s: Question label (lowercase) */
					__( 'HAL links for the %s (extends WP_Post links).', 'learndash' ),
					$question_singular_lowercase
				),
				'properties'  => [
					'about'               => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'href' => [
									'type'        => 'string',
									'description' => __( 'The link URL.', 'learndash' ),
								],
							],
						],
					],
					'version-history'     => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'count' => [
									'type'        => 'integer',
									'description' => __( 'Number of revisions.', 'learndash' ),
								],
								'href'  => [
									'type'        => 'string',
									'description' => __( 'The link URL.', 'learndash' ),
								],
							],
						],
					],
					'predecessor-version' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id'   => [
									'type'        => 'integer',
									'description' => __( 'The revision ID.', 'learndash' ),
								],
								'href' => [
									'type'        => 'string',
									'description' => __( 'The link URL.', 'learndash' ),
								],
							],
						],
					],
					'question-types'      => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'href'       => [
									'type'        => 'string',
									'description' => __( 'The link URL.', 'learndash' ),
								],
								'embeddable' => [
									'type'        => 'boolean',
									'description' => __( 'Whether the link is embeddable.', 'learndash' ),
								],
							],
						],
					],
					'wp:attachment'       => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'href' => [
									'type'        => 'string',
									'description' => __( 'The link URL.', 'learndash' ),
								],
							],
						],
					],
					'curies'              => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'name'      => [
									'type'        => 'string',
									'description' => __( 'The curie name.', 'learndash' ),
								],
								'href'      => [
									'type'        => 'string',
									'description' => __( 'The curie href template.', 'learndash' ),
								],
								'templated' => [
									'type'        => 'boolean',
									'description' => __( 'Whether the href is templated.', 'learndash' ),
								],
							],
						],
					],
				],
			],
		];

		$links = $question_properties['_links']['properties'];
		unset( $question_properties['_links'] );

		// Merge the base schema properties with question-specific properties.
		$base_schema['properties'] = array_merge(
			$base_schema['properties'],
			$question_properties
		);

		$base_links = is_array( $base_schema['properties']['_links']['properties'] ) ? $base_schema['properties']['_links']['properties'] : [];

		// Merge the _links properties to extend WP_Post links instead of overwriting them.
		$base_schema['properties']['_links']['properties'] = array_merge(
			$base_links,
			$links
		);

		// Add question-specific required fields.
		$base_schema['required'] = array_unique(
			array_merge(
				$base_schema['required'],
				[
					'quiz',
					'question_type',
					'points_total',
					'points_per_answer',
					'points_show_in_message',
					'points_diff_modus',
					'disable_correct',
					'correct_message',
					'incorrect_message',
					'correct_same',
					'hints_enabled',
					'hints_message',
					'answers',
				]
			)
		);

		return $base_schema;
	}
}
