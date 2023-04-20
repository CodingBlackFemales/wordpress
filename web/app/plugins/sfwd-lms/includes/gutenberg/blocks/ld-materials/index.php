<?php
/**
 * Handles all server side logic for the ld-materials Gutenberg Block. This block is functionally the same
 * as the ld_materials shortcode used within LearnDash.
 *
 * @package LearnDash
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_Gutenberg_Block_Materials' ) ) ) {
	/**
	 * Class for handling LearnDash Materials Block
	 */
	class LearnDash_Gutenberg_Block_Materials extends LearnDash_Gutenberg_Block {

		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug   = 'ld_materials';
			$this->block_slug       = 'ld-materials';
			$this->block_attributes = array(
				'post_id'           => array(
					'type' => 'string',
				),
				'autop'             => array(
					'type' => 'string',
				),
				'preview_show'      => array(
					'type' => 'boolean',
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
		 * [ld_materials] shortcode.
		 *
		 * @since 4.0.0
		 *
		 * @param array    $block_attributes The block attributes.
		 * @param string   $block_content    The block content.
		 * @param WP_block $block            The block object.
		 * @return none The output is echoed.
		 */
		public function render_block( $block_attributes = array(), $block_content = '', WP_block $block = null ) {
			$block_attributes = $this->preprocess_block_attributes( $block_attributes );

			if ( $this->block_attributes_is_editing_post( $block_attributes ) ) {
				$block_attributes['post_id'] = $this->block_attributes_get_post_id( $block_attributes, 'post' );
				if ( ! empty( $block_attributes['post_id'] ) ) {
					$post = get_post( (int) $block_attributes['post_id'] );
					if ( ( ! $post ) || ( ! is_a( $post, 'WP_Post' ) ) || ( ! in_array( $post->post_type, learndash_get_post_types(), true ) ) ) {
						return $this->render_block_wrap(
							'<span class="learndash-block-error-message">' . esc_html__( 'Invalid LearnDash Post ID.', 'learndash' ) . '</span>'
						);
					}
				}
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

		// End of functions.
	}
}
new LearnDash_Gutenberg_Block_Materials();
