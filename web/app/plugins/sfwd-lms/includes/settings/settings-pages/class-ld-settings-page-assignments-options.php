<?php
/**
 * LearnDash Settings Page Assignments Options.
 *
 * @since 2.6.0
 * @package LearnDash\Settings\Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) && ( ! class_exists( 'LearnDash_Settings_Page_Assignments_Options' ) ) ) {
	/**
	 * Class LearnDash Settings Page Assignments Options.
	 *
	 * @since 2.6.0
	 */
	class LearnDash_Settings_Page_Assignments_Options extends LearnDash_Settings_Page {

		/**
		 * Public constructor for class
		 *
		 * @since 2.6.0
		 */
		public function __construct() {

			$this->parent_menu_page_url = 'edit.php?post_type=sfwd-assignment';
			$this->menu_page_capability = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id     = 'assignments-options';
			$this->settings_page_title  = esc_html__( 'Settings', 'learndash' );

			parent::__construct();
		}
	}
}
add_action(
	'learndash_settings_pages_init',
	function() {
		LearnDash_Settings_Page_Assignments_Options::add_page_instance();
	}
);
