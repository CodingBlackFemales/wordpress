<?php
/***
 * Course Grid Filter shortcode.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Course_Grid\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use LearnDash\Course_Grid\Utilities;

/**
 * Course Grid Filter shortcode.
 *
 * @since 4.21.4
 */
class LearnDash_Course_Grid_Filter extends Base {
	/**
	 * Shortcode tag.
	 *
	 * @since 4.21.4
	 *
	 * @var string
	 */
	protected $tag = 'learndash_course_grid_filter';

	/**
	 * Get default shortcode attributes.
	 *
	 * @since 4.21.4
	 *
	 * @return array<string, mixed>
	 */
	public function get_default_atts() {
		/**
		 * Returns the default attributes for the course grid filter shortcode.
		 *
		 * @since 4.21.4
		 *
		 * @return array<string, mixed> Array of default shortcode attributes.
		 */
		return apply_filters(
			'learndash_course_grid_filter_default_shortcode_attributes',
			[
				'course_grid_id'     => '',
				'search'             => true,
				'taxonomies'         => 'category, post_tag',
				'default_taxonomies' => '',
				'price'              => true,
				'price_min'          => 0,
				'price_max'          => 1000,
			]
		);
	}

	/**
	 * Render shortcode.
	 *
	 * @since 4.21.4
	 *
	 * @param array<string, mixed> $shortcode_atts Shortcode attributes.
	 * @param string               $content        Shortcode content.
	 *
	 * @return string
	 */
	public function render( $shortcode_atts = [], $content = '' ) {
		$atts = shortcode_atts( $this->get_default_atts(), $shortcode_atts, $this->tag );

		$atts = $this->validate_atts_type( $atts );

		if ( empty( $atts['course_grid_id'] ) ) {
			$output = __( 'Missing course_grid_id attribute.', 'learndash' );
			return $output;
		}

		$default_taxonomies = Utilities::parse_taxonomies( $atts['default_taxonomies'] );

		// Get the template file
		$template = Utilities::get_template( 'filter/layout' );

		// Include the template file
		ob_start();

		echo '<div class="learndash-course-grid-filter" data-course_grid_id="' . esc_attr( $atts['course_grid_id'] ) . '" data-taxonomies="' . esc_attr( $atts['taxonomies'] ) . '">';

		$atts['taxonomies'] = array_map(
			function ( $tax ) {
				return trim( $tax );
			},
			array_filter( explode( ',', $atts['taxonomies'] ) )
		);

		if ( '' === $atts['price_min'] ) {
			$atts['price_min'] = 0;
		}

		if ( '' === $atts['price_max'] ) {
			$atts['price_max'] = 1000;
		}

		$atts['price_step'] = ( $atts['price_max'] - $atts['price_min'] ) / 2 / 10;
		$atts['price_step'] = ceil( $atts['price_step'] );

		if ( $template ) {
			include $template;
		}

		echo '</div>';

		// Return the template HTML string
		return ob_get_clean();
	}
}
