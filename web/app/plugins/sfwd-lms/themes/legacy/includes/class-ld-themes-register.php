<?php
/**
 * LearnDash Legacy Theme Register.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'LearnDash_Theme_Register' ) && ! class_exists( 'LearnDash_Theme_Register_Legacy' ) ) {
	/**
	 * Class to create the settings section.
	 *
	 * @since 3.0.0
	 */
	class LearnDash_Theme_Register_Legacy extends LearnDash_Theme_Register {
		/**
		 * Protected constructor for class.
		 *
		 * @since 3.0.0
		 *
		 * @return void
		 */
		protected function __construct() {
			$this->theme_key          = 'legacy';
			$this->theme_name         = esc_html__( 'Legacy', 'learndash' );
			$this->theme_base_dir     = trailingslashit( LEARNDASH_LMS_PLUGIN_DIR ) . 'themes/' . $this->theme_key;
			$this->theme_base_url     = trailingslashit( LEARNDASH_LMS_PLUGIN_URL ) . 'themes/' . $this->theme_key;
			$this->theme_template_dir = $this->theme_base_dir . '/templates';
			$this->theme_template_url = $this->theme_base_url . '/templates';
			$this->supports_views     = false;

			parent::__construct();
		}
	}
}

add_action(
	'learndash_themes_init',
	function() {
		LearnDash_Theme_Register_Legacy::add_theme_instance( 'legacy' );
	}
);
