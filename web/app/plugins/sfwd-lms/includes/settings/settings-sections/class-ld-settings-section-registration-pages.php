<?php
/**
 * LearnDash Settings Section for Registration Pages Metabox.
 *
 * @since 3.6.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Registration_Pages' ) ) ) {

	/**
	 * Class LearnDash Settings Section for Registration Pages Metabox.
	 *
	 * @since 3.6.0
	 */
	class LearnDash_Settings_Section_Registration_Pages extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 *
		 * @since 3.6.0
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_registration';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_registration_pages';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_registration_pages';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_registration_pages';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Registration/Login Pages', 'learndash' );

			parent::__construct();
			$this->save_settings_fields();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 3.6.0
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			if ( ! isset( $this->setting_option_values['registration'] ) ) {
				$this->setting_option_values['registration'] = '';
			}

			if ( ! isset( $this->setting_option_values['registration_success'] ) ) {
				$this->setting_option_values['registration_success'] = '';
			}

			if ( ! isset( $this->setting_option_values['reset_password'] ) ) {
				$this->setting_option_values['reset_password'] = '';
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 3.6.0
		 */
		public function load_settings_fields() {
			$this->setting_option_fields = array();

			$this->setting_option_fields['registration'] = array(
				'name'             => 'registration',
				'type'             => 'select',
				'label'            => esc_html__( 'Registration/Login', 'learndash' ),
				'value'            => $this->setting_option_values['registration'],
				'display_callback' => array( $this, 'display_pages_selector' ),
			);

			$this->setting_option_fields['registration_success'] = array(
				'name'             => 'registration_success',
				'type'             => 'select',
				'label'            => esc_html__( 'Registration Success', 'learndash' ),
				'value'            => $this->setting_option_values['registration_success'],
				'display_callback' => array( $this, 'display_pages_selector' ),
			);

			$this->setting_option_fields['reset_password'] = array(
				'name'             => 'reset_password',
				'type'             => 'select',
				'label'            => esc_html__( 'Reset Password', 'learndash' ),
				'value'            => $this->setting_option_values['reset_password'],
				'display_callback' => array( $this, 'display_pages_selector' ),
			);

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		/**
		 * Display function for custom selectors.
		 *
		 * @since 3.6.0
		 *
		 * @param array $field_args An array of field arguments used to process the output.
		 */
		public function display_pages_selector( $field_args = array() ) {
			$html = '';

			/** This filter is documented in includes/settings/settings-fields/class-ld-settings-fields-checkbox-switch.php */
			$field_args = apply_filters( 'learndash_settings_field', $field_args );

			if ( ( isset( $field_args['type'] ) ) && ( ! empty( $field_args['type'] ) ) ) {
				$field_ref = LearnDash_Settings_Fields::get_field_instance( $field_args['type'] );
				if ( is_a( $field_ref, 'LearnDash_Settings_Fields' ) ) {

					/** This filter is documented in includes/settings/settings-fields/class-ld-settings-fields-checkbox-switch.php */
					$html = apply_filters( 'learndash_settings_field_html_before', '', $field_args );

					$html .= '<span class="ld-select">';

					$field_name  = $field_ref->get_field_attribute_name( $field_args, false );
					$field_id    = $field_ref->get_field_attribute_id( $field_args, false );
					$field_class = $field_ref->get_field_attribute_class( $field_args, false );

					$select_args = array(
						'echo'             => false,
						'show_option_none' => esc_html__( 'Select a Page...', 'learndash' ),
						'name'             => $field_name,
						'id'               => $field_id,
						'class'            => $field_class,
					);
					if ( isset( $field_args['value'] ) ) {
						$select_args['selected'] = $field_args['value'];
					}

					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$select_output = wp_dropdown_pages( $select_args );

					if ( learndash_use_select2_lib() ) {
						$select_output = str_replace( '<select ', '<select data-ld-select2="1" ', $select_output );
					}
					$html .= $select_output;

					$html .= '</span>';

					/** This filter is documented in includes/settings/settings-fields/class-ld-settings-fields-checkbox-switch.php */
					$html = apply_filters( 'learndash_settings_field_html_after', $html, $field_args );
				}
			}

			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
		}

		/**
		 * Save the metabox fields. This is needed due to special processing needs.
		 *
		 * @since 4.4.0
		 *
		 * @return void
		 */
		public function save_settings_fields(): void {
			if ( ! isset( $_POST['_wpnonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return;
			}

			$reset_password_id = isset( $_POST[ $this->setting_field_prefix ]['reset_password'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			? (int) (
				sanitize_text_field(
					wp_unslash( $_POST[ $this->setting_field_prefix ]['reset_password'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
				)
			)
			: '';

			if ( empty( $reset_password_id ) ) {
				return;
			}

			$content = get_the_content( '', false, $reset_password_id );

			if ( ! strpos( $content, 'ld-reset-password' ) ) {
				wp_update_post(
					array(
						'ID'           => $reset_password_id,
						'post_title'   => get_the_title( $reset_password_id ),
						'post_content' => $content . '<!-- wp:learndash/ld-reset-password {"width":""} /-->',
						'post_type'    => 'page',
					)
				);
			}
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Registration_Pages::add_section_instance();
	}
);
