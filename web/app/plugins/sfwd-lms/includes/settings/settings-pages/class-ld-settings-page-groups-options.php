<?php
/**
 * LearnDash Settings Page Groups Options.
 *
 * @since 3.2.0
 * @package LearnDash\Settings\Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) && ( ! class_exists( 'LearnDash_Settings_Page_Groups_Options' ) ) ) {
	/**
	 * Class LearnDash Settings Page Groups Options.
	 *
	 * @since 3.2.0
	 */
	class LearnDash_Settings_Page_Groups_Options extends LearnDash_Settings_Page {

		/**
		 * Public constructor for class
		 *
		 * @since 3.2.0
		 */
		public function __construct() {

			$this->parent_menu_page_url = 'edit.php?post_type=groups';
			$this->menu_page_capability = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id     = 'groups-options';
			$this->settings_page_title  = esc_html_x( 'Settings', 'Group Settings', 'learndash' );
			$this->settings_tab_title   = $this->settings_page_title;

			parent::__construct();
		}
	}
}
add_action(
	'learndash_settings_pages_init',
	function() {
		LearnDash_Settings_Page_Groups_Options::add_page_instance();
	}
);
