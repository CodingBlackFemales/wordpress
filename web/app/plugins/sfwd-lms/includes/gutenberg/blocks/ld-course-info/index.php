<?php
/**
 * Handles all server side logic for the ld-course-info Gutenberg Block. This block is functionally the same
 * as the ld_course_info shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_Course_Info' ) ) ) {
	/**
	 * Class for handling LearnDash Course Info Block
	 */
	class LearnDash_Gutenberg_Block_Course_Info extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug   = 'ld_course_info';
			$this->block_slug       = 'ld-course-info';
			$this->block_attributes = array(
				'user_id'                   => array(
					'type' => 'string',
				),
				'registered_show'           => array(
					'type' => 'boolean',
				),
				'registered_show_thumbnail' => array(
					'type' => 'boolean',
				),
				'registered_num'            => array(
					'type' => 'string',
				),
				'registered_order'          => array(
					'type' => 'string',
				),
				'registered_orderby'        => array(
					'type' => 'string',
				),
				'progress_show'             => array(
					'type' => 'boolean',
				),
				'progress_num'              => array(
					'type' => 'string',
				),
				'progress_order'            => array(
					'type' => 'string',
				),
				'progress_orderby'          => array(
					'type' => 'string',
				),
				'quiz_show'                 => array(
					'type' => 'boolean',
				),
				'quiz_num'                  => array(
					'type' => 'string',
				),
				'quiz_order'                => array(
					'type' => 'string',
				),
				'quiz_orderby'              => array(
					'type' => 'string',
				),
				'preview_show'              => array(
					'type' => 'boolean',
				),
				'preview_user_id'           => array(
					'type' => 'string',
				),
				'example_show'              => array(
					'type' => 'boolean',
				),
				'editing_post_meta'         => array(
					'type' => 'object',
				),

			);
			$this->self_closing = true;

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
				$block_attributes['preview_show'] = true;
				unset( $block_attributes['example_show'] );
			}

			// Only the 'editing_post_meta' element will be sent from within the post edit screen.
			if ( $this->block_attributes_is_editing_post( $block_attributes ) ) {
				$block_attributes['user_id'] = $this->block_attributes_get_user_id( $block_attributes );
			}

			$types = array();
			if ( isset( $block_attributes['registered_show'] ) ) {
				if ( true === $block_attributes['registered_show'] ) {
					$types[] = 'registered';
				}
				unset( $block_attributes['registered_show'] );
			}

			if ( isset( $block_attributes['registered_show_thumbnail'] ) ) {
				if ( true === $block_attributes['registered_show_thumbnail'] ) {
					$block_attributes['registered_show_thumbnail'] = 'true';
				} else {
					$block_attributes['registered_show_thumbnail'] = 'false';
				}
			}

			if ( isset( $block_attributes['progress_show'] ) ) {
				if ( true === $block_attributes['progress_show'] ) {
					$types[] = 'course';
				}
				unset( $block_attributes['progress_show'] );
			}

			if ( isset( $block_attributes['quiz_show'] ) ) {
				if ( true === $block_attributes['quiz_show'] ) {
					$types[] = 'quiz';
				}
				unset( $block_attributes['quiz_show'] );
			}

			if ( empty( $types ) ) {
				$block_attributes['type'] = implode( ',', array( 'registered', 'course', 'quiz' ) );
			} else {
				$block_attributes['type'] = implode( ',', $types );
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
				} else {
					$shortcode_out = '<div class="learndash-wrap">' . $shortcode_out . '</div>';
				}
			}

			return $shortcode_out;
		}

		// End of functions.
	}
}
new LearnDash_Gutenberg_Block_Course_Info();
