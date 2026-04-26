<?php
/**
 * Base repository class.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Repositories;

use StellarWP\Learndash\StellarWP\Models\Repositories\Repository as StellarRepository;

/**
 * Base repository class.
 *
 * @since 4.25.0
 */
abstract class Repository extends StellarRepository {
	/**
	 * Saves a post with meta.
	 *
	 * @since 4.25.0
	 *
	 * @param array               $post_array The post array.
	 * @param array<string,mixed> $meta_array The meta array.
	 *
	 * @phpstan-param array{
	 *     post_author?: int,
	 *     post_parent?: int,
	 *     post_status?: string,
	 *     post_type?: string,
	 * } $post_array
	 *
	 * @return int The post ID.
	 */
	public static function save_post_with_meta( array $post_array, array $meta_array ): int {
		$post_id = wp_insert_post( $post_array );

		if ( is_wp_error( $post_id ) ) { // @phpstan-ignore-line -- False positive. `wp_insert_post` returns `int|WP_Error`.
			return 0;
		}

		foreach ( $meta_array as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		return $post_id;
	}
}
