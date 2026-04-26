<?php
/**
 * LearnDash Settings Section for Terms and Privacy Pages Metabox.
 *
 * @since 4.20.2
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Terms_Pages' ) ) ) {
	/**
	 * Class LearnDash Settings Section for Terms and Privacy Pages Metabox.
	 *
	 * @since 4.20.2
	 */
	class LearnDash_Settings_Section_Terms_Pages extends LearnDash_Settings_Section {
		/**
		 * Protected constructor for class
		 *
		 * @since 4.20.2
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_registration';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_terms_pages';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_terms_pages';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_terms_pages';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Terms/Policies', 'learndash' );

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 4.20.2
		 *
		 * @return void
		 */
		public function load_settings_values(): void {
			parent::load_settings_values();

			if ( ! isset( $this->setting_option_values['terms_enabled'] ) ) {
				$this->setting_option_values['terms_enabled'] = '';
			}

			if ( ! isset( $this->setting_option_values['terms_page'] ) ) {
				$this->setting_option_values['terms_page'] = '';
			}

			if ( ! isset( $this->setting_option_values['privacy_enabled'] ) ) {
				$this->setting_option_values['privacy_enabled'] = '';
			}

			if ( ! isset( $this->setting_option_values['privacy_page'] ) ) {
				$this->setting_option_values['privacy_page'] = '';
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 4.20.2
		 *
		 * @return void
		 */
		public function load_settings_fields(): void {
			$this->setting_option_fields = [];

			$this->setting_option_fields['terms_enabled']  = [
				'name'                => 'terms_enabled',
				'type'                => 'checkbox-switch',
				'help_text'           => esc_html__( 'This will force all registering students to read and accept your terms and conditions before they can sign-up.', 'learndash' ),
				'label'               => LearnDash_Custom_Label::get_label( 'terms_of_service' ),
				'value'               => $this->setting_option_values['terms_enabled'],
				'child_section_state' => ! empty( $this->setting_option_values['terms_enabled'] ) ? 'open' : 'closed',
				'options'             => [
					'yes' => '',
				],
			];

			$custom_label_url = admin_url( 'admin.php?page=learndash_lms_advanced&section-advanced=settings_custom_labels' );
			$input_note       = sprintf(
				/* translators: %1$s: Terms of Service label, %2$s: opening anchor tag, %3$s: closing anchor tag. */
				__( 'Select the page(s) that will provide content for the %1$s Checkbox. Customize the “Terms of Service” label on %2$sLearnDash > Settings > Advanced > Custom Labels%3$s', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'terms_of_service' ),
				'<a href="' . esc_url( $custom_label_url ) . '" target="_blank">',
				'</a>'
			);
			$this->setting_option_fields['terms_page'] = [
				'name'             => 'terms_page',
				'type'             => 'select',
				'parent_setting'   => 'terms_enabled',
				'label'            => esc_html__( 'Display content from', 'learndash' ),
				'input_note'       => $input_note,
				'value'            => $this->setting_option_values['terms_page'],
				'display_callback' => '\LearnDash_Settings_Section_Registration_Pages::display_pages_selector',
			];

			$this->setting_option_fields['privacy_enabled'] = [
				'name'                => 'privacy_enabled',
				'type'                => 'checkbox-switch',
				'label'               => LearnDash_Custom_Label::get_label( 'privacy_policy' ),
				'help_text'           => esc_html__( 'This will force all registering students to read and accept your privacy policy before they can sign-up.', 'learndash' ),
				'value'               => $this->setting_option_values['privacy_enabled'],
				'child_section_state' => ! empty( $this->setting_option_values['privacy_enabled'] ) ? 'open' : 'closed',
				'options'             => [
					'yes' => '',
				],
			];

			$input_note = sprintf(
				/* translators: %1$s: Privacy Policy label, %2$s: opening anchor tag, %3$s: closing anchor tag. */
				__( 'Select the page(s) that will provide content for the %1$s Checkbox. Customize the “Privacy Policy” label on %2$sLearnDash > Settings > Advanced > Custom Labels%3$s', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'privacy_policy' ),
				'<a href="' . esc_url( $custom_label_url ) . '" target="_blank">',
				'</a>'
			);
			$this->setting_option_fields['privacy_page'] = [
				'name'             => 'privacy_page',
				'type'             => 'select',
				'parent_setting'   => 'privacy_enabled',
				'label'            => esc_html__( 'Display content from', 'learndash' ),
				'input_note'       => $input_note,
				'value'            => $this->setting_option_values['privacy_page'],
				'display_callback' => '\LearnDash_Settings_Section_Registration_Pages::display_pages_selector',
			];

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}
	}
}
add_action(
	'learndash_settings_sections_init',
	function () {
		LearnDash_Settings_Section_Terms_Pages::add_section_instance();
	}
);
