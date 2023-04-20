<?php
/**
 * LearnDash Settings Metabox for Lesson Access Settings.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Metaboxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Lesson_Access_Settings' ) ) ) {
	/**
	 * Class LearnDash Settings Metabox for Lesson Access Settings.
	 *
	 * @since 3.0.0
	 */
	class LearnDash_Settings_Metabox_Lesson_Access_Settings extends LearnDash_Settings_Metabox {

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-lessons';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-lesson-access-settings';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Lesson.
				esc_html_x( '%s Access Settings', 'placeholder: Lesson', 'learndash' ),
				learndash_get_custom_label( 'lesson' )
			);

			$this->settings_section_description = sprintf(
				// translators: placeholder: lessons.
				esc_html_x( 'Controls the timing and way %s can be accessed.', 'placeholder: lessons', 'learndash' ),
				learndash_get_custom_label_lower( 'lessons' )
			);

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				'lesson_schedule'             => 'lesson_schedule',
				'course'                      => 'course',
				'sample_lesson'               => 'sample_lesson',
				'visible_after'               => 'visible_after',
				'visible_after_specific_date' => 'visible_after_specific_date',
			);

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 3.0.0
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			if ( true === $this->settings_values_loaded ) {

				if ( ! isset( $this->setting_option_values['lesson_materials_enabled'] ) ) {
					$this->setting_option_values['lesson_materials_enabled'] = '';
					if ( ( isset( $this->setting_option_values['lesson_materials'] ) ) && ( ! empty( $this->setting_option_values['lesson_materials'] ) ) ) {
						$this->setting_option_values['lesson_materials_enabled'] = 'on';
					}
				}

				if ( ! isset( $this->setting_option_values['course'] ) ) {
					$this->setting_option_values['course'] = '';
				}

				if ( ! isset( $this->setting_option_values['sample_lesson'] ) ) {
					$this->setting_option_values['sample_lesson'] = '';
				}

				if ( ! isset( $this->setting_option_values['visible_after'] ) ) {
					$this->setting_option_values['visible_after'] = '';
				}

				if ( ! isset( $this->setting_option_values['visible_after_specific_date'] ) ) {
					$this->setting_option_values['visible_after_specific_date'] = '';
				}

				if ( ! empty( $this->setting_option_values['visible_after'] ) ) {
					$this->setting_option_values['lesson_schedule'] = 'visible_after';
				} elseif ( ! empty( $this->setting_option_values['visible_after_specific_date'] ) ) {
					$this->setting_option_values['lesson_schedule'] = 'visible_after_specific_date';
				} else {
					$this->setting_option_values['lesson_schedule'] = '';
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
		 * @since 3.0.0
		 */
		public function load_settings_fields() {
			global $sfwd_lms;

			$select_course_options         = array();
			$select_course_query_data_json = '';

			if ( learndash_use_select2_lib() ) {
				$select_course_options_default = array(
					'-1' => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Search or select a %s…', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
				);

				if ( ! empty( $this->setting_option_values['course'] ) ) {
					$course_post = get_post( absint( $this->setting_option_values['course'] ) );
					if ( ( $course_post ) && ( is_a( $course_post, 'WP_Post' ) ) ) {
						$select_course_options[ $course_post->ID ] = get_the_title( $course_post->ID );
					}
				}

				if ( learndash_use_select2_lib_ajax_fetch() ) {
					$select_course_query_data_json = $this->build_settings_select2_lib_ajax_fetch_json(
						array(
							'query_args'       => array(
								'post_type' => 'sfwd-courses',
							),
							'settings_element' => array(
								'settings_parent_class' => get_parent_class( __CLASS__ ),
								'settings_class'        => __CLASS__,
								'settings_field'        => 'course',
							),
						)
					);
				} else {
					$select_course_options = $sfwd_lms->select_a_course();
				}
				$select_course_options = $select_course_options_default + $select_course_options;
			} else {
				$select_course_options_default = array(
					'' => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Select %s', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
				);
				$select_course_options         = $sfwd_lms->select_a_course();
				if ( ( is_array( $select_course_options ) ) && ( ! empty( $select_course_options ) ) ) {
					$select_course_options = $select_course_options_default + $select_course_options;
				} else {
					$select_course_options = $select_course_options_default;
				}
				$select_course_options_default = '';
			}

			$this->setting_option_fields = array(
				'visible_after' => array(
					'name'        => 'visible_after',
					'type'        => 'number',
					'value'       => $this->setting_option_values['visible_after'],
					'class'       => 'small-text',
					'label_none'  => true,
					'input_full'  => true,
					'input_label' => esc_html__( 'day(s)', 'learndash' ),
					'attrs'       => array(
						'step' => 1,
						'min'  => 0,
					),
					'default'     => 0,
					'rest'        => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								// translators: placeholder: Lesson.
								'description' => esc_html__( 'Visible After X day(s)', 'learndash' ),
								'type'        => 'integer',
								'default'     => 0,
							),
						),
					),
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['lesson_schedule_visible_after_days_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'visible_after_specific_date' => array(
					'name'       => 'visible_after_specific_date',
					'value'      => $this->setting_option_values['visible_after_specific_date'],
					'label_none' => true,
					'input_full' => true,
					'type'       => 'date-entry',
					'class'      => 'learndash-datepicker-field',
					'rest'       => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								// translators: placeholder: Lesson.
								'description' => esc_html__( 'Visible After Specific Date (YYYY-MM-DD)', 'learndash' ),
								'type'        => 'date',
								'default'     => '',
							),
						),
					),
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['visible_after_specific_date_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'course'          => array(
					'name'        => 'course',
					'label'       => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Associated %s', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'        => 'select',
					'default'     => '',
					'value'       => $this->setting_option_values['course'],
					'lazy_load'   => true,
					'options'     => $select_course_options,
					'placeholder' => $select_course_options_default,
					'attrs'       => array(
						'data-ld_selector_nonce'   => wp_create_nonce( 'sfwd-courses' ),
						'data-ld_selector_default' => '1',
						'data-select2-query-data'  => $select_course_query_data_json,
					),
					'rest'        => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'type'    => 'integer',
								'default' => 0,
							),
						),
					),
				),
				'sample_lesson'   => array(
					'name'    => 'sample_lesson',
					'label'   => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( 'Sample %s', 'placeholder: Lesson', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
					'type'    => 'checkbox-switch',
					'value'   => $this->setting_option_values['sample_lesson'],
					'options' => array(
						'on' => sprintf(
							// Translators: placeholder: lesson, course.
							esc_html_x( 'This %1$s is accessible to all visitors regardless of %2$s enrollment', 'placeholder: lesson, course', 'learndash' ),
							learndash_get_custom_label_lower( 'lesson' ),
							learndash_get_custom_label_lower( 'course' )
						),
						''   => '',
					),
					'rest'    => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'is_sample',
								'type'      => 'boolean',
								'default'   => false,
							),
						),
					),
				),
				'lesson_schedule' => array(
					'name'    => 'lesson_schedule',
					'label'   => sprintf(
						// Translators: placeholder: Lesson.
						esc_html_x( '%s Release Schedule', 'placeholder: Lesson', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
					'type'    => 'radio',
					'value'   => $this->setting_option_values['lesson_schedule'],
					'options' => array(
						''                            => array(
							'label'       => esc_html__( 'Immediately', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: lesson, course.
								esc_html_x( 'The %1$s is made available on %2$s enrollment.', 'placeholder: lesson, course', 'learndash' ),
								learndash_get_custom_label_lower( 'lesson' ),
								learndash_get_custom_label_lower( 'course' )
							),
						),
						'visible_after'               => array(
							'label'               => esc_html__( 'Enrollment-based', 'learndash' ),
							'description'         => sprintf(
								// translators: placeholder: lesson, course.
								esc_html_x( 'The %1$s will be available X days after %2$s enrollment.', 'placeholder: lesson, course.', 'learndash' ),
								learndash_get_custom_label_lower( 'lesson' ),
								learndash_get_custom_label_lower( 'course' )
							),
							'inline_fields'       => array(
								'lesson_schedule_visible_after_days' => $this->settings_sub_option_fields['lesson_schedule_visible_after_days_fields'],
							),
							'inner_section_state' => ( 'visible_after' === $this->setting_option_values['lesson_schedule'] ) ? 'open' : 'closed',
						),
						'visible_after_specific_date' => array(
							'label'               => esc_html__( 'Specific date', 'learndash' ),
							'description'         => sprintf(
								// translators: placeholder: lesson.
								esc_html_x( 'The %s will be available on a specific date.', 'placeholder: lesson', 'learndash' ),
								learndash_get_custom_label_lower( 'lesson' )
							),
							'inline_fields'       => array(
								'visible_after_specific_date' => $this->settings_sub_option_fields['visible_after_specific_date_fields'],
							),
							'inner_section_state' => ( 'visible_after_specific_date' === $this->setting_option_values['lesson_schedule'] ) ? 'open' : 'closed',
						),
					),
					'rest'    => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'visible_type',
								'description' => esc_html__( 'Available Release Schedule', 'learndash' ),
								'type'        => 'string',
								'default'     => '',
								'required'    => false,
								'enum'        => array(
									'',
									'visible_after',
									'visible_after_specific_date',
								),
							),
						),
					),
				),
			);

			if ( ( ! defined( 'REST_REQUEST' ) ) || ( true !== REST_REQUEST ) ) {
				if ( learndash_is_course_shared_steps_enabled() ) {
					unset( $this->setting_option_fields['course'] );
				}
			}

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_metabox_key );

			parent::load_settings_fields();
		}

		/**
		 * Filter settings values for metabox before save to database.
		 *
		 * @since 3.0.0
		 *
		 * @param array  $settings_values Array of settings values.
		 * @param string $settings_metabox_key Metabox key.
		 * @param string $settings_screen_id Screen ID.
		 *
		 * @return array $settings_values.
		 */
		public function filter_saved_fields( $settings_values = array(), $settings_metabox_key = '', $settings_screen_id = '' ) {
			if ( ( $settings_screen_id === $this->settings_screen_id ) && ( $settings_metabox_key === $this->settings_metabox_key ) ) {
				if ( isset( $settings_values['lesson_schedule'] ) ) {
					switch ( $settings_values['lesson_schedule'] ) {
						case 'visible_after':
							$settings_values['visible_after_specific_date'] = '';
							break;

						case 'visible_after_specific_date':
							$settings_values['visible_after'] = '';
							break;

						case '':
						default:
							$settings_values['visible_after']               = '';
							$settings_values['visible_after_specific_date'] = '';
							break;
					}
				}

				if ( learndash_is_course_shared_steps_enabled() ) {
					unset( $settings_values['course'] );
				} elseif ( ( ! isset( $settings_values['course'] ) ) || ( '-1' === $settings_values['course'] ) ) {
					$settings_values['course'] = '';
				}
			}

			return $settings_values;
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'lesson' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Lesson_Access_Settings'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Lesson_Access_Settings' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Lesson_Access_Settings'] = LearnDash_Settings_Metabox_Lesson_Access_Settings::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
