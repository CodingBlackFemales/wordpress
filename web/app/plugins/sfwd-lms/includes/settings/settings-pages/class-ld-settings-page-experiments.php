<?php
/**
 * LearnDash Settings Page Experiments.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Settings\Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'LearnDash_Settings_Page' )
	&& ! class_exists( 'LearnDash_Settings_Page_Experiments' )
) {
	/**
	 * LearnDash Settings Page Experiments.
	 *
	 * @since 4.13.0
	 */
	class LearnDash_Settings_Page_Experiments extends LearnDash_Settings_Page {
		/**
		 * Constructor.
		 *
		 * @since 4.13.0
		 */
		public function __construct() {
			$this->parent_menu_page_url  = 'admin.php?page=learndash_lms_settings';
			$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id      = 'learndash_experiments';
			$this->settings_page_title   = esc_html__( 'Experiments', 'learndash' );
			$this->settings_tab_title    = $this->settings_page_title;
			$this->settings_tab_priority = 110;
			$this->show_quick_links_meta = false;

			parent::__construct();
		}
	}
}
