<?php
/**
 * LearnDash Settings Section for Email Purchase Invoice Metabox.
 *
 * @since 4.1.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Emails_Purchase_Invoice' ) ) ) {

	/**
	 * Class LearnDash Settings Section for Emails New User Registration Metabox.
	 *
	 * @since 4.1.0
	 */
	class LearnDash_Settings_Section_Emails_Purchase_Invoice extends LearnDash_Settings_Section {
		/**
		 * Protected constructor for class
		 *
		 * @since 4.1.0
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_emails';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_emails_purchase_invoice';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_emails_purchase_invoice';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_emails_purchase_invoice';

			// Used to associate this section with the parent section.
			$this->settings_parent_section_key = 'settings_emails_list';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Purchase Invoice', 'learndash' );

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 4.1.0
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			$new_settings = false;
			if ( ! is_array( $this->setting_option_values ) ) {
				$new_settings                = true;
				$this->setting_option_values = array();
			}

			if ( ! isset( $this->setting_option_values['enabled'] ) ) {
				if ( true === $new_settings ) {
					$this->setting_option_values['enabled'] = 'yes';
				} else {
					$this->setting_option_values['enabled'] = '';
				}
			}

			if ( ! isset( $this->setting_option_values['recipients'] ) ) {
				$this->setting_option_values['recipients'] = esc_html__( 'Customer', 'learndash' );
			}

			if ( ! isset( $this->setting_option_values['subject'] ) ) {
				$this->setting_option_values['subject'] = '';
			}

			if ( ! isset( $this->setting_option_values['message'] ) ) {
				$this->setting_option_values['message'] = '';
			}

			if ( ! isset( $this->setting_option_values['purchaser_name'] ) ) {
				$this->setting_option_values['purchaser_name'] = '';
			}

			if ( ! isset( $this->setting_option_values['vat_number'] ) ) {
				$this->setting_option_values['vat_number'] = '';
			}

			if ( ! isset( $this->setting_option_values['company_name'] ) ) {
				$this->setting_option_values['company_name'] = '';
			}

			if ( ! isset( $this->setting_option_values['company_address'] ) ) {
				$this->setting_option_values['company_address'] = '';
			}

			if ( ! isset( $this->setting_option_values['company_logo'] ) ) {
				$this->setting_option_values['company_logo'] = 0;
			}

			if ( ! isset( $this->setting_option_values['logo_location'] ) ) {
				$this->setting_option_values['logo_location'] = 'right';
			}

			if ( ! isset( $this->setting_option_values['content_type'] ) ) {
				$this->setting_option_values['content_type'] = 'text/html';
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 4.1.0
		 */
		public function load_settings_fields() {

			$this->setting_option_fields = array();

			$this->setting_option_fields['enabled'] = array(
				'name'    => 'enabled',
				'type'    => 'checkbox-switch',
				'label'   => esc_html__( 'Active', 'learndash' ),
				'value'   => $this->setting_option_values['enabled'],
				'default' => '',
				'options' => array(
					'on' => '',
					''   => '',
				),
			);

			$this->setting_option_fields['recipients'] = array(
				'name'  => 'recipients',
				'label' => esc_html__( 'Recipient(s)', 'learndash' ),
				'type'  => 'html',
				'value' => $this->setting_option_values['recipients'],
			);

			$this->setting_option_fields['subject'] = array(
				'name'    => 'subject',
				'label'   => esc_html__( 'Subject', 'learndash' ),
				'type'    => 'text',
				'class'   => '-medium',
				'value'   => $this->setting_option_values['subject'],
				'default' => '',
			);

			$this->setting_option_fields['message'] = array(
				'name'              => 'message',
				'label'             => esc_html__( 'Message', 'learndash' ),
				'type'              => 'wpeditor',
				'value'             => $this->setting_option_values['message'],
				'default'           => '',
				'editor_args'       => array(
					'textarea_name' => $this->setting_option_key . '[message]',
					'textarea_rows' => 8,
				),
				'label_description' => '<div>
					<h4>' . esc_html__( 'Supported placeholders', 'learndash' ) . ':</h4>
					<ul>
						<li><span>{user_login}</span> - ' . esc_html__( 'User Login', 'learndash' ) . '</li>
						<li><span>{first_name}</span> - ' . esc_html__( 'User first name', 'learndash' ) . '</li>
						<li><span>{last_name}</span> - ' . esc_html__( 'User last name', 'learndash' ) . '</li>
						<li><span>{display_name}</span> - ' . esc_html__( 'User display name', 'learndash' ) . '</li>
						<li><span>{user_email}</span> - ' . esc_html__( 'User email', 'learndash' ) . '</li>
						<li><span>{post_title}</span> - ' . sprintf(
							// translators: placeholders: Course, Group label.
							esc_html_x( '%1$s/%2$s Title', 'placeholders: Course, Group label', 'learndash' ),
							learndash_get_custom_label( 'course' ),
							learndash_get_custom_label( 'group' )
						) . '</li>
					</ul>
				</div>',
			);

			$this->setting_option_fields['purchaser_name'] = array(
				'name'      => 'purchaser_name',
				'label'     => esc_html__( 'Customer Name', 'learndash' ),
				'type'      => 'text',
				'class'     => '-medium',
				'value'     => $this->setting_option_values['purchaser_name'],
				'default'   => '',
				'help_text' => '<div>
					<h4>' . esc_html__( 'Supported placeholders', 'learndash' ) . ':</h4>
					<ul>
						<li><span>{user_login}</span> - ' . esc_html__( 'User Login', 'learndash' ) . '</li>
						<li><span>{first_name}</span> - ' . esc_html__( 'User first name', 'learndash' ) . '</li>
						<li><span>{last_name}</span> - ' . esc_html__( 'User last name', 'learndash' ) . '</li>
						<li><span>{display_name}</span> - ' . esc_html__( 'User display name', 'learndash' ) . '</li>
					</ul>
				</div>',
			);

			$this->setting_option_fields['vat_number'] = array(
				'name'    => 'vat_number',
				'label'   => esc_html__( 'Vat/Tax Number', 'learndash' ),
				'type'    => 'text',
				'class'   => '-medium',
				'value'   => $this->setting_option_values['vat_number'],
				'default' => '',
			);

			$this->setting_option_fields['company_name'] = array(
				'name'    => 'company_name',
				'label'   => esc_html__( 'Company Name', 'learndash' ),
				'type'    => 'text',
				'class'   => '-medium',
				'value'   => $this->setting_option_values['company_name'],
				'default' => '',
			);

			$this->setting_option_fields['company_address'] = array(
				'name'    => 'company_address',
				'label'   => esc_html__( 'Company Address', 'learndash' ),
				'type'    => 'text',
				'class'   => '-medium',
				'value'   => $this->setting_option_values['company_address'],
				'default' => '',
			);

			$this->setting_option_fields['company_logo'] = array(
				'name'              => 'company_logo',
				'type'              => 'media-upload',
				'label'             => esc_html__( 'Logo Upload', 'learndash' ),
				'help_text'         => '<div>
											<p>This logo will appear on the Purchase Invoice template. Optional.</p>
											<p>Supported formats: .jpg, .png</p>
											<p><strong>Note:</strong> PNG with alpha channel images require Imagick or GD extensions to function, contact your host for more information.</p>
										</div>',
				'value'             => $this->setting_option_values['company_logo'],
				'validate_callback' => array( $this, 'validate_section_field_media_upload' ),
				'validate_args'     => array(
					'allow_empty' => 1,
				),
			);

			$this->setting_option_fields['logo_location'] = array(
				'name'      => 'logo_location',
				'type'      => 'select',
				'label'     => esc_html__( 'Logo Location', 'learndash' ),
				'help_text' => 'Whether to display the logo on the left or right of the purchase invoice.',
				'value'     => $this->setting_option_values['logo_location'],
				'default'   => 'right',
				'options'   => array(
					'right' => esc_html__( 'Right', 'learndash' ),
					'left'  => esc_html__( 'Left', 'learndash' ),
				),
			);

			$this->setting_option_fields['content_type'] = array(
				'name'    => 'content_type',
				'type'    => 'select',
				'label'   => esc_html__( 'Content Type', 'learndash' ),
				'value'   => $this->setting_option_values['content_type'],
				'default' => 'text/html',
				'options' => array(
					'text/html'  => esc_html__( 'HTML/Text', 'learndash' ),
					'text/plain' => esc_html__( 'Text only', 'learndash' ),
				),
			);

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		/**
		 * Validate settings field.
		 *
		 * @since 4.1.0
		 *
		 * @param string $val Value to be validated.
		 * @param string $key settings fields key.
		 * @param array  $args Settings field args array.
		 *
		 * @return integer $val.
		 */
		public function validate_section_field_media_upload( $val, $key, $args = array() ) {
			// Get the digits only.
			$val = absint( $val );
			if ( ( isset( $args['field']['validate_args']['allow_empty'] ) ) && ( true == $args['field']['validate_args']['allow_empty'] ) && ( empty( $val ) ) ) {
				$val = '';
			}
			return $val;
		}

		/**
		 * Filter the section saved values.
		 *
		 * @since 4.1.0
		 *
		 * @param array  $value                An array of setting fields values.
		 * @param array  $old_value            An array of setting fields old values.
		 * @param string $settings_section_key Settings section key.
		 * @param string $settings_screen_id   Settings screen ID.
		 */
		public function filter_section_save_fields( $value, $old_value, $settings_section_key, $settings_screen_id ) {
			if ( $settings_section_key === $this->settings_section_key ) {
				if ( ! isset( $value['enabled'] ) ) {
					$value['enabled'] = '';
				}

				if ( ! isset( $value['content_type'] ) ) {
					$value['content_type'] = esc_html__( 'PDF', 'learndash' );
				}

				if ( isset( $_POST['learndash_settings_emails_list_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					if ( ! is_array( $old_value ) ) {
						$old_value = array();
					}

					foreach ( $value as $value_idx => $value_val ) {
						$old_value[ $value_idx ] = $value_val;
					}

					$value = $old_value;
				}
			}

			return $value;
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Emails_Purchase_Invoice::add_section_instance();
	}
);
