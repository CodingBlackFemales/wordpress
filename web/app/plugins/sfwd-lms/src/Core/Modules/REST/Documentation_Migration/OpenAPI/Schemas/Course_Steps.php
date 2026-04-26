<?php
/**
 * LearnDash Course Steps OpenAPI Schema Class.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Schemas;

use LDLMS_Post_Types;

/**
 * Class that provides LearnDash Course Steps OpenAPI schema.
 *
 * @since 4.25.2
 */
class Course_Steps {
	/**
	 * Returns the OpenAPI response schema for a LearnDash Course Steps.
	 *
	 * @since 4.25.2
	 *
	 * @return array{
	 *     oneOf: array{
	 *         type: string,
	 *         properties?: array<string,array<string,mixed>|string>,
	 *         items?: array<string,mixed>,
	 *     }[],
	 * }
	 */
	public static function get_schema(): array {
		return [
			'oneOf' => [
				[
					'type'        => 'object',
					'description' => sprintf(
						// translators: %s: singular course label.
						__( 'Response when type=all or type is undefined: All %s step views returned.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'properties'  => [
						'h'        => [
							'type'        => 'object',
							'description' => sprintf(
								// translators: %s: singular course label (lowercase).
								__( 'Hierarchical view of %s steps with nested structure.', 'learndash' ),
								learndash_get_custom_label_lower( 'course' )
							),
							'properties'  => self::get_hierarchical_properties(),
						],
						't'        => [
							'type'        => 'object',
							'description' => sprintf(
								// translators: %s: singular course label (lowercase).
								__( 'Type-based view of %s steps grouped by post type.', 'learndash' ),
								learndash_get_custom_label_lower( 'course' )
							),
							'properties'  => self::get_type_based_properties(),
						],
						'r'        => [
							'type'                 => 'object',
							'description'          => sprintf(
								// translators: %s: singular course label (lowercase).
								__( 'Reverse mapping of %s steps to their parent steps.', 'learndash' ),
								learndash_get_custom_label_lower( 'course' )
							),
							'additionalProperties' => self::get_reverse_properties(),
						],
						'l'        => [
							'type'        => 'array',
							'description' => sprintf(
								// translators: %s: singular course label (lowercase).
								__( 'Linear list of all %s steps in order.', 'learndash' ),
								learndash_get_custom_label_lower( 'course' )
							),
							'items'       => self::get_linear_items(),
						],
						'co'       => [
							'type'        => 'array',
							'description' => sprintf(
								// translators: %s: singular course label (lowercase).
								__( 'Completion order list of %s steps.', 'learndash' ),
								learndash_get_custom_label_lower( 'course' )
							),
							'items'       => self::get_completion_order_items(),
						],
						'legacy'   => [
							'type'        => 'object',
							'description' => __( 'Legacy format for backward compatibility.', 'learndash' ),
							'properties'  => self::get_legacy_properties(),
						],
						'sections' => [
							'type'        => 'array',
							'description' => sprintf(
								// translators: %s: singular course label.
								__( '%s sections array.', 'learndash' ),
								learndash_get_custom_label( 'course' )
							),
							'items'       => self::get_sections_items(),
						],
					],
				],
				[
					'type'        => 'object',
					'description' => sprintf(
						// translators: %s: singular course label (lowercase).
						__( 'Response when type=h: Hierarchical view of %s steps with nested structure.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'properties'  => self::get_hierarchical_properties(),
				],
				[
					'type'        => 'object',
					'description' => sprintf(
						// translators: %s: singular course label (lowercase).
						__( 'Response when type=t: Type-based view of %s steps grouped by post type.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'properties'  => self::get_type_based_properties(),
				],
				[
					'type'                 => 'object',
					'description'          => sprintf(
						// translators: %s: singular course label (lowercase).
						__( 'Response when type=r: Reverse mapping of %s steps to their parent steps.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'additionalProperties' => self::get_reverse_properties(),
				],
				[
					'type'        => 'array',
					'description' => sprintf(
						// translators: %s: singular course label (lowercase).
						__( 'Response when type=l: Linear list of all %s steps in order.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'items'       => self::get_linear_items(),
				],
				[
					'type'        => 'array',
					'description' => sprintf(
						// translators: %s: singular course label (lowercase).
						__( 'Response when type=co: Completion order list of %s steps.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'items'       => self::get_completion_order_items(),
				],
				[
					'type'        => 'object',
					'description' => __( 'Response when type=legacy: Legacy format for backward compatibility.', 'learndash' ),
					'properties'  => self::get_legacy_properties(),
				],
				[
					'type'        => 'array',
					'description' => sprintf(
						// translators: %s: singular course label.
						__( 'Response when type=sections: %s sections array.', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'items'       => self::get_sections_items(),
				],
			],
		];
	}

	/**
	 * Returns the hierarchical properties schema.
	 *
	 * @since 4.25.2
	 *
	 * @return array<string,array<string,mixed>|string>
	 */
	public static function get_hierarchical_properties(): array {
		$lessons_post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON );
		$topics_post_type  = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC );
		$quizzes_post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ );

		return [
			$lessons_post_type => [
				'type'                 => 'object',
				'description'          => sprintf(
					// translators: %1$s: plural lessons label (lowercase), %2$s: plural topics label (lowercase), %3$s: plural quizzes label (lowercase), %4$s: singular lesson label (lowercase).
					__( 'Associated %1$s with nested %2$s and %3$s. Keys are %4$s post IDs.', 'learndash' ),
					learndash_get_custom_label_lower( 'lessons' ),
					learndash_get_custom_label_lower( 'topics' ),
					learndash_get_custom_label_lower( 'quizzes' ),
					learndash_get_custom_label_lower( 'lesson' )
				),
				'additionalProperties' => [
					'type'        => 'object',
					'description' => sprintf(
						// translators: %1$s: singular lesson label, %2$s: plural topics label (lowercase), %3$s: plural quizzes label (lowercase).
						__( '%1$s object with nested %2$s and %3$s.', 'learndash' ),
						learndash_get_custom_label( 'lesson' ),
						learndash_get_custom_label_lower( 'topics' ),
						learndash_get_custom_label_lower( 'quizzes' )
					),
					'properties'  => [
						$topics_post_type  => [
							'type'                 => 'object',
							'description'          => sprintf(
								// translators: %1$s: plural topics label (lowercase), %2$s: singular topic label (lowercase).
								__( 'Associated %1$s with nested quizzes. Keys are %2$s post IDs.', 'learndash' ),
								learndash_get_custom_label_lower( 'topics' ),
								learndash_get_custom_label_lower( 'topic' )
							),
							'additionalProperties' => [
								'type'        => 'object',
								'description' => sprintf(
									// translators: %s: singular topic label.
									__( '%s object with nested quizzes.', 'learndash' ),
									learndash_get_custom_label( 'topic' )
								),
								'properties'  => [
									$quizzes_post_type => [
										'type'        => 'array',
										'description' => sprintf(
											// translators: %s: plural quizzes label (lowercase).
											__( 'Associated %s IDs.', 'learndash' ),
											learndash_get_custom_label_lower( 'quizzes' )
										),
										'items'       => [
											'type'        => 'integer',
											'description' => sprintf(
												// translators: %s: singular quiz label.
												__( '%s ID.', 'learndash' ),
												learndash_get_custom_label( 'quiz' )
											),
										],
									],
								],
							],
						],
						$quizzes_post_type => [
							'type'        => 'array',
							'description' => sprintf(
								// translators: %1$s: plural quizzes label (lowercase), %2$s: singular quiz label (lowercase).
								__( 'Associated %1$s with empty arrays (no child steps). Keys are %2$s post IDs.', 'learndash' ),
								learndash_get_custom_label_lower( 'quizzes' ),
								learndash_get_custom_label_lower( 'quiz' )
							),
							'items'       => [
								'type'        => 'integer',
								'description' => sprintf(
									// translators: %s: singular quiz label.
									__( '%s ID.', 'learndash' ),
									learndash_get_custom_label( 'quiz' )
								),
							],
						],
					],
				],
			],
			$quizzes_post_type => [
				'type'                 => 'object',
				'description'          => sprintf(
					// translators: %1$s: plural quizzes label (lowercase). %2$s: singular quiz label (lowercase).
					__( 'Associated final %1$s with empty arrays (no child steps). Keys are %2$s post IDs.', 'learndash' ),
					learndash_get_custom_label_lower( 'quizzes' ),
					learndash_get_custom_label_lower( 'quiz' )
				),
				'additionalProperties' => [
					'type'        => 'array',
					'description' => sprintf(
						// translators: %s: plural quizzes label (lowercase).
						__( 'Empty array because %s are leaf nodes with no child steps.', 'learndash' ),
						learndash_get_custom_label_lower( 'quizzes' )
					),
					'items'       => [
						'type' => 'string',
					],
				],
			],
		];
	}

	/**
	 * Returns the type-based properties schema.
	 *
	 * @since 4.25.2
	 *
	 * @return array<string,array<string,mixed>|string>
	 */
	public static function get_type_based_properties(): array {
		$lessons_post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON );
		$topics_post_type  = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC );
		$quizzes_post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ );

		return [
			$lessons_post_type => [
				'type'        => 'array',
				'description' => sprintf(
					// translators: %s: plural lessons label (lowercase).
					__( 'Array of %s IDs.', 'learndash' ),
					learndash_get_custom_label_lower( 'lessons' )
				),
				'items'       => [
					'type'        => 'integer',
					'description' => sprintf(
						// translators: %s: singular lesson label.
						__( '%s ID.', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
				],
			],
			$topics_post_type  => [
				'type'        => 'array',
				'description' => sprintf(
					// translators: %s: plural topics label (lowercase).
					__( 'Array of %s IDs.', 'learndash' ),
					learndash_get_custom_label_lower( 'topics' )
				),
				'items'       => [
					'type'        => 'integer',
					'description' => sprintf(
						// translators: %s: singular topic label.
						__( '%s ID.', 'learndash' ),
						learndash_get_custom_label( 'topic' )
					),
				],
			],
			$quizzes_post_type => [
				'type'        => 'array',
				'description' => sprintf(
					// translators: %s: plural quizzes label (lowercase).
					__( 'Array of %s IDs.', 'learndash' ),
					learndash_get_custom_label_lower( 'quizzes' )
				),
				'items'       => [
					'type'        => 'integer',
					'description' => sprintf(
						// translators: %s: singular quiz label.
						__( '%s ID.', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
				],
			],
		];
	}

	/**
	 * Returns the reverse relationship properties schema.
	 *
	 * @since 4.25.2
	 *
	 * @return array<string,array<string,mixed>|string>
	 */
	public static function get_reverse_properties(): array {
		$lessons_post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON );

		return [
			'type'                 => 'object',
			'description'          => __( 'Reverse mapping of steps to their parent steps.', 'learndash' ),
			'additionalProperties' => [
				'type'        => 'array',
				'description' => __( 'Array of parent step references for this step.', 'learndash' ),
				'items'       => [
					'type'        => 'string',
					'description' => __( 'Parent step reference in format "post_type:ID".', 'learndash' ),
					'example'     => "{$lessons_post_type}:123",
				],
			],
		];
	}

	/**
	 * Returns the linear items schema.
	 *
	 * @since 4.25.2
	 *
	 * @return array<string,array<string,mixed>|string>
	 */
	public static function get_linear_items(): array {
		$lessons_post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON );

		return [
			'type'        => 'string',
			'description' => __( 'Step reference in format "post_type:ID".', 'learndash' ),
			'example'     => "{$lessons_post_type}:123",
		];
	}

	/**
	 * Returns the completion order items schema.
	 *
	 * @since 4.25.2
	 *
	 * @return array<string,array<string,mixed>|string>
	 */
	public static function get_completion_order_items(): array {
		$lessons_post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON );

		return [
			'type'        => 'string',
			'description' => __( 'Step reference in format "post_type:ID".', 'learndash' ),
			'example'     => "{$lessons_post_type}:123",
		];
	}

	/**
	 * Returns the sections items schema.
	 *
	 * @since 4.25.2
	 *
	 * @return array<string,array<string,mixed>|string>
	 */
	public static function get_sections_items(): array {
		return [
			'type'        => 'object',
			'description' => sprintf(
				// translators: %s: singular course label.
				__( '%s section with heading and associated steps.', 'learndash' ),
				learndash_get_custom_label( 'course' )
			),
			'properties'  => [
				'order'      => [
					'type'        => 'integer',
					'description' => __( 'Section display order.', 'learndash' ),
					'example'     => 0,
				],
				'ID'         => [
					'type'        => 'integer',
					'description' => __( 'Section ID.', 'learndash' ),
					'example'     => 123,
				],
				'post_title' => [
					'type'        => 'string',
					'description' => __( 'Section heading.', 'learndash' ),
					'example'     => _x( 'Section 1', 'example section heading', 'learndash' ),
				],
				'type'       => [
					'type'        => 'string',
					'description' => __( 'Section type identifier.', 'learndash' ),
					'example'     => 'section-heading',
				],
				'steps'      => [
					'type'        => 'array',
					'description' => __( 'Array of step IDs associated with this section.', 'learndash' ),
					'items'       => [
						'type'        => 'integer',
						'description' => __( 'Step ID.', 'learndash' ),
						'example'     => 123,
					],
				],
			],
		];
	}

	/**
	 * Returns the legacy properties schema.
	 *
	 * @since 4.25.2
	 *
	 * @return array<string,array<string,mixed>|string>
	 */
	public static function get_legacy_properties(): array {
		return [
			'lessons' => [
				'type'                 => 'object',
				'description'          => sprintf(
					// translators: %s: plural lessons label (lowercase).
					__( 'Legacy %s structure.', 'learndash' ),
					learndash_get_custom_label_lower( 'lessons' )
				),
				'additionalProperties' => [
					'type'        => 'integer',
					'description' => sprintf(
						// translators: %s: singular lesson label.
						__( '%s order value.', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
				],
			],
			'topics'  => [
				'type'                 => 'object',
				'description'          => sprintf(
					// translators: %s: plural topics label (lowercase).
					__( 'Legacy %s structure.', 'learndash' ),
					learndash_get_custom_label_lower( 'topics' )
				),
				'additionalProperties' => [
					'oneOf' => [
						[
							'type'                 => 'object',
							'additionalProperties' => [
								'type'        => 'integer',
								'description' => sprintf(
									// translators: %s: singular topic label.
									__( '%s order value.', 'learndash' ),
									learndash_get_custom_label( 'topic' )
								),
							],
						],
						[
							'type'        => 'array',
							'description' => sprintf(
								// translators: %1$s: singular topic label. %2$s: plural quizzes label (lowercase).
								__( 'Empty array for %1$s with no %2$s.', 'learndash' ),
								learndash_get_custom_label( 'topic' ),
								learndash_get_custom_label_lower( 'quizzes' ),
							),
							'items'       => [
								'type' => 'string',
							],
						],
					],
				],
			],
			'total'   => [
				'type'        => 'integer',
				'description' => sprintf(
					// translators: %s: singular course label (lowercase).
					__( 'Total number of %s steps.', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
			],
		];
	}
}
