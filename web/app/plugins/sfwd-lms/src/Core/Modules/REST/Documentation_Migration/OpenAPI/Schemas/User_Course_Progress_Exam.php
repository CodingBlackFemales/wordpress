<?php
/**
 * LearnDash User Course Progress Exam OpenAPI Schema Class.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Schemas;

use LDLMS_Post_Types;
use LearnDash\Core\Mappers\Progress\Post_Type_Status;

/**
 * Class that provides LearnDash User Course Progress Exam OpenAPI schema.
 *
 * @since 5.0.0
 */
class User_Course_Progress_Exam {
	/**
	 * Returns the OpenAPI response schema for a LearnDash User Course Progress Exam.
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
		// Get exam statuses.
		$exam_statuses = Post_Type_Status::get_statuses( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::EXAM ) );

		return [
			'type'       => 'object',
			'properties' => [
				'id'                        => [
					'description' => sprintf(
						// translators: placeholder: Exam.
						esc_html__( '%s ID', 'learndash' ),
						learndash_get_custom_label( LDLMS_Post_Types::EXAM )
					),
					'type'        => 'integer',
					'example'     => 123,
				],
				'course_id'                 => [
					'description' => sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'%s ID',
							'placeholder: Course',
							'learndash'
						),
						learndash_get_custom_label( 'course' )
					),
					'type'        => 'integer',
					'example'     => 456,
				],
				'user_id'                   => [
					'description' => esc_html__( 'User ID', 'learndash' ),
					'type'        => 'integer',
					'example'     => 789,
				],
				'title'                     => [
					'description' => sprintf(
						// translators: placeholder: Exam.
						esc_html__( '%s title', 'learndash' ),
						learndash_get_custom_label( LDLMS_Post_Types::EXAM )
					),
					'type'        => 'string',
					'example'     => 'Exam Title',
				],
				'status'                    => [
					'description' => sprintf(
						// translators: placeholder: Exam.
						esc_html__( '%s status', 'learndash' ),
						learndash_get_custom_label( LDLMS_Post_Types::EXAM )
					),
					'type'        => 'string',
					'enum'        => array_keys( $exam_statuses ),
					'example'     => 'completed',
				],
				'status_label'              => [
					'description' => sprintf(
						// translators: placeholder: Exam.
						esc_html__( '%s status label', 'learndash' ),
						learndash_get_custom_label( LDLMS_Post_Types::EXAM )
					),
					'type'        => 'string',
					'example'     => 'Completed',
				],
				'date_started_gmt'          => [
					'description' => esc_html__( 'Date started in GMT', 'learndash' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'example'     => '2025-10-07T18:06:03',
				],
				'date_started'              => [
					'description' => esc_html__( 'Date started', 'learndash' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'example'     => '2025-10-07T18:06:03',
				],
				'date_completed_gmt'        => [
					'description' => esc_html__( 'Date completed in GMT', 'learndash' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'example'     => '2025-10-07T18:06:03',
				],
				'date_completed'            => [
					'description' => esc_html__( 'Date completed', 'learndash' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'example'     => '2025-10-07T18:06:03',
				],
				'questions_amount'          => [
					'description' => sprintf(
						// translators: placeholder: Questions.
						esc_html__( 'Total of %s', 'learndash' ),
						learndash_get_custom_label( 'questions' )
					),
					'type'        => 'integer',
					'example'     => 10,
				],
				'questions_total_correct'   => [
					'description' => sprintf(
						// translators: placeholder: Questions.
						esc_html__( 'Total of %s answered correctly', 'learndash' ),
						learndash_get_custom_label( 'questions' )
					),
					'type'        => 'integer',
					'example'     => 5,
				],
				'questions_total_incorrect' => [
					'description' => sprintf(
						// translators: placeholder: Questions.
						esc_html__( 'Total of %s answered incorrectly', 'learndash' ),
						learndash_get_custom_label( 'questions' )
					),
					'type'        => 'integer',
					'example'     => 5,
				],
				'questions_success_rate'    => [
					'description' => sprintf(
						// translators: placeholder: Questions.
						esc_html__( 'Success rate of %s answered correctly', 'learndash' ),
						learndash_get_custom_label( 'questions' )
					),
					'type'        => 'number',
					'example'     => 50.0,
				],
			],
			'required'   => [
				'id',
				'course_id',
				'user_id',
				'title',
				'status',
				'status_label',
				'questions_amount',
				'questions_total_correct',
				'questions_total_incorrect',
				'questions_success_rate',
			],
		];
	}
}
