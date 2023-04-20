<?php
/**
 * LearnDash Settings Metabox for Quiz Access Settings.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Metaboxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Quiz_Access_Settings' ) ) ) {
	/**
	 * Class LearnDash Settings Metabox for Quiz Access Settings.
	 *
	 * @since 3.0.0
	 */
	class LearnDash_Settings_Metabox_Quiz_Access_Settings extends LearnDash_Settings_Metabox {

		/**
		 * Quiz edit object
		 *
		 * @var object
		 */
		public $quiz_edit = null;

		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-quiz';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-quiz-access-settings';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Quiz.
				esc_html_x( '%s Access Settings', 'placeholder: Quiz', 'learndash' ),
				learndash_get_custom_label( 'quiz' )
			);

			$this->settings_section_description = sprintf(
				// translators: placeholder: quiz.
				esc_html_x( 'Controls the requirements for accessing the %s', 'placeholder: quiz', 'learndash' ),
				learndash_get_custom_label_lower( 'quiz' )
			);

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );
			add_filter( 'learndash_settings_row_input_inside_before', array( $this, 'settings_row_input_inside_before' ), 30, 2 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				'course'                      => 'course',
				'lesson'                      => 'lesson',
				'lesson_schedule'             => 'lesson_schedule',
				'visible_after'               => 'visible_after',
				'visible_after_specific_date' => 'visible_after_specific_date',
				'startOnlyRegisteredUser'     => 'startOnlyRegisteredUser',
				'prerequisite'                => 'prerequisite',
				'prerequisiteList'            => 'prerequisiteList',
			);

			parent::__construct();
		}

		/**
		 * Used to save the settings fields back to the global $_POST object so
		 * the WPProQuiz normal form processing can take place.
		 *
		 * @since 3.0.0
		 *
		 * @param object $pro_quiz_edit WpProQuiz_Controller_Quiz instance (not used).
		 * @param array  $settings_values Array of settings fields.
		 */
		public function save_fields_to_post( $pro_quiz_edit, $settings_values = array() ) {
			foreach ( $settings_values as $setting_key => $setting_value ) {
				if ( isset( $this->settings_fields_map[ $setting_key ] ) ) {
					$_POST[ $setting_key ] = $setting_value;
				}
			}
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 3.0.0
		 */
		public function load_settings_values() {
			$reload_pro_quiz = false;
			if ( true !== $this->settings_values_loaded ) {
				$reload_pro_quiz = true;
			}

			parent::load_settings_values();

			if ( true === $this->settings_values_loaded ) {
				$this->quiz_edit = $this->init_quiz_edit( $this->_post, $reload_pro_quiz );

				if ( ! isset( $this->setting_option_values['course'] ) ) {
					$this->setting_option_values['course'] = '';
				}
				if ( ! isset( $this->setting_option_values['lesson'] ) ) {
					$this->setting_option_values['lesson'] = '';
				}

				if ( ( isset( $this->quiz_edit['quiz'] ) ) && ( ! empty( $this->quiz_edit['quiz'] ) ) ) {
					$this->setting_option_values['startOnlyRegisteredUser'] = $this->quiz_edit['quiz']->isStartOnlyRegisteredUser();
					if ( true === $this->setting_option_values['startOnlyRegisteredUser'] ) {
						$this->setting_option_values['startOnlyRegisteredUser'] = 'on';
					} else {
						$this->setting_option_values['startOnlyRegisteredUser'] = '';
					}

					if ( $this->quiz_edit['prerequisiteQuizList'] ) {
						$this->setting_option_values['prerequisiteList'] = $this->quiz_edit['prerequisiteQuizList'];
					} else {
						$this->setting_option_values['prerequisiteList'] = array();
					}
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

				$select_course_options = $sfwd_lms->select_a_course();
				if ( ( is_array( $select_course_options ) ) && ( ! empty( $select_course_options ) ) ) {
					$select_course_options = $select_course_options_default + $select_course_options;
				} else {
					$select_course_options = $select_course_options_default;
				}
				$select_course_options_default = '';
			}

			/**
			 * Select a Lesson/Topic Selector
			 */
			$select_lesson_options = array();
			if ( ( isset( $this->setting_option_values['course'] ) ) && ( ! empty( $this->setting_option_values['course'] ) ) ) {
				$select_lesson_options = $sfwd_lms->select_a_lesson_or_topic( absint( $this->setting_option_values['course'] ), true, false );
			}

			/** This filter is documented in includes/class-ld-lms.php */
			if ( learndash_use_select2_lib() ) {
				$select_lesson_options_default = array(
					'-1' => sprintf(
						// translators: placeholder: Lesson, Topic.
						esc_html_x( 'Search or select a %1$s or %2$s…', 'placeholder: Lesson, Topic', 'learndash' ),
						learndash_get_custom_label( 'lesson' ),
						learndash_get_custom_label( 'topic' )
					),
				);
				$select_lesson_options         = $select_lesson_options_default + $select_lesson_options;

			} else {
				$select_lesson_options_default = array(
					'' => sprintf(
						// translators: placeholder: Lesson, Topic.
						esc_html_x( 'Select a %1$s or %2$s', 'placeholder: Lesson, Topic', 'learndash' ),
						learndash_get_custom_label( 'lesson' ),
						learndash_get_custom_label( 'topic' )
					),
				);

				if ( ( is_array( $select_lesson_options ) ) && ( ! empty( $select_lesson_options ) ) ) {
					if ( isset( $select_lesson_options[0] ) ) {
						unset( $select_lesson_options[0] );
					}

					$select_lesson_options = $select_lesson_options_default + $select_lesson_options;
				} else {
					$select_lesson_options = $select_lesson_options_default;
				}
			}

			if ( learndash_use_select2_lib() ) {
				$select_quiz_prerequisite_options_default = array(
					'-1' => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( 'No previous %s required', 'placeholder: Quiz.', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
				);
			} else {
				$select_quiz_prerequisite_options_default = array(
					'' => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( 'No previous %s required', 'placeholder: Quiz.', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
				);
			}

			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				$select_quiz_prerequisite_options = array();
			} else {
				$quiz_mapper                      = new WpProQuiz_Model_QuizMapper();
				$prerequisite_quizzes             = $quiz_mapper->fetchAllAsArray( array( 'id', 'name' ), array() );
				$select_quiz_prerequisite_options = array();
				if ( ! empty( $prerequisite_quizzes ) ) {
					foreach ( $prerequisite_quizzes as $quiz_set ) {
						if ( ( isset( $this->quiz_edit['quiz'] ) ) && ( absint( $quiz_set['id'] ) !== absint( $this->quiz_edit['quiz']->getId() ) ) ) {
							$select_quiz_prerequisite_options[ $quiz_set['id'] ] = $quiz_set['name'];
						}
					}
				}
				if ( ( is_array( $select_quiz_prerequisite_options ) ) && ( ! empty( $select_quiz_prerequisite_options ) ) ) {
					$select_quiz_prerequisite_options = $select_quiz_prerequisite_options_default + $select_quiz_prerequisite_options;
				} else {
					$select_quiz_prerequisite_options = $select_quiz_prerequisite_options_default;
				}
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
				'course'                  => array(
					'name'        => 'course',
					'label'       => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Associated %s', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'        => 'select',
					'default'     => '',
					'value'       => $this->setting_option_values['course'],
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
				'lesson'                  => array(
					'name'        => 'lesson',
					'label'       => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( 'Associated %s', 'placeholder: Lesson', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
					'type'        => 'select',
					'default'     => '',
					'value'       => $this->setting_option_values['lesson'],
					'options'     => $select_lesson_options,
					'placeholder' => $select_lesson_options_default,
					'attrs'       => array(
						'data-ld_selector_nonce'   => wp_create_nonce( 'sfwd-lessons' ),
						'data-ld_selector_default' => '1',
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
				'lesson_schedule'         => array(
					'name'    => 'lesson_schedule',
					'label'   => sprintf(
						// Translators: placeholder: Quiz.
						esc_html_x( '%s Release Schedule', 'placeholder: Quiz', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'type'    => 'radio',
					'value'   => $this->setting_option_values['lesson_schedule'],
					'options' => array(
						''                            => array(
							'label'       => esc_html__( 'Immediately', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: quiz, course.
								esc_html_x( 'The %1$s is made available on %2$s enrollment.', 'placeholder: quiz, course', 'learndash' ),
								learndash_get_custom_label_lower( 'quiz' ),
								learndash_get_custom_label_lower( 'course' )
							),
						),
						'visible_after'               => array(
							'label'               => esc_html__( 'Enrollment-based', 'learndash' ),
							'description'         => sprintf(
								// translators: placeholder: quiz, course.
								esc_html_x( 'The %1$s will be available X days after %2$s enrollment.', 'placeholder: quiz, course.', 'learndash' ),
								learndash_get_custom_label_lower( 'quiz' ),
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
								// translators: placeholder: quiz.
								esc_html_x( 'The %s will be available on a specific date.', 'placeholder: quiz', 'learndash' ),
								learndash_get_custom_label_lower( 'quiz' )
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
				'prerequisiteList'        => array(
					'name'      => 'prerequisiteList',
					'type'      => 'multiselect',
					'label'     => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '%s Prerequisites', 'placeholder: Quiz', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'help_text' => sprintf(
						// translators: placeholders: quizzes, quiz.
						esc_html_x( 'Select one or more %1$s that must be completed prior to taking this %2$s.', 'placeholders: Quizzes Quiz', 'learndash' ),
						learndash_get_custom_label_lower( 'quizzes' ),
						learndash_get_custom_label_lower( 'quiz' )
					),
					'value'     => $this->setting_option_values['prerequisiteList'],
					'options'   => $select_quiz_prerequisite_options,
					'rest'      => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'prerequisites',
								'default'   => array(),
								'type'      => 'array',
								'items'     => array(
									'type' => 'integer',
								),
							),
						),
					),
				),

				'startOnlyRegisteredUser' => array(
					'name'      => 'startOnlyRegisteredUser',
					'type'      => 'checkbox',
					'label'     => esc_html__( 'Allowed Users', 'learndash' ),
					'value'     => $this->setting_option_values['startOnlyRegisteredUser'],
					'help_text' => sprintf(
						// translators: placeholders: quizzes, course, courses, quiz.
						esc_html_x( 'This option is especially useful if administering %1$s via shortcodes on non-%2$s pages, or if your %3$s are open but you wish only authenticated users to take the %4$s.', 'placeholders: quizzes, courses, quiz.', 'learndash' ),
						learndash_get_custom_label( 'quizzes' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label( 'course' ),
						learndash_get_custom_label( 'quiz' )
					),
					'default'   => '',
					'options'   => array(
						'on' => sprintf(
							// translators: placeholder: quiz.
							esc_html_x( 'Only registered users can take this %s', 'placeholder: quiz', 'learndash' ),
							learndash_get_custom_label( 'quiz' )
						),
					),
					'rest'      => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'registered_users_only',
								esc_html__( 'Only Logged-in Users', 'learndash' ),
								'type'      => 'boolean',
								'default'   => false,
							),
						),
					),
				),
			);

			if ( learndash_is_course_shared_steps_enabled() ) {
				unset( $this->setting_option_fields['course'] );
				unset( $this->setting_option_fields['lesson'] );
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

				if ( ( ! isset( $settings_values['course'] ) ) || ( '-1' === $settings_values['course'] ) ) {
					$settings_values['course'] = '';
				}

				if ( ( ! isset( $settings_values['lesson'] ) ) || ( '-1' === $settings_values['lesson'] ) ) {
					$settings_values['lesson'] = '';
				}

				if ( ( isset( $settings_values['startOnlyRegisteredUser'] ) ) && ( 'on' === $settings_values['startOnlyRegisteredUser'] ) ) {
					$settings_values['startOnlyRegisteredUser'] = true;
				} else {
					$settings_values['startOnlyRegisteredUser'] = false;
				}

				if ( ! isset( $settings_values['prerequisiteList'] ) ) {
					$settings_values['prerequisiteList'] = '';
				}

				if ( '-1' === $settings_values['prerequisiteList'] ) {
					$settings_values['prerequisiteList'] = '';
				}
				if ( ! empty( $settings_values['prerequisiteList'] ) ) {
					$settings_values['prerequisite'] = true;
				} else {
					$settings_values['prerequisite'] = '';
				}

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
					unset( $settings_values['lesson'] );
				}
			}

			return $settings_values;
		}

		/**
		 * Filter function for `learndash_settings_row_input_inside_before` to add a custom HTML before the input.
		 *
		 * @since 4.2.0
		 *
		 * @param string $input_before HTML to output.
		 * @param array  $field_args   Array of field arguments.
		 *
		 * @return string HTML to output.
		 */
		public function settings_row_input_inside_before( $input_before = '', $field_args = array() ) {
			if ( 'lesson_schedule' === $field_args['name'] ) {
				if ( is_admin() ) {
					$screen = get_current_screen();
					if ( ( is_a( $screen, 'WP_Screen' ) ) && ( 'post' === $screen->base ) && ( 'edit' === $screen->parent_base ) ) {
						if ( ( is_a( $this, __CLASS__ ) ) && ( ! empty( $this->setting_option_values['lesson'] ) ) && ( ( learndash_get_setting( $this->setting_option_values['lesson'], 'visible_after' ) ) || ( learndash_get_setting( $this->setting_option_values['lesson'], 'visible_after_specific_date' ) ) ) ) {
							if ( ! learndash_is_course_shared_steps_enabled() ) {
								$post_link_label = learndash_get_custom_label_lower( learndash_get_post_type_key( get_post_type( $this->setting_option_values['lesson'] ) ) );

								$lesson_schedule_alert = sprintf(
									// translators: placeholder: parent post edit link.
									esc_html_x( 'The parent %s is setup for scheduled release. This step will be available after.', 'placeholder: parent post edit link.', 'learndash' ),
									'<a href="' . get_edit_post_link( $this->setting_option_values['lesson'] ) . '">' . $post_link_label . '</a>'
								);

								$input_before .= '<div class="ld-settings-info-banner ld-settings-info-banner-alert">' . wpautop( $lesson_schedule_alert ) . '</div>';
							}
						}
					}
				}
			}
			return $input_before;
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'quiz' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Quiz_Access_Settings'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Quiz_Access_Settings' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Quiz_Access_Settings'] = LearnDash_Settings_Metabox_Quiz_Access_Settings::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}

