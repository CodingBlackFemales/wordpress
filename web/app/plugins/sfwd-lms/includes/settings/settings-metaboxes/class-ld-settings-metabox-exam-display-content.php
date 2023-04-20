<?php
/**
 * LearnDash Settings Metabox for Exam Display and Content Options.
 *
 * @since 4.0.0
 * @package LearnDash\Settings\Metaboxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Exam_Display_Content' ) ) ) {
	/**
	 * Class LearnDash Settings Metabox for Exam Display and Content Options.
	 *
	 * @since 4.0.0
	 */
	class LearnDash_Settings_Metabox_Exam_Display_Content extends LearnDash_Settings_Metabox {

		/**
		 * Public constructor for class
		 *
		 * @since 4.0.0
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = learndash_get_post_type_slug( 'exam' );

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-exam-display-content-settings';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Display and Content Options', 'learndash' );

			// Used to show the section description above the fields. Can be empty.
			$this->settings_section_description = sprintf(
				// translators: placeholder: exam.
				esc_html_x( 'Controls the look and feel of the %s and optional content settings', 'placeholder: exam', 'learndash' ),
				learndash_get_custom_label_lower( 'exam' )
			);

			add_filter( 'learndash_settings_row_outside_after', array( $this, 'learndash_settings_row_outside_after' ), 10, 2 );

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				'show_new_enroll'              => 'show_new_enroll',
				'exam_challenge_course_show'   => 'exam_challenge_course_show',
				'exam_challenge_course_passed' => 'exam_challenge_course_passed',
				'exam_passed_button_label'     => 'exam_passed_button_label',
				'exam_passed_redirect_url'     => 'exam_passed_redirect_url',
				'exam_failed_button_label'     => 'exam_failed_button_label',
				'exam_failed_redirect_url'     => 'exam_failed_redirect_url',
				'message_passed'               => 'message_passed',
				'message_failed'               => 'message_failed',
			);

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 4.0.0
		 */
		public function load_settings_values() {
			global $sfwd_lms;

			parent::load_settings_values();
			if ( true === $this->settings_values_loaded ) {

				if ( ! isset( $this->setting_option_values['show_new_enroll'] ) ) {
					$this->setting_option_values['show_new_enroll'] = 'on';
				}

				if ( ! isset( $this->setting_option_values['exam_challenge_course_show'] ) ) {
					$this->setting_option_values['exam_challenge_course_show'] = '';
				}

				if ( ! isset( $this->setting_option_values['exam_challenge_course_passed'] ) ) {
					$this->setting_option_values['exam_challenge_course_passed'] = '';
				}

				if ( ! isset( $this->setting_option_values['exam_passed_button_label'] ) ) {
					$this->setting_option_values['exam_passed_button_label'] = '';
				}

				if ( ! isset( $this->setting_option_values['exam_passed_redirect_url'] ) ) {
					$this->setting_option_values['exam_passed_redirect_url'] = '';
				}

				if ( ! isset( $this->setting_option_values['exam_failed_button_label'] ) ) {
					$this->setting_option_values['exam_failed_button_label'] = '';
				}

				if ( ! isset( $this->setting_option_values['exam_failed_redirect_url'] ) ) {
					$this->setting_option_values['exam_failed_redirect_url'] = '';
				}

				if ( ! isset( $this->setting_option_values['message_passed'] ) ) {
					$this->setting_option_values['message_passed'] = '';
				}

				if ( ! isset( $this->setting_option_values['message_failed'] ) ) {
					$this->setting_option_values['message_failed'] = '';
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

			$select_exam_challenge_course_show_query_data_json = '';
			$select_exam_challenge_course_show_options         = array();

			$select_exam_challenge_course_passed_query_data_json = '';
			$select_exam_challenge_course_passed_options         = array();

			/** This filter is documented in includes/class-ld-lms.php */
			if ( learndash_use_select2_lib() ) {

				$select_exam_challenge_course_show_options_default = sprintf(
					// translators: placeholder: Exam.
					esc_html_x( 'Search or select a %s…', 'placeholder: Exam', 'learndash' ),
					learndash_get_custom_label( 'exam' )
				);

				if ( ! empty( $this->setting_option_values['exam_challenge_course_show'] ) ) {
					$exam_post = get_post( absint( $this->setting_option_values['exam_challenge_course_show'] ) );
					if ( ( $exam_post ) && ( is_a( $exam_post, 'WP_Post' ) ) ) {
						$select_exam_challenge_course_show_options[ $exam_post->ID ] = learndash_format_step_post_title_with_status_label( $exam_post );
					}
				}

				$select_exam_challenge_course_passed_options_default = sprintf(
					// translators: placeholder: Exam.
					esc_html_x( 'Search or select a %s…', 'placeholder: Exam', 'learndash' ),
					learndash_get_custom_label( 'exam' )
				);

				if ( ! empty( $this->setting_option_values['exam_challenge_course_passed'] ) ) {
					$exam_post = get_post( absint( $this->setting_option_values['exam_challenge_course_passed'] ) );
					if ( ( $exam_post ) && ( is_a( $exam_post, 'WP_Post' ) ) ) {
						$select_exam_challenge_course_passed_options[ $exam_post->ID ] = learndash_format_step_post_title_with_status_label( $exam_post );
					}
				}

				if ( learndash_use_select2_lib_ajax_fetch() ) {

					$select_exam_challenge_course_show_query_data_json = $this->build_settings_select2_lib_ajax_fetch_json(
						array(
							'query_args'       => array(
								'post_type'           => 'sfwd-courses',
								'ld_include_selected' => absint( $this->setting_option_values['exam_challenge_course_show'] ),
								'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
									array(
										'key'     => LEARNDASH_EXAM_CHALLENGE_POST_META_KEY,
										'compare' => 'NOT EXISTS',
									),
								),
							),
							'settings_element' => array(
								'settings_parent_class' => get_parent_class( __CLASS__ ),
								'settings_class'        => __CLASS__,
								'settings_field'        => 'exam_challenge_course_show',
							),
						)
					);

					$select_exam_challenge_course_passed_query_data_json = $this->build_settings_select2_lib_ajax_fetch_json(
						array(
							'query_args'       => array(
								'post_type' => 'sfwd-courses',
							),
							'settings_element' => array(
								'settings_parent_class' => get_parent_class( __CLASS__ ),
								'settings_class'        => __CLASS__,
								'settings_field'        => 'exam_challenge_course_passed',
							),
						)
					);
				} else {
					$select_exam_challenge_course_show_options   = $sfwd_lms->select_a_course();
					$select_exam_challenge_course_passed_options = $sfwd_lms->select_a_course();
				}
			} else {

				$select_exam_challenge_course_show_options_default = array(
					'' => sprintf(
					// translators: placeholder: Course.
						esc_html_x( 'Select %s', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
				);

				$select_exam_challenge_course_passed_options_default = array(
					'' => sprintf(
					// translators: placeholder: Course.
						esc_html_x( 'Select %s', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
				);
			}

			$this->setting_option_fields = array(
				'show_new_enroll'              => array(
					'name'      => 'show_new_enroll',
					'type'      => 'checkbox-switch',
					'label'     => esc_html__( 'New enrollees only', 'learndash' ),
					'default'   => 'on',
					'help_text' => sprintf(
						// translators: placeholder: Exam, Course.
						esc_html_x( 'Show %1$s to users that have a status of "Not Started" for the %2$s.', 'placeholder: Exam, Course', 'learndash' ),
						learndash_get_custom_label_lower( 'exam' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'value'     => $this->setting_option_values['show_new_enroll'],
					'options'   => array(
						''   => '',
						'on' => '',
					),
					'rest'      => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'show_new_enroll',
								'type'      => 'boolean',
								'default'   => true,
							),
						),
					),
				),
				'exam_challenge_course_show'   => array(
					'name'        => 'exam_challenge_course_show',
					'type'        => 'select',
					'label'       => sprintf(
						// translators: placeholder: Exam, Course.
						esc_html_x( '%1$s %2$s Show', 'placeholder: Exam, Course', 'learndash' ),
						learndash_get_custom_label( 'exam' ),
						learndash_get_custom_label( 'course' )
					),
					'default'     => '',
					'help_text'   => sprintf(
						// translators: placeholder: Course, Exam.
						esc_html_x( 'Select the %1$s you want to display this %2$s on.', 'placeholder: Course, Exam', 'learndash' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'exam' )
					),
					'value'       => $this->setting_option_values['exam_challenge_course_show'],
					'options'     => $select_exam_challenge_course_show_options,
					'placeholder' => $select_exam_challenge_course_show_options_default,
					'attrs'       => array(
						'data-select2-query-data' => $select_exam_challenge_course_show_query_data_json,
					),
					'rest'        => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'exam_challenge_course_show',
								'type'      => 'integer',
								'default'   => 0,
							),
						),
					),
				),
				'exam_challenge_course_passed' => array(
					'name'        => 'exam_challenge_course_passed',
					'type'        => 'select',
					'label'       => sprintf(
						// translators: placeholder: Exam, Course.
						esc_html_x( '%1$s Passed %2$s', 'placeholder: Exam, Course', 'learndash' ),
						learndash_get_custom_label( 'exam' ),
						learndash_get_custom_label( 'course' )
					),
					'default'     => '',
					'help_text'   => sprintf(
						// translators: placeholder: Course, Exam, Course.
						esc_html_x( 'Select the %1$s you want the user to complete when they successfully complete this %2$s. If different from the above setting, the user must already be enrolled in the chosen %3$s.', 'placeholder: Course, Exam, Course', 'learndash' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'exam' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'value'       => $this->setting_option_values['exam_challenge_course_passed'],
					'options'     => $select_exam_challenge_course_passed_options,
					'placeholder' => $select_exam_challenge_course_passed_options_default,
					'attrs'       => array(
						'data-select2-query-data' => $select_exam_challenge_course_passed_query_data_json,
					),
					'rest'        => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'exam_challenge_course_passed',
								'type'      => 'integer',
								'default'   => 0,
							),
						),
					),
				),
				'exam_passed_button_label'     => array(
					'name'      => 'exam_passed_button_label',
					'type'      => 'text',
					'label'     => sprintf(
						// translators: placeholder: Exam.
						esc_html_x( '%s Passed Button Label', 'placeholder: Exam', 'learndash' ),
						learndash_get_custom_label( 'exam' )
					),
					'default'   => '',
					'help_text' => sprintf(
						// translators: placeholder: Exam.
						esc_html_x( 'Label for button when a user passes a %s', 'placeholder: Exam', 'learndash' ),
						learndash_get_custom_label_lower( 'exam' )
					),
					'value'     => $this->setting_option_values['exam_passed_button_label'],
					'rest'      => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'exam_passed_button_label',
								'type'      => 'string',
								'default'   => '',
							),
						),
					),
				),
				'exam_passed_redirect_url'     => array(
					'name'      => 'exam_passed_redirect_url',
					'type'      => 'text',
					'label'     => sprintf(
						// translators: placeholder: Exam.
						esc_html_x( '%s Passed Redirect URL', 'placeholder: Exam', 'learndash' ),
						learndash_get_custom_label( 'exam' )
					),
					'default'   => '',
					'help_text' => sprintf(
						// translators: placeholder: Exam.
						esc_html_x( 'The URL to redirect the user to when clicking on the button after passing a %s.', 'placeholder: Exam', 'learndash' ),
						learndash_get_custom_label_lower( 'exam' )
					),
					'value'     => $this->setting_option_values['exam_passed_redirect_url'],
					'rest'      => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'exam_passed_redirect_url',
								'type'      => 'string',
								'default'   => '',
							),
						),
					),
				),
				'message_passed'               => array(
					'name'        => 'message_passed',
					'type'        => 'wpeditor',
					'value'       => $this->setting_option_values['message_passed'],
					'label'       => sprintf(
						// translators: placeholder: Exam.
						esc_html_x( '%s Passed Message', 'placeholder: Exam', 'learndash' ),
						learndash_get_custom_label( 'exam' )
					),
					'default'     => '',
					'help_text'   => sprintf(
						// translators: placeholder: Exam.
						esc_html_x( 'Message shown when user passes the %s', 'placeholder: Exam', 'learndash' ),
						learndash_get_custom_label_lower( 'exam' )
					),
					'editor_args' => array(
						'textarea_name' => $this->settings_metabox_key . '[message_passed]',
						'textarea_rows' => 3,
					),
					'rest'        => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'message_passed',
								'description' => sprintf(
									// translators: placeholder: Exam.
									esc_html_x( '%s Passed Message', 'placeholder: Exam', 'learndash' ),
									learndash_get_custom_label( 'exam' )
								),
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
							),
						),
					),
				),
				'exam_failed_button_label'     => array(
					'name'      => 'exam_failed_button_label',
					'type'      => 'text',
					'label'     => sprintf(
						// translators: placeholder: Exam.
						esc_html_x( '%s Failed Button Label', 'placeholder: Exam', 'learndash' ),
						learndash_get_custom_label( 'exam' )
					),
					'default'   => '',
					'help_text' => sprintf(
						// translators: placeholder: Exam.
						esc_html_x( 'Label for button when a user fails a %s', 'placeholder: Exam', 'learndash' ),
						learndash_get_custom_label_lower( 'exam' )
					),
					'value'     => $this->setting_option_values['exam_failed_button_label'],
					'rest'      => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'exam_failed_button_label',
								'type'      => 'string',
								'default'   => '',
							),
						),
					),
				),
				'exam_failed_redirect_url'     => array(
					'name'      => 'exam_failed_redirect_url',
					'type'      => 'text',
					'label'     => sprintf(
						// translators: placeholder: Exam, Course.
						esc_html_x( '%s Failed Redirect URL', 'placeholder: Exam', 'learndash' ),
						learndash_get_custom_label( 'exam' )
					),
					'default'   => '',
					'help_text' => sprintf(
						// translators: placeholder: Course, Exam.
						esc_html_x( 'The URL to redirect the user to when clicking on the button after failing a %s.', 'placeholder: Course, Exam', 'learndash' ),
						learndash_get_custom_label_lower( 'exam' )
					),
					'value'     => $this->setting_option_values['exam_failed_redirect_url'],
					'rest'      => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'exam_failed_redirect_url',
								'type'      => 'string',
								'default'   => '',
							),
						),
					),
				),
				'message_failed'               => array(
					'name'        => 'message_failed',
					'type'        => 'wpeditor',
					'value'       => $this->setting_option_values['message_failed'],
					'label'       => sprintf(
						// translators: placeholder: Exam.
						esc_html_x( '%s Failed Message', 'placeholder: Exam', 'learndash' ),
						learndash_get_custom_label( 'exam' )
					),
					'default'     => '',
					'help_text'   => sprintf(
						// translators: placeholder: Exam.
						esc_html_x( 'Message shown when user fails the %s', 'placeholder: Exam', 'learndash' ),
						learndash_get_custom_label( 'exam' )
					),
					'editor_args' => array(
						'textarea_name' => $this->settings_metabox_key . '[message_failed]',
						'textarea_rows' => 3,
					),
					'rest'        => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'message_failed',
								'description' => sprintf(
									// translators: placeholder: Exam.
									esc_html_x( '%s Failed Message', 'placeholder: Exam', 'learndash' ),
									learndash_get_custom_label( 'exam' )
								),
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

				if ( (int) $settings_values['exam_challenge_course_show'] !== (int) $this->setting_option_values['exam_challenge_course_show'] ) {
					if ( ! empty( $this->setting_option_values['exam_challenge_course_show'] ) ) {
						learndash_update_setting( (int) $this->setting_option_values['exam_challenge_course_show'], 'exam_challenge', '' );
					}
					if ( ! empty( $settings_values['exam_challenge_course_show'] ) ) {
						learndash_update_setting( (int) $settings_values['exam_challenge_course_show'], 'exam_challenge', $this->_post->ID );
					}
				}

				/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
				$settings_values = apply_filters( 'learndash_settings_save_values', $settings_values, $this->settings_metabox_key );
			}

			return $settings_values;

		}

		/**
		 * Hook into action after the fieldset is output. This allows adding custom content like JS/CSS.
		 *
		 * @since 3.3.0
		 *
		 * @param string $html This is the field output which will be send to the screen.
		 * @param array  $field_args Array of field args used to build the field HTML.
		 *
		 * @return string $html.
		 */
		public function learndash_settings_row_outside_after( $html = '', $field_args = array() ) {
			/**
			 * Here we hook into the bottom of the field HTML output and add some inline JS to handle the
			 * change event on the radio buttons. This is really just to update the 'custom' input field
			 * display.
			 */
			if ( ( isset( $field_args['name'] ) ) && ( 'message_passed' === $field_args['name'] || 'exam_challenge_course_passed' === $field_args['name'] ) ) {
				$html .= '<div class="ld-divider"></div>';
			}
			return $html;
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'exam' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Exam_Display_Content'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Exam_Display_Content' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Exam_Display_Content'] = LearnDash_Settings_Metabox_Exam_Display_Content::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
