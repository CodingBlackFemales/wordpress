<?php
/**
 * LearnDash Course Grid Compatibility class file.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Course_Grid;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use LearnDash\Course_Grid;
use LearnDash\Course_Grid\Utilities;
use WP_Post;

/**
 * Class Compatibility.
 *
 * @since 4.21.4
 */
class Compatibility {
	/**
	 * Constructor.
	 *
	 * @since 4.21.4
	 */
	public function __construct() {
		add_filter( 'learndash_template', [ $this, 'load_v1_template' ], 100, 5 );

		// Elementor
		add_action( 'elementor/preview/enqueue_styles', [ $this, 'elementor_preview_enqueue_styles' ], 100 );
		add_filter( 'learndash_course_grid_post_extra_course_grids', [ $this, 'elementor_post_extra_course_grids' ], 10, 2 );
		add_action( 'learndash_course_grid_assets_loaded', [ $this, 'elementor_assets_loaded' ] );
	}

	/**
	 * Filters the filepath to load the legacy course grid addon v1 template.
	 *
	 * @since 4.21.4
	 *
	 * @param string $filepath         File path.
	 * @param string $name             Template name.
	 * @param array  $args             Template arguments.
	 * @param bool   $echo             Echo flag.
	 * @param bool   $return_file_path Return file path flag.
	 *
	 * @return string File path.
	 */
	public function load_v1_template( $filepath, $name, $args, $echo, $return_file_path ) {
		if (
			$name === 'course_list_template'
			&& defined( 'LEARNDASH_LMS_PLUGIN_DIR' ) && strpos( $filepath, LEARNDASH_LMS_PLUGIN_DIR ) !== false
		) {
			if (
				filter_var( $args['shortcode_atts']['course_grid'], FILTER_VALIDATE_BOOLEAN ) === false
				|| ! isset( $args['shortcode_atts']['course_grid'] )
			) {
				return $filepath;
			}

			$template = Utilities::get_skin_item( 'legacy-v1' );

			/**
			 * Filters the course grid template path.
			 *
			 * @since 4.21.4
			 *
			 * @param string $template         The template file path.
			 * @param string $filepath         Original file path.
			 * @param string $name             Template name.
			 * @param array  $args             Template arguments.
			 * @param bool   $return_file_path Whether to return file path.
			 *
			 * @return string Modified template file path.
			 */
			return apply_filters( 'learndash_course_grid_template', $template, $filepath, $name, $args, $return_file_path );
		}

		return $filepath;
	}

	/**
	 * Enqueues the styles for the Elementor preview.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function elementor_preview_enqueue_styles() {
		Course_Grid::instance()->skins->enqueue_editor_skin_assets();
	}

	/**
	 * Adds extra course grids to an Elementor post.
	 *
	 * @since 4.21.4
	 *
	 * @param array<array<string, mixed>> $course_grids Course grids.
	 * @param WP_Post                     $post         Post object.
	 *
	 * @return array<array<string, mixed>> Course grids.
	 */
	public function elementor_post_extra_course_grids( $course_grids, $post ) {
		if ( ! is_a( $post, 'WP_Post' ) ) {
			return $course_grids;
		}

		$is_elementor = get_post_meta( $post->ID, '_elementor_edit_mode', true );

		if ( $is_elementor ) {
			global $learndash_course_grid_post_elementor_enabled;
			$learndash_course_grid_post_elementor_enabled = true;

			$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
			if ( $elementor_data ) {
				if ( is_string( $elementor_data ) ) {
					$elementor_data = json_decode( $elementor_data, true );
				}
				$elements = Utilities::associative_list_pluck( $elementor_data, 'elements' );

				foreach ( $elements as $element ) {
					if ( isset( $element['widgetType'] ) ) {
						switch ( $element['widgetType'] ) {
							case 'tabs':
								foreach ( $element['settings']['tabs'] as $tab ) {
									$tags = Course_Grid::instance()->skins->parse_content_shortcodes( $tab['tab_content'], [] );

									$course_grids[] = $tags;
								}
								break;
						}
					}
				}
			}
		}

		return $course_grids;
	}

	/**
	 * Enqueues the scripts for Elementor compatibility.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function elementor_assets_loaded() {
		global $learndash_course_grid_post_elementor_enabled;

		if ( $learndash_course_grid_post_elementor_enabled ) {
			wp_enqueue_script( 'learndash-course-grid-elementor-compatibility', LEARNDASH_COURSE_GRID_PLUGIN_URL . 'assets/js/elementor.js', [], LEARNDASH_VERSION, true );
		}
	}

	/**
	 * Parses the Elementor data.
	 *
	 * @since 4.21.4
	 *
	 * @param array<string, mixed> $data Elementor data.
	 *
	 * @return void
	 */
	public function parse_elementor_data( $data ) {
		$elements = Utilities::associative_list_pluck( $data, 'elements' );
	}
}
