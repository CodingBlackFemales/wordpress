<?php
/**
 * LearnDash Quiz Statistic OpenAPI Schema Class.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Schemas;

use LDLMS_Post_Types;

/**
 * Class that provides LearnDash Quiz Statistic OpenAPI schema.
 *
 * @since 5.0.0
 */
class Quiz_Statistic {
	/**
	 * Returns the OpenAPI response schema for a LearnDash Quiz Statistic.
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
		return [
			'type'       => 'object',
			'properties' => [
				'id'                => [
					'description' => esc_html__( 'Statistics Ref ID.', 'learndash' ),
					'type'        => 'integer',
					'example'     => 123,
				],
				'quiz'              => [
					'description' => sprintf(
						// translators: %s: quiz label.
						esc_html_x(
							'%s ID.',
							'placeholder: Quiz label',
							'learndash'
						),
						learndash_get_custom_label( 'quiz' )
					),
					'type'        => 'integer',
					'example'     => 456,
				],
				'user'              => [
					'description' => esc_html__( 'User ID.', 'learndash' ),
					'type'        => 'integer',
					'example'     => 789,
				],
				'date'              => [
					'description' => esc_html__( 'Date in local timezone.', 'learndash' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'example'     => '2025-10-07T18:06:03',
				],
				'date_gmt'          => [
					'description' => esc_html__( 'Date in GMT timezone.', 'learndash' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'example'     => '2025-10-07T18:06:03',
				],
				'answers_correct'   => [
					'description' => esc_html__( 'Answer correct.', 'learndash' ),
					'type'        => 'integer',
					'example'     => 8,
				],
				'answers_incorrect' => [
					'description' => esc_html__( 'Answer incorrect.', 'learndash' ),
					'type'        => 'integer',
					'example'     => 2,
				],
				'points_scored'     => [
					'description' => esc_html__( 'Points scored.', 'learndash' ),
					'type'        => 'integer',
					'example'     => 8,
				],
				'points_total'      => [
					'description' => esc_html__( 'Total points available.', 'learndash' ),
					'type'        => 'integer',
					'example'     => 10,
				],
				'_links'            => [
					'description' => esc_html__( 'Links to related resources.', 'learndash' ),
					'type'        => 'object',
					'properties'  => [
						'collection' => [
							'description' => sprintf(
								// translators: %s: quiz label.
								esc_html_x(
									'Link to the collection of %s statistics.',
									'placeholder: Quiz label',
									'learndash'
								),
								learndash_get_custom_label_lower( LDLMS_Post_Types::QUIZ )
							),
							'type'        => 'array',
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'href' => [
										'type'   => 'string',
										'format' => 'uri',
									],
								],
							],
							'example'     => [
								'href' => 'https://example.com/wp-json/ldlms/v2/sfwd-quiz/123/statistics',
							],
						],
						'self'       => [
							'description' => sprintf(
								// translators: %s: quiz label.
								esc_html_x(
									'Link to this %s statistic.',
									'placeholder: Quiz label',
									'learndash'
								),
								learndash_get_custom_label_lower( 'quiz' )
							),
							'type'        => 'array',
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'href' => [
										'type'   => 'string',
										'format' => 'uri',
									],
								],
							],
							'example'     => [
								'href' => 'https://example.com/wp-json/ldlms/v2/sfwd-quiz/123/statistics/456',
							],
						],
						'questions'  => [
							'description' => sprintf(
								// translators: %s: quiz label.
								esc_html_x(
									'Link to the %1$s for this %2$s statistic.',
									'placeholder: Questions label, Quiz label',
									'learndash'
								),
								learndash_get_custom_label_lower( 'questions' ),
								learndash_get_custom_label_lower( 'quiz' )
							),
							'type'        => 'array',
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'href'       => [
										'type'   => 'string',
										'format' => 'uri',
									],
									'embeddable' => [
										'type'    => 'boolean',
										'default' => true,
									],
								],
							],
							'example'     => [
								'href' => 'https://example.com/wp-json/ldlms/v2/sfwd-quiz/123/statistics/456/questions',
							],
						],
						'quiz'       => [
							'description' => sprintf(
								// translators: %s: quiz label.
								esc_html_x(
									'Link to the %s.',
									'placeholder: Quiz label',
									'learndash'
								),
								learndash_get_custom_label_lower( 'quiz' )
							),
							'type'        => 'array',
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'href'       => [
										'type'   => 'string',
										'format' => 'uri',
									],
									'embeddable' => [
										'type'    => 'boolean',
										'default' => true,
									],
								],
							],
							'example'     => [
								'href' => 'https://example.com/wp-json/ldlms/v2/sfwd-quiz/123',
							],
						],
						'user'       => [
							'description' => esc_html__( 'Link to the user.', 'learndash' ),
							'type'        => 'array',
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'href'       => [
										'type'   => 'string',
										'format' => 'uri',
									],
									'embeddable' => [
										'type'    => 'boolean',
										'default' => true,
									],
								],
							],
							'example'     => [
								'href' => 'https://example.com/wp-json/wp/v2/users/789',
							],
						],
					],
				],
			],
			'required'   => [
				'id',
				'quiz',
				'user',
				'date_gmt',
				'answers_correct',
				'answers_incorrect',
				'points_scored',
				'points_total',
				'_links',
			],
		];
	}
}
