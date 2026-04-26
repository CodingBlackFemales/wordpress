<?php
/**
 * LearnDash Essay OpenAPI Schema Class.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Schemas;

/**
 * Class that provides LearnDash Essay OpenAPI schema.
 *
 * @since 5.0.0
 */
class Essay extends WP_Post {
	/**
	 * Returns the OpenAPI response schema for a LearnDash Essay.
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

		$course_singular_lowercase = learndash_get_custom_label_lower( 'course' );
		$lesson_singular_lowercase = learndash_get_custom_label_lower( 'lesson' );
		$topic_singular_lowercase  = learndash_get_custom_label_lower( 'topic' );
		$essay_singular            = learndash_get_custom_label( 'essay' );
		$essay_singular_lowercase  = learndash_get_custom_label_lower( 'essay' );

		// Add LearnDash Essay specific properties based on actual API response.
		$essay_properties = [
			// Essay relationships.
			'course'         => [
				'type'        => 'integer',
				'description' => sprintf(
					// translators: %s: Course label (lowercase).
					__( '%s ID', 'learndash' ),
					$course_singular_lowercase
				),
				'default'     => 0,
				'example'     => 123,
			],
			'lesson'         => [
				'type'        => 'integer',
				'description' => sprintf(
					// translators: %s: Lesson label (lowercase).
					__( '%s ID', 'learndash' ),
					$lesson_singular_lowercase
				),
				'default'     => 0,
				'example'     => 456,
			],
			'topic'          => [
				'type'        => 'integer',
				'description' => sprintf(
					// translators: %s: Topic label (lowercase).
					__( '%s ID', 'learndash' ),
					$topic_singular_lowercase
				),
				'default'     => 0,
				'example'     => 789,
			],

			// Essay scoring.
			'points_max'     => [
				'type'        => 'number',
				'format'      => 'float',
				'description' => sprintf(
					// translators: %s: singular essay label.
					__( '%s Points Maximum', 'learndash' ),
					$essay_singular
				),
				'default'     => 0.0,
				'example'     => 10.0,
			],
			'points_awarded' => [
				'type'        => 'number',
				'format'      => 'float',
				'description' => sprintf(
					// translators: %s: singular essay label.
					__( '%s Points Awarded', 'learndash' ),
					$essay_singular
				),
				'default'     => 0.0,
				'example'     => 8.5,
			],

			// Essay links.
			'_links'         => [
				'type'        => 'object',
				'description' => __( 'Links to related resources.', 'learndash' ),
				'properties'  => [
					'course' => [
						'type'        => 'object',
						'description' => sprintf(
							// translators: %1$s: Course label (lowercase). %2$s: Essay label.
							__( 'Link to the %1$s this %2$s belongs to.', 'learndash' ),
							$course_singular_lowercase,
							$essay_singular_lowercase
						),
						'properties'  => [
							'href'       => [
								'type'    => 'string',
								'format'  => 'uri',
								'example' => 'https://example.com/wp-json/ldlms/v2/courses/123',
							],
							'embeddable' => [
								'type'    => 'boolean',
								'example' => true,
							],
						],
					],
					'lesson' => [
						'type'        => 'object',
						'description' => sprintf(
							// translators: %1$s: Lesson label (lowercase). %2$s: Essay label.
							__( 'Link to the %1$s this %2$s belongs to.', 'learndash' ),
							$lesson_singular_lowercase,
							$essay_singular_lowercase
						),
						'properties'  => [
							'href'       => [
								'type'    => 'string',
								'format'  => 'uri',
								'example' => 'https://example.com/wp-json/ldlms/v2/lessons/456?course=123',
							],
							'embeddable' => [
								'type'    => 'boolean',
								'example' => true,
							],
						],
					],
					'topic'  => [
						'type'        => 'object',
						'description' => sprintf(
							// translators: %1$s: Topic label (lowercase). %2$s: Essay label.
							__( 'Link to the %1$s this %2$s belongs to.', 'learndash' ),
							$topic_singular_lowercase,
							$essay_singular_lowercase
						),
						'properties'  => [
							'href'       => [
								'type'    => 'string',
								'format'  => 'uri',
								'example' => 'https://example.com/wp-json/ldlms/v2/topics/789?course=123&lesson=456',
							],
							'embeddable' => [
								'type'    => 'boolean',
								'example' => true,
							],
						],
					],
				],
			],
		];

		$links = $essay_properties['_links']['properties'];
		unset( $essay_properties['_links'] );

		// Merge the base schema properties with essay-specific properties.
		$base_schema['properties'] = array_merge(
			$base_schema['properties'],
			$essay_properties
		);

		$base_links = is_array( $base_schema['properties']['_links']['properties'] ) ? $base_schema['properties']['_links']['properties'] : [];

		// Merge the _links properties to extend WP_Post links instead of overwriting them.
		$base_schema['properties']['_links']['properties'] = array_merge(
			$base_links,
			$links
		);

		// Add essay-specific required fields.
		$base_schema['required'] = array_unique(
			array_merge(
				$base_schema['required'],
				[
					'course',
					'lesson',
					'topic',
					'points_max',
					'points_awarded',
				]
			)
		);

		return $base_schema;
	}
}
