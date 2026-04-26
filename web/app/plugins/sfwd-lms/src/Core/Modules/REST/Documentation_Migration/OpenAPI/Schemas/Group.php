<?php
/**
 * LearnDash Group OpenAPI Schema Trait.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Schemas;

use LDLMS_Post_Types;

/**
 * Trait that provides LearnDash Group OpenAPI schema.
 *
 * @since 4.25.2
 */
class Group extends WP_Post {
	/**
	 * Returns the OpenAPI response schema for a LearnDash Group.
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

		// Remove some properties that are not relevant to the Group schema.

		foreach ( [ 'comment_status', 'excerpt', 'format', 'meta', 'ping_status', 'sticky' ] as $property ) {
			$feature_map = [
				'comment_status' => 'comments',
				'excerpt'        => 'excerpt',
				'format'         => 'post-formats',
				'meta'           => 'custom-fields',
				'ping_status'    => 'trackbacks',
			];

			if (
				in_array( $property, $feature_map, true ) &&
				post_type_supports( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::GROUP ), $feature_map[ $property ] )
			) {
				continue;
			}

			unset( $base_schema['properties'][ $property ] );
			unset( $base_schema['required'][ $property ] );
			unset( $base_schema['properties'][ $property ]['example'] );
		}

		$group_singular_lowercase = learndash_get_custom_label_lower( 'group' );
		$group_singular           = learndash_get_custom_label( 'group' );
		$course_plural_lowercase  = learndash_get_custom_label_lower( 'courses' );

		// Add LearnDash Group specific properties based on actual API response.
		$group_properties = [
			// Group materials.
			'materials_enabled'                         => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( 'Whether %s materials are enabled.', 'learndash' ),
					$group_singular_lowercase
				),
				'example'     => false,
			],
			'materials'                                 => [
				'type'        => 'object',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( '%s materials information.', 'learndash' ),
					$group_singular
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

			// Group certificate.
			'certificate'                               => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( 'The ID of the certificate associated with the %s.', 'learndash' ),
					$group_singular_lowercase
				),
				'example'     => 0,
			],

			// Group content visibility.
			'disable_content_table'                     => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( 'Whether the %s content is always visible or only visible to members. False if only visible to members.', 'learndash' ),
					$group_singular_lowercase
				),
				'example'     => false,
			],

			'group_courses_order_enabled'               => [
				'type'        => 'boolean',
				'description' => sprintf(
					/* translators: %1$s: Group label (lowercase), %2$s: Course label (lowercase, plural) */
					__( 'Whether a custom %1$s %2$s order is enabled.', 'learndash' ),
					$group_singular_lowercase,
					$course_plural_lowercase
				),
				'example'     => false,
			],

			// Group course ordering.
			'courses_orderby'                           => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %1$s: Course label (lowercase, plural), %2$s: Group label (lowercase) */
					__( 'How to order %1$s within the %2$s. Empty string means use the default orderby value.', 'learndash' ),
					$course_plural_lowercase,
					$group_singular_lowercase
				),
				'example'     => '',
				'enum'        => [ '', 'title', 'date', 'menu_order' ],
			],
			'courses_order'                             => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %1$s: Course label (lowercase, plural), %2$s: Group label (lowercase) */
					__( 'The order direction for %1$s within the %2$s. Empty string means use the default order.', 'learndash' ),
					$course_plural_lowercase,
					$group_singular_lowercase
				),
				'example'     => '',
				'enum'        => [ '', 'ASC', 'DESC' ],
			],

			// Group pricing and enrollment.
			'price_type'                                => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( 'The %s price type. See ldlms/v2/price-types for available price types.', 'learndash' ),
					$group_singular_lowercase
				),
				'example'     => 'free',
			],
			'price_type_paynow_price'                   => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( 'The pay now price for the %s.', 'learndash' ),
					$group_singular_lowercase
				),
				'example'     => '',
			],
			'group_price_type_paynow_enrollment_url'    => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( 'The enrollment URL for the pay now %s.', 'learndash' ),
					$group_singular_lowercase
				),
				'example'     => '',
			],
			'price_type_subscribe_price'                => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( 'The subscription price for the %s.', 'learndash' ),
					$group_singular_lowercase
				),
				'example'     => '',
			],
			'trial_price'                               => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( 'The trial price for the %s.', 'learndash' ),
					$group_singular_lowercase
				),
				'example'     => '',
			],
			'group_price_type_subscribe_enrollment_url' => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( 'The enrollment URL for subscription %s.', 'learndash' ),
					$group_singular_lowercase
				),
				'example'     => '',
			],
			'price_type_closed_price'                   => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( 'The price for closed %s.', 'learndash' ),
					$group_singular_lowercase
				),
				'example'     => '',
			],
			'price_type_closed_custom_button_url'       => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( 'The custom button URL for closed %s.', 'learndash' ),
					$group_singular_lowercase
				),
				'example'     => '',
			],

			// Group dates and limits.
			'group_start_date'                          => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( 'The start date for the %s as a unix timestamp. 0 means no start date.', 'learndash' ),
					$group_singular_lowercase
				),
				'example'     => '0',
			],
			'group_end_date'                            => [
				'type'        => 'string',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( 'The end date for the %s as a unix timestamp. 0 means no end date.', 'learndash' ),
					$group_singular_lowercase
				),
				'example'     => '0',
			],
			'group_seats_limit'                         => [
				'type'        => 'integer',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( 'The maximum number of students allowed in the %s. 0 means no limit. Admins can enroll students even if the limit is reached.', 'learndash' ),
					$group_singular_lowercase
				),
				'example'     => 0,
			],

			// Group taxonomies.
			'categories'                                => [
				'type'        => 'array',
				'description' => __( 'The terms assigned to the post in the category taxonomy.', 'learndash' ),
				'items'       => [
					'type' => 'integer',
				],
				'example'     => [],
			],
			'tags'                                      => [
				'type'        => 'array',
				'description' => __( 'The terms assigned to the post in the post_tag taxonomy.', 'learndash' ),
				'items'       => [
					'type' => 'integer',
				],
				'example'     => [],
			],
			'ld_group_category'                         => [
				'type'        => 'array',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( '%s categories term IDs.', 'learndash' ),
					$group_singular_lowercase
				),
				'items'       => [
					'type' => 'integer',
				],
				'example'     => [],
			],
			'ld_group_tag'                              => [
				'type'        => 'array',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( '%s tags term IDs.', 'learndash' ),
					$group_singular_lowercase
				),
				'items'       => [
					'type' => 'integer',
				],
				'example'     => [],
			],

			// Group links (extending WP_Post _links).
			'_links'                                    => [
				'type'        => 'object',
				'description' => sprintf(
					/* translators: %s: Group label (lowercase) */
					__( 'HAL links for the %s (extends WP_Post links).', 'learndash' ),
					$group_singular_lowercase
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
					'leaders'             => [
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
					'courses'             => [
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

		$links = $group_properties['_links']['properties'];
		unset( $group_properties['_links'] );

		// Merge the base schema properties with group-specific properties.
		$base_schema['properties'] = array_merge(
			$base_schema['properties'],
			$group_properties
		);

		$base_links = is_array( $base_schema['properties']['_links']['properties'] ) ? $base_schema['properties']['_links']['properties'] : [];

		// Merge the _links properties to extend WP_Post links instead of overwriting them.
		$base_schema['properties']['_links']['properties'] = array_merge(
			$base_links,
			$links
		);

		// Add group-specific required fields.
		$base_schema['required'] = array_unique(
			array_merge(
				$base_schema['required'],
				[
					'materials_enabled',
					'materials',
					'certificate',
					'disable_content_table',
					'group_courses_order_enabled',
					'courses_orderby',
					'courses_order',
					'price_type',
					'price_type_paynow_price',
					'group_price_type_paynow_enrollment_url',
					'price_type_subscribe_price',
					'trial_price',
					'group_price_type_subscribe_enrollment_url',
					'price_type_closed_price',
					'price_type_closed_custom_button_url',
					'group_start_date',
					'group_end_date',
					'group_seats_limit',
					'courses_per_page_enabled',
					'categories',
					'tags',
					'ld_group_category',
					'ld_group_tag',
				]
			)
		);

		return $base_schema;
	}
}
