<?php
/**
 * LearnDash Settings Section for Groups Management and Display Settings Metabox.
 *
 * @since 3.2.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Groups_Management_Display' ) ) ) {
	/**
	 * Class LearnDash Settings Section for Groups Management and Display Settings Metabox.
	 *
	 * @since 3.2.0
	 */
	class LearnDash_Settings_Groups_Management_Display extends LearnDash_Settings_Section {

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
			$this->setting_option_key = 'learndash_settings_groups_management_display';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_groups_management_display';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'groups_management_display';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Group.
				esc_html_x( 'Global %s Management & Display Settings', 'placeholder: Group', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'group' )
			);

			// Used to show the section description above the fields. Can be empty.
			$this->settings_section_description = sprintf(
				// translators: placeholder: group.
				esc_html_x( 'Control settings for %s creation, and visual organization', 'placeholder: group', 'learndash' ),
				learndash_get_custom_label_lower( 'group' )
			);

			// Define the deprecated Class and Fields.
			$this->settings_deprecated = array();

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 3.2.0
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			// If the settings set as a whole is empty then we set a default.
			if ( ( false === $this->setting_option_values ) || ( '' === $this->setting_option_values ) ) {
				if ( '' === $this->setting_option_values ) {
					$this->setting_option_values = array();
				}
				$this->transition_deprecated_settings();
			}

			if ( '' === $this->setting_option_values ) {
				$this->setting_option_values = array();
			}

			if ( ! isset( $this->setting_option_values['group_hierarchical_enabled'] ) ) {
				$this->setting_option_values['group_hierarchical_enabled'] = '';
			}

			if ( ! isset( $this->setting_option_values['group_pagination_courses'] ) ) {
				$this->setting_option_values['group_pagination_courses'] = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
			}

			if ( ( LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE === $this->setting_option_values['group_pagination_courses'] ) && ( LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE === $this->setting_option_values['group_pagination_courses'] ) ) {
				$this->setting_option_values['group_pagination_enabled'] = '';
			} else {
				$this->setting_option_values['group_pagination_enabled'] = 'yes';
			}

			if ( ! isset( $this->setting_option_values['group_courses_order'] ) ) {
				$this->setting_option_values['group_courses_order'] = LEARNDASH_DEFAULT_GROUP_ORDER;
			}
			if ( ! isset( $this->setting_option_values['group_courses_orderby'] ) ) {
				$this->setting_option_values['group_courses_orderby'] = LEARNDASH_DEFAULT_GROUP_ORDERBY;
			}

			if ( ( LEARNDASH_DEFAULT_GROUP_ORDERBY === $this->setting_option_values['group_courses_orderby'] ) && ( LEARNDASH_DEFAULT_GROUP_ORDER === $this->setting_option_values['group_courses_order'] ) ) {
				$this->setting_option_values['group_courses_order_enabled'] = '';
			} else {
				$this->setting_option_values['group_courses_order_enabled'] = 'yes';
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 3.2.0
		 */
		public function load_settings_fields() {

			$group_courses_orderby_options = array(
				'menu_order' => esc_html__( 'Menu Order', 'learndash' ),
				'date'       => esc_html__( 'Date', 'learndash' ),
				'title'      => esc_html__( 'Title', 'learndash' ),
			);

			if ( isset( $group_courses_orderby_options[ LEARNDASH_DEFAULT_GROUP_ORDERBY ] ) ) {
				$group_courses_orderby_default = esc_attr( $group_courses_orderby_options[ LEARNDASH_DEFAULT_GROUP_ORDERBY ] );
			} else {
				$group_courses_orderby_default = $group_courses_orderby_options['date'];
			}

			$group_courses_order_options = array(
				'ASC'  => esc_html__( 'Ascending', 'learndash' ),
				'DESC' => esc_html__( 'Descending', 'learndash' ),
			);
			if ( isset( $group_courses_order_options[ LEARNDASH_DEFAULT_GROUP_ORDER ] ) ) {
				$group_courses_order_default = esc_attr( $group_courses_order_options[ LEARNDASH_DEFAULT_GROUP_ORDER ] );
			} else {
				$group_courses_order_default = $group_courses_order_options['ASC'];
			}

			$this->setting_option_fields = array();

			$this->setting_option_fields['group_hierarchical_enabled'] = array(
				'name'      => 'group_hierarchical_enabled',
				'type'      => 'checkbox-switch',
				'label'     => sprintf(
					// translators: placeholder: Group.
					esc_html_x( '%s Hierarchy', 'placeholder: Group', 'learndash' ),
					learndash_get_custom_label( 'group' )
				),
				'help_text' => sprintf(
					// translators: placeholder: Group, Groups.
					esc_html_x( 'A %1$s can be nested within other %2$s.', 'placeholder: Group, Groups', 'learndash' ),
					learndash_get_custom_label_lower( 'group' ),
					learndash_get_custom_label_lower( 'groups' )
				),
				'value'     => $this->setting_option_values['group_hierarchical_enabled'],
				'options'   => array(
					''    => '',
					'yes' => '',
				),
			);

			$this->setting_option_fields['group_pagination_enabled']    = array(
				'name'                => 'group_pagination_enabled',
				'type'                => 'checkbox-switch',
				'label'               => sprintf(
					// translators: placeholder: Group.
					esc_html_x( '%s Table Pagination', 'placeholder: Group', 'learndash' ),
					learndash_get_custom_label( 'group' )
				),
				'help_text'           => sprintf(
					// translators: placeholder: group.
					esc_html_x( 'Customize the pagination options for ALL %s content tables.', 'placeholder: course, course', 'learndash' ),
					learndash_get_custom_label_lower( 'group' )
				),
				'value'               => $this->setting_option_values['group_pagination_enabled'],
				'options'             => array(
					''    => sprintf(
						// translators: placeholder: default per page number.
						esc_html_x( 'Currently showing default pagination %d', 'placeholder: default per page number', 'learndash' ),
						LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE
					),
					'yes' => '',
				),
				'child_section_state' => ( 'yes' === $this->setting_option_values['group_pagination_enabled'] ) ? 'open' : 'closed',
			);
			$this->setting_option_fields['group_pagination_courses']    = array(
				'name'           => 'group_pagination_courses',
				'type'           => 'number',
				'label'          => learndash_get_custom_label( 'courses' ),
				'value'          => $this->setting_option_values['group_pagination_courses'],
				'class'          => 'small-text',
				'input_label'    => esc_html__( 'per page', 'learndash' ),
				'attrs'          => array(
					'step' => 1,
					'min'  => 0,
				),
				'parent_setting' => 'group_pagination_enabled',
			);
			$this->setting_option_fields['group_courses_order_enabled'] = array(
				'name'                => 'group_courses_order_enabled',
				'type'                => 'checkbox-switch',
				'label'               => sprintf(
					// translators: placeholder: Group, Courses.
					esc_html_x( '%1$s %2$s Order', 'placeholder: Group, Courses', 'learndash' ),
					learndash_get_custom_label( 'group' ),
					learndash_get_custom_label( 'courses' )
				),
				'help_text'           => sprintf(
					// translators: placeholder: courses.
					esc_html_x( 'Customize the display order of %s.', 'placeholder: courses', 'learndash' ),
					learndash_get_custom_label_lower( 'course' )
				),
				'value'               => $this->setting_option_values['group_courses_order_enabled'],
				'options'             => array(
					''    => array(
						'label'       => sprintf(
							// translators: placeholder: Default Order By, Order.
							esc_html_x( 'Using default sorting by %1$s in %2$s order', 'placeholder: Default Order By, Order', 'learndash' ),
							'<em>' . $group_courses_orderby_default . '</em>',
							'<em>' . $group_courses_order_default . '</em>'
						),
						'description' => '',
					),
					'yes' => array(
						'label'       => '',
						'description' => '',
					),
				),
				'child_section_state' => ( 'yes' === $this->setting_option_values['group_courses_order_enabled'] ) ? 'open' : 'closed',
			);
			$this->setting_option_fields['group_courses_orderby']       = array(
				'name'           => 'group_courses_orderby',
				'type'           => 'select',
				'label'          => esc_html__( 'Sort By', 'learndash' ),
				'value'          => $this->setting_option_values['group_courses_orderby'],
				'default'        => LEARNDASH_DEFAULT_GROUP_ORDERBY,
				'options'        => $group_courses_orderby_options,
				'parent_setting' => 'group_courses_order_enabled',
			);

			$this->setting_option_fields['group_courses_order'] = array(
				'name'           => 'group_courses_order',
				'type'           => 'select',
				'label'          => esc_html__( 'Order Direction', 'learndash' ),
				'value'          => $this->setting_option_values['group_courses_order'],
				'default'        => LEARNDASH_DEFAULT_GROUP_ORDER,
				'options'        => $group_courses_order_options,
				'parent_setting' => 'group_courses_order_enabled',
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
		 * @param array  $current_values Array of section fields values.
		 * @param array  $old_values     Array of old values.
		 * @param string $option         Section option key should match $this->setting_option_key.
		 */
		public function section_pre_update_option( $current_values = '', $old_values = '', $option = '' ) {
			if ( $option === $this->setting_option_key ) {
				$current_values = parent::section_pre_update_option( $current_values, $old_values, $option );
				if ( $current_values !== $old_values ) {

					if ( ( isset( $current_values['group_pagination_enabled'] ) ) && ( 'yes' === $current_values['group_pagination_enabled'] ) ) {
						$current_values['group_pagination_courses'] = absint( $current_values['group_pagination_courses'] );

						if ( LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE === $current_values['group_pagination_courses'] ) {
							$current_values['group_pagination_courses'] = '';
						}
					} else {
						$current_values['group_pagination_courses'] = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
					}

					// Group Courses Order and Order By.
					if ( ( isset( $current_values['group_courses_order_enabled'] ) ) && ( 'yes' === $current_values['group_courses_order_enabled'] ) ) {
						if ( ( ! isset( $current_values['group_courses_order'] ) ) || ( empty( $current_values['group_courses_order'] ) ) ) {
							$current_values['group_courses_order'] = LEARNDASH_DEFAULT_GROUP_ORDER;
						}
						if ( ( ! isset( $current_values['group_courses_orderby'] ) ) || ( empty( $current_values['group_courses_orderby'] ) ) ) {
							$current_values['group_courses_orderby'] = LEARNDASH_DEFAULT_GROUP_ORDERBY;
						}

						if ( ( LEARNDASH_DEFAULT_GROUP_ORDER === $current_values['group_courses_order'] ) && ( LEARNDASH_DEFAULT_GROUP_ORDERBY === $current_values['group_courses_orderby'] ) ) {
							$current_values['group_courses_order_enabled'] = '';
						}
					} else {
						$current_values['group_courses_order']   = LEARNDASH_DEFAULT_GROUP_ORDER;
						$current_values['group_courses_orderby'] = LEARNDASH_DEFAULT_GROUP_ORDERBY;
					}
				}
			}

			return $current_values;
		}
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Groups_Management_Display::add_section_instance();
	}
);
