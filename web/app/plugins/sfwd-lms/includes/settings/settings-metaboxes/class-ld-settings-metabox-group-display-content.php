<?php
/**
 * LearnDash Settings Metabox for Group Display and Content Options.
 *
 * @since 3.2.0
 * @package LearnDash\Settings\Metaboxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Group_Display_Content' ) ) ) {
	/**
	 * Class LearnDash Settings Metabox for Group Display and Content Options.
	 *
	 * @since 3.2.0
	 */
	class LearnDash_Settings_Metabox_Group_Display_Content extends LearnDash_Settings_Metabox {

		/**
		 * Public constructor for class
		 *
		 * @since 3.2.0
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'groups';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-group-display-content-settings';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Display and Content Options', 'learndash' );

			// Used to show the section description above the fields. Can be empty.
			$this->settings_section_description = sprintf(
				// translators: placeholder: group.
				esc_html_x( 'Controls the look and feel of the %s and optional content settings', 'placeholder: group', 'learndash' ),
				learndash_get_custom_label_lower( 'group' )
			);

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				// New fields.
				'group_materials_enabled'        => 'group_materials_enabled',
				'group_materials'                => 'group_materials',
				'certificate'                    => 'certificate',
				'group_disable_content_table'    => 'group_disable_content_table',
				'group_courses_per_page_enabled' => 'group_courses_per_page_enabled',
				'group_courses_per_page_custom'  => 'group_courses_per_page_custom',

				'group_courses_order_enabled'    => 'group_courses_order_enabled',
				'group_courses_orderby'          => 'group_courses_orderby',
				'group_courses_order'            => 'group_courses_order',
			);

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 3.2.0
		 */
		public function load_settings_values() {
			global $sfwd_lms;

			parent::load_settings_values();
			if ( true === $this->settings_values_loaded ) {

				if ( ! isset( $this->setting_option_values['group_materials_enabled'] ) ) {
					$this->setting_option_values['group_materials_enabled'] = '';
					if ( ( isset( $this->setting_option_values['group_materials'] ) ) && ( ! empty( $this->setting_option_values['group_materials'] ) ) ) {
						$this->setting_option_values['group_materials_enabled'] = 'on';
					}
				}

				if ( ! isset( $this->setting_option_values['group_materials'] ) ) {
					$this->setting_option_values['group_materials'] = '';
				}

				if ( ! isset( $this->setting_option_values['certificate'] ) ) {
					$this->setting_option_values['certificate'] = '';
				}

				if ( ! isset( $this->setting_option_values['group_disable_content_table'] ) ) {
					$this->setting_option_values['group_disable_content_table'] = '';
				}

				if ( ! isset( $this->setting_option_values['group_courses_per_page_enabled'] ) ) {
					$this->setting_option_values['group_courses_per_page_enabled'] = '';
				}
				if ( 'CUSTOM' === $this->setting_option_values['group_courses_per_page_enabled'] ) {
					$this->setting_option_values['group_courses_per_page_custom'] = absint( $this->setting_option_values['group_courses_per_page_custom'] );
				} else {
					$this->setting_option_values['group_courses_per_page_custom'] = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_Management_Display', 'group_pagination_courses' );
				}
				if ( ! isset( $this->setting_option_values['group_courses_order_enabled'] ) ) {
					$this->setting_option_values['group_courses_order_enabled'] = '';
				}

				if ( ! isset( $this->setting_option_values['group_courses_order'] ) ) {
					$this->setting_option_values['group_courses_order'] = '';
				}

				if ( ! isset( $this->setting_option_values['group_courses_orderby'] ) ) {
					$this->setting_option_values['group_courses_orderby'] = '';
				}
			}

			// Ensure all settings fields are present.
			foreach ( $this->settings_fields_map as $_internal => $_external ) {
				if ( ! isset( $this->setting_option_values[ $_internal ] ) ) {
					$this->setting_option_values[ $_internal ] = '';
				}
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 3.2.0
		 */
		public function load_settings_fields() {
			global $sfwd_lms;

			$group_courses_options_labels = array(
				'orderby' => LearnDash_Settings_Section::get_section_setting_select_option_label( 'LearnDash_Settings_Groups_Management_Display', 'group_courses_orderby' ),

				'order'   => LearnDash_Settings_Section::get_section_setting_select_option_label( 'LearnDash_Settings_Section_Lessons_Display_Order', 'group_courses_order' ),
			);

			if ( learndash_use_select2_lib() ) {
				$select_cert_options_default = array(
					'-1' => esc_html__( 'Search or select a certificate…', 'learndash' ),
				);
			} else {
				$select_cert_options_default = array(
					'' => esc_html__( 'Select Certificate', 'learndash' ),
				);
			}
			$select_cert_options = $sfwd_lms->select_a_certificate();
			if ( ( is_array( $select_cert_options ) ) && ( ! empty( $select_cert_options ) ) ) {
				$select_cert_options = $select_cert_options_default + $select_cert_options;
			} else {
				$select_cert_options = $select_cert_options_default;
			}

			$this->setting_option_fields = array(
				'group_materials_enabled'        => array(
					'name'                => 'group_materials_enabled',
					'type'                => 'checkbox-switch',
					'label'               => sprintf(
						// translators: placeholder: Group.
						esc_html_x( '%s Materials', 'placeholder: Group', 'learndash' ),
						learndash_get_custom_label( 'group' )
					),
					'help_text'           => sprintf(
						// translators: placeholder: group.
						esc_html_x( 'List and display support materials for the %s. This is visible to all users (including non-enrollees) by default.', 'placeholder: group', 'learndash' ),
						learndash_get_custom_label_lower( 'group' )
					),
					'value'               => $this->setting_option_values['group_materials_enabled'],
					'default'             => '',
					'options'             => array(
						'on' => sprintf(
							// translators: placeholder: Group.
							esc_html_x( 'Any content added below is displayed on the main %s page', 'placeholder: Group', 'learndash' ),
							learndash_get_custom_label( 'group' )
						),
						''   => '',

					),
					'child_section_state' => ( 'on' === $this->setting_option_values['group_materials_enabled'] ) ? 'open' : 'closed',
					'rest'                => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'materials_enabled',
								'description' => esc_html__( 'Materials Enabled', 'learndash' ),
								'type'        => 'boolean',
								'default'     => false,
							),
						),
					),
				),
				'group_materials'                => array(
					'name'           => 'group_materials',
					'type'           => 'wpeditor',
					'parent_setting' => 'group_materials_enabled',
					'value'          => $this->setting_option_values['group_materials'],
					'default'        => '',
					'placeholder'    => esc_html__( 'Add a list of needed documents or URLs. This field supports HTML.', 'learndash' ),
					'editor_args'    => array(
						'textarea_name' => $this->settings_metabox_key . '[group_materials]',
						'textarea_rows' => 3,
					),
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'materials',
								'description' => esc_html__( 'Materials', 'learndash' ),
								'type'        => 'object',
								'properties'  => array(
									'raw'      => array(
										'description' => 'Content for the object, as it exists in the database.',
										'type'        => 'string',
										'context'     => array( 'edit' ),
									),
									'rendered' => array(
										'description' => 'HTML content for the object, transformed for display.',
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
										'readonly'    => true,
									),
								),
								'arg_options' => array(
									'sanitize_callback' => null, // Note: sanitization performed in rest_pre_insert_filter().
									'validate_callback' => null,
								),
							),
						),
					),
				),
				'certificate'                    => array(
					'name'    => 'certificate',
					'type'    => 'select',
					'label'   => sprintf(
						// translators: placeholder: Group.
						esc_html_x( '%s Certificate', 'placeholder: Group', 'learndash' ),
						learndash_get_custom_label( 'group' )
					),
					'default' => '',
					'value'   => $this->setting_option_values['certificate'],
					'options' => $select_cert_options,
					'rest'    => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'type'    => 'integer',
								'default' => 0,
							),
						),
					),
				),
				'group_disable_content_table'    => array(
					'name'      => 'group_disable_content_table',
					'label'     => sprintf(
						// translators: placeholder: Group.
						esc_html_x( '%s Content', 'placeholder: Group', 'learndash' ),
						learndash_get_custom_label( 'group' )
					),
					'type'      => 'radio',
					'default'   => '',
					'help_text' => sprintf(
						// translators: placeholder: Group.
						esc_html_x( 'Choose whether to display the %s content table to ALL users or only enrollees', 'placeholder: Group', 'learndash' ),
						learndash_get_custom_label( 'group' )
					),
					'options'   => array(
						''   => esc_html__( 'Always visible', 'learndash' ),
						'on' => esc_html__( 'Only visible to enrollees', 'learndash' ),
					),
					'value'     => $this->setting_option_values['group_disable_content_table'],
					'rest'      => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'disable_content_table',
								'type'      => 'boolean',
								'default'   => false,
							),
						),
					),
				),

				'group_courses_per_page_enabled' => array(
					'name'                => 'group_courses_per_page_enabled',
					'label'               => esc_html__( 'Custom Pagination', 'learndash' ),
					'type'                => 'checkbox-switch',
					'help_text'           => sprintf(
						// translators: placeholders: group.
						esc_html_x( 'Customize the pagination options for this %s content table.', 'placeholders: group', 'learndash' ),
						learndash_get_custom_label_lower( 'group' )
					),
					'options'             => array(
						''       => sprintf(
							// translators: placeholder: default per page number.
							esc_html_x( 'Currently showing default pagination %d', 'placeholder: default per page number', 'learndash' ),
							LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_Management_Display', 'group_pagination_courses' )
						),
						'CUSTOM' => '',
					),
					'value'               => $this->setting_option_values['group_courses_per_page_enabled'],
					'child_section_state' => ( 'CUSTOM' === $this->setting_option_values['group_courses_per_page_enabled'] ) ? 'open' : 'closed',
				),
				'group_courses_per_page_custom'  => array(
					'name'           => 'group_courses_per_page_custom',
					'type'           => 'number',
					'class'          => 'small-text',
					'label'          => learndash_get_custom_label( 'courses' ),
					'input_label'    => esc_html__( 'per page', 'learndash' ),
					'value'          => $this->setting_option_values['group_courses_per_page_custom'],
					'default'        => '',
					'attrs'          => array(
						'step' => 1,
						'min'  => 0,
					),
					'parent_setting' => 'group_courses_per_page_enabled',
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'courses_per_page_custom',
								// translators: placeholder: Courses per page.
								'description' => sprintf( esc_html_x( '%s per page', 'placeholder: Courses per page', 'learndash' ), learndash_get_custom_label( 'courses' ) ),
								'type'        => 'integer',
								'default'     => (int) $this->setting_option_values['group_courses_per_page_custom'],
							),
						),
					),
				),

				'group_courses_order_enabled'    => array(
					'name'                => 'group_courses_order_enabled',
					'label'               => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Custom %s Order', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'                => 'checkbox-switch',
					'help_text'           => sprintf(
						// translators: placeholders: courses.
						esc_html_x( 'Customize the display order of %s.', 'placeholders: courses', 'learndash' ),
						learndash_get_custom_label_lower( 'courses' )
					),
					'options'             => array(
						''   => sprintf(
							// translators: placeholder: group course order by, group course order direction labels.
							esc_html_x( 'Using default sorting by %1$s in %2$s order', 'placeholder: group course order by, group course order direction labels.', 'learndash' ),
							'<em>' .
							LearnDash_Settings_Section::get_section_setting_select_option_label( 'LearnDash_Settings_Groups_Management_Display', 'group_courses_orderby' )
							. '</em>',
							'<em>' .
							LearnDash_Settings_Section::get_section_setting_select_option_label( 'LearnDash_Settings_Groups_Management_Display', 'group_courses_order' )
							. '</em>'
						),
						'on' => '',
					),
					'value'               => $this->setting_option_values['group_courses_order_enabled'],
					'child_section_state' => ( 'on' === $this->setting_option_values['group_courses_order_enabled'] ) ? 'open' : 'closed',
				),

				'group_courses_orderby'          => array(
					'name'           => 'group_courses_orderby',
					'label'          => esc_html__( 'Sort By', 'learndash' ),
					'type'           => 'select',
					'options'        => array(
						''           => esc_html__( 'Use Default', 'learndash' ) . ' ( ' . $group_courses_options_labels['orderby'] . ' )',
						'title'      => esc_html__( 'Title', 'learndash' ),
						'date'       => esc_html__( 'Date', 'learndash' ),
						'menu_order' => esc_html__( 'Menu Order', 'learndash' ),
					),
					'default'        => '',
					'value'          => $this->setting_option_values['group_courses_orderby'],
					'parent_setting' => 'group_courses_order_enabled',
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'courses_orderby',
								'type'      => 'string',
								'default'   => '',
							),
						),
					),
				),
				'group_courses_order'            => array(
					'name'           => 'group_courses_order',
					'label'          => esc_html__( 'Order Direction', 'learndash' ),
					'type'           => 'select',
					'options'        => array(
						''     => esc_html__( 'Use Default', 'learndash' ) . ' ( ' . $group_courses_options_labels['order'] . ' )',
						'ASC'  => esc_html__( 'Ascending', 'learndash' ),
						'DESC' => esc_html__( 'Descending', 'learndash' ),
					),
					'default'        => '',
					'value'          => $this->setting_option_values['group_courses_order'],
					'parent_setting' => 'group_courses_order_enabled',
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'courses_order',
								'type'      => 'string',
								'default'   => '',
							),
						),
					),
				),
			);

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_metabox_key );

			parent::load_settings_fields();
		}

		/**
		 * Filter settings values for metabox before save to database.
		 *
		 * @since 3.2.0
		 *
		 * @param array  $settings_values Array of settings values.
		 * @param string $settings_metabox_key Metabox key.
		 * @param string $settings_screen_id Screen ID.
		 *
		 * @return array $settings_values.
		 */
		public function filter_saved_fields( $settings_values = array(), $settings_metabox_key = '', $settings_screen_id = '' ) {
			if ( ( $settings_screen_id === $this->settings_screen_id ) && ( $settings_metabox_key === $this->settings_metabox_key ) ) {

				if ( ! isset( $settings_values['group_materials_enabled'] ) ) {
					$settings_values['group_materials_enabled'] = '';
				}

				if ( ! isset( $settings_values['group_materials'] ) ) {
					$settings_values['group_materials'] = '';
				}

				if ( 'on' !== $settings_values['group_materials_enabled'] ) {
					$settings_values['group_materials'] = '';
				}

				if ( ( 'on' === $settings_values['group_materials_enabled'] ) && ( empty( $settings_values['group_materials'] ) ) ) {
					$settings_values['group_materials_enabled'] = '';
				}

				/**
				 * Check Certificate choice.
				 */
				if ( ! isset( $settings_values['certificate'] ) ) {
					$settings_values['certificate'] = '';
				}
				if ( '-1' === $settings_values['certificate'] ) {
					$settings_values['certificate'] = '';
				}

				/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
				$settings_values = apply_filters( 'learndash_settings_save_values', $settings_values, $this->settings_metabox_key );
			}

			return $settings_values;
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'group' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Group_Display_Content'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Group_Display_Content' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Group_Display_Content'] = LearnDash_Settings_Metabox_Group_Display_Content::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
