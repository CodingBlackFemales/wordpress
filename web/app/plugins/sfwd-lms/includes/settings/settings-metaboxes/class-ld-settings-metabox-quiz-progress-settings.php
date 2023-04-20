<?php
/**
 * LearnDash Settings Metabox for Quiz Progress Settings.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Metaboxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Quiz_Progress_Settings' ) ) ) {
	/**
	 * Class LearnDash Settings Metabox for Quiz Progress Settings.
	 *
	 * @since 3.0.0
	 */
	class LearnDash_Settings_Metabox_Quiz_Progress_Settings extends LearnDash_Settings_Metabox {

		/**
		 * Quiz edit
		 *
		 * @var object
		 */
		protected $quiz_edit = null;
		/**
		 * Public constructor for class
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-quiz';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-quiz-progress-settings';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Progression and Restriction Settings', 'learndash' );

			$this->settings_section_description = sprintf(
				// translators: placeholder: quiz.
				esc_html_x( 'Controls the requirement for accessing and completing the %s', 'placeholder: quiz', 'learndash' ),
				learndash_get_custom_label_lower( 'quiz' )
			);

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				'retry_restrictions'            => 'retry_restrictions',
				'quiz_resume'                   => 'quiz_resume',
				'quiz_resume_cookie_send_timer' => 'quiz_resume_cookie_send_timer',
				'repeats'                       => 'repeats',
				'quizRunOnce'                   => 'quizRunOnce',
				'quizRunOnceType'               => 'quizRunOnceType',
				'quizRunOnceCookie'             => 'quizRunOnceCookie',

				'passingpercentage'             => 'passingpercentage',

				'certificate'                   => 'certificate',
				'threshold'                     => 'threshold',

				'quiz_time_limit_enabled'       => 'quiz_time_limit_enabled',
				'timeLimit'                     => 'timeLimit',
				'forcingQuestionSolve'          => 'forcingQuestionSolve',
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

				if ( ( isset( $this->setting_option_values['passingpercentage'] ) ) && ( '' !== $this->setting_option_values['passingpercentage'] ) ) {
					$this->setting_option_values['passingpercentage'] = floatval( $this->setting_option_values['passingpercentage'] );
				} else {
					$this->setting_option_values['passingpercentage'] = '80';
				}
				if ( ( isset( $this->setting_option_values['threshold'] ) ) && ( '' !== $this->setting_option_values['threshold'] ) ) {
					$this->setting_option_values['threshold'] = floatval( $this->setting_option_values['threshold'] ) * 100;
				} else {
					$this->setting_option_values['threshold'] = '80';
				}

				if ( ! isset( $_GET['templateLoadId'] ) ) {
					$this->setting_option_values['quiz_resume'] = learndash_get_setting( $this->_post->ID, 'quiz_resume' );
				}
				if ( true === (bool) $this->setting_option_values['quiz_resume'] ) {
					$this->setting_option_values['quiz_resume'] = 'on';
				} else {
					$this->setting_option_values['quiz_resume'] = '';
				}

				if ( ( isset( $this->setting_option_values['quiz_resume_cookie_send_timer'] ) ) && ( '' !== $this->setting_option_values['quiz_resume_cookie_send_timer'] ) ) {
					$this->setting_option_values['quiz_resume_cookie_send_timer'] = absint( $this->setting_option_values['quiz_resume_cookie_send_timer'] );
					if ( LEARNDASH_QUIZ_RESUME_COOKIE_SEND_TIMER_MIN > $this->setting_option_values['quiz_resume_cookie_send_timer'] ) {
						$this->setting_option_values['quiz_resume_cookie_send_timer'] = LEARNDASH_QUIZ_RESUME_COOKIE_SEND_TIMER_MIN;
					}
				} else {
					$this->setting_option_values['quiz_resume_cookie_send_timer'] = LEARNDASH_QUIZ_RESUME_COOKIE_SEND_TIMER_DEFAULT;
				}

				if ( ( isset( $this->quiz_edit['quiz'] ) ) && ( ! empty( $this->quiz_edit['quiz'] ) ) ) {
					$this->setting_option_values['timeLimit'] = $this->quiz_edit['quiz']->getTimeLimit();

					$this->setting_option_values['forcingQuestionSolve'] = $this->quiz_edit['quiz']->isForcingQuestionSolve();
					if ( true === $this->setting_option_values['forcingQuestionSolve'] ) {
						$this->setting_option_values['forcingQuestionSolve'] = 'on';
					}

					$this->setting_option_values['quizRunOnceType']   = '';
					$this->setting_option_values['quizRunOnceCookie'] = '';

					if ( ( isset( $this->setting_option_values['repeats'] ) ) && ( '' !== $this->setting_option_values['repeats'] ) ) {
						$this->setting_option_values['quizRunOnceType']   = $this->quiz_edit['quiz']->getQuizRunOnceType();
						$this->setting_option_values['quizRunOnceCookie'] = $this->quiz_edit['quiz']->isQuizRunOnceCookie();
					} else {
						$this->setting_option_values['repeats'] = '';
						if ( $this->quiz_edit['quiz']->isQuizRunOnce() ) {
							$this->setting_option_values['repeats']           = '0';
							$this->setting_option_values['quizRunOnceType']   = $this->quiz_edit['quiz']->getQuizRunOnceType();
							$this->setting_option_values['quizRunOnceCookie'] = $this->quiz_edit['quiz']->isQuizRunOnceCookie();
						}
					}

					if ( in_array( $this->setting_option_values['quizRunOnceType'], array( 1, 3 ), true ) ) {
						$this->setting_option_values['quizRunOnceCookie'] = true;
					}

					if ( true === $this->setting_option_values['quizRunOnceCookie'] ) {
						$this->setting_option_values['quizRunOnceCookie'] = 'on';
					}

					if ( ( isset( $this->setting_option_values['repeats'] ) ) && ( '' !== $this->setting_option_values['repeats'] ) ) {
						$this->setting_option_values['retry_restrictions'] = 'on';
					} else {
						$this->setting_option_values['retry_restrictions'] = '';
						$this->setting_option_values['repeats']            = '0';
						$this->setting_option_values['quizRunOnce']        = false;
						$this->setting_option_values['quizRunOnceType']    = '';
						$this->setting_option_values['quizRunOnceCookie']  = '';
					}

					if ( ! isset( $this->setting_option_values['quiz_time_limit_enabled'] ) ) {
						$this->setting_option_values['quiz_time_limit_enabled'] = '';
						if ( ( isset( $this->setting_option_values['timeLimit'] ) ) && ( ! empty( $this->setting_option_values['timeLimit'] ) ) ) {
							$this->setting_option_values['quiz_time_limit_enabled'] = 'on';
						}
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

			$select_cert_options         = array();
			$select_cert_query_data_json = '';

			if ( learndash_use_select2_lib() ) {
				$select_cert_options_default = array(
					'-1' => esc_html__( 'Search or select a certificate…', 'learndash' ),
				);

				if ( ! empty( $this->setting_option_values['certificate'] ) ) {
					$cert_post = get_post( absint( $this->setting_option_values['certificate'] ) );
					if ( ( $cert_post ) && ( is_a( $cert_post, 'WP_Post' ) ) ) {
						$select_cert_options[ $cert_post->ID ] = get_the_title( $cert_post->ID );
					}
				}

				if ( learndash_use_select2_lib_ajax_fetch() ) {
					$select_cert_query_data_json = $this->build_settings_select2_lib_ajax_fetch_json(
						array(
							'query_args'       => array(
								'post_type' => learndash_get_post_type_slug( 'certificate' ),
							),
							'settings_element' => array(
								'settings_parent_class' => get_parent_class( __CLASS__ ),
								'settings_class'        => __CLASS__,
								'settings_field'        => 'certificate',
							),
						)
					);
				} else {
					$select_cert_options = $sfwd_lms->select_a_certificate();
				}
			} else {
				$select_cert_options_default = array(
					'' => esc_html__( 'Select Certificate', 'learndash' ),
				);
				$select_cert_options         = $sfwd_lms->select_a_certificate();

				if ( ( is_array( $select_cert_options ) ) && ( ! empty( $select_cert_options ) ) ) {
					$select_cert_options = $select_cert_options_default + $select_cert_options;
				} else {
					$select_cert_options = $select_cert_options_default;
				}
				$select_cert_options_default = '';
			}

			$this->setting_option_fields = array(
				'passingpercentage'             => array(
					'name'        => 'passingpercentage',
					'label'       => esc_html__( 'Passing Score', 'learndash' ),
					'type'        => 'number',
					'value'       => $this->setting_option_values['passingpercentage'],
					'default'     => '80',
					'placeholder' => 'e.g. 80',
					'class'       => '-small',
					'input_label' => '%',
					'attrs'       => array(
						'min' => '0',
						'max' => '100',
					),
					'rest'        => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'passing_percentage',
								'description' => esc_html__( 'Passing Score Percentage', 'learndash' ),
								'type'        => 'float',
								'default'     => 0.0,
							),
						),
					),
				),
				'certificate'                   => array(
					'name'                => 'certificate',
					'label'               => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '%s Certificate', 'placeholder: Quiz', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'type'                => 'select',
					'value'               => $this->setting_option_values['certificate'],
					'options'             => $select_cert_options,
					'placeholder'         => $select_cert_options_default,
					'child_section_state' => ( ( ! empty( $this->setting_option_values['certificate'] ) ) && ( '-1' !== $this->setting_option_values['certificate'] ) ) ? 'open' : 'closed',
					'attrs'               => array(
						'data-select2-query-data' => $select_cert_query_data_json,
					),
					'rest'                => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'type'    => 'integer',
								'default' => 0,
							),
						),
					),
				),
				'threshold'                     => array(
					'name'           => 'threshold',
					'label'          => esc_html__( 'Certificate Awarded for', 'learndash' ),
					'type'           => 'number',
					'default'        => '80',
					'placeholder'    => 'e.g. 80',
					'class'          => '-small',
					'help_text'      => esc_html__( 'Set the score needed to receive a certificate. This can be different from the "Passing Score".', 'learndash' ),
					'input_label'    => esc_html__( '% score', 'learndash' ),
					'attrs'          => array(
						'min' => '0',
						'max' => '100',
					),
					'value'          => $this->setting_option_values['threshold'],
					'parent_setting' => 'certificate',
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'certificate_award_threshold',
								'type'      => 'float',
								'default'   => 0.0,
							),
						),
					),
				),
				'quiz_resume'                   => array(
					'name'                => 'quiz_resume',
					'type'                => 'checkbox-switch',
					'label'               => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( 'Enable %s Saving', 'placeholder: Quiz', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'value'               => $this->setting_option_values['quiz_resume'],
					'default'             => '',
					'options'             => array(
						''   => '',
						'on' => esc_html__( 'Progress will be saved to the server', 'learndash' ),
					),
					'help_text'           => sprintf(
						// translators: placeholder: quiz, quiz.
						esc_html_x( '%1$s saving allows your users to save their current %2$s progress and return to it at a later date and preserve their progress.', 'placeholder: quiz, quiz', 'learndash' ),
						learndash_get_custom_label( 'quiz' ),
						learndash_get_custom_label( 'quiz' )
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['quiz_resume'] ) ? 'open' : 'closed',
					'rest'                => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'quiz_resume',
								'type'      => 'boolean',
								'default'   => false,
							),
						),
					),
				),
				'quiz_resume_cookie_send_timer' => array(
					'name'           => 'quiz_resume_cookie_send_timer',
					'label_full'     => true,
					'label'          => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( 'Save %s data to the server every', 'placeholder: Quiz', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'type'           => 'number',
					'class'          => '-small',
					'parent_setting' => 'quiz_resume',
					'value'          => $this->setting_option_values['quiz_resume_cookie_send_timer'],
					'input_label'    => esc_html__( 'seconds', 'learndash' ),
					'default'        => '',
					'attrs'          => array(
						'step' => 1,
						'min'  => LEARNDASH_QUIZ_RESUME_COOKIE_SEND_TIMER_MIN,
					),
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'quiz_resume_cookie_send_timer',
								'description' => esc_html__( 'Save cookie data to the server every', 'learndash' ),
								'type'        => 'integer',
								'default'     => LEARNDASH_QUIZ_RESUME_COOKIE_SEND_TIMER_DEFAULT,
							),
						),
					),

				),
				'retry_restrictions'            => array(
					'name'                => 'retry_restrictions',
					'label'               => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( 'Restrict %s Retakes', 'placeholder: Quiz', 'learndash' ),
						learndash_get_custom_label( 'quiz' )
					),
					'type'                => 'checkbox-switch',
					'options'             => array(
						'on' => '',
					),
					'value'               => $this->setting_option_values['retry_restrictions'],
					'default'             => '',
					'child_section_state' => ( 'on' === $this->setting_option_values['retry_restrictions'] ) ? 'open' : 'closed',
					'rest'                => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key' => 'retry_restrictions_enabled',
								'type'      => 'boolean',
								'default'   => false,
							),
						),
					),
				),
				'repeats'                       => array(
					'name'           => 'repeats',
					'label'          => esc_html__( 'Number of Retries Allowed', 'learndash' ),
					'help_text'      => esc_html__( 'You must input a whole number value or leave blank to default to 0.', 'learndash' ),
					'type'           => 'number',
					'class'          => '-small',
					'default'        => '',
					'value'          => $this->setting_option_values['repeats'],
					'attrs'          => array(
						'step'        => 1,
						'min'         => 0,
						'can_empty'   => true,
						'can_decimal' => false,
					),
					'parent_setting' => 'retry_restrictions',
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'retry_repeats',
								'description' => esc_html__( 'Number of repeats allowed. blank is unlimited, 0 is 1 repeats, 1 is 2 repeats, etc.', 'learndash' ),
								'type'        => 'string',
								'default'     => '',
							),
						),
					),
				),
				'quizRunOnceType'               => array(
					'name'           => 'quizRunOnceType',
					'label'          => esc_html__( 'Retries Applicable to', 'learndash' ),
					'type'           => 'select',
					'default'        => '1',
					'value'          => $this->setting_option_values['quizRunOnceType'],
					'options'        => array(
						'1' => esc_html__( 'All users', 'learndash' ),
						'2' => esc_html__( 'Registered users only', 'learndash' ),
						'3' => esc_html__( 'Anonymous user only', 'learndash' ),
					),
					'parent_setting' => 'retry_restrictions',
				),
				'quizRunOnceCookie'             => array(
					'name'           => 'quizRunOnceCookie',
					'label'          => '',
					'type'           => 'checkbox',
					'options'        => array(
						'on' => esc_html__( 'Use a cookie to restrict anonymous visitors', 'learndash' ),
					),
					'value'          => $this->setting_option_values['quizRunOnceCookie'],
					'default'        => 'on',
					'parent_setting' => 'retry_restrictions',
					'attrs'          => array(
						'disabled' => 'disabled',
					),
				),

				'quiz_reset_cookies'            => array(
					'name'           => 'quiz_reset_cookies',
					'type'           => 'custom',
					'html'           => '<div><input class="button-secondary" type="button" name="resetQuizLock" data-nonce="' . wp_create_nonce( 'learndash-wpproquiz-reset-lock' ) . '" value="' . esc_html__( 'Reset the user identification', 'learndash' ) . '"><span id="resetLockMsg" style="display:none; background-color: rgb(255, 255, 173); border: 1px solid rgb(143, 143, 143); padding: 4px; margin-left: 5px; ">' . esc_html__( 'User identification has been reset.', 'learndash' ) . '</span><p class="description"></p></div>',
					'label'          => '',
					'help_text'      => esc_html__( 'Anonymous visitors only', 'learndash' ),
					'parent_setting' => 'retry_restrictions',
				),

				'forcingQuestionSolve'          => array(
					'name'    => 'forcingQuestionSolve',
					'label'   => sprintf(
						// translators: placeholder: Question.
						esc_html_x( '%s Completion', 'placeholder: Question', 'learndash' ),
						learndash_get_custom_label( 'question' )
					),
					'type'    => 'checkbox',
					'options' => array(
						'on' => sprintf(
							// translators: placeholder: Questions.
							esc_html_x( 'All %s required to complete', 'placeholder: Questions', 'learndash' ),
							learndash_get_custom_label( 'questions' )
						),
					),
					'value'   => $this->setting_option_values['forcingQuestionSolve'],
					'default' => 'on',
					'rest'    => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'answer_all_questions_enabled',
								'description' => sprintf(
									// translators: placeholder: Questions.
									esc_html_x( 'All %s required to complete', 'placeholder: Questions', 'learndash' ),
									learndash_get_custom_label( 'questions' )
								),
								'type'        => 'boolean',
								'default'     => true,
							),
						),
					),
				),
				'quiz_time_limit_enabled'       => array(
					'name'                => 'quiz_time_limit_enabled',
					'label'               => esc_html__( 'Time Limit', 'learndash' ),
					'type'                => 'checkbox-switch',
					'options'             => array(
						'on' => '',
					),
					'value'               => $this->setting_option_values['quiz_time_limit_enabled'],
					'default'             => '',
					'child_section_state' => ( 'on' === $this->setting_option_values['quiz_time_limit_enabled'] ) ? 'open' : 'closed',
					'rest'                => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'time_limit_enabled',
								'description' => esc_html__( 'Time Limit Enabled', 'learndash' ),
								'type'        => 'boolean',
								'default'     => false,
							),
						),
					),
				),
				'timeLimit'                     => array(
					'name'           => 'timeLimit',
					'label'          => esc_html__( 'Automatically Submit After', 'learndash' ),
					'type'           => 'timer-entry',
					'class'          => 'small-text',
					'placeholder'    => esc_html__( 'e.g. 0', 'learndash' ),
					'default'        => '',
					'value'          => $this->setting_option_values['timeLimit'],
					'parent_setting' => 'quiz_time_limit_enabled',
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'field_key'   => 'time_limit_time',
								'description' => esc_html__( 'Automatically Submit After', 'learndash' ),
								'type'        => 'integer',
								'default'     => 0,
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

				if ( ! isset( $settings_values['certificate'] ) ) {
					$settings_values['certificate'] = '';
				}

				if ( ! isset( $settings_values['passingpercentage'] ) ) {
					$settings_values['passingpercentage'] = '';
				}
				$settings_values['passingpercentage'] = strval( $settings_values['passingpercentage'] );

				if ( ! isset( $settings_values['threshold'] ) ) {
					$settings_values['threshold'] = '';
				}

				if ( '-1' === $settings_values['certificate'] ) {
					$settings_values['certificate'] = '';
				}

				if ( ! empty( $settings_values['certificate'] ) ) {
					$settings_values['threshold'] = floatval( $settings_values['threshold'] ) / 100;
				} else {
					$settings_values['threshold']   = '';
					$settings_values['certificate'] = '';
				}

				// Clear out the time limit is the time limit enabled is not set.
				if ( ! isset( $settings_values['quiz_time_limit_enabled'] ) ) {
					$settings_values['quiz_time_limit_enabled'] = '';
				}
				if ( ! isset( $settings_values['timeLimit'] ) ) {
					$settings_values['timeLimit'] = '';
				}

				if ( 'on' === $settings_values['quiz_time_limit_enabled'] ) {
					if ( empty( $settings_values['timeLimit'] ) ) {
						$settings_values['quiz_time_limit_enabled'] = '';
					}
				}

				if ( ! empty( $settings_values['timeLimit'] ) ) {
					if ( 'on' !== $settings_values['quiz_time_limit_enabled'] ) {
						$settings_values['timeLimit'] = 0;
					}
				}

				if ( 'on' === $settings_values['forcingQuestionSolve'] ) {
					$settings_values['forcingQuestionSolve'] = true;
				} else {
					$settings_values['forcingQuestionSolve'] = false;
				}

				if ( ! isset( $settings_values['retry_restrictions'] ) ) {
					$settings_values['retry_restrictions'] = '';
				}

				if ( ( isset( $settings_values['quiz_resume'] ) ) && ( 'on' === $settings_values['quiz_resume'] ) ) {
					$settings_values['quiz_resume'] = true;
				} else {
					$settings_values['quiz_resume'] = false;
				}

				if ( ( isset( $settings_values['quiz_resume_cookie_send_timer'] ) ) && ( ! empty( $settings_values['quiz_resume_cookie_send_timer'] ) ) ) {
					$settings_values['quiz_resume_cookie_send_timer'] = absint( $settings_values['quiz_resume_cookie_send_timer'] );
					if ( empty( $settings_values['quiz_resume_cookie_send_timer'] ) ) {
						$settings_values['quiz_resume_cookie_send_timer'] = '0';
					};
				}

				if ( ! isset( $settings_values['repeats'] ) ) {
					$settings_values['repeats'] = '';
				}

				if ( ( 'on' !== $settings_values['retry_restrictions'] ) || ( '' === $settings_values['repeats'] ) ) {
					$settings_values['repeats']            = '';
					$settings_values['retry_restrictions'] = '';
					$settings_values['quizRunOnce']        = false;
					$settings_values['quizRunOnceType']    = '';
					$settings_values['quizRunOnceCookie']  = '';
				} else {
					$settings_values['quizRunOnce'] = true;
					if ( ( isset( $settings_values['quizRunOnceCookie'] ) ) && ( 'on' === $settings_values['quizRunOnceCookie'] ) ) {
						$settings_values['quizRunOnceCookie'] = true;
					}

					if ( ( isset( $settings_values['quizRunOnceType'] ) ) && ( in_array( absint( $settings_values['quizRunOnceType'] ), array( 1, 3 ), true ) ) ) {
						$settings_values['quizRunOnceCookie'] = true;
					}
				}
			}

			return $settings_values;
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'quiz' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Quiz_Progress_Settings'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Quiz_Progress_Settings' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Quiz_Progress_Settings'] = LearnDash_Settings_Metabox_Quiz_Progress_Settings::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
