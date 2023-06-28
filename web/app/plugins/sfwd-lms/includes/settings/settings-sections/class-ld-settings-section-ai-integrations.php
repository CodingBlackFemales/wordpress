<?php
/**
 * LearnDash Settings Section for AI Integrations Metabox.
 *
 * @since 4.6.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnDash\Core\Utilities\Str;

if ( class_exists( 'LearnDash_Settings_Section' ) && ! class_exists( 'LearnDash_Settings_Section_AI_Integrations' ) ) {
	/**
	 * Class LearnDash Settings Section for AI Integrations Metabox.
	 *
	 * @since 4.6.0
	 */
	class LearnDash_Settings_Section_AI_Integrations extends LearnDash_Settings_Section {
		/**
		 * Protected constructor for class
		 *
		 * @since 4.6.0
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_advanced';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_ai_integrations';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_ai_integrations';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_ai_integrations';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'AI Integrations', 'learndash' );

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 4.6.0
		 *
		 * @return void
		 */
		public function load_settings_fields(): void {
			$this->setting_option_fields = [
				'openai_api_key' => [
					'name'       => 'openai_api_key',
					'type'       => 'password',
					'label'      => esc_html__( 'OpenAI API Key', 'learndash' ),
					'input_note' => ! empty( $this->setting_option_values['openai_api_key'] ?? '' )
						? sprintf(
							// translators: Secret key.
							esc_html__( 'Saved key: %1$s', 'learndash' ),
							Str::mask( $this->setting_option_values['openai_api_key'], '*', 3, 45 )
						)
						: '',
					'help_text'  => sprintf(
						// translators: HTML tags.
						esc_html__( '%1$sClick here%2$s to get your API key.', 'learndash' ),
						'<a href="https://platform.openai.com/account/api-keys" target="_blank" rel="noreferrer noopener">',
						'</a>'
					),
					'value'      => ! empty( $this->setting_option_values['openai_api_key'] ?? '' ) ? $this->get_placeholder_for_keys() : '',
					'class'      => 'regular-text',
				],
			];

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}
	}
}

add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_AI_Integrations::add_section_instance();
	}
);
