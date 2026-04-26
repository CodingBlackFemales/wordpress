<?php
/**
 * The base view class for dashboards based on the post (e.g. course dashboard, group dashboard).
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Admin_Views\Dashboards;

use LDLMS_Post_Types;
use LearnDash\Core\Template\Dashboards\Sections\Section;
use LearnDash\Core\Template\Dashboards\Widgets\Interfaces\Requires_Post;
use WP_Post;

/**
 * The base view class for dashboards based on the post (e.g. course dashboard, group dashboard).
 *
 * @since 4.9.0
 */
class Post extends Dashboard {
	/**
	 * Post.
	 *
	 * @since 4.9.0
	 *
	 * @var WP_Post
	 */
	protected $post;

	/**
	 * Constructor.
	 *
	 * @since 4.9.0
	 *
	 * @param WP_Post $post    The post.
	 * @param Section $section Section.
	 */
	public function __construct( WP_Post $post, Section $section ) {
		$this->post = $post;

		$this->pass_post_to_widgets( $section );

		/**
		 * Filters whether the dashboard is enabled for a specific post. Default true.
		 *
		 * @since 4.9.0
		 *
		 * @param bool    $is_enabled Whether the dashboard is enabled.
		 * @param WP_Post $post       The post.
		 *
		 * @return bool
		 */
		$this->is_enabled = apply_filters( 'learndash_dashboard_post_is_enabled', true, $post );

		parent::__construct(
			sprintf(
				'dashboards/%s',
				LDLMS_Post_Types::get_post_type_key( $post->post_type )
			),
			$section
		);
	}

	/**
	 * Recursively passes a post to the widgets (that require a post).
	 *
	 * @since 4.9.0
	 *
	 * @param Section $section Section.
	 *
	 * @return void
	 */
	protected function pass_post_to_widgets( Section $section ) {
		if ( $section->has_widgets() ) {
			foreach ( $section->get_widgets()->all() as $widget ) {
				if ( $widget instanceof Requires_Post ) {
					$widget->set_post( $this->post );
				}
			}
		} elseif ( $section->has_sections() ) {
			foreach ( $section->get_sections()->all() as $child_section ) {
				$this->pass_post_to_widgets( $child_section );
			}
		}
	}
}
