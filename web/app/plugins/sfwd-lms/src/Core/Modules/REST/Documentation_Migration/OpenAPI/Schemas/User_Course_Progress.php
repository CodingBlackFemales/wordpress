<?php
/**
 * LearnDash User Course Progress OpenAPI Schema Class.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Schemas;

use LDLMS_Post_Types;
use LearnDash\Core\Mappers\Progress\Post_Type_Status;

/**
 * Class that provides LearnDash User Course Progress OpenAPI schema.
 *
 * @since 5.0.0
 */
class User_Course_Progress {
	/**
	 * Returns the OpenAPI response schema for a LearnDash User Course Progress.
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
		$course_singular_lowercase = learndash_get_custom_label_lower( LDLMS_Post_Types::COURSE );
		$course_singular           = learndash_get_custom_label( LDLMS_Post_Types::COURSE );

		return [
			'type'       => 'object',
			'properties' => [
				'course'             => [
					'type'        => 'integer',
					'description' => sprintf(
						// translators: %s: Course label.
						__( '%s ID', 'learndash' ),
						$course_singular
					),
					'example'     => 456,
				],
				'progress_status'    => [
					'type'        => 'string',
					'description' => sprintf(
						// translators: %s: Course label.
						__( '%s Progress Status', 'learndash' ),
						$course_singular
					),
					'enum'        => array_keys(
						Post_Type_Status::get_statuses(
							learndash_get_post_type_slug( LDLMS_Post_Types::COURSE )
						)
					),
					'example'     => 'in_progress',
				],
				'last_step'          => [
					'type'        => 'integer',
					'description' => __( 'Last completed step', 'learndash' ),
					'example'     => 99,
				],
				'steps_completed'    => [
					'type'        => 'integer',
					'description' => __( 'Total completed steps', 'learndash' ),
					'example'     => 1,
				],
				'steps_total'        => [
					'type'        => 'integer',
					'description' => sprintf(
						// translators: %s: Course label.
						__( 'Total %s steps', 'learndash' ),
						$course_singular_lowercase
					),
					'example'     => 3,
				],
				'date_started_gmt'   => [
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Date started in GMT', 'learndash' ),
					'example'     => '2025-10-07T18:06:03',
				],
				'date_started'       => [
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Date started', 'learndash' ),
					'example'     => '2025-10-07T18:06:03',
				],
				'date_completed_gmt' => [
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Date completed in GMT', 'learndash' ),
					'example'     => '',
				],
				'date_completed'     => [
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'Date completed', 'learndash' ),
					'example'     => '',
				],
				'_links'             => [
					'type'        => 'object',
					'description' => sprintf(
						// translators: %s: course label.
						__( 'All links for the user %s progress', 'learndash' ),
						$course_singular_lowercase
					),
					'properties'  => [
						'self'            => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'href' => [
										'type'        => 'string',
										'description' => __( 'The link URL.', 'learndash' ),
										'example'     => 'https://localhost/wp-json/ldlms/v2/users/123/course-progress/456',
									],
								],
							],
						],
						'collection'      => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'href' => [
										'type'        => 'string',
										'description' => __( 'The link URL.', 'learndash' ),
										'example'     => 'https://localhost/wp-json/ldlms/v2/users/123/course-progress',
									],
								],
							],
						],
						'steps'           => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'href'       => [
										'type'        => 'string',
										'description' => __( 'The link URL.', 'learndash' ),
										'example'     => 'https://localhost/wp-json/ldlms/v2/users/123/course-progress/456/steps',
									],
									'embeddable' => [
										'type'        => 'boolean',
										'description' => __( 'Whether the link is embeddable.', 'learndash' ),
										'example'     => true,
									],
								],
							],
						],
						'progress_status' => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'href'       => [
										'type'        => 'string',
										'description' => __( 'The link URL.', 'learndash' ),
										'example'     => 'https://localhost/wp-json/ldlms/v2/progress-status/in-progress',
									],
									'embeddable' => [
										'type'        => 'boolean',
										'description' => __( 'Whether the link is embeddable.', 'learndash' ),
										'example'     => false,
									],
								],
							],
						],
					],
				],
			],
			'required'   => [
				'course',
				'progress_status',
				'last_step',
				'steps_completed',
				'steps_total',
				'date_started_gmt',
				'date_started',
				'date_completed_gmt',
				'date_completed',
				'_links',
			],
		];
	}
}
