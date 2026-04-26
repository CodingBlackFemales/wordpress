<?php
/**
 * An interface for widgets that require a post.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Dashboards\Widgets\Interfaces;

use WP_Post;

/**
 * An interface for widgets that require a post.
 *
 * @since 4.9.0
 */
interface Requires_Post {
	/**
	 * Sets a post.
	 *
	 * @since 4.9.0
	 *
	 * @param WP_Post $post A post.
	 *
	 * @return void
	 */
	public function set_post( WP_Post $post ): void;

	/**
	 * Returns a post.
	 *
	 * @since 4.9.0
	 *
	 * @return WP_Post
	 */
	public function get_post(): WP_Post;
}
