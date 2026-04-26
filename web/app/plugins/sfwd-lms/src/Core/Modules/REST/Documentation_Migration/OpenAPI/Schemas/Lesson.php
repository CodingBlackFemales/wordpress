<?php
/**
 * LearnDash Lesson OpenAPI Schema Trait.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Schemas;

/**
 * Trait that provides LearnDash Lesson OpenAPI schema.
 *
 * @since 4.25.2
 */
class Lesson extends WP_Post {
	/**
	 * Returns the OpenAPI response schema for a LearnDash Lesson.
	 *
	 * @since 4.25.2
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

		$lesson_singular_lowercase = learndash_get_custom_label_lower( 'lesson' );
		$course_singular_lowercase = learndash_get_custom_label_lower( 'course' );
		$lesson_singular           = learndash_get_custom_label( 'lesson' );

		// Add LearnDash Lesson specific properties based on actual API response.
		$lesson_properties = [
			// Lesson materials.
			'materials_enabled'                  => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Whether %s materials are enabled.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => false,
			],
			'materials'                          => [
				'type'        => 'object',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( '%s materials information.', 'learndash' ),
					$lesson_singular
				),
				'properties'  => [
					'rendered' => [
						'type'        => 'string',
						'description' => __( 'The rendered materials content.', 'learndash' ),
						'example'     => '',
						'readOnly'    => true,
					],
				],
			],

			// Lesson video settings.
			'video_enabled'                      => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Whether video progression is enabled for the %s.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => false,
			],
			'video_url'                          => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Video URL for the %s.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => '',
			],
			'video_shown'                        => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'When to show the video in the %s. BEFORE to show before the completing sub-steps, AFTER to show after the completing sub-steps.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'enum'        => [ 'BEFORE', 'AFTER' ],
				'example'     => 'BEFORE',
			],

			// Video completion settings.
			'video_auto_complete'                => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Whether the %s automatically completes when the video finishes. Only applies when video_shown is AFTER.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => false,
			],
			'video_auto_complete_delay'          => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Delay before auto-completing the %s after video ends. Only applies when video_shown is AFTER.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => '0',
			],
			'video_show_complete_button'         => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Whether to show the "mark complete" button in the %s even if it is not clickable. Only applies when video_shown is AFTER.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => false,
			],

			// Video player settings.
			'video_auto_start'                   => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Whether video auto-starts in the %s.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => false,
			],
			'video_show_controls'                => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Whether to show video controls in the %s.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => false,
			],
			'video_focus_pause'                  => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Whether video pauses when tab loses focus in the %s.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => false,
			],
			'video_resume'                       => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Whether video resumes from last position in the %s.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => false,
			],

			// Assignment settings.
			'assignment_upload_enabled'          => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Whether assignment uploads are enabled for the %s.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => false,
			],
			'assignment_points_enabled'          => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Whether assignment points are enabled for the %s.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => false,
			],
			'assignment_points_amount'           => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Points awarded for %s assignment completion.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => '',
			],
			'assignment_auto_approve'            => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Whether assignments are auto-approved for the %s. False if manual approval is required.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => false,
			],
			'assignment_deletion_enabled'        => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Whether assignment deletion is enabled for the %s.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => false,
			],

			// Assignment upload limits.
			'assignment_upload_limit_extensions' => [
				'type'        => 'array',
				'items'       => [
					'type' => 'string',
				],
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Allowed file extensions for %s assignments. Can be an array of file extensions (e.g., ["pdf", "xls"]) or an empty string for all default allowed file extensions.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => [ 'pdf', 'xls' ],
			],
			'assignment_upload_limit_size'       => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Maximum file size for %s assignments in bytes.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => '1024',
			],
			'assignment_upload_limit_count'      => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Maximum number of files for %s assignments.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => 1,
			],

			// Timer settings.
			'forced_timer_enabled'               => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Whether a forced timer is enabled for the %s.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => false,
			],
			'forced_timer_amount'                => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Forced timer duration for the %s.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => '',
			],

			// Lesson visibility and access.
			'course'                             => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( 'The ID of the parent %s.', 'learndash' ),
					$course_singular_lowercase
				),
				'example'     => 277,
			],
			'is_sample'                          => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Whether the %s is a sample lesson.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => false,
			],
			'visible_type'                       => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %1$s: Lesson label (lowercase), %2$s: Course label (lowercase) */
					__( 'Visibility type for the %1$s. Empty string means always visible, visible_after means visible after a set number of days after %2$s enrollment, visible_after_specific_date means visible after a specific date.', 'learndash' ),
					$lesson_singular_lowercase,
					$course_singular_lowercase
				),
				'example'     => '',
				'enum'        => [ '', 'visible_after', 'visible_after_specific_date' ],
			],

			// External lesson settings.
			'is_external'                        => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Whether the %s is external.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => false,
			],
			'external_type'                      => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'External type for the %s.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => 'virtual',
				'enum'        => [ 'virtual', 'external' ],
			],
			'external_require_attendance'        => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Attendance requirement for external %s.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => '',
				'enum'        => [ '', 'yes' ],
			],

			// Visibility timing.
			'visible_after'                      => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %1$s: Course label (lowercase), %2$s: Lesson label (lowercase) */
					__( 'The number of days after %1$s enrollment when the %2$s becomes visible.', 'learndash' ),
					$course_singular_lowercase,
					$lesson_singular_lowercase
				),
				'example'     => '',
			],
			'visible_after_specific_date'        => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Specific date when the %s becomes visible.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'example'     => '',
			],

			// Lesson taxonomies.
			'ld_lesson_category'                 => [
				'type'        => 'array',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( '%s categories.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'items'       => [
					'type' => 'integer',
				],
				'example'     => [],
			],
			'ld_lesson_tag'                      => [
				'type'        => 'array',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( '%s tags.', 'learndash' ),
					$lesson_singular_lowercase
				),
				'items'       => [
					'type' => 'integer',
				],
				'example'     => [],
			],

			// Lesson links (extending WP_Post _links).
			'_links'                             => [
				'type'        => 'object',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'HAL links for the %s (extends WP_Post links).', 'learndash' ),
					$lesson_singular_lowercase
				),
				'properties'  => [
					'about'           => [
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
					'version-history' => [
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
					'wp:attachment'   => [
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
					'wp:term'         => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'taxonomy'   => [
									'type'        => 'string',
									'description' => __( 'The taxonomy name.', 'learndash' ),
								],
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
					'curies'          => [
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

		$links = $lesson_properties['_links']['properties'];
		unset( $lesson_properties['_links'] );

		// Merge the base schema properties with lesson-specific properties.
		$base_schema['properties'] = array_merge(
			$base_schema['properties'],
			$lesson_properties
		);

		$base_links = is_array( $base_schema['properties']['_links']['properties'] ) ? $base_schema['properties']['_links']['properties'] : [];

		// Merge the _links properties to extend WP_Post links instead of overwriting them.
		$base_schema['properties']['_links']['properties'] = array_merge(
			$base_links,
			$links
		);

		// Add lesson-specific required fields.
		$base_schema['required'] = array_unique(
			array_merge(
				$base_schema['required'],
				[
					'materials_enabled',
					'materials',
					'video_enabled',
					'video_url',
					'video_shown',
					'video_auto_complete',
					'video_auto_complete_delay',
					'video_show_complete_button',
					'video_auto_start',
					'video_show_controls',
					'video_focus_pause',
					'video_resume',
					'assignment_upload_enabled',
					'assignment_points_enabled',
					'assignment_points_amount',
					'assignment_auto_approve',
					'assignment_deletion_enabled',
					'assignment_upload_limit_extensions',
					'assignment_upload_limit_size',
					'assignment_upload_limit_count',
					'forced_timer_enabled',
					'forced_timer_amount',
					'course',
					'is_sample',
					'visible_type',
					'is_external',
					'external_type',
					'external_require_attendance',
					'visible_after',
					'visible_after_specific_date',
					'ld_lesson_category',
					'ld_lesson_tag',
				]
			)
		);

		return $base_schema;
	}
}
