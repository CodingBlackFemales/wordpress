<?php
/**
 * Per-post glossary data that lives outside the content.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Reference;

use CodingBlackFemales\SemanticGlossary\Entry\PostType;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PostFields.
 *
 * Two lists of entry IDs per post:
 *
 * - extra: entries listed in the post's glossary with no inline reference
 *   (the sidebar's "Add term without inline reference");
 * - ignored: known entries the sidebar should stop suggesting for this post
 *   ("Ignore in this post").
 *
 * Exposed to the block editor as one REST field, `glossary`, on every post
 * type, rather than as registered meta. Meta only reaches REST on post types
 * that support custom-fields, which LearnDash's do not all do.
 */
final class PostFields {

	/**
	 * Meta key of the extra entry list.
	 */
	const EXTRA_META = '_glossary_extra_terms';

	/**
	 * Meta key of the ignored entry list.
	 */
	const IGNORED_META = '_glossary_ignored_terms';

	/**
	 * REST field name.
	 */
	const REST_FIELD = 'glossary';


	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_field' ) );
	}


	/**
	 * Add the `glossary` field to every REST-enabled post type but entries.
	 */
	public static function register_rest_field(): void {
		$types = array_diff( get_post_types( array( 'show_in_rest' => true ) ), array( PostType::NAME, 'attachment', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation' ) );

		register_rest_field(
			array_values( $types ),
			self::REST_FIELD,
			array(
				'get_callback'    => fn ( array $post ) => self::get( (int) $post['id'] ),
				'update_callback' => array( __CLASS__, 'update_from_rest' ),
				'schema'          => array(
					'type'       => 'object',
					'context'    => array( 'edit' ),
					'properties' => array(
						'extra'   => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
						'ignored' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'integer' ),
						),
					),
				),
			)
		);
	}


	/**
	 * Both lists for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{extra: int[], ignored: int[]}
	 */
	public static function get( int $post_id ): array {
		return array(
			'extra'   => self::extra( $post_id ),
			'ignored' => self::ignored( $post_id ),
		);
	}


	/**
	 * Entries listed without an inline reference.
	 *
	 * @param int $post_id Post ID.
	 * @return int[]
	 */
	public static function extra( int $post_id ): array {
		return self::ids( get_post_meta( $post_id, self::EXTRA_META, true ) );
	}


	/**
	 * Entries not to suggest for this post.
	 *
	 * @param int $post_id Post ID.
	 * @return int[]
	 */
	public static function ignored( int $post_id ): array {
		return self::ids( get_post_meta( $post_id, self::IGNORED_META, true ) );
	}


	/**
	 * REST update callback.
	 *
	 * The posts controller has already checked the user may edit the post.
	 *
	 * @param mixed   $value New field value.
	 * @param WP_Post $post  Post being updated.
	 */
	public static function update_from_rest( $value, WP_Post $post ): bool {
		$value = is_array( $value ) ? $value : array();

		if ( array_key_exists( 'extra', $value ) ) {
			self::save( $post->ID, self::EXTRA_META, $value['extra'] );
		}

		if ( array_key_exists( 'ignored', $value ) ) {
			self::save( $post->ID, self::IGNORED_META, $value['ignored'] );
		}

		return true;
	}


	/**
	 * Store a list, deleting the meta when it is empty.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param mixed  $ids     Entry IDs.
	 */
	public static function save( int $post_id, string $key, $ids ): void {
		$ids = self::ids( $ids );

		if ( $ids === array() ) {
			delete_post_meta( $post_id, $key );
			return;
		}

		update_post_meta( $post_id, $key, $ids );
	}


	/**
	 * Positive, unique integer IDs, in their original order.
	 *
	 * @param mixed $value Stored or submitted list.
	 * @return int[]
	 */
	private static function ids( $value ): array {
		$ids = array_filter( array_map( 'intval', is_array( $value ) ? $value : array() ), fn ( int $id ): bool => $id > 0 );
		return array_values( array_unique( $ids ) );
	}
}
