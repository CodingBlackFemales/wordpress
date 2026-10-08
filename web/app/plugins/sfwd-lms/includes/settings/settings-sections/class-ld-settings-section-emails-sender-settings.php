<?php
/**
 * LearnDash Settings Section for Email Sender Settings Metabox.
 *
 * @since 3.6.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Emails_Sender_Settings' ) ) ) {

	/**
	 * Class LearnDash Settings Section for Emails Sender Settings Metabox.
	 *
	 * @since 3.6.0
	 */
	class LearnDash_Settings_Section_Emails_Sender_Settings extends LearnDash_Settings_Section {

		/**
		 * Current Section
		 *
		 * @var string $current_section
		 */
		private $current_section = '';

		/**
		 * Protected constructor for class
		 *
		 * @since 3.6.0
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_emails';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_emails_sender';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_emails_sender';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_emails_sender';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Email Sender Settings', 'learndash' );

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 3.6.0
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			if ( ! isset( $this->setting_option_values['from_name'] ) ) {
				$this->setting_option_values['from_name'] = '';
			}

			if ( ! isset( $this->setting_option_values['from_email'] ) ) {
				$this->setting_option_values['from_email'] = '';
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 3.6.0
		 */
		public function load_settings_fields() {

			$this->setting_option_fields = array();

			$this->setting_option_fields['from_name'] = array(
				'name'        => 'from_name',
				'label'       => esc_html__( '"From" name', 'learndash' ),
				'type'        => 'text',
				'help_text'   => esc_html__( 'How the sender name appears in outgoing emails.', 'learndash' ),
				'value'       => $this->setting_option_values['from_name'],
				'placeholder' => esc_html__( 'If empty will use site title', 'learndash' ),
			);

			$this->setting_option_fields['from_email'] = array(
				'name'        => 'from_email',
				'label'       => esc_html__( '"From" email', 'learndash' ),
				'type'        => 'email',
				'help_text'   => esc_html__( 'How the sender email appears in outgoing emails.', 'learndash' ),
				'value'       => $this->setting_option_values['from_email'],
				'placeholder' => esc_html__( 'If empty will use site administration email address', 'learndash' ),
			);

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}


		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Emails_Sender_Settings::add_section_instance();
	}
);
