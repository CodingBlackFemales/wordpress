<?php
/**
 * A trait for widgets that require a post.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Dashboards\Widgets\Traits;

use WP_Post;

/**
 * A trait for widgets that require a post.
 *
 * @since 4.9.0
 */
trait Supports_Post {
	/**
	 * Post.
	 *
	 * @since 4.9.0
	 *
	 * @var WP_Post
	 */
	protected $post;

	/**
	 * Sets a post.
	 *
	 * @since 4.9.0
	 *
	 * @param WP_Post $post A post.
	 *
	 * @return void
	 */
	public function set_post( WP_Post $post ): void {
		$this->post = $post;
	}

	/**
	 * Returns a post.
	 *
	 * @since 4.9.0
	 *
	 * @return WP_Post
	 */
	public function get_post(): WP_Post {
		return $this->post;
	}
}
