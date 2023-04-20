<?php
/**
 * Handles all server side logic for the ld-visitor Gutenberg Block. This block is functionally the same
 * as the [visitor] shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 2.5.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_Visitor' ) ) ) {
	/**
	 * Class for handling LearnDash Visitor Block
	 */
	class LearnDash_Gutenberg_Block_Visitor extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug = 'visitor';
			$this->block_slug     = 'ld-visitor';
			$this->self_closing   = false;

			$this->block_attributes = array(
				'course_id' => array(
					'type' => 'string',
				),
				'group_id'  => array(
					'type' => 'string',
				),
				'user_id'   => array(
					'type' => 'string',
				),
				'autop'     => array(
					'type' => 'boolean',
				),
			);

			$this->init();
		}

		/**
		 * Render Block
		 *
		 * This function is called per the register_block_type() function above. This function will output
		 * the block rendered content. In the case of this function the rendered output will be for the
		 * [visitor] shortcode.
		 *
		 * @since 4.0.0
		 *
		 * @param array    $block_attributes The block attributes.
		 * @param string   $block_content    The block content.
		 * @param WP_Block $block            The block object.
		 *
		 * @return string The rendered output HTML.
		 */
		public function render_block( $block_attributes = array(), $block_content = '', WP_Block $block = null ) {
			$block_attributes = $this->preprocess_block_attributes( $block_attributes );

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
	}
}
new LearnDash_Gutenberg_Block_Visitor();
