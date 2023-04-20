<?php
/**
 * LearnDash Settings Section for Certificates Custom Post Type Metabox.
 *
 * @since 3.2.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Certificates_CPT' ) ) ) {
	/**
	 * Class LearnDash Settings Section for Certificates Custom Post Type Metabox.
	 *
	 * @since 3.2.0
	 */
	class LearnDash_Settings_Certificates_CPT extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 *
		 * @since 3.2.0
		 */
		protected function __construct() {

			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-certificates_page_certificate-options';

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'certificate-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_certificates_cpt';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_certificates_cpt';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'cpt_options';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Certificate Custom Post Type Options', 'learndash' );

			// Used to show the section description above the fields. Can be empty.
			$this->settings_section_description = esc_html__( 'Control options specific to the Certificates post type', 'learndash' );

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 3.2.0
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			if ( ( false === $this->setting_option_values ) || ( '' === $this->setting_option_values ) ) {
				if ( '' === $this->setting_option_values ) {
					$this->setting_option_values = array();
				}

				$this->setting_option_values = array(
					'supports' => array( 'thumbnail', 'revisions' ),
				);
			}

			if ( ! isset( $this->setting_option_values['supports'] ) ) {
				$this->setting_option_values['supports'] = array( 'thumbnail', 'revisions' );
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 3.2.0
		 */
		public function load_settings_fields() {

			$this->setting_option_fields = array(
				'supports' => array(
					'name'      => 'supports',
					'type'      => 'checkbox',
					'label'     => esc_html__( 'Editor Supported Settings', 'learndash' ),
					'help_text' => esc_html__( 'Enables WordPress supported settings within the editor and theme.', 'learndash' ),
					'value'     => $this->setting_option_values['supports'],
					'options'   => array(
						'thumbnail'     => esc_html__( 'Featured image', 'learndash' ),
						'custom-fields' => esc_html__( 'Custom Fields', 'learndash' ),
						'revisions'     => esc_html__( 'Revisions', 'learndash' ),
					),
				),
			);

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		/**
		 * Intercept the WP options save logic and check that we have a valid nonce.
		 *
		 * @since 3.2.0
		 *
		 * @param array  $new_values          Array of section fields values.
		 * @param array  $old_values          Array of old values.
		 * @param string $setting_option_key Section option key should match $this->setting_option_key.
		 */
		public function section_pre_update_option( $new_values = '', $old_values = '', $setting_option_key = '' ) {
			if ( $setting_option_key === $this->setting_option_key ) {
				$new_values = parent::section_pre_update_option( $new_values, $old_values, $setting_option_key );

				if ( ! isset( $new_values['supports'] ) ) {
					$new_values['supports'] = array();
				}
			}

			return $new_values;
		}

		// End of functions.
	}
}

add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Certificates_CPT::add_section_instance();
	}
);
