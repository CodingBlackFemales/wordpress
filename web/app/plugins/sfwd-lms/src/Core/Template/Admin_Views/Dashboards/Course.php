<?php
/**
 * The course dashboard view class.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Admin_Views\Dashboards;

use InvalidArgumentException;
use LearnDash\Core\Mappers;
use WP_Post;

/**
 * The view class for a course dashboard.
 *
 * @since 4.9.0
 */
class Course extends Post {
	/**
	 * Constructor.
	 *
	 * @since 4.9.0
	 *
	 * @param WP_Post $post The post.
	 *
	 * @throws InvalidArgumentException If the post type is not allowed.
	 */
	public function __construct( WP_Post $post ) {
		$sections_mapper = new Mappers\Dashboards\Course\Mapper( $post );

		parent::__construct( $post, $sections_mapper->map() );
	}
}
