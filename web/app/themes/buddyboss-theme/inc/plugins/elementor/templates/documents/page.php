<?php

namespace BBElementor\Templates\Documents;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

class BB_Elementor_Pages_Document extends BB_Elementor_Document_Base {
	
	/**
	 * Get Elementor Section name.
	 *
	 * @since  1.4.7
	 * @return string
	 */
	public function get_name() {
		return 'bb_elementor_sections_page';
	}
	
	/**
	 * Get Elementor Section title.
	 *
	 * @since  1.4.7
	 * @return string
	 */
	public static function get_title() {
		return __( 'Page', 'buddyboss-theme' );
	}
	
}