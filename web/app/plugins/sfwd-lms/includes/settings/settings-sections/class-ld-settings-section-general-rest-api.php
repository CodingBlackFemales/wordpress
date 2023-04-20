<?php
/**
 * LearnDash Settings Section for REST API Metabox.
 *
 * @since 2.5.8
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_General_REST_API' ) ) ) {
	/**
	 * Class LearnDash Settings Section for REST API Metabox.
	 *
	 * @since 2.5.8
	 */
	class LearnDash_Settings_Section_General_REST_API extends LearnDash_Settings_Section {

		/**
		 * Setting Option Fields REST API V1
		 *
		 * @var array
		 */
		protected $setting_option_fields_v1 = array();

		/**
		 * Setting Option Fields REST API V2
		 *
		 * @var array
		 */
		protected $setting_option_fields_v2 = array();

		/**
		 * Protected constructor for class
		 *
		 * @since 2.5.8
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_advanced';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_rest_api';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_rest_api';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_rest_api';

			// Section label/header.
			$this->settings_section_label     = esc_html__( 'REST API Settings', 'learndash' );
			$this->settings_section_sub_label = esc_html__( 'REST API', 'learndash' );

			$this->settings_section_description = esc_html__( 'Control and customize the REST API endpoints.', 'learndash' );

			add_filter( 'learndash_settings_row_outside_after', array( $this, 'learndash_settings_row_outside_after' ), 10, 2 );
			add_filter( 'learndash_settings_row_outside_before', array( $this, 'learndash_settings_row_outside_before' ), 30, 2 );

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 2.5.8
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			if ( ! isset( $this->setting_option_values['enabled'] ) ) {
				$this->setting_option_values['enabled'] = 'yes';
			}

			// V1 Endpoint values.
			if ( ( ! isset( $this->setting_option_values['sfwd-courses'] ) ) || ( empty( $this->setting_option_values['sfwd-courses'] ) ) ) {
				$this->setting_option_values['sfwd-courses'] = learndash_get_post_type_slug( 'course' );
			}

			if ( ( ! isset( $this->setting_option_values['sfwd-lessons'] ) ) || ( empty( $this->setting_option_values['sfwd-lessons'] ) ) ) {
				$this->setting_option_values['sfwd-lessons'] = learndash_get_post_type_slug( 'lesson' );
			}

			if ( ( ! isset( $this->setting_option_values['sfwd-topic'] ) ) || ( empty( $this->setting_option_values['sfwd-topic'] ) ) ) {
				$this->setting_option_values['sfwd-topic'] = learndash_get_post_type_slug( 'topic' );
			}

			if ( ( ! isset( $this->setting_option_values['sfwd-quiz'] ) ) || ( empty( $this->setting_option_values['sfwd-quiz'] ) ) ) {
				$this->setting_option_values['sfwd-quiz'] = learndash_get_post_type_slug( 'quiz' );
			}

			if ( ( ! isset( $this->setting_option_values['sfwd-question'] ) ) || ( empty( $this->setting_option_values['sfwd-question'] ) ) ) {
				$this->setting_option_values['sfwd-question'] = learndash_get_post_type_slug( 'question' );
			}

			if ( ( ! isset( $this->setting_option_values['users'] ) ) || ( empty( $this->setting_option_values['users'] ) ) ) {
				$this->setting_option_values['users'] = 'users';
			}

			if ( ( ! isset( $this->setting_option_values['groups'] ) ) || ( empty( $this->setting_option_values['groups'] ) ) ) {
				$this->setting_option_values['groups'] = learndash_get_post_type_slug( 'group' );
			}

			// V2 Endpoint values.

			if ( ( ! isset( $this->setting_option_values['courses_v2'] ) ) || ( empty( $this->setting_option_values['courses_v2'] ) ) ) {
				$this->setting_option_values['courses_v2'] = learndash_get_post_type_slug( 'course' );
			}

			if ( ( ! isset( $this->setting_option_values['courses-users_v2'] ) ) || ( empty( $this->setting_option_values['courses-users_v2'] ) ) ) {
				$this->setting_option_values['courses-users_v2'] = 'users';
			}
			if ( ( ! isset( $this->setting_option_values['courses-steps_v2'] ) ) || ( empty( $this->setting_option_values['courses-steps_v2'] ) ) ) {
				$this->setting_option_values['courses-steps_v2'] = 'steps';
			}
			if ( ( ! isset( $this->setting_option_values['courses-groups_v2'] ) ) || ( empty( $this->setting_option_values['courses-groups_v2'] ) ) ) {
				$this->setting_option_values['courses-groups_v2'] = 'groups';
			}
			if ( ( ! isset( $this->setting_option_values['courses-prerequisites_v2'] ) ) || ( empty( $this->setting_option_values['courses-prerequisites_v2'] ) ) ) {
				$this->setting_option_values['courses-prerequisites_v2'] = 'prerequisites';
			}

			if ( ( ! isset( $this->setting_option_values['lessons_v2'] ) ) || ( empty( $this->setting_option_values['lessons_v2'] ) ) ) {
				$this->setting_option_values['lessons_v2'] = learndash_get_post_type_slug( 'lesson' );
			}

			if ( ( ! isset( $this->setting_option_values['topics_v2'] ) ) || ( empty( $this->setting_option_values['topics_v2'] ) ) ) {
				$this->setting_option_values['topics_v2'] = learndash_get_post_type_slug( 'topic' );
			}

			if ( ( ! isset( $this->setting_option_values['quizzes_v2'] ) ) || ( empty( $this->setting_option_values['quizzes_v2'] ) ) ) {
				$this->setting_option_values['quizzes_v2'] = learndash_get_post_type_slug( 'quiz' );
			}

			if ( ( ! isset( $this->setting_option_values['questions_v2'] ) ) || ( empty( $this->setting_option_values['questions_v2'] ) ) ) {
				$this->setting_option_values['questions_v2'] = learndash_get_post_type_slug( 'question' );
			}

			if ( ( ! isset( $this->setting_option_values['quizzes-form-entries_v2'] ) ) || ( empty( $this->setting_option_values['quizzes-form-entries_v2'] ) ) ) {
				$this->setting_option_values['quizzes-form-entries_v2'] = 'form-entries';
			}

			if ( ( ! isset( $this->setting_option_values['quizzes-statistics_v2'] ) ) || ( empty( $this->setting_option_values['quizzes-statistics_v2'] ) ) ) {
				$this->setting_option_values['quizzes-statistics_v2'] = 'statistics';
			}

			if ( ( ! isset( $this->setting_option_values['quizzes-statistics-questions_v2'] ) ) || ( empty( $this->setting_option_values['quizzes-statistics-questions_v2'] ) ) ) {
				$this->setting_option_values['quizzes-statistics-questions_v2'] = 'questions';
			}

			if ( ( ! isset( $this->setting_option_values['groups_v2'] ) ) || ( empty( $this->setting_option_values['groups_v2'] ) ) ) {
				$this->setting_option_values['groups_v2'] = learndash_get_post_type_slug( 'group' );
			}
			if ( ( ! isset( $this->setting_option_values['groups-leaders_v2'] ) ) || ( empty( $this->setting_option_values['groups-leaders_v2'] ) ) ) {
				$this->setting_option_values['groups-leaders_v2'] = 'leaders';
			}
			if ( ( ! isset( $this->setting_option_values['groups-courses_v2'] ) ) || ( empty( $this->setting_option_values['groups-courses_v2'] ) ) ) {
				$this->setting_option_values['groups-courses_v2'] = 'courses';
			}
			if ( ( ! isset( $this->setting_option_values['groups-users_v2'] ) ) || ( empty( $this->setting_option_values['groups-users_v2'] ) ) ) {
				$this->setting_option_values['groups-users_v2'] = 'users';
			}

			$this->setting_option_values['exams_v2'] = $this->setting_option_values['exams_v2'] ?? 'exams';

			if ( ( ! isset( $this->setting_option_values['assignments_v2'] ) ) || ( empty( $this->setting_option_values['assignments_v2'] ) ) ) {
				$this->setting_option_values['assignments_v2'] = learndash_get_post_type_slug( 'assignment' );
			}

			if ( ( ! isset( $this->setting_option_values['essays_v2'] ) ) || ( empty( $this->setting_option_values['essays_v2'] ) ) ) {
				$this->setting_option_values['essays_v2'] = learndash_get_post_type_slug( 'essay' );
			}

			if ( ( ! isset( $this->setting_option_values['users_v2'] ) ) || ( empty( $this->setting_option_values['users_v2'] ) ) ) {
				$this->setting_option_values['users_v2'] = 'users';
			}
			if ( ( ! isset( $this->setting_option_values['users-courses_v2'] ) ) || ( empty( $this->setting_option_values['users-courses_v2'] ) ) ) {
				$this->setting_option_values['users-courses_v2'] = 'courses';
			}
			if ( ( ! isset( $this->setting_option_values['users-groups_v2'] ) ) || ( empty( $this->setting_option_values['users-groups_v2'] ) ) ) {
				$this->setting_option_values['users-groups_v2'] = 'groups';
			}
			if ( ( ! isset( $this->setting_option_values['users-course-progress_v2'] ) ) || ( empty( $this->setting_option_values['users-course-progress_v2'] ) ) ) {
				$this->setting_option_values['users-course-progress_v2'] = 'course-progress';
			}
			if ( ( ! isset( $this->setting_option_values['users-quiz-progress_v2'] ) ) || ( empty( $this->setting_option_values['users-quiz-progress_v2'] ) ) ) {
				$this->setting_option_values['users-quiz-progress_v2'] = 'quiz-progress';
			}

			if ( ( ! isset( $this->setting_option_values['progress-status_v2'] ) ) || ( empty( $this->setting_option_values['progress-status_v2'] ) ) ) {
				$this->setting_option_values['progress-status_v2'] = 'progress-status';
			}
			if ( ( ! isset( $this->setting_option_values['price-types_v2'] ) ) || ( empty( $this->setting_option_values['price-types_v2'] ) ) ) {
				$this->setting_option_values['price-types_v2'] = 'price-types';
			}
			if ( ( ! isset( $this->setting_option_values['question-types_v2'] ) ) || ( empty( $this->setting_option_values['question-types_v2'] ) ) ) {
				$this->setting_option_values['question-types_v2'] = 'question-types';
			}

			$this->setting_option_values = apply_filters( 'learndash_rest_settings_values', $this->setting_option_values );
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 2.5.8
		 */
		public function load_settings_fields() {

			$this->setting_option_fields = array(
				'enabled' => array(
					'name'      => 'enabled',
					'type'      => 'hidden',
					'label'     => esc_html__( 'Enabled REST API Active Version', 'learndash' ),
					'help_text' => esc_html__( 'Customize the LearnDash REST API namespace and endpoints. Leave text fields blank to revert to default.', 'learndash' ),
					'value'     => 'yes',
					'options'   => array(
						'yes' => array(
							'label'       => '',
							'description' => '',
							'tooltip'     => esc_html__( 'REST API must be enabled', 'learndash' ),
						),
					),
					'attrs'     => array(
						'disabled' => 'disabled',
					),
				),
			);

			$site_rest_url    = get_rest_url();
			$site_rest_prefix = rest_get_url_prefix();
			$value_prefix_top = rest_get_url_prefix() . '/' . LEARNDASH_REST_API_NAMESPACE . '/v1/';

			$value_prefix_courses = $value_prefix_top . $this->setting_option_values['sfwd-courses'] . '/';
			$value_prefix_users   = $value_prefix_top . $this->setting_option_values['users'] . '/';
			$value_prefix_groups  = $value_prefix_top . $this->setting_option_values['groups'] . '/';

			$this->setting_option_fields_v1 = array(
				'sfwd-courses'  => array(
					'name'         => 'sfwd-courses',
					'type'         => 'text',
					'label'        => LearnDash_Custom_Label::get_label( 'course' ),
					'value'        => $this->setting_option_values['sfwd-courses'],
					'value_prefix' => $value_prefix_top,
					'class'        => '-medium',
				),
				'sfwd-lessons'  => array(
					'name'         => 'sfwd-lessons',
					'type'         => 'text',
					'label'        => LearnDash_Custom_Label::get_label( 'lesson' ),
					'value'        => $this->setting_option_values['sfwd-lessons'],
					'value_prefix' => $value_prefix_top,
					'class'        => '-medium',
				),
				'sfwd-topic'    => array(
					'name'         => 'sfwd-topic',
					'type'         => 'text',
					'label'        => LearnDash_Custom_Label::get_label( 'topic' ),
					'value'        => $this->setting_option_values['sfwd-topic'],
					'value_prefix' => $value_prefix_top,
					'class'        => '-medium',
				),
				'sfwd-quiz'     => array(
					'name'         => 'sfwd-quiz',
					'type'         => 'text',
					'label'        => LearnDash_Custom_Label::get_label( 'quiz' ),
					'value'        => $this->setting_option_values['sfwd-quiz'],
					'value_prefix' => $value_prefix_top,
					'class'        => '-medium',
				),
				'sfwd-question' => array(
					'name'         => 'sfwd-question',
					'type'         => 'text',
					'label'        => LearnDash_Custom_Label::get_label( 'question' ),
					'value'        => $this->setting_option_values['sfwd-question'],
					'value_prefix' => $value_prefix_top,
					'class'        => '-medium',
				),
				'users'         => array(
					'name'         => 'users',
					'type'         => 'text',
					'label'        => esc_html__( 'User', 'learndash' ),
					'value'        => $this->setting_option_values['users'],
					'value_prefix' => $value_prefix_top,
					'class'        => '-medium',
				),
				'groups'        => array(
					'name'         => 'groups',
					'type'         => 'text',
					'label'        => LearnDash_Custom_Label::get_label( 'group' ),
					'value'        => $this->setting_option_values['groups'],
					'value_prefix' => $value_prefix_top,
					'class'        => '-medium',
				),
			);

			$value_prefix_top = rest_get_url_prefix() . '/' . LEARNDASH_REST_API_NAMESPACE . '/v2/';

			$value_prefix_courses = $value_prefix_top . $this->setting_option_values['courses_v2'] . '/&lt;Course ID&gt;/';
			$value_prefix_users   = $value_prefix_top . $this->setting_option_values['users_v2'] . '/&lt;User ID&gt;/';
			$value_prefix_groups  = $value_prefix_top . $this->setting_option_values['groups_v2'] . '/&lt;Group ID&gt;/';
			$value_prefix_quizzes = $value_prefix_top . $this->setting_option_values['quizzes_v2'] . '/&lt;Quiz ID&gt;/';

			$value_prefix_statistics = $value_prefix_quizzes . $this->setting_option_values['quizzes-statistics_v2'] . '/&lt;Stat ID&gt;/';

			$this->setting_option_fields_v2 = array(
				'courses_v2'                      => array(
					'name'                => 'courses_v2',
					'type'                => 'text',
					'label'               => LearnDash_Custom_Label::get_label( 'course' ),
					'value'               => $this->setting_option_values['courses_v2'],
					'value_prefix'        => $value_prefix_top,
					'class'               => '-medium',
					'placeholder'         => learndash_get_post_type_slug( 'course' ),
					'child_section_state' => 'open',
				),
				'courses-users_v2'                => array(
					'name'           => 'courses-users_v2',
					'type'           => 'text',
					'label'          => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Users', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'          => $this->setting_option_values['courses-users_v2'],
					'value_prefix'   => $value_prefix_courses,
					'class'          => '-medium',
					'placeholder'    => 'users',
					'parent_setting' => 'courses_v2',
				),
				'courses-steps_v2'                => array(
					'name'           => 'courses-steps_v2',
					'type'           => 'text',
					'label'          => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Steps', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'          => $this->setting_option_values['courses-steps_v2'],
					'value_prefix'   => $value_prefix_courses,
					'class'          => '-medium',
					'placeholder'    => 'steps',
					'parent_setting' => 'courses_v2',
				),
				'courses-groups_v2'               => array(
					'name'           => 'courses-groups_v2',
					'type'           => 'text',
					'label'          => sprintf(
						// translators: placeholder: Course, Groups.
						esc_html_x( '%1$s %2$s', 'placeholder: Course, Groups', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' ),
						LearnDash_Custom_Label::get_label( 'groups' )
					),
					'value'          => $this->setting_option_values['courses-groups_v2'],
					'value_prefix'   => $value_prefix_courses,
					'class'          => '-medium',
					'placeholder'    => 'groups',
					'parent_setting' => 'courses_v2',
				),
				'courses-prerequisites_v2'        => array(
					'name'           => 'courses-prerequisites_v2',
					'type'           => 'text',
					'label'          => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Prerequisites', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'          => $this->setting_option_values['courses-prerequisites_v2'],
					'value_prefix'   => $value_prefix_courses,
					'class'          => '-medium',
					'parent_setting' => 'courses_v2',
					'placeholder'    => 'prerequisites',
				),
				'lessons_v2'                      => array(
					'name'         => 'lessons_v2',
					'type'         => 'text',
					'label'        => LearnDash_Custom_Label::get_label( 'lesson' ),
					'value'        => $this->setting_option_values['lessons_v2'],
					'value_prefix' => $value_prefix_top,
					'class'        => '-medium',
					'placeholder'  => learndash_get_post_type_slug( 'lesson' ),
				),
				'topics_v2'                       => array(
					'name'         => 'topics_v2',
					'type'         => 'text',
					'label'        => LearnDash_Custom_Label::get_label( 'topic' ),
					'value'        => $this->setting_option_values['topics_v2'],
					'value_prefix' => $value_prefix_top,
					'class'        => '-medium',
					'placeholder'  => learndash_get_post_type_slug( 'topic' ),
				),
				'quizzes_v2'                      => array(
					'name'                => 'quizzes_v2',
					'type'                => 'text',
					'label'               => LearnDash_Custom_Label::get_label( 'quiz' ),
					'value'               => $this->setting_option_values['quizzes_v2'],
					'value_prefix'        => $value_prefix_top,
					'class'               => '-medium',
					'placeholder'         => learndash_get_post_type_slug( 'quiz' ),
					'child_section_state' => 'open',
				),
				'quizzes-form-entries_v2'         => array(
					'name'           => 'quizzes-form-entries_v2',
					'type'           => 'text',
					'label'          => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '%s Form Entries', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'value'          => $this->setting_option_values['quizzes-form-entries_v2'],
					'value_prefix'   => $value_prefix_quizzes,
					'class'          => '-medium',
					'placeholder'    => 'statistics',
					'parent_setting' => 'quizzes_v2',
				),
				'quizzes-statistics_v2'           => array(
					'name'           => 'quizzes-statistics_v2',
					'type'           => 'text',
					'label'          => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '%s Statistics', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'value'          => $this->setting_option_values['quizzes-statistics_v2'],
					'value_prefix'   => $value_prefix_quizzes,
					'class'          => '-medium',
					'placeholder'    => 'statistics',
					'parent_setting' => 'quizzes_v2',
				),
				'quizzes-statistics-questions_v2' => array(
					'name'           => 'quizzes-statistics-questions_v2',
					'type'           => 'text',
					'label'          => sprintf(
						// translators: placeholder: Quiz, Questions.
						esc_html_x( '%1$s Statistics %2$s', 'placeholder: Quiz, Questions', 'learndash' ),
						learndash_get_custom_label( 'quiz' ),
						learndash_get_custom_label( 'questions' )
					),
					'value'          => $this->setting_option_values['quizzes-statistics-questions_v2'],
					'value_prefix'   => $value_prefix_statistics,
					'class'          => '-medium',
					'placeholder'    => 'questions',
					'parent_setting' => 'quizzes_v2',
				),
				'questions_v2'                    => array(
					'name'         => 'questions_v2',
					'type'         => 'text',
					'label'        => LearnDash_Custom_Label::get_label( 'question' ),
					'value'        => $this->setting_option_values['questions_v2'],
					'value_prefix' => $value_prefix_top,
					'class'        => '-medium',
					'placeholder'  => learndash_get_post_type_slug( 'question' ),
				),
				'assignments_v2'                  => array(
					'name'         => 'assignments_v2',
					'type'         => 'text',
					'label'        => esc_html__( 'Assignment', 'learndash' ),
					'value'        => $this->setting_option_values['assignments_v2'],
					'value_prefix' => $value_prefix_top,
					'class'        => '-medium',
					'placeholder'  => learndash_get_post_type_slug( 'assignment' ),
				),
				'essays_v2'                       => array(
					'name'         => 'essays_v2',
					'type'         => 'text',
					'label'        => esc_html__( 'Essay', 'learndash' ),
					'value'        => $this->setting_option_values['essays_v2'],
					'value_prefix' => $value_prefix_top,
					'class'        => '-medium',
					'placeholder'  => learndash_get_post_type_slug( 'essay' ),
				),
				'groups_v2'                       => array(
					'name'                => 'groups_v2',
					'type'                => 'text',
					'label'               => LearnDash_Custom_Label::get_label( 'group' ),
					'value'               => $this->setting_option_values['groups_v2'],
					'value_prefix'        => $value_prefix_top,
					'class'               => '-medium',
					'placeholder'         => learndash_get_post_type_slug( 'group' ),
					'child_section_state' => 'open',
				),
				'groups-courses_v2'               => array(
					'name'           => 'groups-courses_v2',
					'type'           => 'text',
					'label'          => sprintf(
						// translators: placeholder: Group, Courses.
						esc_html_x( '%1$s %2$s', 'placeholder: Group, Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'group' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'value'          => $this->setting_option_values['groups-courses_v2'],
					'value_prefix'   => $value_prefix_groups,
					'class'          => '-medium',
					'parent_setting' => 'groups_v2',
				),
				'groups-leaders_v2'               => array(
					'name'           => 'groups-leaders_v2',
					'type'           => 'text',
					'label'          => sprintf(
						// translators: placeholder: Group.
						esc_html_x( '%s Leaders', 'placeholder: Group', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'group' )
					),
					'value'          => $this->setting_option_values['groups-leaders_v2'],
					'value_prefix'   => $value_prefix_groups,
					'class'          => '-medium',
					'parent_setting' => 'groups_v2',
				),
				'groups-users_v2'                 => array(
					'name'           => 'groups-users_v2',
					'type'           => 'text',
					'label'          => sprintf(
						// translators: placeholder: Group.
						esc_html_x( '%s Users', 'placeholder: Group', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'group' )
					),
					'value'          => $this->setting_option_values['groups-users_v2'],
					'value_prefix'   => $value_prefix_groups,
					'class'          => '-medium',
					'parent_setting' => 'groups_v2',
				),
				'exams_v2'                        => array(
					'name'         => 'exams_v2',
					'type'         => 'text',
					'label'        => LearnDash_Custom_Label::get_label( 'exam' ),
					'value'        => $this->setting_option_values['exams_v2'],
					'value_prefix' => $value_prefix_top,
					'class'        => '-medium',
					'placeholder'  => 'exams',
				),
				'users_v2'                        => array(
					'name'                => 'users_v2',
					'type'                => 'text',
					'label'               => esc_html__( 'User', 'learndash' ),
					'value'               => $this->setting_option_values['users_v2'],
					'value_prefix'        => $value_prefix_top,
					'class'               => '-medium',
					'placeholder'         => 'users',
					'child_section_state' => 'open',
				),
				'users-courses_v2'                => array(
					'name'           => 'users-courses_v2',
					'type'           => 'text',
					'label'          => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'User %s', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'value'          => $this->setting_option_values['users-courses_v2'],
					'value_prefix'   => $value_prefix_users,
					'class'          => '-medium',
					'placeholder'    => 'courses',
					'parent_setting' => 'users_v2',
				),
				'users-groups_v2'                 => array(
					'name'           => 'users-groups_v2',
					'type'           => 'text',
					'label'          => sprintf(
						// translators: placeholder: Groups.
						esc_html_x( 'User %s', 'placeholder: Groups', 'learndash' ),
						learndash_get_custom_label( 'groups' )
					),
					'value'          => $this->setting_option_values['users-groups_v2'],
					'value_prefix'   => $value_prefix_users,
					'class'          => '-medium',
					'placeholder'    => 'groups',
					'parent_setting' => 'users_v2',
				),
				'users-course-progress_v2'        => array(
					'name'           => 'users-course-progress_v2',
					'type'           => 'text',
					'label'          => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'User %s Progress', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'          => $this->setting_option_values['users-course-progress_v2'],
					'value_prefix'   => $value_prefix_users,
					'class'          => '-medium',
					'placeholder'    => 'course-progress',
					'parent_setting' => 'users_v2',
				),
				'users-quiz-progress_v2'          => array(
					'name'           => 'users-quiz-progress_v2',
					'type'           => 'text',
					'label'          => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( 'User %s Attempts', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'value'          => $this->setting_option_values['users-quiz-progress_v2'],
					'value_prefix'   => $value_prefix_users,
					'class'          => '-medium',
					'placeholder'    => 'quiz-progress',
					'parent_setting' => 'users_v2',
				),

				'progress-status_v2'              => array(
					'name'         => 'progress-status_v2',
					'type'         => 'text',
					'label'        => esc_html__( 'Progress Status', 'learndash' ),
					'value'        => $this->setting_option_values['progress-status_v2'],
					'value_prefix' => $value_prefix_top,
					'class'        => '-medium',
					'placeholder'  => 'progress-status',
				),
				'price-types_v2'                  => array(
					'name'         => 'price-types_v2',
					'type'         => 'text',
					'label'        => esc_html__( 'Price Types', 'learndash' ),
					'value'        => $this->setting_option_values['price-types_v2'],
					'value_prefix' => $value_prefix_top,
					'class'        => '-medium',
					'placeholder'  => 'price-types',
				),
				'question-types_v2'               => array(
					'name'         => 'question-types_v2',
					'type'         => 'text',
					'label'        => sprintf(
						// translators: placeholder: Question.
						esc_html_x( '%s Types', 'placeholder: Question', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'question' )
					),
					'value'        => $this->setting_option_values['question-types_v2'],
					'value_prefix' => $value_prefix_top,
					'class'        => '-medium',
					'placeholder'  => 'question-types',
				),
			);

			$this->setting_option_fields = array_merge( $this->setting_option_fields, $this->setting_option_fields_v1, $this->setting_option_fields_v2 );
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
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
			if ( ( isset( $field_args['setting_option_key'] ) ) && ( $this->setting_option_key === $field_args['setting_option_key'] ) ) {
				if ( ( isset( $field_args['name'] ) ) && ( 'groups' === $field_args['name'] ) ) {
					$html .= '<div class="ld-divider"></div>';
				}
			}
			return $html;
		}


		/**
		 * Settings row outside before
		 *
		 * @since 3.3.0
		 *
		 * @param string $content    Content to show before row.
		 * @param array  $field_args Row field Args.
		 */
		public function learndash_settings_row_outside_before( $content = '', $field_args = array() ) {
			if ( ( isset( $field_args['name'] ) ) && ( in_array( $field_args['name'], array( 'sfwd-courses', 'courses_v2' ), true ) ) ) {
				if ( 'sfwd-courses' === $field_args['name'] ) {
					$content .= '<div class="ld-settings-email-header-wrapper">';

					$content .= '<div class="ld-settings-email-header">';
					$content .= esc_html__( 'V1 Endpoints', 'learndash' );
					$content .= '</div>';

					$content .= '</div>';
				} elseif ( 'courses_v2' === $field_args['name'] ) {
					$content .= '<div class="ld-settings-email-header-wrapper">';

					$content .= '<div class="ld-settings-email-header">';
					$content .= esc_html__( 'V2 Endpoints (Beta)', 'learndash' );
					$content .= '</div>';

					$content .= '</div>';
				}
			}

			return $content;
		}

		// End of function.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_General_REST_API::add_section_instance();
	}
);
