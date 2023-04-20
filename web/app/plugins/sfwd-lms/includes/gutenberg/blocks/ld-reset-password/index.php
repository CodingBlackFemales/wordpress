<?php
/**
 * Handles all server side logic for the ld-reset-password Gutenberg Block. This block is functionally the same
 * as the ld_reset_password shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_Reset_Password' ) ) ) {
	/**
	 * Class for handling LearnDash Reset Password Block
	 */
	class LearnDash_Gutenberg_Block_Reset_Password extends LearnDash_Gutenberg_Block {
		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug   = 'ld_reset_password';
			$this->block_slug       = 'ld-reset-password';
			$this->block_attributes = array(
				'width'             => array(
					'type' => 'string',
				),
				'example_show'      => array(
					'type' => 'boolean',
				),
				'preview_show'      => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'editing_post_meta' => array(
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
		 * [ld_reset_password] shortcode.
		 *
		 * @since 2.5.9
		 *
		 * @param array    $block_attributes The block attributes.
		 * @param string   $block_content    The block content.
		 * @param WP_Block $block            The block object.
		 *
		 * @return string The output is echoed.
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
				} else {
					$shortcode_out = '<div class="learndash-wrap">' . $shortcode_out . '</div>';
				}
			}
			return $shortcode_out;
		}
		// End of functions.
	}
}
new LearnDash_Gutenberg_Block_Reset_Password();
