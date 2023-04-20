<?php
/**
 * LearnDash LD30 Theme Register.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Theme_Register' ) ) && ( ! class_exists( 'LearnDash_Theme_Register_LD30' ) ) ) {
	/**
	 * Class to create the settings section.
	 *
	 * @since 3.0.0
	 * @uses LearnDash_Theme_Register
	 */
	class LearnDash_Theme_Register_LD30 extends LearnDash_Theme_Register {

		/**
		 * Protected constructor for class
		 *
		 * @since 3.0.0
		 */
		protected function __construct() {
			$this->theme_key          = 'ld30';
			$this->theme_name         = esc_html__( 'LearnDash 3.0', 'learndash' );
			$this->theme_base_dir     = trailingslashit( LEARNDASH_LMS_PLUGIN_DIR ) . 'themes/' . $this->theme_key;
			$this->theme_base_url     = trailingslashit( LEARNDASH_LMS_PLUGIN_URL ) . 'themes/' . $this->theme_key;
			$this->theme_template_dir = $this->theme_base_dir . '/templates';
			$this->theme_template_url = $this->theme_base_url . '/templates';
		}

		/**
		 * Load the theme files and assets.
		 *
		 * @since 4.0.0
		 */
		public function load_theme() {
			include_once trailingslashit( $this->get_theme_base_dir() ) . 'includes/helpers.php';
		}

		/**
		 * Load the theme settings sections.
		 *
		 * @since 4.0.0
		 */
		public function load_settings_sections() {
			include_once trailingslashit( $this->get_theme_base_dir() ) . 'includes/class-ld-settings-section-theme-ld30.php';
		}

		// End of functions.
	}
}

add_action(
	'learndash_themes_init',
	function() {
		LearnDash_Theme_Register_LD30::add_theme_instance( 'ld30' );
	}
);
