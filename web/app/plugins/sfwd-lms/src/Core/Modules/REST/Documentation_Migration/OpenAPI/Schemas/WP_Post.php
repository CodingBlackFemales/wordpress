<?php
/**
 * WordPress Post OpenAPI Schema Class.
 * This is based directly on wp/v2/posts/<id>.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 *
 * cspell:ignore hentry .
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Schemas;

use stdClass;

/**
 * Class that provides WordPress Post OpenAPI schema.
 *
 * @since 4.25.2
 */
class WP_Post {
	/**
	 * Returns the OpenAPI response schema for a WordPress Post.
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
				'id'             => [
					'type'        => 'integer',
					'description' => __( 'The post ID.', 'learndash' ),
					'example'     => 123,
					'readOnly'    => true,
				],
				'date'           => [
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'The date the post was published.', 'learndash' ),
					'example'     => '2024-01-15T10:30:00Z',
				],
				'date_gmt'       => [
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'The date the post was published in GMT.', 'learndash' ),
					'example'     => '2024-01-15T10:30:00Z',
				],
				'guid'           => [
					'type'        => 'object',
					'description' => __( 'The globally unique identifier for the post.', 'learndash' ),
					'readOnly'    => true,
					'properties'  => [
						'raw'      => [
							'type'        => 'string',
							'description' => __( 'The raw GUID.', 'learndash' ),
							'example'     => 'https://example.com/?p=123',
							'readOnly'    => true,
						],
						'rendered' => [
							'type'        => 'string',
							'description' => __( 'The rendered GUID.', 'learndash' ),
							'example'     => 'https://example.com/?p=123',
							'readOnly'    => true,
						],
					],
				],
				'modified'       => [
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'The date the post was last modified.', 'learndash' ),
					'example'     => '2024-01-15T10:30:00Z',
					'readOnly'    => true,
				],
				'modified_gmt'   => [
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => __( 'The date the post was last modified in GMT.', 'learndash' ),
					'example'     => '2024-01-15T10:30:00Z',
					'readOnly'    => true,
				],
				'slug'           => [
					'type'        => 'string',
					'description' => __( 'The post slug.', 'learndash' ),
					'example'     => 'sample-post',
				],
				'status'         => [
					'type'        => 'string',
					'description' => __( 'The post status.', 'learndash' ),
					'enum'        => [ 'publish', 'future', 'draft', 'pending', 'private' ],
					'example'     => 'publish',
				],
				'type'           => [
					'type'        => 'string',
					'description' => __( 'The post type.', 'learndash' ),
					'example'     => 'post',
					'readOnly'    => true,
				],
				'link'           => [
					'type'        => 'string',
					'format'      => 'uri',
					'description' => __( 'URL to the post.', 'learndash' ),
					'example'     => 'https://example.com/sample-post/',
				],
				'title'          => [
					'type'        => 'object',
					'description' => __( 'The post title.', 'learndash' ),
					'properties'  => [
						'raw'      => [
							'type'        => 'string',
							'description' => __( 'The raw title.', 'learndash' ),
							'example'     => 'Sample Post Title',
						],
						'rendered' => [
							'type'        => 'string',
							'description' => __( 'The rendered title.', 'learndash' ),
							'example'     => 'Sample Post Title',
							'readOnly'    => true,
						],
					],
				],
				'content'        => [
					'type'        => 'object',
					'description' => __( 'The post content.', 'learndash' ),
					'properties'  => [
						'raw'       => [
							'type'        => 'string',
							'description' => __( 'The raw content.', 'learndash' ),
							'example'     => 'This is the post content...',
						],
						'rendered'  => [
							'type'        => 'string',
							'description' => __( 'The rendered content.', 'learndash' ),
							'example'     => '<p>This is the post content...</p>',
							'readOnly'    => true,
						],
						'protected' => [
							'type'        => 'boolean',
							'description' => __( 'Whether the content is protected.', 'learndash' ),
							'example'     => false,
							'readOnly'    => true,
						],
					],
				],
				'excerpt'        => [
					'type'        => 'object',
					'description' => __( 'The post excerpt.', 'learndash' ),
					'properties'  => [
						'raw'       => [
							'type'        => 'string',
							'description' => __( 'The raw excerpt.', 'learndash' ),
							'example'     => 'This is the post excerpt...',
						],
						'rendered'  => [
							'type'        => 'string',
							'description' => __( 'The rendered excerpt.', 'learndash' ),
							'example'     => '<p>This is the post excerpt...</p>',
							'readOnly'    => true,
						],
						'protected' => [
							'type'        => 'boolean',
							'description' => __( 'Whether the excerpt is protected.', 'learndash' ),
							'example'     => false,
							'readOnly'    => true,
						],
					],
				],
				'author'         => [
					'type'        => 'integer',
					'description' => __( 'The ID of the post author.', 'learndash' ),
					'example'     => 1,
				],
				'featured_media' => [
					'type'        => 'integer',
					'description' => __( 'The ID of the featured media.', 'learndash' ),
					'example'     => 456,
				],
				'comment_status' => [
					'type'        => 'string',
					'description' => __( 'The comment status.', 'learndash' ),
					'enum'        => [ 'open', 'closed' ],
					'example'     => 'open',
				],
				'ping_status'    => [
					'type'        => 'string',
					'description' => __( 'The ping status.', 'learndash' ),
					'enum'        => [ 'open', 'closed' ],
					'example'     => 'open',
				],
				'sticky'         => [
					'type'        => 'boolean',
					'description' => __( 'Whether the post is sticky.', 'learndash' ),
					'example'     => false,
				],
				'template'       => [
					'type'        => 'string',
					'description' => __( 'The post template.', 'learndash' ),
					'example'     => '',
				],
				'format'         => [
					'type'        => 'string',
					'description' => __( 'The post format.', 'learndash' ),
					'enum'        => [ 'standard', 'aside', 'chat', 'gallery', 'link', 'image', 'quote', 'status', 'video', 'audio' ],
					'example'     => 'standard',
				],
				'meta'           => [
					'type'        => 'object',
					'description' => __( 'Meta fields.', 'learndash' ),
					'properties'  => new stdClass(),
				],
				'class_list'     => [
					'type'        => 'array',
					'description' => __( 'CSS classes for the post.', 'learndash' ),
					'items'       => [
						'type' => 'string',
					],
					'example'     => [ 'post-123', 'post', 'type-post', 'status-publish', 'hentry' ],
				],
				'_links'         => [
					'type'        => 'object',
					'description' => __( 'HAL links for the post.', 'learndash' ),
					'properties'  => [
						'self'       => [
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
						'collection' => [
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
						'author'     => [
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
					],
				],
			],
			'required'   => [
				'id',
				'date',
				'date_gmt',
				'guid',
				'modified',
				'modified_gmt',
				'slug',
				'status',
				'type',
				'link',
				'title',
				'content',
				'excerpt',
				'author',
				'featured_media',
				'comment_status',
				'ping_status',
				'sticky',
				'template',
				'format',
				'meta',
				'class_list',
				'_links',
			],
		];
	}
}
