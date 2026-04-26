<?php
/**
 * Handles all server side logic for the ProPanel Link Gutenberg Block.
 *
 * @since 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'LearnDash_ProPanel_Gutenberg_Block' ) && ! class_exists( 'LearnDash_ProPanel_Gutenberg_Block_Link' ) ) {
	/**
	 * Class for handling LearnDash Login Block
	 *
	 * @since 4.17.0
	 */
	class LearnDash_ProPanel_Gutenberg_Block_Link extends LearnDash_ProPanel_Gutenberg_Block {
		/**
		 * Object constructor
		 *
		 * @since 4.17.0
		 */
		public function __construct() {
			$this->shortcode_slug   = 'ld_reports';
			$this->shortcode_widget = 'link';
			$this->block_slug       = 'ld-propanel-link';
			$this->block_attributes = array(
				'content' => array(
					'type'    => 'link',
					'default' => __( 'Show ProPanel Full Page', 'learndash' ),
				),
			);
			$this->self_closing     = false;

			$this->init();
		}

		/**
		 * Process the block attributes before render.
		 *
		 * @since 4.17.0
		 *
		 * @param array $block_attributes Array of block attributes.
		 *
		 * @return array $block_attributes
		 */
		protected function process_block_attributes( $block_attributes = array() ) {
			if ( isset( $block_attributes['content'] ) ) {
				unset( $block_attributes['content'] );
			}

			return $block_attributes;
		}
	}
}
new LearnDash_ProPanel_Gutenberg_Block_Link();
