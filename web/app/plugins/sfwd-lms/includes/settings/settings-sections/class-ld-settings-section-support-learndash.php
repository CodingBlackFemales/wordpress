<?php
/**
 * LearnDash Settings Section for Support LearnDash Metabox.
 *
 * @since 3.1.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Support_LearnDash' ) ) ) {
	/**
	 * Class LearnDash Settings Section for Support LearnDash Metabox.
	 *
	 * @since 3.1.0
	 */
	class LearnDash_Settings_Section_Support_LearnDash extends LearnDash_Settings_Section {

		/**
		 * Settings set array for this section.
		 *
		 * @var array $settings_set Array of settings used by this section.
		 */
		protected $settings_set = array();

		/**
		 * Translations MO files array.
		 *
		 * @var array $mo_files Array of translation MO files.
		 */
		private $mo_files = array();

		/**
		 * Protected constructor for class
		 *
		 * @since 3.1.0
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_support';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'ld_settings';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_support_ld_settings';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'LearnDash Settings', 'learndash' );

			$this->load_options = false;

			add_filter( 'learndash_support_sections_init', array( $this, 'learndash_support_sections_init' ) );
			add_action( 'learndash_section_fields_before', array( $this, 'show_support_section' ), 30, 2 );

			parent::__construct();
		}

		/**
		 * Support Sections Init
		 *
		 * @since 3.1.0
		 *
		 * @param array $support_sections Support sections array.
		 */
		public function learndash_support_sections_init( $support_sections = array() ) {
			global $wpdb, $wp_version, $wp_rewrite;
			global $sfwd_lms;

			$abspath_tmp = str_replace( '\\', '/', ABSPATH );

			/************************************************************************************************
			 * LearnDash Settings
			 */
			if ( ! isset( $support_sections[ $this->setting_option_key ] ) ) {

				$this->settings_set = array();

				$this->settings_set['header'] = array(
					'html' => $this->settings_section_label,
					'text' => $this->settings_section_label,
				);

				$this->settings_set['columns'] = array(
					'label' => array(
						'html'  => esc_html__( 'Setting', 'learndash' ),
						'text'  => 'Setting',
						'class' => 'learndash-support-settings-left',
					),
					'value' => array(
						'html'  => esc_html__( 'Value', 'learndash' ),
						'text'  => 'Value',
						'class' => 'learndash-support-settings-right',
					),
				);

				$this->settings_set['settings'] = array();

				$learndash_version_value      = '';
				$learndash_version_value_html = '';

				$ld_version_history = learndash_data_upgrades_setting( 'version_history' );
				if ( ! empty( $ld_version_history ) ) {
					krsort( $ld_version_history );
					$ld_version_history = array_slice( $ld_version_history, 0, 5, true );
					$_first_item        = true;
					foreach ( $ld_version_history as $timestamp => $version ) {
						$version_date = ' - ';
						if ( ! empty( $timestamp ) ) {
							$version_date = learndash_adjust_date_time_display( $timestamp );
						}

						if ( true === $_first_item ) {
							$_first_item = false;

							$ld_license_info = get_option( 'nss_plugin_info_sfwd_lms' );
							if ( ( $ld_license_info ) && ( property_exists( $ld_license_info, 'new_version' ) ) && ( ! empty( $ld_license_info->new_version ) ) ) {
								if ( version_compare( $version, $ld_license_info->new_version, 'lt' ) ) {
									$learndash_version_value_html = '<span style="color: red">' . $version . '</span>: ' . $version_date . ' - ' .
									sprintf(
										// translators: placeholder: version number.
										esc_html_x( 'Installed version does not match latest (%s).', 'placeholder: version number', 'learndash' ),
										$ld_license_info->new_version
									) . ' <a href="' . admin_url( 'plugins.php?plugin_status=upgrade' ) . '">' . esc_html__( 'Please upgrade.', 'learndash' ) . '</a><br />';
									$learndash_version_value = $version . ': ' . $version_date . ' - (X)' . "\r\n";

								} else {
									$learndash_version_value_html .= '<span style="color: green">' . $version . '</span>: ' . $version_date . '<br />';
									$learndash_version_value      .= $version . ': ' . $version_date . "\r\n";
								}
							} else {
								$learndash_version_value      .= $version . ': ' . $version_date . "\r\n";
								$learndash_version_value_html .= $version . ': ' . $version_date . '<br />';
							}
						} else {
							$learndash_version_value      .= $version . ': ' . $version_date . "\r\n";
							$learndash_version_value_html .= $version . ': ' . $version_date . '<br />';
						}
					}
				}

				$this->settings_set['settings']['LEARNDASH_VERSION'] = array(
					'label'      => 'Learndash Version',
					'label_html' => esc_html__( 'Learndash Version', 'learndash' ),
					'value'      => $learndash_version_value,
					'value_html' => $learndash_version_value_html,
				);

				$ld_license_valid = learndash_is_learndash_license_valid();
				$ld_license_check = learndash_get_last_license_check_time();

				if ( $ld_license_valid ) {
					$license_value_html = '<span style="color: green">' . esc_html__( 'Yes', 'learndash' ) . '</span>';
					$license_value      = 'Yes';
					if ( ! empty( $ld_license_check ) ) {
						$license_value_html .= ' (' . sprintf(
							// translators: placeholder: date.
							esc_html_x( 'last check: %s', 'placeholder: date', 'learndash' ),
							learndash_adjust_date_time_display( $ld_license_check )
						) . ')';
						$license_value .= ' (last check: ' . learndash_adjust_date_time_display( $ld_license_check ) . ')';
					}
				} else {
					$license_value_html = '<span style="color: red">' . esc_html__( 'No', 'learndash' ) . '</span>';
					$license_value      = 'No (X)';
				}
				$this->settings_set['settings']['LEARNDASH_license'] = array(
					'label'      => 'LearnDash License Valid',
					'label_html' => esc_html__( 'LearnDash License Valid', 'learndash' ),
					'value'      => $license_value,
					'value_html' => $license_value_html,
				);

				$this->settings_set['settings']['LEARNDASH_SETTINGS_DB_VERSION'] = array(
					'label'      => 'DB Version',
					'label_html' => esc_html__( 'DB Version', 'learndash' ),
					'value'      => LEARNDASH_SETTINGS_DB_VERSION,
				);

				$data_settings_courses = learndash_data_upgrades_setting( 'user-meta-courses' );
				if ( ( ! empty( $data_settings_courses ) ) && ( ! empty( $data_settings_courses ) ) ) {
					if ( version_compare( $data_settings_courses['version'], LEARNDASH_SETTINGS_DB_VERSION, '<' ) ) {
						$color      = 'red';
						$color_text = ' (X)';
					} else {
						$color      = 'green';
						$color_text = '';
					}
					$data_upgrade_courses_value      = $data_settings_courses['version'] . $color_text;
					$data_upgrade_courses_value_html = '<span style="color: ' . $color . '">' . $data_settings_courses['version'] . '</span>';

					if ( 'red' == $color ) {
						$data_upgrade_courses_value_html .= ' <a href="' . admin_url( 'admin.php?page=learndash_data_upgrades' ) . '">' . esc_html__( 'Please run the Data Upgrade.', 'learndash' ) . '</a>';
					} elseif ( ( isset( $data_settings_courses['last_run'] ) ) && ( ! empty( $data_settings_courses['last_run'] ) ) ) {
						$data_upgrade_courses_value      .= ' (' . learndash_adjust_date_time_display( $data_settings_courses['last_run'] ) . ')';
						$data_upgrade_courses_value_html .= ' (' . sprintf(
							// translators: placeholder: datetime.
							esc_html_x( 'last run %s', 'placeholder: datetime', 'learndash' ),
							learndash_adjust_date_time_display( $data_settings_courses['last_run'] )
						) . ')';
					}
				} else {
					$data_upgrade_courses_value      = '';
					$data_upgrade_courses_value_html = '';
				}

				$this->settings_set['settings']['Data Upgrade Courses'] = array(
					'label'      => 'Data Upgrade Courses',
					'label_html' => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'Data Upgrade %s', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'value'      => $data_upgrade_courses_value,
					'value_html' => $data_upgrade_courses_value_html,
				);

				$data_settings_quizzes = learndash_data_upgrades_setting( 'user-meta-quizzes' );
				if ( ( ! empty( $data_settings_quizzes ) ) && ( ! empty( $data_settings_quizzes ) ) ) {
					if ( version_compare( $data_settings_quizzes['version'], LEARNDASH_SETTINGS_DB_VERSION, '<' ) ) {
						$color      = 'red';
						$color_text = ' (X)';
					} else {
						$color      = 'green';
						$color_text = '';
					}
					$data_upgrade_quizzes_value      = $data_settings_quizzes['version'] . $color_text;
					$data_upgrade_quizzes_value_html = '<span style="color: ' . $color . '">' . $data_settings_quizzes['version'] . '</span>';
					if ( 'red' == $color ) {
						$data_upgrade_quizzes_value_html .= ' <a href="' . admin_url( 'admin.php?page=learndash_data_upgrades' ) . '">' . esc_html__( 'Please run the Data Upgrade.', 'learndash' );
					} elseif ( ( isset( $data_settings_quizzes['last_run'] ) ) && ( ! empty( $data_settings_quizzes['last_run'] ) ) ) {
						$data_upgrade_quizzes_value      .= ' (' . learndash_adjust_date_time_display( $data_settings_quizzes['last_run'] ) . ')';
						$data_upgrade_quizzes_value_html .= ' (' . sprintf(
							// translators: placeholder: datetime.
							esc_html_x( 'last run %s', 'placeholder: datetime', 'learndash' ),
							learndash_adjust_date_time_display( $data_settings_quizzes['last_run'] )
						) . ')';
					}
				} else {
					$data_upgrade_quizzes_value      = '';
					$data_upgrade_quizzes_value_html = '';
				}

				$this->settings_set['settings']['Data Upgrade Quizzes'] = array(
					'label'      => 'Data Upgrade Quizzes',
					'label_html' => sprintf(
						// translators: placeholder: Quizzes.
						esc_html_x( 'Data Upgrade %s', 'placeholder: Quizzes', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quizzes' )
					),
					'value'      => $data_upgrade_quizzes_value,
					'value_html' => $data_upgrade_quizzes_value_html,
				);

				$data_pro_quiz_questions = learndash_data_upgrades_setting( 'pro-quiz-questions' );
				if ( ( ! empty( $data_pro_quiz_questions ) ) && ( ! empty( $data_pro_quiz_questions ) ) ) {
					if ( version_compare( $data_pro_quiz_questions['version'], LEARNDASH_SETTINGS_DB_VERSION, '<' ) ) {
						$color      = 'red';
						$color_text = ' (X)';
					} else {
						$color      = 'green';
						$color_text = '';
					}
					$data_pro_quiz_questions_value = $data_pro_quiz_questions['version'] . $color_text;
					$data_pro_quiz_questions_html  = '<span style="color: ' . $color . '">' . $data_pro_quiz_questions['version'] . '</span>';
					if ( 'red' == $color ) {
						$data_pro_quiz_questions_html .= ' <a href="' . admin_url( 'admin.php?page=learndash_data_upgrades' ) . '">' . esc_html__( 'Please run the Data Upgrade.', 'learndash' );
					} elseif ( ( isset( $data_pro_quiz_questions['last_run'] ) ) && ( ! empty( $data_pro_quiz_questions['last_run'] ) ) ) {
						$data_pro_quiz_questions_value .= ' (' . learndash_adjust_date_time_display( $data_pro_quiz_questions['last_run'] ) . ')';
						$data_pro_quiz_questions_html  .= ' (' . sprintf(
							// translators: placeholder: datetime.
							esc_html_x( 'last run %s', 'placeholder: datetime', 'learndash' ),
							learndash_adjust_date_time_display( $data_pro_quiz_questions['last_run'] )
						) . ')';
					}
				} else {
					$data_pro_quiz_questions_value = '';
					$data_pro_quiz_questions_html  = '';
				}

				$this->settings_set['settings']['Data ProQuiz Questions'] = array(
					'label'      => 'Data ProQuiz Questions',
					'label_html' => sprintf(
						// translators: placeholder: Questions.
						esc_html_x( 'Data Upgrade ProQuiz %s', 'placeholder: Questions', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'questions' )
					),
					'value'      => $data_pro_quiz_questions_value,
					'value_html' => $data_pro_quiz_questions_html,
				);

				$data_course_access_lists = learndash_data_upgrades_setting( 'course-access-lists-convert' );
				if ( ( ! empty( $data_course_access_lists ) ) && ( ! empty( $data_course_access_lists ) ) ) {
					if ( version_compare( $data_course_access_lists['version'], LEARNDASH_SETTINGS_DB_VERSION, '<' ) ) {
						$color      = 'red';
						$color_text = ' (X)';
					} else {
						$color      = 'green';
						$color_text = '';
					}
					$data_course_access_lists_value = $data_course_access_lists['version'] . $color_text;
					$data_course_access_lists_html  = '<span style="color: ' . $color . '">' . $data_course_access_lists['version'] . '</span>';
					if ( 'red' == $color ) {
						$data_course_access_lists_html .= ' <a href="' . admin_url( 'admin.php?page=learndash_data_upgrades' ) . '">' . esc_html__( 'Please run the Data Upgrade.', 'learndash' );
					} elseif ( ( isset( $data_course_access_lists['last_run'] ) ) && ( ! empty( $data_course_access_lists['last_run'] ) ) ) {
						$data_course_access_lists_value .= ' (' . learndash_adjust_date_time_display( $data_course_access_lists['last_run'] ) . ')';
						$data_course_access_lists_html  .= ' (' . sprintf(
							// translators: placeholder: datetime.
							esc_html_x( 'last run %s', 'placeholder: datetime', 'learndash' ),
							learndash_adjust_date_time_display( $data_course_access_lists['last_run'] )
						) . ')';
					}
				} else {
					$data_course_access_lists_value = '';
					$data_course_access_lists_html  = '';
				}

				$this->settings_set['settings']['Data Course Access Lists Convert'] = array(
					'label'      => 'Data Course Access Lists Convert',
					'label_html' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Data Upgrade %s Access Lists Convert', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'Course' )
					),
					'value'      => $data_course_access_lists_value,
					'value_html' => $data_course_access_lists_html,
				);

				$this->settings_set['settings']['courses_count'] = array(
					'label'      => 'Courses Count',
					'label_html' => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( '%s Count', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'Courses' )
					),
					'value'      => $this->get_post_type_counts( learndash_get_post_type_slug( 'course' ) ),
				);

				$this->settings_set['settings']['lessons_count'] = array(
					'label'      => 'Lessons Count',
					'label_html' => sprintf(
						// translators: placeholder: Lessons.
						esc_html_x( '%s Count', 'placeholder: Lessons', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lessons' )
					),
					'value'      => $this->get_post_type_counts( learndash_get_post_type_slug( 'lesson' ) ),
				);

				$this->settings_set['settings']['topics_count'] = array(
					'label'      => 'Topics Count',
					'label_html' => sprintf(
						// translators: placeholder: Topics.
						esc_html_x( '%s Count', 'placeholder: Topics', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'topics' )
					),
					'value'      => $this->get_post_type_counts( learndash_get_post_type_slug( 'topic' ) ),
				);

				$this->settings_set['settings']['quizzes_count'] = array(
					'label'      => 'Quizzes Count',
					'label_html' => sprintf(
						// translators: placeholder: Quizzes.
						esc_html_x( '%s Count', 'placeholder: Quizzes', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quizzes' )
					),
					'value'      => $this->get_post_type_counts( learndash_get_post_type_slug( 'quiz' ) ),
				);

				$this->settings_set['settings']['groups_count'] = array(
					'label'      => 'Groups Count',
					'label_html' => sprintf(
						// translators: placeholder: Groups.
						esc_html_x( '%s Count', 'placeholder: Groups', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'groups' )
					),
					'value'      => $this->get_post_type_counts( learndash_get_post_type_slug( 'group' ) ),
				);

				$this->settings_set['settings']['assignments_count'] = array(
					'label'      => 'Assignments Count',
					'label_html' => esc_html__( 'Assignments Count', 'learndash' ),
					'value'      => $this->get_post_type_counts( learndash_get_post_type_slug( 'assignment' ) ),
				);

				$this->settings_set['settings']['essays_count'] = array(
					'label'      => 'Essays Count',
					'label_html' => esc_html__( 'Essays Count', 'learndash' ),
					'value'      => $this->get_post_type_counts( learndash_get_post_type_slug( 'essay' ) ),
				);

				$this->settings_set['settings']['active_theme'] = array(
					'label'      => 'Active LD Theme',
					'label_html' => esc_html__( 'Active LD Theme', 'learndash' ),
					'value'      => LearnDash_Theme_Register::get_active_theme_name(),
				);

				$this->settings_set['settings']['settings-sub-section-ld_settings_admin_user_settings'] = array(
					'html' => esc_html__( 'Admin User Settings', 'learndash' ),
					'text' => 'Admin User Settings',
				);
				$this->settings_set['settings']['courses_autoenroll_admin_users']                       = array(
					'label'      => 'Courses Auto-enroll',
					'label_html' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Auto-enroll', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' ) === 'yes' ) ? 'Yes' : 'No',
					'value_html' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);
				$this->settings_set['settings']['bypass_course_limits_admin_users']                     = array(
					'label'      => 'Bypass Course limits',
					'label_html' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Bypass %s limits', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'bypass_course_limits_admin_users' ) === 'yes' ) ? 'Yes' : 'No',
					'value_html' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'bypass_course_limits_admin_users' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				$this->settings_set['settings']['reports_include_admin_users'] = array(
					'label'      => 'Include in Reports',
					'label_html' => esc_html__( 'Include in Reports', 'learndash' ),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'reports_include_admin_users' ) === 'yes' ) ? 'Yes' : 'No',
					'value_html' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'reports_include_admin_users' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				$this->settings_set['settings']['settings-sub-section-ld_settings_group-leader_user_settings'] = array(
					'html' => sprintf(
						// translators: placeholder: Group Leader.
						esc_html_x( '%s User Settings', 'placeholder: Group Leader', 'learndash' ),
						learndash_get_custom_label( 'group_leader' )
					),
					'text' => sprintf(
						// translators: placeholder: Group Leader.
						esc_html_x( '%s User Settings', 'placeholder: Group Leader', 'learndash' ),
						learndash_get_custom_label( 'group_leader' )
					),
				);
				$this->settings_set['settings']['courses_autoenroll_group_leader_users']                       = array(
					'label'      => 'Courses Auto-enroll',
					'label_html' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Auto-enroll', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'courses_autoenroll' ) === 'yes' ) ? 'Yes' : 'No',
					'value_html' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'courses_autoenroll' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);
				$this->settings_set['settings']['bypass_course_limits_group_leader_users']                     = array(
					'label'      => 'Bypass Course limits',
					'label_html' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Bypass %s limits', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'bypass_course_limits' ) === 'yes' ) ? 'Yes' : 'No',
					'value_html' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'bypass_course_limits' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				$this->settings_set['settings']['manage_groups_capabilities_group_leader_users'] = array(
					'label'      => 'Manage Groups',
					'label_html' => sprintf(
						// translators: placeholder: Groups.
						esc_html_x( 'Manage %s', 'placeholder: Groups', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'groups' )
					),
				);
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_groups_enabled' ) === 'yes' ) {
					$this->settings_set['settings']['manage_groups_capabilities_group_leader_users']['value'] = ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_groups_capabilities' ) === 'advanced' ) ? 'Advanced' : 'Basic';

					$this->settings_set['settings']['manage_groups_capabilities_group_leader_users']['value_html'] = ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_groups_capabilities' ) === 'advanced' ) ? esc_html__( 'Advanced', 'learndash' ) : esc_html__( 'Basic', 'learndash' );
				} else {
					$this->settings_set['settings']['manage_groups_capabilities_group_leader_users']['value']      = 'No';
					$this->settings_set['settings']['manage_groups_capabilities_group_leader_users']['value_html'] = esc_html__( 'No', 'learndash' );
				}

				$this->settings_set['settings']['manage_courses_capabilities_group_leader_users'] = array(
					'label'      => 'Manage Courses',
					'label_html' => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'Manage %s', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
				);
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_courses_enabled' ) === 'yes' ) {
					$this->settings_set['settings']['manage_courses_capabilities_group_leader_users']['value'] = ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_courses_capabilities' ) === 'advanced' ) ? 'Advanced' : 'Basic';

					$this->settings_set['settings']['manage_courses_capabilities_group_leader_users']['value_html'] = ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_courses_capabilities' ) === 'advanced' ) ? esc_html__( 'Advanced', 'learndash' ) : esc_html__( 'Basic', 'learndash' );
				} else {
					$this->settings_set['settings']['manage_courses_capabilities_group_leader_users']['value']      = 'No';
					$this->settings_set['settings']['manage_courses_capabilities_group_leader_users']['value_html'] = esc_html__( 'No', 'learndash' );
				}

				$this->settings_set['settings']['manage_users_capabilities_group_leader_users'] = array(
					'label'      => 'Manage Users',
					'label_html' => esc_html__( 'Manage Users', 'learndash' ),
				);
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_users_enabled' ) === 'yes' ) {
					$this->settings_set['settings']['manage_users_capabilities_group_leader_users']['value'] = ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_users_capabilities' ) === 'advanced' ) ? 'Advanced' : 'Basic';

					$this->settings_set['settings']['manage_users_capabilities_group_leader_users']['value_html'] = ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_users_capabilities' ) === 'advanced' ) ? esc_html__( 'Advanced', 'learndash' ) : esc_html__( 'Basic', 'learndash' );
				} else {
					$this->settings_set['settings']['manage_users_capabilities_group_leader_users']['value']      = 'No';
					$this->settings_set['settings']['manage_users_capabilities_group_leader_users']['value_html'] = esc_html__( 'No', 'learndash' );
				}

				$this->settings_set['settings']['settings-sub-section-ld_settings_builders'] = array(
					'html' => esc_html__( 'Builders', 'learndash' ),
					'text' => 'Builders',
				);

				$this->settings_set['settings']['course_builder'] = array(
					'label'      => 'Course Builder Interface',
					'label_html' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Builder Interface', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'enabled' ) === 'yes' ) ? 'Yes' : 'No',
					'value_html' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'enabled' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				$this->settings_set['settings']['course_shared_steps'] = array(
					'label'      => 'Shared Course Steps',
					'label_html' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Shared %s Steps', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) === 'yes' ) ? 'Yes' : 'No',
					'value_html' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				$this->settings_set['settings']['quiz_builder'] = array(
					'label'      => 'Quiz Builder Interface',
					'label_html' => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '%s Builder Interface', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) === 'yes' ) ? 'Yes' : 'No',
					'value_html' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				$this->settings_set['settings']['quiz_shared_questions'] = array(
					'label'      => 'Quiz Shared Questions',
					'label_html' => sprintf(
						// translators: placeholder: Quiz, Questions.
						esc_html_x( '%1$s Shared %2$s', 'placeholder: Quiz, Questions', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' ),
						LearnDash_Custom_Label::get_label( 'questions' )
					),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'shared_questions' ) === 'yes' ) ? 'Yes' : 'No',
					'value_html' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'shared_questions' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				$this->settings_set['settings']['settings-sub-section-ld_settings_permalinks'] = array(
					'html' => esc_html__( 'Permalinks', 'learndash' ),
					'text' => 'Permalinks',
				);

				$this->settings_set['settings']['nested_urls'] = array(
					'label'      => 'Nested URLs',
					'label_html' => esc_html__( 'Nested URLs', 'learndash' ),
					'value'      => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'nested_urls' ) === 'yes' ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' ),
				);

				$this->settings_set['settings']['courses_permalink_slug'] = array(
					'label'      => 'Courses Permalink slug',
					'label_html' => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( '%s Permalink slug', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'value'      => '/' . LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'courses' ),
				);
				$this->settings_set['settings']['lessons_permalink_slug'] = array(
					'label'      => 'Lessons Permalink slug',
					'label_html' => sprintf(
						// translators: placeholder: Lessons.
						esc_html_x( '%s Permalink slug', 'placeholder: Lessons', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lessons' )
					),
					'value'      => '/' . LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'lessons' ),
				);
				$this->settings_set['settings']['topics_permalink_slug']  = array(
					'label'      => 'Topics Permalink slug',
					'label_html' => sprintf(
						// translators: placeholder: Topics.
						esc_html_x( '%s Permalink slug', 'placeholder: Topics', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'topics' )
					),
					'value'      => '/' . LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'topics' ),
				);
				$this->settings_set['settings']['quizzes_permalink_slug'] = array(
					'label'      => 'Quizzes Permalink slug',
					'label_html' => sprintf(
						// translators: placeholder: Quizzes.
						esc_html_x( '%s Permalink slug', 'placeholder: Quizzes', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quizzes' )
					),
					'value'      => '/' . LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'quizzes' ),
				);
				$this->settings_set['settings']['groups_permalink_slug']  = array(
					'label'      => 'Groups Permalink slug',
					'label_html' => sprintf(
						// translators: placeholder: Groups.
						esc_html_x( '%s Permalink slug', 'placeholder: Groups', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'groups' )
					),
					'value'      => '/' . LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'groups' ),
				);

				$learndash_settings_permalinks_taxonomies = get_option( 'learndash_settings_permalinks_taxonomies' );
				if ( ! is_array( $learndash_settings_permalinks_taxonomies ) ) {
					$learndash_settings_permalinks_taxonomies = array();
				}
				$learndash_settings_permalinks_taxonomies = wp_parse_args(
					$learndash_settings_permalinks_taxonomies,
					array(
						'ld_course_category' => 'course-category',
						'ld_course_tag'      => 'course-tag',
						'ld_lesson_category' => 'lesson-category',
						'ld_lesson_tag'      => 'lesson-tag',
						'ld_topic_category'  => 'topic-category',
						'ld_topic_tag'       => 'topic-tag',
						'ld_quiz_category'   => 'quiz-category',
						'ld_quiz_tag'        => 'quiz-tag',
						'ld_group_category'  => 'group-category',
						'ld_group_tag'       => 'group-tag',
					)
				);

				$courses_taxonomies = $sfwd_lms->get_post_args_section( learndash_get_post_type_slug( 'course' ), 'taxonomies' );
				if ( ( isset( $courses_taxonomies['ld_course_category'] ) ) && ( true == $courses_taxonomies['ld_course_category']['public'] ) ) {
					$this->settings_set['settings']['ld_course_category'] = array(
						'label'      => 'Courses Category base',
						'label_html' => sprintf(
							// translators: placeholder: Courses.
							esc_html_x( '%s Category base', 'placeholder: Courses', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'courses' )
						),
						'value'      => '/' . $learndash_settings_permalinks_taxonomies['ld_course_category'],
					);
				}

				if ( ( isset( $courses_taxonomies['ld_course_tag'] ) ) && ( true == $courses_taxonomies['ld_course_tag']['public'] ) ) {
					$this->settings_set['settings']['ld_course_tag'] = array(
						'label'      => 'Courses Tag',
						'label_html' => sprintf(
							// translators: placeholder: Courses.
							esc_html_x( '%s Tag base', 'placeholder: Courses', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'courses' )
						),
						'value'      => '/' . $learndash_settings_permalinks_taxonomies['ld_course_tag'],
					);
				}

				$lessons_taxonomies = $sfwd_lms->get_post_args_section( learndash_get_post_type_slug( 'lesson' ), 'taxonomies' );
				if ( ( isset( $lessons_taxonomies['ld_lesson_category'] ) ) && ( true == $lessons_taxonomies['ld_lesson_category']['public'] ) ) {
					$this->settings_set['settings']['ld_lesson_category'] = array(
						'label'      => 'Lesson Category base',
						'label_html' => sprintf(
							// translators: placeholder: Lessons.
							esc_html_x( '%s Category base', 'placeholder: Lessons', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'lessons' )
						),
						'value'      => '/' . $learndash_settings_permalinks_taxonomies['ld_lesson_category'],
					);
				}

				if ( ( isset( $lessons_taxonomies['ld_lesson_tag'] ) ) && ( true == $lessons_taxonomies['ld_lesson_tag']['public'] ) ) {
					$this->settings_set['settings']['ld_lesson_tag'] = array(
						'label'      => 'Lessons Tag',
						'label_html' => sprintf(
							// translators: placeholder: Lesson.
							esc_html_x( '%s Tag base', 'placeholder: Lessons', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'lessons' )
						),
						'value'      => '/' . $learndash_settings_permalinks_taxonomies['ld_lesson_tag'],
					);
				}

				$topics_taxonomies = $sfwd_lms->get_post_args_section( learndash_get_post_type_slug( 'topic' ), 'taxonomies' );
				if ( ( isset( $topics_taxonomies['ld_topic_category'] ) ) && ( true == $topics_taxonomies['ld_topic_category']['public'] ) ) {
					$this->settings_set['settings']['ld_topic_category'] = array(
						'label'      => 'Topics Category base',
						'label_html' => sprintf(
							// translators: placeholder: Topics.
							esc_html_x( '%s Category base', 'placeholder: Topics', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'topics' )
						),
						'value'      => '/' . $learndash_settings_permalinks_taxonomies['ld_topic_category'],
					);
				}

				if ( ( isset( $topics_taxonomies['ld_topic_tag'] ) ) && ( true == $topics_taxonomies['ld_topic_tag']['public'] ) ) {
					$this->settings_set['settings']['ld_topic_tag'] = array(
						'label'      => 'Topics Tag',
						'label_html' => sprintf(
							// translators: placeholder: Topic.
							esc_html_x( '%s Tag base', 'placeholder: Topic', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'topic' )
						),
						'value'      => '/' . $learndash_settings_permalinks_taxonomies['ld_topic_tag'],
					);
				}

				$quizzes_taxonomies = $sfwd_lms->get_post_args_section( learndash_get_post_type_slug( 'quiz' ), 'taxonomies' );
				if ( ( isset( $quizzes_taxonomies['ld_quiz_category'] ) ) && ( true == $quizzes_taxonomies['ld_quiz_category']['public'] ) ) {
					$this->settings_set['settings']['ld_quiz_category'] = array(
						'label'      => 'Quizzes Category base',
						'label_html' => sprintf(
							// translators: placeholder: Quizzes.
							esc_html_x( '%s Category base', 'placeholder: Quizzes', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quizzes' )
						),
						'value'      => '/' . $learndash_settings_permalinks_taxonomies['ld_quiz_category'],
					);
				}

				if ( ( isset( $quizzes_taxonomies['ld_quiz_tag'] ) ) && ( true == $quizzes_taxonomies['ld_quiz_tag']['public'] ) ) {
					$this->settings_set['settings']['ld_quiz_tag'] = array(
						'label'      => 'Quizzes Tag',
						'label_html' => sprintf(
							// translators: placeholder: Quizzes.
							esc_html_x( '%s Tag base', 'placeholder: Quizzes', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'quizzes' )
						),
						'value'      => '/' . $learndash_settings_permalinks_taxonomies['ld_quiz_tag'],
					);
				}

				$groups_taxonomies = $sfwd_lms->get_post_args_section( learndash_get_post_type_slug( 'group' ), 'taxonomies' );
				if ( ( isset( $groups_taxonomies['ld_group_category'] ) ) && ( true === $groups_taxonomies['ld_group_category']['public'] ) ) {
					$this->settings_set['settings']['ld_group_category'] = array(
						'label'      => 'Groups Category base',
						'label_html' => sprintf(
							// translators: placeholder: Groups.
							esc_html_x( '%s Category base', 'placeholder: Groups', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'groups' )
						),
						'value'      => '/' . $learndash_settings_permalinks_taxonomies['ld_group_category'],
					);
				}

				if ( ( isset( $groups_taxonomies['ld_group_tag'] ) ) && ( true == $groups_taxonomies['ld_group_tag']['public'] ) ) {
					$this->settings_set['settings']['ld_group_tag'] = array(
						'label'      => 'Groups Tag',
						'label_html' => sprintf(
							// translators: placeholder: Groups.
							esc_html_x( '%s Tag base', 'placeholder: Groups', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'groups' )
						),
						'value'      => '/' . $learndash_settings_permalinks_taxonomies['ld_group_tag'],
					);
				}

				$this->settings_set['settings']['settings-sub-section-ld_upload_directories'] = array(
					'html' => esc_html__( 'Upload Directories', 'learndash' ),
					'text' => 'Upload Directories',
				);

				// LD Assignment upload path.
				$upload_dir      = wp_upload_dir();
				$upload_dir_base = str_replace( '\\', '/', $upload_dir['basedir'] );
				$upload_url_base = $upload_dir['baseurl'];

				$assignment_upload_dir_path                              = $upload_dir_base . '/assignments';
				$assignment_upload_dir_path_r                            = str_replace( $abspath_tmp, '', $assignment_upload_dir_path );
				$this->settings_set['settings']['Assignment Upload Dir'] = array(
					'label'      => 'Assignment Upload Dir',
					'label_html' => esc_html__( 'Assignment Upload Dir', 'learndash' ),
					'value'      => $assignment_upload_dir_path_r,
				);

				$color = 'green';

				if ( ! file_exists( $assignment_upload_dir_path ) ) {
					$color = 'red';
					$this->settings_set['settings']['Assignment Upload Dir']['value_html']  = '<span style="color: ' . $color . '">' . $assignment_upload_dir_path_r . '</span>';
					$this->settings_set['settings']['Assignment Upload Dir']['value_html'] .= ' - ' . esc_html__( 'Directory does not exists', 'learndash' );

					$this->settings_set['settings']['Assignment Upload Dir']['value'] .= ' - (X) Directory does not exists';

				} elseif ( ! is_writable( $assignment_upload_dir_path ) ) {
					$color = 'red';
					$this->settings_set['settings']['Assignment Upload Dir']['value_html']  = '<span style="color: ' . $color . '">' . $assignment_upload_dir_path_r . '</span>';
					$this->settings_set['settings']['Assignment Upload Dir']['value_html'] .= ' - ' . esc_html__( 'Directory not writable', 'learndash' );

					$this->settings_set['settings']['Assignment Upload Dir']['value'] .= ' - (X) Directory not writable';

				} else {
					$this->settings_set['settings']['Assignment Upload Dir']['value_html'] = '<span style="color: ' . $color . '">' . $assignment_upload_dir_path_r . '</span>';
				}

				$essay_upload_dir_path                              = $upload_dir_base . '/essays';
				$essay_upload_dir_path_r                            = str_replace( $abspath_tmp, '', $essay_upload_dir_path );
				$this->settings_set['settings']['Essay Upload Dir'] = array(
					'label'      => 'Essay Upload Dir',
					'label_html' => esc_html__( 'Essay Upload Dir', 'learndash' ),
					'value'      => $essay_upload_dir_path_r,
				);

				$color = 'green';

				if ( ! file_exists( $essay_upload_dir_path ) ) {
					$color = 'red';
					$this->settings_set['settings']['Essay Upload Dir']['value_html']  = '<span style="color: ' . $color . '">' . $essay_upload_dir_path_r . '</span>';
					$this->settings_set['settings']['Essay Upload Dir']['value_html'] .= ' - ' . esc_html__( 'Directory does not exists', 'learndash' );

					$this->settings_set['settings']['Essay Upload Dir']['value'] .= ' - (X) Directory does not exists';

				} elseif ( ! is_writable( $essay_upload_dir_path ) ) {
					$color = 'red';
					$this->settings_set['settings']['Essay Upload Dir']['value_html']  = '<span style="color: ' . $color . '">' . $essay_upload_dir_path_r . '</span>';
					$this->settings_set['settings']['Essay Upload Dir']['value_html'] .= ' - ' . esc_html__( 'Directory not writable', 'learndash' );

					$this->settings_set['settings']['Essay Upload Dir']['value'] .= ' - (X) Directory not writable';

				} else {
					$this->settings_set['settings']['Essay Upload Dir']['value_html'] = '<span style="color: ' . $color . '">' . $essay_upload_dir_path_r . '</span>';
				}

				$this->settings_set['settings']['settings-sub-section-ld_settings_defines'] = array(
					'html' => esc_html__( 'Defines', 'learndash' ),
					'text' => 'Defines',
				);

				$learndash_defines_array = array(
					'LEARNDASH_LMS_PLUGIN_DIR',
					'LEARNDASH_LMS_PLUGIN_URL',
					'LEARNDASH_DEBUG',
					'LEARNDASH_SCRIPT_DEBUG',
					'LEARNDASH_SCRIPT_VERSION_TOKEN',
					'LEARNDASH_GUTENBERG',
					'LEARNDASH_TRANSLATIONS',
					'LEARNDASH_DEFAULT_THEME',
					'LEARNDASH_LEGACY_THEME',
					'LEARNDASH_ADMIN_CAPABILITY_CHECK',
					'LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK',
					'LEARNDASH_COURSE_BUILDER',
					'LEARNDASH_BUILDER_STEPS_UPDATE_POST',
					'LEARNDASH_COURSE_STEPS_PRELOAD',
					'LEARNDASH_COURSE_FUNCTIONS_LEGACY',
					'LEARNDASH_LMS_DEFAULT_CB_INSERT_CHUNK_SIZE',
					'LEARNDASH_QUIZ_BUILDER',
					'LEARNDASH_BUILDER_DEBUG',
					'LEARNDASH_DEFAULT_COURSE_PRICE_TYPE',
					'LEARNDASH_DEFAULT_COURSE_ORDER',
					'LEARNDASH_DEFAULT_COURSE_ORDERBY',
					'LEARNDASH_DEFAULT_GROUP_PRICE_TYPE',
					'LEARNDASH_DEFAULT_GROUP_ORDER',
					'LEARNDASH_DEFAULT_GROUP_ORDERBY',
					'LEARNDASH_GROUP_ENROLLED_COURSE_FROM_USER_REGISTRATION',
					'LEARNDASH_FILTER_SEARCH',
					'LEARNDASH_FILTER_PRIORITY_THE_CONTENT',
					'LEARNDASH_UPDATES_ENABLED',
					'LEARNDASH_LESSON_VIDEO',
					'LEARNDASH_ADDONS_UPDATER',
					'LEARNDASH_QUIZ_PREREQUISITE_ALT',
					'LEARNDASH_QUIZ_RESULT_MESSAGE_MAX',
					'LEARNDASH_LMS_DEFAULT_QUESTION_POINTS',
					'LEARNDASH_LMS_DEFAULT_ANSWER_POINTS',
					'LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE',
					'LEARNDASH_REST_API_ENABLED',
					'LEARNDASH_BLOCK_WORDPRESS_CPT_ROUTES',
					'LEARNDASH_TRANSIENTS_DISABLED',
					'LEARNDASH_USE_WP_SAFE_REDIRECT',
					'LEARNDASH_HTTP_REMOTE_GET_TIMEOUT',
					'LEARNDASH_HTTP_REMOTE_POST_TIMEOUT',
					'LEARNDASH_HTTP_BITBUCKET_README_DOWNLOAD_TIMEOUT',
					'LEARNDASH_REPO_ERROR_THRESHOLD_COUNT',
					'LEARNDASH_REPO_ERROR_THRESHOLD_TIME',
					'LEARNDASH_LMS_DEFAULT_LAZY_LOAD_PER_PAGE',
					'LEARNDASH_LMS_DEFAULT_DATA_UPGRADE_BATCH_SIZE',
					'LEARNDASH_LMS_COURSE_STEPS_LOAD_BATCH_SIZE',
					'LEARNDASH_DISABLE_TEMPLATE_CONTENT_OUTSIDE_LOOP',
					'LEARNDASH_GROUP_ENROLLED_COURSE_FROM_USER_REGISTRATION',
					'LEARNDASH_SELECT2_LIB',
					'LEARNDASH_SELECT2_LIB_AJAX_FETCH',
					'LEARNDASH_SETTINGS_METABOXES_LEGACY',
					'LEARNDASH_SETTINGS_METABOXES_LEGACY_QUIZ',
					'LEARNDASH_SETTINGS_HEADER_PANEL',
					'LEARNDASH_SHOW_MARK_INCOMPLETE',
				);

				/**
				 * Filters list of LearnDash constant defines.
				 *
				 * @param array $constants An array of constants.
				 */
				foreach ( apply_filters( 'learndash_support_ld_defines', $learndash_defines_array ) as $defined_item ) {
					$defined_value = ( defined( $defined_item ) ) ? constant( $defined_item ) : '';
					if ( 'LEARNDASH_LMS_PLUGIN_DIR' == $defined_item ) {
						$defined_value = str_replace( $abspath_tmp, '', $defined_value );
					}

					$this->settings_set['settings'][ $defined_item ] = array(
						'label'      => $defined_item,
						'label_html' => $defined_item,
						'value'      => $defined_value,
					);
				}

				$this->settings_set['settings']['settings-sub-section-ld_settings_translations'] = array(
					'html' => esc_html__( 'Translations', 'learndash' ),
					'text' => 'Translations',
				);
				global $l10n;
				$ld_translation_files = '';
				if ( ( isset( $l10n[ LEARNDASH_LMS_TEXT_DOMAIN ] ) ) && ( ! empty( $l10n[ LEARNDASH_LMS_TEXT_DOMAIN ] ) ) ) {
					$mo_file = $l10n[ LEARNDASH_LMS_TEXT_DOMAIN ]->get_filename();
					if ( ! empty( $mo_file ) ) {
						$mo_files_output       = str_replace( ABSPATH, '', $mo_file );
						$mo_files_output      .= ' <em>' . learndash_adjust_date_time_display( filectime( $mo_file ) ) . '</em>';
						$ld_translation_files .= '<strong>' . LEARNDASH_LMS_TEXT_DOMAIN . '</strong> - ' . $mo_files_output . '<br />';
					}
				}

				$this->settings_set['settings']['Translation Files'] = array(
					'label'      => 'Translation Files',
					'label_html' => esc_html__( 'Translation Files', 'learndash' ),
					'value'      => $ld_translation_files,
				);

				/** This filter is documented in includes/settings/settings-sections/class-ld-settings-section-support-database-tables.php */
				$support_sections[ $this->setting_option_key ] = apply_filters( 'learndash_support_section', $this->settings_set, $this->setting_option_key );
			}

			return $support_sections;
		}

		/**
		 * Show Support Section
		 *
		 * @since 3.1.0
		 *
		 * @param string $settings_section_key Section Key.
		 * @param string $settings_screen_id   Screen ID.
		 */
		public function show_support_section( $settings_section_key = '', $settings_screen_id = '' ) {
			if ( $settings_section_key === $this->settings_section_key ) {
				$support_page_instance = LearnDash_Settings_Page::get_page_instance( 'LearnDash_Settings_Page_Support' );
				if ( $support_page_instance ) {
					$support_page_instance->show_support_section( $this->setting_option_key );
				}
			}
		}

		/**
		 * Determine count of post_type posts by post_status.
		 *
		 * @since 3.3.0
		 *
		 * @param string $post_type Post Type to get counts for.
		 *
		 * @return string
		 */
		public function get_post_type_counts( $post_type = '' ) {
			$counts_string = '';
			if ( ! empty( $post_type ) ) {
				$post_counts = wp_count_posts( $post_type );
				$post_counts = json_decode( wp_json_encode( $post_counts ), true );
				if ( is_array( $post_counts ) ) {
					$counts_total  = 0;
					$post_statuses = get_post_statuses();
					foreach ( $post_counts as $count_key => $count_value ) {
						$count_value = absint( $count_value );
						if ( ! empty( $count_value ) ) {
							$counts_total += $count_value;

							if ( ! empty( $counts_string ) ) {
								$counts_string .= ' | ';
							}

							if ( isset( $post_statuses[ $count_key ] ) ) {
								$status_label = $post_statuses[ $count_key ];
							} else {
								$status_label = $count_key;
							}

							$counts_string .= '<a href="' . add_query_arg(
								array(
									'post_type'   => $post_type,
									'post_status' => $count_key,
								),
								admin_url( 'edit.php' )
							) . '">' . $status_label . '</a> (' . $count_value . ')';

						}
					}
					if ( ! empty( $counts_total ) ) {
						$counts_string = '<a href="' . add_query_arg(
							array(
								'post_type' => $post_type,
							),
							admin_url( 'edit.php' )
						) . '">' . esc_html__( 'All', 'learndash' ) . '</a> (' . $counts_total . ') | ' . $counts_string;
					} else {
						$counts_string .= '0';
					}
				} else {
					$counts_string .= '0';
				}
			}

			return $counts_string;
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Support_LearnDash::add_section_instance();
	}
);
