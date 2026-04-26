<?php
/**
 * LearnDash Settings Section for Core Reports.
 *
 * @since 4.23.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports\Settings;

use LearnDash_Settings_Section;

/**
 * Class LearnDash Settings Section for Core Reports.
 *
 * @since 4.23.1
 */
class Reports_Section extends LearnDash_Settings_Section {
	/**
	 * Protected constructor for class.
	 *
	 * @since 4.23.1
	 */
	protected function __construct() {
		$this->settings_page_id = 'learndash_lms_advanced';

		// This is the 'option_name' key used in the wp_options table.
		$this->setting_option_key = 'learndash_reports';

		// This is the HTML form field prefix used.
		$this->setting_field_prefix = 'learndash_reports';

		// Used within the Settings API to uniquely identify this section.
		$this->settings_section_key = 'settings_reports';

		// Section label/header.
		$this->settings_section_label = esc_html__( 'Reports', 'learndash' );

		parent::__construct();
	}

	/**
	 * Initialize the metabox settings fields.
	 *
	 * @since 4.23.1
	 *
	 * @return void
	 */
	public function load_settings_fields(): void {
		$this->setting_option_fields = [
			'display_reports' => [
				'name'       => 'display_reports',
				'type'       => 'checkbox-switch',
				'label'      => esc_html__( 'Display Reports', 'learndash' ),
				'help_text'  => esc_html__( 'Enable this to view reports on your WP Dashboard and in your LearnDash "Reports" Page', 'learndash' ),
				'value'      => $this->setting_option_values['display_reports'] ?? '',
				'options'    => [
					'yes' => '',
				],
			],
		];

		/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
		$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

		parent::load_settings_fields();
	}
}
