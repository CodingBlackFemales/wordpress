<?php
/**
 * Select the posts a command works on.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Cli;

use CodingBlackFemales\SemanticGlossary\Reference\Index;
use WP_CLI;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Posts.
 */
final class Posts {

	/**
	 * Statuses commands work on: everything a reader or editor can still reach.
	 */
	const STATUSES = array( 'publish', 'future', 'draft', 'pending', 'private' );


	/**
	 * Posts named by --post, or every post of --post-type, or every indexed post.
	 *
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return WP_Post[]
	 */
	public static function select( array $assoc_args ): array {
		if ( ! empty( $assoc_args['post'] ) ) {
			return array( self::one( (int) $assoc_args['post'] ) );
		}

		$types = isset( $assoc_args['post-type'] )
			? array( (string) $assoc_args['post-type'] )
			: array_values( array_filter( get_post_types(), array( Index::class, 'is_indexed_type' ) ) );

		return get_posts(
			array(
				'post_type'        => $types,
				'post_status'      => self::STATUSES,
				'posts_per_page'   => -1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'suppress_filters' => true,
			)
		);
	}


	/**
	 * Posts the index says reference something, narrowed by --post or --post-type.
	 *
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return WP_Post[]
	 */
	public static function with_references( array $assoc_args ): array {
		if ( ! empty( $assoc_args['post'] ) ) {
			return array( self::one( (int) $assoc_args['post'] ) );
		}

		$ids = Index::post_ids( (string) ( $assoc_args['post-type'] ?? '' ) );

		return array_values( array_filter( array_map( 'get_post', $ids ), fn ( $post ): bool => $post instanceof WP_Post ) );
	}


	/**
	 * One post, or exit with an error.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function one( int $post_id ): WP_Post {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			/* translators: %d: post ID */
			WP_CLI::error( sprintf( __( 'No post %d.', 'cbf-semantic-glossary' ), $post_id ) );
		}

		return $post;
	}
}
