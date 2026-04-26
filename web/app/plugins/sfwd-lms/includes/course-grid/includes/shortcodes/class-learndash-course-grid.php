<?php
/**
 * Course grid shortcode.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Course_Grid\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use WP_Query;
use LearnDash\Course_Grid\Utilities;
use LearnDash\Course_Grid;

/**
 * Course grid shortcode.
 *
 * @since 4.21.4
 */
class LearnDash_Course_Grid extends Base {
	/**
	 * Shortcode tag.
	 *
	 * @since 4.21.4
	 *
	 * @var string
	 */
	protected $tag = 'learndash_course_grid';

	/**
	 * Get default shortcode attributes.
	 *
	 * @since 4.21.4
	 *
	 * @return array<string, mixed>
	 */
	public function get_default_atts() {
		/**
		 * Filters the default attributes for the course grid shortcode.
		 *
		 * @since 4.21.4
		 *
		 * @param array<string, mixed> $attrs Array of default shortcode attributes.
		 *
		 * @return array<string, mixed> Array of default shortcode attributes.
		 */
		return apply_filters(
			'learndash_course_grid_default_shortcode_attributes',
			[
				// Query
				'post_type'                    => defined( 'LEARNDASH_VERSION' ) ? 'sfwd-courses' : 'post',
				'per_page'                     => 9,
				'orderby'                      => 'ID',
				'order'                        => 'DESC',
				'taxonomies'                   => '',
				'enrollment_status'            => '',
				'progress_status'              => '',
				// Used to filter courses by group. Defaults to 0 to not filter by group.
				'group_id'                     => 0,
				// Elements
				'thumbnail'                    => true,
				'thumbnail_size'               => 'thumbnail',
				'ribbon'                       => true,
				'video'                        => false,
				/**
				 * Content includes title, description and button
				 */
				'content'                      => true,
				'title'                        => true,
				'title_clickable'              => true,
				'description'                  => true,
				'description_char_max'         => 120,
				'button'                       => true,
				'filter'                       => true,
				/**
				 * Accepts:
				 *
				 * 'button'   : Load more button
				 * 'infinite' : Infinite scrolling
				 * 'pages'    : Normal AJAX pagination with number 1, 2, 3, and so on
				 * 'false'    : Doesn't have pagination
				 */
				'pagination'                   => 'button',
				'grid_height_equal'            => false,
				'progress_bar'                 => false,
				'post_meta'                    => true,
				// Template
				/**
				 * Accepts:
				 *
				 * All values available in templates/skins
				 */
				'skin'                         => 'grid',
				'card'                         => 'grid-1',
				/**
				 * Only used in certain skin such as 'grid' and 'masonry'
				 */
				'columns'                      => 3,
				'min_column_width'             => 250,
				/**
				 * Only used in certain skin such as 'carousel'
				 */
				'items_per_row'                => 3,
				// Styles
				'font_family_title'            => '',
				'font_family_description'      => '',
				'font_size_title'              => '',
				'font_size_description'        => '',
				'font_color_title'             => '',
				'font_color_description'       => '',
				'background_color_title'       => '',
				'background_color_description' => '',
				'background_color_ribbon'      => '',
				'font_color_ribbon'            => '',
				'background_color_icon'        => '',
				'font_color_icon'              => '',
				'background_color_button'      => '',
				'font_color_button'            => '',
				// Misc
				'class_name'                   => '',
				/**
				 * Random unique ID for CSS styling purpose
				 */
				'id'                           => '',
				// Filter
				'filter_search'                => true,
				'filter_taxonomies'            => '',
				'filter_price'                 => true,
				'filter_price_min'             => 0,
				'filter_price_max'             => 1000,
			]
		);
	}

	/**
	 * Render shortcode.
	 *
	 * @since 4.21.4
	 *
	 * @param array<string, mixed> $atts    Shortcode attributes.
	 * @param string               $content Shortcode content.
	 *
	 * @return string Shortcode output.
	 */
	public function render( $atts = [], $content = '' ) {
		$atts = shortcode_atts( $this->get_default_atts(), $atts, $this->tag );

		$atts = $this->validate_atts_type( $atts );

		// Enqueue skin assets for this shortcode instance.
		Course_Grid::instance()->skins->handle_skin_assets( $atts );

		$query_args = Utilities::build_posts_query_args( $atts );

		// Query the posts.
		$query = new WP_Query( $query_args );

		$posts         = $query->get_posts();
		$max_num_pages = $query->max_num_pages;

		if ( $max_num_pages > 1 ) {
			$has_pagination = true;
		} else {
			$has_pagination = false;
		}

		$empty_id = false;
		if ( empty( $atts['id'] ) ) {
			$empty_id   = true;
			$atts['id'] = Utilities::generate_random_id();
		}

		$filter = filter_var( $atts['filter'], FILTER_VALIDATE_BOOLEAN );

		// Get the template file
		$template            = Utilities::get_skin_layout( $atts['skin'] );
		$pagination_template = Utilities::get_pagination_template( $atts['pagination'] );

		// Include the template file
		ob_start();

		if ( $empty_id ) {
			printf(
				'<!-- %s -->',
				esc_html__(
					'The LearnDash course grid element below does not have an `id` attribute set in the shortcode so it is generated randomly each time it renders. Please set the `id` attribute in the shortcode so it will be consistent each time it renders. If it is not consistent, custom CSS rules in the site that uses the element particular ID may not work.',
					'learndash'
				)
			);
		}

		echo '<div id="' . esc_attr( $atts['id'] ) . '" class="learndash-course-grid ' . esc_attr( $atts['class_name'] ) . '" data-page="1" ' . $this->process_attributes_as_html_attributes( $atts ) . '>';

		if ( $filter === true ) {
			echo '<button class="toggle-filter">' . __( 'Filter', 'learndash' ) . '</button>';

			echo do_shortcode( '[learndash_course_grid_filter course_grid_id="' . esc_attr( $atts['id'] ) . '" search="' . esc_attr( $atts['filter_search'] ) . '" taxonomies="' . esc_attr( $atts['filter_taxonomies'] ) . '" default_taxonomies="' . esc_attr( $atts['taxonomies'] ) . '" price="' . esc_attr( $atts['filter_price'] ) . '" price_min="' . esc_attr( $atts['filter_price_min'] ) . '" price_max="' . esc_attr( $atts['filter_price_max'] ) . '"]' );
		}

		if ( $template ) {
			include $template;
		}

		if ( $pagination_template && $has_pagination ) {
			include $pagination_template;
		}

		echo '</div>';

		// Return the template HTML string.
		return ob_get_clean();
	}
}
