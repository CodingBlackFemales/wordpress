<?php
/**
 * Handles all server side logic for the ProPanel Filters Gutenberg Block.
 *
 * @since 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

if ( ( class_exists( 'LearnDash_ProPanel_Gutenberg_Block' ) ) && ( ! class_exists( 'LearnDash_ProPanel_Gutenberg_Block_Filters' ) ) ) {
	/**
	 * Class for handling LearnDash Login Block
	 */
	class LearnDash_ProPanel_Gutenberg_Block_Filters extends LearnDash_ProPanel_Gutenberg_Block {
		/**
		 * Object constructor
		 */
		public function __construct() {
			$this->shortcode_slug   = 'ld_reports';
			$this->shortcode_widget = 'filtering';
			$this->block_slug       = 'ld-propanel-filters';
			$this->block_attributes = array(
				'preview_show' => array(
					'type' => 'boolean',
				),
			);
			$this->self_closing     = true;

			$this->init();
		}

		/** This function is documented in includes/gutenberg/lib/class-learndash-propanel-gutenberg-block.php */
		protected function process_block_attributes( $block_attributes = array() ) {
			if ( isset( $block_attributes['preview_show'] ) ) {
				unset( $block_attributes['preview_show'] );
			}

			return $block_attributes;
		}

		// End of functions.
	}
}
new LearnDash_ProPanel_Gutenberg_Block_Filters();
