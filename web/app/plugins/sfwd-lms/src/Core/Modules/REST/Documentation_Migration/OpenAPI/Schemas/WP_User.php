<?php
/**
 * WordPress User OpenAPI Schema Class.
 * This is based directly on wp/v2/users/<id>.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Schemas;

/**
 * Class that provides WordPress User OpenAPI schema.
 *
 * @since 4.25.2
 */
class WP_User {
	/**
	 * Returns the OpenAPI response schema for a WordPress User.
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
		return [
			'type'       => 'object',
			'properties' => [
				'id'          => [
					'type'        => 'integer',
					'description' => __( 'Unique identifier for the user.', 'learndash' ),
					'example'     => 1,
				],
				'name'        => [
					'type'        => 'string',
					'description' => __( 'Display name for the user.', 'learndash' ),
					'example'     => 'admin',
				],
				'url'         => [
					'type'        => 'string',
					'format'      => 'uri',
					'description' => __( 'URL of the user.', 'learndash' ),
					'example'     => 'https://example.com',
				],
				'description' => [
					'type'        => 'string',
					'description' => __( 'Description of the user.', 'learndash' ),
					'example'     => '',
				],
				'link'        => [
					'type'        => 'string',
					'format'      => 'uri',
					'description' => __( 'Author URL of the user.', 'learndash' ),
					'example'     => 'https://example.com/author/admin/',
				],
				'slug'        => [
					'type'        => 'string',
					'description' => __( 'An alphanumeric identifier for the user unique to their site.', 'learndash' ),
					'example'     => 'admin',
				],
				'avatar_urls' => [
					'type'                 => 'object',
					'description'          => __( 'Avatar URLs for the user. Keys represent image sizes in pixels.', 'learndash' ),
					'additionalProperties' => [
						'type'   => 'string',
						'format' => 'uri',
					],
					'example'              => [
						'24' => 'https://example.com/avatar/user123-24x24.jpg',
						'48' => 'https://example.com/avatar/user123-48x48.jpg',
						'96' => 'https://example.com/avatar/user123-96x96.jpg',
					],
				],
				'meta'        => [
					'type'        => 'array',
					'description' => __( 'Meta fields.', 'learndash' ),
					'items'       => [
						'type' => 'object',
					],
					'example'     => [],
				],
				'_links'      => [
					'type'        => 'object',
					'description' => __( 'HAL links for the user.', 'learndash' ),
					'properties'  => [
						'self'            => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'href'        => [
										'type'        => 'string',
										'description' => __( 'The link URL.', 'learndash' ),
										'example'     => 'https://example.com/wp-json/wp/v2/users/1',
									],
									'targetHints' => [
										'type'       => 'object',
										'properties' => [
											'allow' => [
												'type'    => 'array',
												'items'   => [
													'type' => 'string',
												],
												'example' => [ 'GET' ],
											],
										],
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
										'example'     => 'https://example.com/wp-json/wp/v2/users',
									],
								],
							],
						],
						'courses'         => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'embeddable' => [
										'type'        => 'boolean',
										'description' => __( 'Whether the link is embeddable.', 'learndash' ),
										'example'     => true,
									],
									'href'       => [
										'type'        => 'string',
										'description' => __( 'The link URL.', 'learndash' ),
										'example'     => 'https://example.com/wp-json/ldlms/v2/users/1/courses',
									],
								],
							],
						],
						'groups'          => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'embeddable' => [
										'type'        => 'boolean',
										'description' => __( 'Whether the link is embeddable.', 'learndash' ),
										'example'     => true,
									],
									'href'       => [
										'type'        => 'string',
										'description' => __( 'The link URL.', 'learndash' ),
										'example'     => 'https://example.com/wp-json/ldlms/v2/users/1/groups',
									],
								],
							],
						],
						'course-progress' => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'embeddable' => [
										'type'        => 'boolean',
										'description' => __( 'Whether the link is embeddable.', 'learndash' ),
										'example'     => true,
									],
									'href'       => [
										'type'        => 'string',
										'description' => __( 'The link URL.', 'learndash' ),
										'example'     => 'https://example.com/wp-json/ldlms/v2/users/1/course-progress',
									],
								],
							],
						],
						'quiz_progress'   => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'embeddable' => [
										'type'        => 'boolean',
										'description' => __( 'Whether the link is embeddable.', 'learndash' ),
										'example'     => true,
									],
									'href'       => [
										'type'        => 'string',
										'description' => __( 'The link URL.', 'learndash' ),
										'example'     => 'https://example.com/wp-json/ldlms/v2/users/1/quiz-progress',
									],
								],
							],
						],
					],
				],
			],
			'required'   => [
				'id',
				'name',
				'url',
				'description',
				'link',
				'slug',
				'avatar_urls',
				'meta',
				'_links',
			],
		];
	}
}
