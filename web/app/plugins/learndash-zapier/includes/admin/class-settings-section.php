<?php
if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Zapier_Settings_Section' ) ) ) {
	class LearnDash_Zapier_Settings_Section extends LearnDash_Settings_Section {

		function __construct() {
			$this->settings_page_id         = 'learndash-zapier-settings';

			// This is the 'option_name' key used in the wp_options table
			$this->setting_option_key       = '';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix     = '';

			// Used within the Settings API to uniquely identify this section
			$this->settings_section_key     = 'zapier_settings';

			// Section label/header
			$this->settings_section_label   = esc_html__( 'Settings', 'learndash-zapier' );

			parent::__construct();
		}

		function load_settings_values() {
			parent::load_settings_values();

			$_INITIALIZE = false;
			if ( $this->setting_option_values === false ) {
				$_INITIALIZE = true;
				$this->setting_option_values = array();
			}

			$this->setting_option_values = wp_parse_args(
				$this->setting_option_values,
				array(
					'api_key' => '',
				)
			);
		}

		function load_settings_fields() {
			$this->setting_option_fields = array(
				'learndash_zapier_api_key' => array(
					'name'      => 'learndash_zapier_api_key',
					'type'      => 'text',
					'label'     => esc_html__( 'API Key', 'learndash-zapier' ),
					'help_text' => esc_html__( 'API key that needs to be entered to your Zapier account authentication settings when creating a new zap.', 'learndash-zapier' ),
					'value'     => get_option( 'learndash_zapier_api_key' ),
					'attrs'     => array(
						'readonly' => 'readonly',
					),
				),
			);

			$this->setting_option_fields = apply_filters( 'learndash_zapier_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Zapier_Settings_Section::add_section_instance();
	}
);
