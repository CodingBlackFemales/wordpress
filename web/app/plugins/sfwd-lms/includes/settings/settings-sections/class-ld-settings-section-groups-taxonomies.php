<?php
/**
 * LearnDash Settings Section for Groups Taxonomies Metabox.
 *
 * @since 3.2.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Groups_Taxonomies' ) ) ) {
	/**
	 * Class LearnDash Settings Section for Groups Taxonomies Metabox.
	 *
	 * @since 3.2.0
	 */
	class LearnDash_Settings_Groups_Taxonomies extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 *
		 * @since 3.2.0
		 */
		protected function __construct() {

			// What screen ID are we showing on.
			$this->settings_screen_id = 'groups_page_groups-options';

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'groups-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_groups_taxonomies';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_groups_taxonomies';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'taxonomies';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Group.
				esc_html_x( '%s Taxonomies', 'placeholder: Group', 'learndash' ),
				learndash_get_custom_label( 'group' )
			);

			// Used to show the section description above the fields. Can be empty.
			$this->settings_section_description = sprintf(
				// translators: placeholder: groups.
				esc_html_x( 'Control which taxonomies can be used to better organize your LearnDash %s.', 'placeholder: group', 'learndash' ),
				learndash_get_custom_label_lower( 'group' )
			);

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 3.2.0
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			$_init = false;
			if ( false === $this->setting_option_values ) {
				$__init                      = true;
				$this->setting_option_values = array(
					'ld_group_category' => 'yes',
					'ld_group_tag'      => 'yes',
					'wp_post_category'  => 'yes',
					'wp_post_tag'       => 'yes',
				);
			}

			$this->setting_option_values = wp_parse_args(
				$this->setting_option_values,
				array(
					'ld_group_category' => '',
					'ld_group_tag'      => '',
					'wp_post_category'  => '',
					'wp_post_tag'       => '',
				)
			);
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 3.2.0
		 */
		public function load_settings_fields() {

			$this->setting_option_fields = array(
				'ld_group_category' => array(
					'name'    => 'ld_group_category',
					'type'    => 'checkbox-switch',
					'label'   => sprintf(
						// translators: placeholder: Group.
						esc_html_x( '%s Categories', 'placeholder: Group', 'learndash' ),
						learndash_get_custom_label( 'group' )
					),
					'value'   => $this->setting_option_values['ld_group_category'],
					'options' => array(
						''    => '',
						'yes' => sprintf(
							// translators: placeholder: Group.
							esc_html_x( 'Manage %s Categories via the Actions dropdown', 'placeholder: Group', 'learndash' ),
							learndash_get_custom_label( 'group' )
						),
					),
				),
				'ld_group_tag'      => array(
					'name'    => 'ld_group_tag',
					'type'    => 'checkbox-switch',
					'label'   => sprintf(
						// translators: placeholder: Group.
						esc_html_x( '%s Tags', 'placeholder: Group', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'group' )
					),
					'value'   => $this->setting_option_values['ld_group_tag'],
					'options' => array(
						''    => '',
						'yes' => sprintf(
							// translators: placeholder: Group.
							esc_html_x( 'Manage %s Tags via the Actions dropdown', 'placeholder: Group', 'learndash' ),
							learndash_get_custom_label( 'group' )
						),
					),
				),
				'wp_post_category'  => array(
					'name'    => 'wp_post_category',
					'type'    => 'checkbox-switch',
					'label'   => esc_html__( 'WP Post Categories', 'learndash' ),
					'value'   => $this->setting_option_values['wp_post_category'],
					'options' => array(
						''    => '',
						'yes' => esc_html__( 'Manage WP Categories via the Actions dropdown', 'learndash' ),
					),
				),
				'wp_post_tag'       => array(
					'name'    => 'wp_post_tag',
					'type'    => 'checkbox-switch',
					'label'   => esc_html__( 'WP Post Tags', 'learndash' ),
					'value'   => $this->setting_option_values['wp_post_tag'],
					'options' => array(
						''    => '',
						'yes' => esc_html__( 'Manage WP Tags via the Actions dropdown', 'learndash' ),
					),
				),

			);

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Groups_Taxonomies::add_section_instance();
	}
);
