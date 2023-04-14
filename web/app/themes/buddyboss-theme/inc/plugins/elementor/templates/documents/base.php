<?php

namespace BBElementor\Templates\Documents;

use Elementor\Core\Base\Document as Document;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

class BB_Elementor_Document_Base extends Document {
	
	/**
	 * Get name.
	 *
	 * @since  1.4.7
	 * @return string
	 */
	public function get_name() {
		return '';
	}
	
	/**
	 * Get title.
	 *
	 * @since  1.4.7
	 * @return string
	 */
	public static function get_title() {
		return '';
	}
	
}
