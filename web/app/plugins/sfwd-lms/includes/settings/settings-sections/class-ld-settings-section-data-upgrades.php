<?php
/**
 * LearnDash Settings Section for Data Upgrades Metabox.
 *
 * @since 2.6.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Data_Upgrades' ) ) ) {
	/**
	 * Class LearnDash Settings Section for Data Upgrades Metabox.
	 *
	 * @since 2.6.0
	 */
	class LearnDash_Settings_Section_Data_Upgrades extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 *
		 * @since 2.6.0
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_advanced';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_data_upgrades';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_data_upgrades';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_data_upgrades';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Data Upgrades', 'learndash' );

			add_action( 'learndash_settings_page_load', array( $this, 'load_settings_page' ), 30, 2 );

			parent::__construct();

			add_filter(
				'learndash_admin_settings_advanced_sections_with_hidden_metaboxes',
				function( array $section_keys ) {
					$section_keys[] = $this->settings_section_key;

					return $section_keys;
				}
			);
		}

		/**
		 * Show Settings Section meta box.
		 *
		 * @since 2.6.0
		 */
		public function show_meta_box() {
			$ld_admin_data_upgrades = Learndash_Admin_Data_Upgrades::get_instance();
			$ld_admin_data_upgrades->admin_page();
		}

		/**
		 * Load settings page.
		 *
		 * Called from action `learndash_settings_page_load`.
		 *
		 * @since 3.6.0
		 *
		 * @param string $settings_screen_id Settings Screen ID.
		 * @param string $settings_page_id   Settings Page ID.
		 */
		public function load_settings_page( $settings_screen_id = '', $settings_page_id = '' ) {
			if ( $settings_page_id === $this->settings_page_id ) {
				global $learndash_assets_loaded;

				wp_enqueue_style(
					'learndash-admin-style',
					LEARNDASH_LMS_PLUGIN_URL . 'assets/css/learndash-admin-style' . learndash_min_asset() . '.css',
					array(),
					LEARNDASH_SCRIPT_VERSION_TOKEN
				);
				wp_style_add_data( 'learndash-admin-style', 'rtl', 'replace' );
				$learndash_assets_loaded['styles']['learndash-admin-style'] = __FUNCTION__;

				wp_enqueue_script(
					'learndash-admin-settings-data-upgrades-script',
					LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash-admin-settings-data-upgrades' . learndash_min_asset() . '.js',
					array( 'jquery' ),
					LEARNDASH_SCRIPT_VERSION_TOKEN,
					true
				);
				$learndash_assets_loaded['scripts']['learndash-admin-settings-data-upgrades-script'] = __FUNCTION__;
			}
		}
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Data_Upgrades::add_section_instance();
	}
);
