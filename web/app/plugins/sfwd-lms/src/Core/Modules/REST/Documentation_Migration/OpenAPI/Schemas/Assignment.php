<?php
/**
 * LearnDash Assignment OpenAPI Schema Class.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Schemas;

/**
 * Class that provides LearnDash Assignment OpenAPI schema.
 *
 * @since 4.25.2
 */
class Assignment extends WP_Post {
	/**
	 * Returns the OpenAPI response schema for a LearnDash Assignment.
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

		$course_singular_lowercase     = learndash_get_custom_label_lower( 'course' );
		$lesson_singular_lowercase     = learndash_get_custom_label_lower( 'lesson' );
		$topic_singular_lowercase      = learndash_get_custom_label_lower( 'topic' );
		$assignment_singular_lowercase = learndash_get_custom_label_lower( 'assignment' );

		// Add LearnDash Assignment specific properties based on actual API response.
		$assignment_properties = [
			// Assignment relationships.
			'course'          => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %1$s: Course label (lowercase), %2$s: Assignment label (lowercase) */
					__( 'The ID of the %1$s this %2$s belongs to.', 'learndash' ),
					$course_singular_lowercase,
					$assignment_singular_lowercase
				),
				'example'     => 489,
			],
			'lesson'          => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %1$s: Lesson label (lowercase), %2$s: Assignment label (lowercase) */
					__( 'The ID of the %1$s this %2$s belongs to.', 'learndash' ),
					$lesson_singular_lowercase,
					$assignment_singular_lowercase
				),
				'example'     => 490,
			],
			'topic'           => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %1$s: Topic label (lowercase), %2$s: Assignment label (lowercase) */
					__( 'The ID of the %1$s this %2$s belongs to.', 'learndash' ),
					$topic_singular_lowercase,
					$assignment_singular_lowercase
				),
				'example'     => 492,
			],

			// Assignment status and approval.
			'approved_status' => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Assignment label (lowercase) */
					__( 'The approval status of the %s.', 'learndash' ),
					$assignment_singular_lowercase
				),
				'enum'        => [ 'approved', 'not_approved', 'pending' ],
				'example'     => 'approved',
			],

			// Assignment points.
			'points_enabled'  => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Assignment label (lowercase) */
					__( 'Whether points are enabled for this %s.', 'learndash' ),
					$assignment_singular_lowercase
				),
				'example'     => false,
			],
			'points_max'      => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %s: Assignment label (lowercase) */
					__( 'Maximum points that can be awarded for this %s.', 'learndash' ),
					$assignment_singular_lowercase
				),
				'example'     => 0,
			],
			'points_awarded'  => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %s: Assignment label (lowercase) */
					__( 'Points awarded for this %s.', 'learndash' ),
					$assignment_singular_lowercase
				),
				'example'     => 0,
			],

			// Assignment links (extending WP_Post _links).
			'_links'          => [
				'type'        => 'object',
				'description' => sprintf(
					/* translators: %s: Assignment label (lowercase) */
					__( 'HAL links for the %s (extends WP_Post links).', 'learndash' ),
					$assignment_singular_lowercase
				),
				'properties'  => [
					'course'          => [
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
					'lesson'          => [
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
					'topic'           => [
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
					'assignment_link' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'href'       => [
									'type'        => 'string',
									'description' => sprintf(
										/* translators: %s: Assignment label (lowercase) */
										__( 'The %s file download link URL.', 'learndash' ),
										$assignment_singular_lowercase
									),
								],
								'embeddable' => [
									'type'        => 'boolean',
									'description' => __( 'Whether the link is embeddable.', 'learndash' ),
								],
							],
						],
					],
				],
			],
		];

		$links = $assignment_properties['_links']['properties'];
		unset( $assignment_properties['_links'] );

		// Merge the base schema properties with course-specific properties.
		$base_schema['properties'] = array_merge(
			$base_schema['properties'],
			$assignment_properties
		);

		$base_links = is_array( $base_schema['properties']['_links']['properties'] ) ? $base_schema['properties']['_links']['properties'] : [];

		// Merge the _links properties to extend WP_Post links instead of overwriting them.
		$base_schema['properties']['_links']['properties'] = array_merge(
			$base_links,
			$links
		);

		// Add assignment-specific required fields.
		$base_schema['required'] = array_unique(
			array_merge(
				$base_schema['required'],
				[
					'course',
					'lesson',
					'topic',
					'approved_status',
					'points_enabled',
					'points_max',
					'points_awarded',
				]
			)
		);

		return $base_schema;
	}
}
