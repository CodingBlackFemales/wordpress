<?php
/**
 * Handles all server side logic for the ld-profile Gutenberg Block. This block is functionally the same
 * as the ld_profile shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_Profile' ) ) ) {
	/**
	 * Class for handling LearnDash Profile Block
	 */
	class LearnDash_Gutenberg_Block_Profile extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug   = 'ld_profile';
			$this->block_slug       = 'ld-profile';
			$this->block_attributes = array(
				'user_id'            => array(
					'type' => 'integer',
				),
				'per_page'           => array(
					'type' => 'string',
				),
				'order'              => array(
					'type' => 'string',
				),
				'orderby'            => array(
					'type' => 'string',
				),
				'show_search'        => array(
					'type' => 'boolean',
				),
				'show_header'        => array(
					'type' => 'boolean',
				),
				'course_points_user' => array(
					'type' => 'boolean',
				),
				'expand_all'         => array(
					'type' => 'boolean',
				),
				'profile_link'       => array(
					'type' => 'boolean',
				),
				'show_quizzes'       => array(
					'type' => 'boolean',
				),
				'preview_show'       => array(
					'type' => 'boolean',
				),
				'preview_user_id'    => array(
					'type' => 'string',
				),
				'example_show'       => array(
					'type' => 'boolean',
				),
				'quiz_num'           => array(
					'type' => 'string',
				),
				'editing_post_meta'  => array(
					'type' => 'object',
				),
			);
			$this->self_closing     = true;

			$this->init();
		}

		/**
		 * Render Block
		 *
		 * This function is called per the register_block_type() function above. This function will output
		 * the block rendered content. In the case of this function the rendered output will be for the
		 * [ld_profile] shortcode.
		 *
		 * @since 2.5.9
		 *
		 * @param array    $block_attributes The block attributes.
		 * @param string   $block_content    The block content.
		 * @param WP_block $block            The block object.
		 *
		 * @return none The output is echoed.
		 */
		public function render_block( $block_attributes = array(), $block_content = '', WP_block $block = null ) {
			$block_attributes = $this->preprocess_block_attributes( $block_attributes );

			if ( ( isset( $block_attributes['example_show'] ) ) && ( ! empty( $block_attributes['example_show'] ) ) ) {
				$block_attributes['user_id']      = $this->get_example_user_id();
				$block_attributes['preview_show'] = 1;

				unset( $block_attributes['example_show'] );
			}

			/** This filter is documented in includes/gutenberg/blocks/ld-course-list/index.php */
			$block_attributes = apply_filters( 'learndash_block_markers_shortcode_atts', $block_attributes, $this->shortcode_slug, $this->block_slug, '' );

			$shortcode_out = '';

			$shortcode_str = $this->build_block_shortcode( $block_attributes, $block_content );
			if ( ! empty( $shortcode_str ) ) {
				$shortcode_out = do_shortcode( $shortcode_str );
			}

			if ( ! empty( $shortcode_out ) ) {
				if ( $this->block_attributes_is_editing_post( $block_attributes ) ) {
					$shortcode_out = $this->render_block_wrap( $shortcode_out );
				}
			}

			return $shortcode_out;
		}

		/**
		 * Called from the LD function learndash_convert_block_markers_shortcode() when parsing the block content.
		 *
		 * @since 2.5.9
		 *
		 * @param array  $block_attributes The array of attributes parse from the block content.
		 * @param string $shortcode_slug This will match the related LD shortcode ld_profile, ld_course_list, etc.
		 * @param string $block_slug This is the block token being processed. Normally same as the shortcode but underscore replaced with dash.
		 * @param string $content This is the original full content being parsed.
		 *
		 * @return array $block_attributes.
		 */
		public function learndash_block_markers_shortcode_atts_filter( $block_attributes = array(), $shortcode_slug = '', $block_slug = '', $content = '' ) {
			if ( $shortcode_slug === $this->shortcode_slug ) {

				if ( isset( $block_attributes['course_points_user'] ) ) {
					if ( false == $block_attributes['course_points_user'] ) {
						$block_attributes['course_points_user'] = 'no';
					}
				}

				if ( isset( $block_attributes['profile_link'] ) ) {
					if ( false == $block_attributes['profile_link'] ) {
						$block_attributes['profile_link'] = 'no';
					}
				}

				if ( isset( $block_attributes['show_header'] ) ) {
					if ( false == $block_attributes['show_header'] ) {
						$block_attributes['show_header'] = 'no';
					}
				}

				if ( isset( $block_attributes['show_quizzes'] ) ) {
					if ( false == $block_attributes['show_quizzes'] ) {
						$block_attributes['show_quizzes'] = 'no';
					}
				}

				if ( isset( $block_attributes['show_search'] ) ) {
					if ( false == $block_attributes['show_search'] ) {
						$block_attributes['show_search'] = 'no';
					}
				}
			}
			return $block_attributes;
		}

		// End of functions.
	}
}
new LearnDash_Gutenberg_Block_Profile();
