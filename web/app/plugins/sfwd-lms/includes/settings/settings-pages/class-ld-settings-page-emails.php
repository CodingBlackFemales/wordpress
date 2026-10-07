<?php
/**
 * LearnDash Settings Page Emails.
 *
 * @since 3.6.0
 * @package LearnDash\Settings\Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) && ( ! class_exists( 'LearnDash_Settings_Page_Emails' ) ) ) {
	/**
	 * Class LearnDash Settings Page Emails.
	 *
	 * @since 2.4.0
	 */
	class LearnDash_Settings_Page_Emails extends LearnDash_Settings_Page {

		/**
		 * Public constructor for class
		 *
		 * @since 2.4.0
		 */
		public function __construct() {

			$this->parent_menu_page_url  = 'admin.php?page=learndash_lms_settings';
			$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id      = 'learndash_lms_emails';
			$this->settings_page_title   = esc_html_x( 'Emails', 'Emails tab Label', 'learndash' );
			$this->show_quick_links_meta = false;
			$this->settings_tab_priority = 30;

			parent::__construct();
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_pages_init',
	function() {
		LearnDash_Settings_Page_Emails::add_page_instance();
	}
);
