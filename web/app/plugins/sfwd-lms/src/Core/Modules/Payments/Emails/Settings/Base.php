<?php
/**
 * LearnDash Payments Emails Settings Base class.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Emails\Settings;

use LearnDash_Settings_Section;

/**
 * Base class for payment email settings.
 *
 * @since 4.25.3
 */
abstract class Base extends LearnDash_Settings_Section {
	/**
	 * The default email subject.
	 *
	 * @since 4.25.3
	 *
	 * @var string
	 */
	protected $default_subject = '';

	/**
	 * The default email message.
	 *
	 * @since 4.25.3
	 *
	 * @var string
	 */
	protected $default_message = '';

	/**
	 * Constructor.
	 *
	 * @since 4.25.3
	 *
	 * @param string $settings_key   The settings key suffix.
	 * @param string $settings_label The settings section label.
	 *
	 * @return void
	 */
	public function __construct( string $settings_key, string $settings_label ) {
		$this->settings_page_id = 'learndash_lms_emails';

		// This is the 'option_name' key used in the wp_options table.
		$this->setting_option_key = 'learndash_settings_payments_emails_' . $settings_key;

		// This is the HTML form field prefix used.
		$this->setting_field_prefix = 'learndash_settings_payments_emails_' . $settings_key;

		// Used within the Settings API to uniquely identify this section.
		$this->settings_section_key = 'settings_payments_emails_' . $settings_key;

		// Used to associate this section with the parent section.
		$this->settings_parent_section_key = 'settings_emails_list';

		// Section label/header.
		$this->settings_section_label = $settings_label;

		// This is the HTML form field prefix used.
		parent::__construct();
	}


	/**
	 * Initializes the metabox settings values.
	 *
	 * @since 4.25.3
	 *
	 * @return void
	 */
	public function load_settings_values() {
		parent::load_settings_values();

		$new_settings =
			! $this->setting_option_initialized
			&& empty( $this->setting_option_values );

		if ( ! isset( $this->setting_option_values['enabled'] ) ) {
			$this->setting_option_values['enabled'] = $new_settings
				? 'yes'
				: '';
		}

		if ( ! isset( $this->setting_option_values['recipients'] ) ) {
			$this->setting_option_values['recipients'] = esc_html__( 'Customer', 'learndash' );
		}

		if (
			! isset( $this->setting_option_values['subject'] )
			|| empty( $this->setting_option_values['subject'] )
		) {
			$this->setting_option_values['subject'] = $this->default_subject;
		}

		if (
			! isset( $this->setting_option_values['message'] )
			|| empty( $this->setting_option_values['message'] )
		) {
			$this->setting_option_values['message'] = $this->default_message;
		}

		if ( ! isset( $this->setting_option_values['content_type'] ) ) {
			$this->setting_option_values['content_type'] = 'text/html';
		}
	}

	/**
	 * Initializes the metabox settings fields.
	 *
	 * @since 4.25.3
	 *
	 * @return void
	 */
	public function load_settings_fields() {
		$this->setting_option_fields = [];

		$this->setting_option_fields['enabled'] = [
			'name'    => 'enabled',
			'type'    => 'checkbox-switch',
			'label'   => esc_html__( 'Active', 'learndash' ),
			'value'   => $this->setting_option_values['enabled'],
			'options' => [
				'on' => '',
				''   => '',
			],
		];

		$this->setting_option_fields['recipients'] = [
			'name'  => 'recipients',
			'label' => esc_html__( 'Recipient(s)', 'learndash' ),
			'type'  => 'html',
			'value' => $this->setting_option_values['recipients'],
		];

		$this->setting_option_fields['subject'] = [
			'name'  => 'subject',
			'label' => esc_html__( 'Subject', 'learndash' ),
			'type'  => 'text',
			'class' => '-medium',
			'value' => $this->setting_option_values['subject'],
		];

		$this->setting_option_fields['message'] = [
			'name'              => 'message',
			'label'             => esc_html__( 'Message', 'learndash' ),
			'type'              => 'wpeditor',
			'value'             => $this->setting_option_values['message'],
			'editor_args'       => array(
				'textarea_name' => $this->setting_option_key . '[message]',
				'textarea_rows' => 8,
			),
			'label_description' => '<div>
					<h4>' . esc_html__( 'Supported placeholders', 'learndash' ) . ':</h4>
					<ul>
						<li><span>{user_login}</span> - ' . esc_html__( 'User Login', 'learndash' ) . '</li>
						<li><span>{first_name}</span> - ' . esc_html__( 'User First Name', 'learndash' ) . '</li>
						<li><span>{last_name}</span> - ' . esc_html__( 'User Last Name', 'learndash' ) . '</li>
						<li><span>{display_name}</span> - ' . esc_html__( 'User Display Name', 'learndash' ) . '</li>
						<li><span>{user_email}</span> - ' . esc_html__( 'User Email', 'learndash' ) . '</li>
						<li><span>{product_id}</span> - ' . esc_html__( 'Product ID', 'learndash' ) . '</li>
						<li><span>{product_name}</span> - ' . esc_html__( 'Product Name', 'learndash' ) . '</li>
						<li><span>{product_url}</span> - ' . esc_html__( 'Product URL', 'learndash' ) . '</li>
						<li><span>{site_title}</span> - ' . esc_html__( 'Site Title', 'learndash' ) . '</li>
						<li><span>{site_url}</span> - ' . esc_html__( 'Site URL', 'learndash' ) . '</li>
					</ul>
				</div>',
		];

		$this->setting_option_fields['content_type'] = [
			'name'    => 'content_type',
			'type'    => 'select',
			'label'   => esc_html__( 'Content Type', 'learndash' ),
			'value'   => $this->setting_option_values['content_type'],
			'default' => 'text/html',
			'options' => [
				'text/html'  => esc_html__( 'HTML/Text', 'learndash' ),
				'text/plain' => esc_html__( 'Text only', 'learndash' ),
			],
		];

		/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
		$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

		parent::load_settings_fields();
	}

	/**
	 * Filters the section saved values.
	 *
	 * @since 4.25.3
	 *
	 * @param array<string, mixed> $value                An array of setting fields values.
	 * @param array<string, mixed> $old_value            An array of setting fields old values.
	 * @param string               $settings_section_key Settings section key.
	 * @param string               $settings_screen_id   Settings screen ID.
	 *
	 * @return array<string, mixed> The filtered values.
	 */
	public function filter_section_save_fields( $value, $old_value, $settings_section_key, $settings_screen_id ) {
		if (
			$settings_section_key !== $this->settings_section_key
			|| ! isset( $_POST['learndash_settings_emails_list_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['learndash_settings_emails_list_nonce'] ) ), $this->setting_option_key )
		) {
			return $value;
		}

		if ( ! isset( $value['enabled'] ) ) {
			$value['enabled'] = '';
		}

		if ( ! is_array( $old_value ) ) {
			$old_value = [];
		}

		foreach ( $value as $value_idx => $value_val ) {
			$old_value[ $value_idx ] = $value_val;
		}

		$value = $old_value;

		return $value;
	}
}
