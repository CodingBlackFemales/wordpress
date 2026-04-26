<?php
/**
 * LearnDash Course OpenAPI Schema Class.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Schemas;

/**
 * Class that provides LearnDash Course OpenAPI schema.
 *
 * @since 4.25.2
 */
class Course extends WP_Post {
	/**
	 * Returns the OpenAPI response schema for a LearnDash Course.
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

		$course_singular_lowercase = learndash_get_custom_label_lower( 'course' );
		$course_plural_lowercase   = learndash_get_custom_label_lower( 'courses' );
		$course_singular           = learndash_get_custom_label( 'course' );
		$exam_singular_lowercase   = learndash_get_custom_label_lower( 'exam' );

		// Add LearnDash Course specific properties based on actual API response.
		$course_properties = [
			// Course pricing and enrollment.
			'price_type'                          => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( 'The %s price type. See ldlms/v2/price-types for available price types.', 'learndash' ),
					$course_singular_lowercase
				),
				'example'     => 'paynow',
			],
			'price_type_paynow_price'             => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( 'The pay now price for the %s.', 'learndash' ),
					$course_singular_lowercase
				),
				'example'     => '90.12',
			],
			'price_type_paynow_enrollment_url'    => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase, plural) */
					__( 'The enrollment URL for the pay now %s.', 'learndash' ),
					$course_plural_lowercase
				),
				'example'     => '',
			],
			'price_type_subscribe_price'          => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( 'The subscription price for the %s.', 'learndash' ),
					$course_singular_lowercase
				),
				'example'     => '',
			],
			'trial_price'                         => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( 'The trial price for the %s.', 'learndash' ),
					$course_singular_lowercase
				),
				'example'     => '',
			],
			'price_type_subscribe_enrollment_url' => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase, plural) */
					__( 'The enrollment URL for subscription %s.', 'learndash' ),
					$course_plural_lowercase
				),
				'example'     => '',
			],
			'price_type_closed_price'             => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase, plural) */
					__( 'The price for closed %s.', 'learndash' ),
					$course_plural_lowercase
				),
				'example'     => '',
			],
			'price_type_closed_custom_button_url' => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase, plural) */
					__( 'The custom button URL for closed %s.', 'learndash' ),
					$course_plural_lowercase
				),
				'example'     => '',
			],

			// Course materials.
			'materials_enabled'                   => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( 'Whether %s materials are enabled.', 'learndash' ),
					$course_singular_lowercase
				),
				'example'     => false,
			],
			'materials'                           => [
				'type'        => 'object',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( '%s materials information.', 'learndash' ),
					$course_singular
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

			// Course exam and progression.
			'exam_challenge'                      => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %1$s: Exam label (lowercase), %2$s: Course label (lowercase) */
					__( 'The ID of the set %1$s for the %2$s.', 'learndash' ),
					$exam_singular_lowercase,
					$course_singular_lowercase
				),
				'example'     => 0,
			],
			'disable_content_table'               => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( 'Whether the %s content is always visible or only visible to enrollees. False if only visible to enrollees.', 'learndash' ),
					$course_singular_lowercase
				),
				'example'     => false,
			],
			'lessons_per_page'                    => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %1$s: Course label (lowercase), %2$s: Lesson label (lowercase) */
					__( 'Whether a %1$s-specific %2$s and %3$s per page setting is enabled.', 'learndash' ),
					$course_singular_lowercase,
					learndash_get_custom_label_lower( 'lesson' ),
					learndash_get_custom_label_lower( 'topic' )
				),
				'example'     => false,
			],
			'lesson_per_page_custom'              => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %s: Lesson label (lowercase) */
					__( 'Custom %s per page setting.', 'learndash' ),
					learndash_get_custom_label_lower( 'lesson' )
				),
				'example'     => 1,
			],
			'topic_per_page_custom'               => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %s: Topic label (lowercase) */
					__( 'Custom %s per page setting.', 'learndash' ),
					learndash_get_custom_label_lower( 'topic' )
				),
				'example'     => 2,
			],

			// Course prerequisites.
			'requirements_for_enrollment'         => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %1$s: Course label (lowercase), %2$s: Course label (lowercase, plural) */
					__( 'Requirements for %1$s enrollment. Empty string means no restrictions and students have access without prerequisite restrictions. "course_prerequisite_enabled" means prerequisite %2$s must be completed first. "course_points_enabled" means a specific number of %1$s points are required for access.', 'learndash' ),
					$course_singular_lowercase,
					$course_plural_lowercase
				),
				'example'     => '',
				'enum'        => [ '', 'course_prerequisite_enabled', 'course_points_enabled' ],
			],
			'prerequisite_enabled'                => [
				'type'        => 'boolean',
				'description' => __( 'Whether prerequisites are the set requirement for access.', 'learndash' ),
				'example'     => false,
			],
			'prerequisite_compare'                => [
				'type'        => 'string',
				'description' => __( 'How to compare prerequisites (ANY or ALL).', 'learndash' ),
				'enum'        => [ 'ANY', 'ALL' ],
				'example'     => 'ANY',
			],
			'prerequisites'                       => [
				'type'        => 'array',
				'description' => sprintf(
					/* translators: %1$s: Course label (lowercase) */
					__( 'List of %1$s prerequisites by %1$s ID.', 'learndash' ),
					$course_singular_lowercase
				),
				'items'       => [
					'type' => 'integer',
				],
				'example'     => [ 1, 2, 3 ],
			],

			// Course points.
			'points_enabled'                      => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( 'Whether %s points are the set requirement for access.', 'learndash' ),
					$course_singular_lowercase
				),
				'example'     => false,
			],
			'points_access'                       => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( 'Points required for %s access.', 'learndash' ),
					$course_singular_lowercase
				),
				'example'     => '',
			],
			'points_amount'                       => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( 'Points awarded for %s completion.', 'learndash' ),
					$course_singular_lowercase
				),
				'example'     => '',
			],

			// Course progression and expiration.
			'progression_disabled'                => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( 'Whether %s progression is is linear or freeform. False if linear, true if freeform.', 'learndash' ),
					$course_singular_lowercase
				),
				'example'     => false,
			],
			'expire_access'                       => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( 'Whether %s access expires.', 'learndash' ),
					$course_singular_lowercase
				),
				'example'     => true,
			],
			'expire_access_days'                  => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( 'Number of days until %s access expires after enrollment.', 'learndash' ),
					$course_singular_lowercase
				),
				'example'     => 40,
			],
			'expire_access_delete_progress'       => [
				'type'        => 'boolean',
				'description' => __( 'Whether to delete progress when access expires.', 'learndash' ),
				'example'     => false,
			],

			// Course password.
			'password'                            => [
				'type'        => 'string',
				'description' => __( 'Password if password protected.', 'learndash' ),
				'example'     => '',
			],

			// Course taxonomies.
			'ld_course_category'                  => [
				'type'        => 'array',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( '%s categories.', 'learndash' ),
					$course_singular_lowercase
				),
				'items'       => [
					'type' => 'integer',
				],
				'example'     => [],
			],
			'ld_course_tag'                       => [
				'type'        => 'array',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( '%s tags.', 'learndash' ),
					$course_singular_lowercase
				),
				'items'       => [
					'type' => 'integer',
				],
				'example'     => [],
			],

			// Course links (extending WP_Post _links).
			'_links'                              => [
				'type'        => 'object',
				'description' => sprintf(
					/* translators: %s: Course label (lowercase) */
					__( 'HAL links for the %s (extends WP_Post links).', 'learndash' ),
					$course_singular_lowercase
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
					'price-types'         => [
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
					'prerequisites'       => [
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
					'steps'               => [
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
					'users'               => [
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
					'groups'              => [
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
					'wp:term'             => [
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

		$links = $course_properties['_links']['properties'];
		unset( $course_properties['_links'] );

		// Merge the base schema properties with course-specific properties.
		$base_schema['properties'] = array_merge(
			$base_schema['properties'],
			$course_properties
		);

		$base_links = is_array( $base_schema['properties']['_links']['properties'] ) ? $base_schema['properties']['_links']['properties'] : [];

		// Merge the _links properties to extend WP_Post links instead of overwriting them.
		$base_schema['properties']['_links']['properties'] = array_merge(
			$base_links,
			$links
		);

		// Add course-specific required fields.
		$base_schema['required'] = array_unique(
			array_merge(
				$base_schema['required'],
				[
					'price_type',
					'price_type_paynow_price',
					'price_type_paynow_enrollment_url',
					'price_type_subscribe_price',
					'trial_price',
					'price_type_subscribe_enrollment_url',
					'price_type_closed_price',
					'price_type_closed_custom_button_url',
					'materials_enabled',
					'materials',
					'exam_challenge',
					'disable_content_table',
					'lessons_per_page',
					'lesson_per_page_custom',
					'topic_per_page_custom',
					'requirements_for_enrollment',
					'prerequisite_enabled',
					'prerequisite_compare',
					'prerequisites',
					'points_enabled',
					'points_access',
					'points_amount',
					'progression_disabled',
					'expire_access',
					'expire_access_days',
					'expire_access_delete_progress',
					'password',
					'ld_course_category',
					'ld_course_tag',
				]
			)
		);

		return $base_schema;
	}
}
