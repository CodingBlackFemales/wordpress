<?php
/**
 * LearnDash Settings Section for Appearance Section on General Page.
 *
 * @since 4.21.0
 * @package LearnDash\Settings\Sections
 */

use LearnDash\Core\Template\Template;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LearnDash_Settings_Section_General_Appearance
 *
 * @since 4.21.0
 */
class LearnDash_Settings_Section_General_Appearance extends LearnDash_Settings_Section {
	/**
	 * Constructor to initialize properties for LearnDash_Settings_Section_General_Appearance.
	 *
	 * @since 4.21.0
	 */
	protected function __construct() {
		$this->settings_page_id = 'learndash_lms_settings';

		// This is the 'option_name' key used in the wp_options table.
		$this->setting_option_key = 'learndash_settings_appearance';

		// This is the HTML form field prefix used.
		$this->setting_field_prefix = 'learndash_settings_appearance';

		// Used within the Settings API to uniquely identify this section.
		$this->settings_section_key = 'settings_appearance';

		// Section label/header.
		$this->settings_section_label = esc_html__( 'Appearance', 'learndash' );

		parent::__construct();
	}

	/**
	 * Load the Appearance section values.
	 *
	 * @since 4.21.0
	 *
	 * @return void
	 */
	public function load_settings_values() {
		parent::load_settings_values();

		$this->setting_option_values = wp_parse_args(
			$this->setting_option_values,
			[
				'registration_enabled' => '',
				'course_enabled'       => '',
				'group_enabled'        => '',
			]
		);
	}

	/**
	 * Load the Appearances section fields.
	 *
	 * @since 4.21.0
	 *
	 * @return void
	 */
	public function load_settings_fields() {
		$this->setting_option_fields = wp_parse_args(
			$this->setting_option_fields,
			[
				'registration_enabled' => [
					'name'    => 'registration_enabled',
					'type'    => 'checkbox-switch',
					'label'   => '',
					'value'   => $this->setting_option_values['registration_enabled'] ?? '',
					'options' => [
						'yes' => '',
					],
				],
				'course_enabled'       => [
					'name'    => 'course_enabled',
					'type'    => 'checkbox-switch',
					'label'   => '',
					'value'   => $this->setting_option_values['course_enabled'] ?? '',
					'options' => [
						'yes' => [
							'tooltip' => sprintf(
								/* translators: placeholder: Course. */
								__( 'Tabs in Modern mode appear only when multiple sections have content. If just one section (%1$s, Materials, or Reviews) is populated, the tab navigation is hidden.', 'learndash' ),
								learndash_get_custom_label( 'course' )
							),
						],
					],
				],
				'group_enabled'        => [
					'name'    => 'group_enabled',
					'type'    => 'checkbox-switch',
					'label'   => '',
					'value'   => $this->setting_option_values['group_enabled'] ?? '',
					'options' => [
						'yes' => [
							'tooltip' => sprintf(
								/* translators: placeholder: Group. */
								__( 'Tabs in Modern mode appear only when multiple sections have content. If just one section (%1$s or Materials) is populated, the tab navigation is hidden.', 'learndash' ),
								learndash_get_custom_label( 'group' )
							),
						],
					],
				],
			]
		);

		/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
		$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

		parent::load_settings_fields();
	}

	/**
	 * Customer Show the meta box settings
	 *
	 * @since 4.21.0
	 *
	 * @param string $section Section to be shown.
	 *
	 * @return void
	 */
	public function show_settings_section( $section = null ): void {
		$course_label = learndash_get_custom_label( 'course' );
		$course_page  = sprintf(
			/* translators: placeholder: %1$s = Course custom label */
			__( '%1$s Page', 'learndash' ),
			$course_label
		);
		$course_description = sprintf(
			/* translators: placeholder: %1$s = course custom label */
			__( 'When active, Modern LearnDash Styles will be used for all %1$s, %2$s, and %3$s pages', 'learndash' ),
			learndash_get_custom_label_lower( 'course' ),
			learndash_get_custom_label_lower( 'lesson' ),
			learndash_get_custom_label_lower( 'topic' )
		);

		$group_label = learndash_get_custom_label( 'group' );
		$group_page  = sprintf(
			/* translators: placeholder: %1$s = Group custom label */
			__( '%1$s Page', 'learndash' ),
			$group_label
		);
		$group_description = sprintf(
			/* translators: placeholder: %1$s = group custom label */
			__( 'When active, Modern LearnDash Styles will be used for all %1$s pages', 'learndash' ),
			learndash_get_custom_label_lower( 'group' )
		);

		ob_start();
		call_user_func( $this->setting_option_fields['registration_enabled']['display_callback'], $this->setting_option_fields['registration_enabled'] );
		$registration_toggle = ob_get_clean();

		ob_start();
		call_user_func( $this->setting_option_fields['course_enabled']['display_callback'], $this->setting_option_fields['course_enabled'] );
		$course_toggle = ob_get_clean();

		ob_start();
		call_user_func( $this->setting_option_fields['group_enabled']['display_callback'], $this->setting_option_fields['group_enabled'] );
		$group_toggle = ob_get_clean();

		// phpcs:disable -- Many escaping errors, but these are being escaped. False error.
		echo Template::get_admin_template(
			'settings/general/appearance/section',
			[
				'rows' => [
					[
						'field_html'     => $registration_toggle,
						'label'          => __( 'Login & Registration', 'learndash' ),
						'description'    => __( 'When active, the Modern LearnDash Styles will be used for user login and registration pages', 'learndash' ),
						'learn_more_url' => 'https://go.learndash.com/modernregistration',
						'feedback_url'   => 'https://go.learndash.com/modernfeedback',
					],
					[
						'field_html'     => $course_toggle,
						'label'          => $course_page,
						'description'    => $course_description,
						'learn_more_url' => 'https://go.learndash.com/moderncourse',
						'feedback_url'   => 'https://go.learndash.com/modernfeedback',
					],
					[
						'field_html'     => $group_toggle,
						'label'          => $group_page,
						'description'    => $group_description,
						'learn_more_url' => 'https://go.learndash.com/moderngroup',
						'feedback_url'   => 'https://go.learndash.com/modernfeedback',
					],
				],
			]
		);
		// phpcs:enable
	}
}

add_action(
	'learndash_settings_sections_init',
	function () {
		if ( LearnDash_Theme_Register::get_active_theme_key() === 'ld30' ) {
			LearnDash_Settings_Section_General_Appearance::add_section_instance();
		}
	}
);
