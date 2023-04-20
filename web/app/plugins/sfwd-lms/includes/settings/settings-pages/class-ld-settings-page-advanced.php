<?php
/**
 * LearnDash Settings Page Advanced.
 *
 * @since   3.6.0
 * @package LearnDash\Settings\Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'LearnDash_Settings_Page' ) && ! class_exists( 'LearnDash_Settings_Page_Advanced' ) ) {
	/**
	 * Class LearnDash Settings Page Advanced.
	 *
	 * @since 3.6.0
	 */
	class LearnDash_Settings_Page_Advanced extends LearnDash_Settings_Page {
		/**
		 * Public constructor for class
		 *
		 * @since 3.6.0
		 */
		public function __construct() {
			$this->parent_menu_page_url  = 'admin.php?page=learndash_lms_settings';
			$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id      = 'learndash_lms_advanced';
			$this->settings_page_title   = esc_html_x( 'Advanced', 'Advanced settings Label', 'learndash' );
			$this->settings_tab_priority = 100;

			$this->show_quick_links_meta   = false;
			$this->settings_metabox_as_sub = true;

			add_action( 'learndash_settings_page_init', array( $this, 'learndash_settings_page_init' ), 10, 1 );

			parent::__construct();
		}

		/**
		 * Settings page init. Called from `learndash_settings_page_init` action.
		 *
		 * @since 3.6.0
		 *
		 * @param string $settings_page_id Settings Page ID.
		 */
		public function learndash_settings_page_init( string $settings_page_id ) {
			if ( $settings_page_id !== $this->settings_page_id ) {
				return;
			}

			if ( true !== $this->settings_metabox_as_sub ) {
				return;
			}

			/**
			 * Filters the list of advanced settings pages which should not display metaboxes.
			 *
			 * @since 4.5.0
			 *
			 * @param string[] $section_keys Section keys.
			 */
			$section_keys = apply_filters( 'learndash_admin_settings_advanced_sections_with_hidden_metaboxes', array() );

			if ( in_array( $this->get_current_settings_section_as_sub(), $section_keys, true ) ) {
				$this->show_submit_meta      = false;
				$this->show_quick_links_meta = false;
				$this->settings_columns      = 1;
			}
		}
	}
}

add_action(
	'learndash_settings_pages_init',
	function () {
		LearnDash_Settings_Page_Advanced::add_page_instance();
	}
);
