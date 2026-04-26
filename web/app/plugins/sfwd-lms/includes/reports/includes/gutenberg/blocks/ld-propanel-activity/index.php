<?php
/**
 * Handles all server side logic for the ld-propanel-activity Gutenberg Block.
 *
 * @since 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'LearnDash_ProPanel_Gutenberg_Block' ) && ! class_exists( 'LearnDash_ProPanel_Gutenberg_Block_Activity' ) ) {
	/**
	 * Class for handling LearnDash Login Block
	 *
	 * @since 4.17.0
	 */
	class LearnDash_ProPanel_Gutenberg_Block_Activity extends LearnDash_ProPanel_Gutenberg_Block {
		/**
		 * Object constructor
		 *
		 * @since 4.17.0
		 */
		public function __construct() {
			$this->shortcode_slug   = 'ld_reports';
			$this->shortcode_widget = 'activity';
			$this->block_slug       = 'ld-propanel-activity';
			$this->block_attributes = array(
				'preview_show'   => array(
					'type' => 'boolean',
				),
				'filter_groups'  => array(
					'type' => 'int',
				),
				'filter_courses' => array(
					'type' => 'int',
				),
				'filter_users'   => array(
					'type' => 'int',
				),
				'filter_status'  => array(
					'type' => 'string',
				),
				'per_page'       => array(
					'type' => 'int',
				),
			);
			$this->self_closing     = true;

			$this->init();
		}

		/**
		 * Process the block attributes before render.
		 *
		 * @param array $block_attributes Array of block attributes.
		 *
		 * @since 4.17.0
		 * @return array $block_attributes
		 */
		protected function process_block_attributes( $block_attributes = array() ) {
			if ( isset( $block_attributes['preview_show'] ) ) {
				unset( $block_attributes['preview_show'] );
			}

			return $block_attributes;
		}
	}
}
new LearnDash_ProPanel_Gutenberg_Block_Activity();
