<?php
/**
 * LearnDash User Course Progress Step OpenAPI Schema Class.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Schemas;

use LDLMS_Post_Types;
use LearnDash\Core\Mappers\Progress\Post_Type_Status;

/**
 * Class that provides LearnDash User Course Progress Step OpenAPI schema.
 *
 * @since 5.0.0
 */
class User_Course_Progress_Step {
	/**
	 * Returns the OpenAPI response schema for a LearnDash User Course Progress Step.
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
		// Get all possible step statuses for different post types.

		$lesson_statuses = Post_Type_Status::get_statuses( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON ) );
		$topic_statuses  = Post_Type_Status::get_statuses( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC ) );
		$quiz_statuses   = Post_Type_Status::get_statuses( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ) );

		// Combine all possible statuses.

		$all_statuses = array_unique(
			array_merge(
				array_keys( $lesson_statuses ),
				array_keys( $topic_statuses ),
				array_keys( $quiz_statuses )
			)
		);

		return [
			'type'       => 'object',
			'properties' => [
				'step'                    => [
					'description' => esc_html__( 'Step ID', 'learndash' ),
					'type'        => 'integer',
					'example'     => 1,
				],
				'post_type'               => [
					'description' => esc_html__( 'Post type for step', 'learndash' ),
					'type'        => 'string',
					'enum'        => [
						LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON ),
						LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC ),
						LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ),
					],
					'example'     => LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON ),
				],
				'step_name'               => [
					'description' => esc_html__( 'Step name', 'learndash' ),
					'type'        => 'string',
					'example'     => 'Lesson 1',
				],
				'step_status'             => [
					'description' => esc_html__( 'Step status value', 'learndash' ),
					'type'        => 'string',
					'enum'        => array_values( $all_statuses ),
					'example'     => 'completed',
				],
				'date_started_gmt'        => [
					'description' => esc_html__( 'Date started in GMT', 'learndash' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'example'     => '2025-10-07T18:06:03',
				],
				'date_started'            => [
					'description' => esc_html__( 'Date started', 'learndash' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'example'     => '2025-10-07T18:06:03',
				],
				'date_completed_gmt'      => [
					'description' => esc_html__( 'Date completed in GMT', 'learndash' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'example'     => '2025-10-07T18:06:03',
				],
				'date_completed'          => [
					'description' => esc_html__( 'Date completed', 'learndash' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'example'     => '2025-10-07T18:06:03',
				],
				'awarded_certificate_url' => [
					'description' => sprintf(
						// translators: placeholder: Certificate, Quiz.
						esc_html_x(
							'URL to the %1$s if the step is a %2$s with an attached %1$s and the %2$s is passed.',
							'placeholder: Certificate, Quiz',
							'learndash'
						),
						learndash_get_custom_label_lower( LDLMS_Post_Types::CERTIFICATE ),
						learndash_get_custom_label_lower( LDLMS_Post_Types::QUIZ )
					),
					'type'        => 'string',
					'format'      => 'uri',
					'example'     => 'https://example.com/certificate.pdf',
				],
			],
			'required'   => [
				'step',
				'post_type',
				'step_name',
				'step_status',
				'date_started_gmt',
				'date_started',
				'date_completed_gmt',
				'date_completed',
				'awarded_certificate_url',
			],
		];
	}
}
