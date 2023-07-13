<?php
/**
 * SFWD_LMS
 *
 * @since 2.1.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// cspell:ignore i18nize .

use LearnDash\Core\App;
use LearnDash\Core\Provider;


if ( ! class_exists( 'SFWD_LMS' ) ) {

	/**
	 * Class to create the SFWD_LMS instance.
	 */
	class SFWD_LMS extends Semper_Fi_Module {

		/**
		 * Array of post types
		 *
		 * @var array
		 */
		public $post_types = array();

		/**
		 * Cache key
		 *
		 * @var string
		 */
		public $cache_key = '';

		/**
		 * Quiz JSON
		 *
		 * @var string
		 */
		public $quiz_json = '';

		/**
		 * Count
		 *
		 * @var int
		 */
		public $count = null;

		/**
		 * Post arguments
		 *
		 * @var array
		 */
		private $post_args = array();

		/**
		 * All plugins called
		 *
		 * @var bool
		 */
		private $all_plugins_called = false;

		/**
		 * LearnDash plugin path
		 *
		 * @var string
		 */
		private $learndash_standard_plugin_path = 'sfwd-lms/sfwd_lms.php';


		/**
		 * LearnDash Admin Groups Users List instance
		 *
		 * @var Learndash_Admin_Groups_Users_List
		 */
		public $ld_admin_groups_users_list = null;

		/**
		 * LearnDash Admin Data Upgrades instance
		 *
		 * @var Learndash_Admin_Data_Upgrades
		 */
		public $ld_admin_data_upgrades = null;

		/**
		 * Learndash Admin Settings Data Reports instance
		 *
		 * @var Learndash_Admin_Settings_Data_Reports
		 */
		public $ld_admin_settings_data_reports = null;

		/**
		 * LearnDash Admin User Profile Edit instance
		 *
		 * @var Learndash_Admin_User_Profile_Edit
		 */
		public $ld_admin_user_profile_edit = null;

		/**
		 * LearnDash Setup Wizard
		 *
		 * @var LearnDash_Setup_Wizard
		 */
		public $ld_setup_wizard = null;

		/**
		 * LearnDash Course Wizard instance
		 *
		 * @var LearnDash_Course_Wizard
		 */
		public $ld_course_wizard = null;

		/**
		 * LearnDash Design Wizard instance
		 *
		 * @var LearnDash_Design_Wizard
		 */
		public $ld_design_wizard = null;

		/**
		 * Set up properties and hooks for this class
		 */
		public function __construct() {
			self::$instance      =& $this;
			$this->file          = __FILE__;
			$this->name          = 'LMS';
			$this->plugin_name   = 'SFWD LMS';
			$this->name          = 'LMS Options';
			$this->prefix        = 'sfwd_lms_';
			$this->parent_option = 'sfwd_lms_options';
			parent::__construct();

			// maybe call the activate function.
			add_action(
				'init',
				function () {
					if ( get_option( 'learndash_activation' ) ) {
						$this->activate();
						delete_option( 'learndash_activation' );
					}
				}
			);

			add_action( 'init', array( $this, 'trigger_actions' ), 1 );
			add_action( 'init', array( $this, 'add_post_types' ), 2 );

			// WPMU (Multisite) actions when a new blog is added/deleted.
			add_action( 'wpmu_new_blog', array( $this, 'wpmu_new_blog' ) );
			add_action( 'delete_blog', array( $this, 'delete_blog' ), 10, 2 );

			add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
			add_action( 'generate_rewrite_rules', array( $this, 'paypal_rewrite_rules' ) );
			add_filter( 'sfwd_cpt_loop', array( $this, 'cpt_loop_filter' ) );
			add_filter( 'edit_term_count', array( $this, 'tax_term_count' ), 10, 3 );
			add_action( 'plugins_loaded', array( $this, 'i18nize' ) ); // cspell:disable-line.
			add_action( 'current_screen', array( $this, 'add_telemetry_modal' ) );

			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/payments/gateways/init.php';

			add_filter( 'all_plugins', array( $this, 'all_plugins_proc' ) );
			add_action( 'pre_current_active_plugins', array( $this, 'pre_current_active_plugins_proc' ) );
			add_filter( 'option_active_plugins', array( $this, 'option_active_plugins_proc' ) );
			add_filter( 'site_option_active_sitewide_plugins', array( $this, 'site_option_active_sitewide_plugins_proc' ) );
			add_filter( 'pre_update_option_active_plugins', array( $this, 'pre_update_option_active_plugins' ) );
			add_filter( 'pre_update_site_option_active_sitewide_plugins', array( $this, 'pre_update_site_option_active_sitewide_plugins' ) );

			add_action( 'after_setup_theme', array( $this, 'load_template_functions' ), 50 );

			add_filter( 'category_row_actions', array( $this, 'ld_course_category_row_actions' ), 10, 2 );
			add_filter( 'post_tag_row_actions', array( $this, 'ld_course_category_row_actions' ), 10, 2 );

			add_action( 'admin_notices', array( $this, 'hub_after_upgrade_admin_notice' ), 99 );

			add_action( 'shutdown', array( $this, 'wp_shutdown' ), 0 );

			if ( is_admin() ) {
				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-groups-users-list.php';
				$this->ld_admin_groups_users_list = new Learndash_Admin_Groups_Users_List();

				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-data-upgrades.php';
				$this->ld_admin_data_upgrades = Learndash_Admin_Data_Upgrades::get_instance();

				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-settings-data-reports.php';
				$this->ld_admin_settings_data_reports = new Learndash_Admin_Settings_Data_Reports();

				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-user-profile-edit.php';
				$this->ld_admin_user_profile_edit = new Learndash_Admin_User_Profile_Edit();

				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-posts-edit.php';
				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-posts-listing.php';

				/**
				 * WP-admin pointers functions
				 */
				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-pointers.php';

				/**
				 * Setup Wizard
				 */
				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/class-ld-setup-wizard.php';
				$this->ld_setup_wizard = new LearnDash_Setup_Wizard();

				/**
				 * Course Wizard
				 */
				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/class-ld-course-wizard.php';
				$this->ld_course_wizard = new LearnDash_Course_Wizard();
				$this->ld_course_wizard->init();

				if ( ! learndash_cloud_is_enabled() ) {
					/**
					 * Design wizard.
					 */
					require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/class-ld-design-wizard.php';
					$this->ld_design_wizard = new LearnDash_Design_Wizard();
				}
			}

			add_action( 'wp_ajax_select_a_lesson', [ $this, 'select_a_lesson_ajax' ] );
			add_action( 'wp_ajax_select_a_lesson_or_topic', [ $this, 'select_a_lesson_or_topic_ajax' ] );
			add_action( 'wp_ajax_select_a_quiz', [ $this, 'select_a_quiz_ajax' ] );
			add_action(
				'learndash_files_included',
				function() {
					App::register( Provider::class );
				}
			);
		}

		/**
		 * Triggered actions
		 */
		public function trigger_actions() {
			global $learndash_course_statuses, $learndash_question_types, $learndash_exam_challenge_statuses;

			$learndash_course_statuses = array(
				'not_started' => esc_html__( 'Not Started', 'learndash' ),
				'in_progress' => esc_html__( 'In Progress', 'learndash' ),
				'completed'   => esc_html__( 'Completed', 'learndash' ),
			);

			$learndash_question_types = array(
				'single'             => esc_html__( 'Single choice', 'learndash' ),
				'multiple'           => esc_html__( 'Multiple choice', 'learndash' ),
				'free_answer'        => esc_html__( '"Free" choice', 'learndash' ),
				'sort_answer'        => esc_html__( '"Sorting" choice', 'learndash' ),
				'matrix_sort_answer' => esc_html__( '"Matrix Sorting" choice', 'learndash' ),
				'cloze_answer'       => esc_html__( 'Fill in the blank', 'learndash' ),
				'assessment_answer'  => esc_html__( 'Assessment', 'learndash' ),
				'essay'              => esc_html__( 'Essay / Open Answer', 'learndash' ),
			);

			$learndash_exam_challenge_statuses = array(
				'not_taken' => esc_html__( 'Not Taken', 'learndash' ),
				'passed'    => esc_html__( 'Passed', 'learndash' ),
				'failed'    => esc_html__( 'Failed', 'learndash' ),
			);

			$this->upgrade_plugin();

			if ( is_admin() ) {
				if ( ( is_multisite() ) && ( ! is_network_admin() ) ) {
					if ( isset( $_GET['learndash_activate'] ) ) {
						$this->activate();
					}
				}
				/**
				 * Fires on plugin initialization init for admins.
				 */
				do_action( 'learndash_admin_init' );
			}

			/**
			 * Fires on plugin initialization.
			 */
			do_action( 'learndash_init' );

			/**
			 * Fires on LearnDash setting sections fields init.
			 */
			do_action( 'learndash_settings_sections_fields_init' );

			/**
			 * Fires on LearnDash setting sections init.
			 */
			do_action( 'learndash_settings_sections_init' );

			if ( is_admin() ) {
				/**
				 * Fires on LearnDash setting pages init.
				 */
				do_action( 'learndash_settings_pages_init' );
			}

			/**
			 * Fires to trigger active theme/template to load.
			 *
			 * @since 4.0.0
			 */
			do_action( 'learndash_themes_load' );

			/**
			 * Fires when LearnDash core is loaded.
			 *
			 * @since 4.0.0
			 */
			do_action( 'learndash_loaded' );
		}

		/**
		 * Called when new Multisite blog is created
		 * this is used to trigger the activate logic
		 *
		 * @since 2.5.5
		 *
		 * @param int $blog_id Blog ID.
		 */
		public function wpmu_new_blog( $blog_id = 0 ) {
			if ( ! empty( $blog_id ) ) {
				switch_to_blog( $blog_id );
				$this->activate();
				restore_current_blog();
			}
		}

		/**
		 * Called when Multisite blog is deleted
		 * this is used to remove any custom DB tables.
		 *
		 * @since 2.5.5
		 *
		 * @param int  $blog_id     Blog ID.
		 * @param bool $drop_tables Whether to delete DB tables.
		 */
		public function delete_blog( $blog_id = 0, $drop_tables = false ) {
			if ( ( ! empty( $blog_id ) ) && ( true === $drop_tables ) ) {
				switch_to_blog( $blog_id );
				learndash_delete_all_data();
				restore_current_blog();
			}
		}

		/**
		 * Get post args section
		 *
		 * @param string $section     Section.
		 * @param string $sub_section Sub-section.
		 */
		public function get_post_args_section( $section = '', $sub_section = '' ) {
			if ( ( ! empty( $section ) ) && ( isset( $this->post_args[ $section ] ) ) ) {
				if ( ( ! empty( $sub_section ) ) && ( isset( $this->post_args[ $section ][ $sub_section ] ) ) ) {
					return $this->post_args[ $section ][ $sub_section ];
				} else {
					return $this->post_args[ $section ];
				}
			}
		}

		/**
		 * Shutdown actions.
		 */
		public function wp_shutdown() {
			// If we are activating LD then we wait to flush the rewrite on the next page load because the $this->post_args is not setup yet.
			if ( defined( 'LEARNDASH_ACTIVATED' ) && LEARNDASH_ACTIVATED ) {
				return;
			}

			if ( defined( 'LEARNDASH_SETTINGS_UPDATING' ) && LEARNDASH_SETTINGS_UPDATING ) {
				return;
			}

			// check if we triggered the rewrite flush.
			$sfwd_lms_rewrite_flush_transient = get_option( 'sfwd_lms_rewrite_flush' );

			if ( $sfwd_lms_rewrite_flush_transient ) {

				delete_option( 'sfwd_lms_rewrite_flush' );

				$ld_rewrite_post_types = array(
					'sfwd-courses'  => 'courses',
					'sfwd-lessons'  => 'lessons',
					'sfwd-topic'    => 'topics',
					'sfwd-quiz'     => 'quizzes',
					'sfwd-question' => 'questions',
					'groups'        => 'groups',
				);

				// First, we update the $post_args array item with the new permalink slug.
				foreach ( $ld_rewrite_post_types as $cpt_key => $custom_label_key ) {
					if ( isset( $this->post_args[ $cpt_key ] ) ) {
						$this->post_args[ $cpt_key ]['slug_name']                  = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', $custom_label_key );
						$this->post_args[ $cpt_key ]['cpt_options']['has_archive'] = learndash_post_type_has_archive( $cpt_key );
					}
				}

				// Second, we allow external filters. This is the same filter used when the post types are registered.
				/**
				 * Filters post arguments used to create the custom post types and everything
				 * associated with them.
				 *
				 * @since 2.1.0
				 *
				 * @param array $post_args An array of custom post type arguments.
				 */
				$this->post_args = apply_filters( 'learndash_post_args', $this->post_args );

				// Last we need to update the registered post type.
				foreach ( $ld_rewrite_post_types as $cpt_key => $custom_label_key ) {
					$post_type_object = get_post_type_object( $cpt_key );
					if ( $post_type_object instanceof WP_Post_Type ) {
						$post_type_object->rewrite['slug'] = $this->post_args[ $cpt_key ]['slug_name'];
						$post_type_object->has_archive     = $this->post_args[ $cpt_key ]['cpt_options']['has_archive'];

						$post_type_object = wp_parse_args( $post_type_object );
						register_post_type( $cpt_key, $post_type_object );
					}
				}

				flush_rewrite_rules();
			}
		}

		/**
		 * Load functions used for templates
		 *
		 * @since 2.1.0
		 */
		public function load_template_functions() {
			$this->init_ld_templates_dir();
			$template_file = $this->get_template( 'learndash_template_functions', array(), false, true );
			if ( ( ! empty( $template_file ) ) && ( file_exists( $template_file ) ) && ( is_file( $template_file ) ) ) {
				include_once $template_file;
			}

			// Add support for generic name functions.php file in our template directory.
			$template_functions_file = LEARNDASH_TEMPLATES_DIR;
			$template_functions_file = trailingslashit( $template_functions_file ) . 'functions.php';
			if ( file_exists( $template_functions_file ) ) {
				include_once $template_functions_file;
			}
		}

		/**
		 * Loads the plugin's translated strings
		 *
		 * @since 2.1.0
		 */
		public function i18nize() {
			if ( ( defined( 'LD_LANG_DIR' ) ) && ( LD_LANG_DIR ) ) {
				load_plugin_textdomain( LEARNDASH_LMS_TEXT_DOMAIN, false, LD_LANG_DIR );
			} else {
				load_plugin_textdomain( LEARNDASH_LMS_TEXT_DOMAIN, false, dirname( plugin_basename( dirname( __FILE__ ) ) ) . '/languages' );
			}
		}

		/**
		 * Update count of posts with a term
		 *
		 * Callback for add_filter 'edit_term_count'
		 * There is no apply_filters or php call to execute this function
		 *
		 * @todo  consider for deprecation, other docblock tags removed
		 *
		 * @since 2.1.0
		 *
		 * @param string $columns Columns.
		 * @param string $id      Field slug.
		 * @param string $tax     Taxonomy.
		 */
		public function tax_term_count( $columns, $id, $tax ) {
			if ( empty( $tax ) || ( 'courses' != $tax ) ) {
				return $columns;
			}

			if ( ! empty( $_GET ) && ! empty( $_GET['post_type'] ) ) {
				$post_type   = $_GET['post_type'];
				$wpq         = array(
					'tax_query'      => array(
						array(
							'taxonomy' => $tax,
							'field'    => 'id',
							'terms'    => $id,
						),
					),
					'post_type'      => $post_type,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
				);
				$q           = new WP_Query( $wpq );
				$this->count = $q->found_posts;
				add_filter( 'number_format_i18n', array( $this, 'column_term_number' ) );
			}

			return $columns;
		}

		/**
		 * Set column term number
		 *
		 * This function is called by the 'tax_term_count' method and is no longer being ran
		 * See tax_term_count()
		 *
		 * @todo  consider for deprecation, other docblock tags removed
		 *
		 * @since 2.1.0
		 *
		 * @param int $number Number.
		 */
		public function column_term_number( $number ) {
			remove_filter( 'number_format_i18n', array( $this, 'column_term_number' ) );
			if ( null !== $this->count ) {
				$number = $this->count;
				unset( $this->count );
			}
			return $number;
		}



		/**
		 * [usermeta] shortcode
		 *
		 * This shortcode takes a parameter named field, which is the name of the user meta data field to be displayed.
		 * Example: [usermeta field="display_name"] would display the user's Display Name.
		 *
		 * @since 2.1.0
		 *
		 * @param  array  $attr    shortcode attributes.
		 * @param  string $content content of shortcode.
		 * @return string            output of shortcode.
		 */
		public function usermeta_shortcode( $attr, $content = '' ) {
			return learndash_usermeta_shortcode( $attr, $content );
		}


		/**
		 * Callback for add_filter 'sfwd_cpt_loop'
		 * There is no apply_filters or php call to execute this function
		 *
		 * @since 2.1.0
		 *
		 * @todo  consider for deprecation, other docblock tags removed
		 *
		 * @param string $content Content.
		 */
		public function cpt_loop_filter( $content ) {
			global $post;
			if ( 'sfwd-quiz' === $post->post_type ) {
				$meta = get_post_meta( $post->ID, '_sfwd-quiz' );
				if ( is_array( $meta ) && ! empty( $meta ) ) {
					$meta = $meta[0];
					if ( is_array( $meta ) && ( ! empty( $meta['sfwd-quiz_lesson'] ) ) ) {
						$content = '';
					}
				}
			}
			return $content;
		}

		/**
		 * Upgrade plugin
		 */
		public function upgrade_plugin() {
			$ld_is_upgrade = learndash_data_upgrades_setting( 'is_upgrade' );
			if ( true === $ld_is_upgrade ) {
				$this->activate();

				$ld_admin_data_upgrades = Learndash_Admin_Data_Upgrades::get_instance();
				$ld_admin_data_upgrades->set_data_settings( 'is_upgrade', false );
			}
		}

		/**
		 * Fire on plugin activation
		 *
		 * Currently sets 'sfwd_lms_rewrite_flush' to true
		 *
		 * @param bool $network_wide Whether to enable the plugin for all sites in the network
		 *                           or just the current site. Multisite only. Default false.
		 *
		 * @since 4.1.1 Added $network_wide param.
		 * @since 2.1.0
		 */
		public function activate( $network_wide = false ) {
			learndash_setup_rewrite_flush();

			if ( ! defined( 'LEARNDASH_ACTIVATED' ) ) {
				$learndash_activated = true;

				/**
				 * Define LearnDash LMS - Set during plugin activation.
				 *
				 * @since 2.4.0
				 * @internal Will be set by LearnDash LMS.
				 */
				define( 'LEARNDASH_ACTIVATED', $learndash_activated );
			}

			/**
			 * Remove legacy option item
			 *
			 * @since 2.5.7
			 */
			delete_option( 'ld-repositories' );

			/**
			 * Ensure we call WPProQuiz activate functions
			 *
			 * @since 2.4.6.1
			 */
			WpProQuiz_Helper_Upgrade::upgrade();

			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-data-upgrades.php';

			$ld_prior_version = learndash_data_upgrades_setting( 'prior_version' );

			learndash_init_admin_courses_capabilities();
			learndash_init_admin_groups_capabilities();
			learndash_init_admin_coupons_capabilities();
			learndash_init_assignments_capabilities();

			if ( 'new' === $ld_prior_version ) {

				// As this is a new install we want to set the prior data run on the Courses and Quizzes.
				$data_upgrade_courses = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_User_Meta_Courses' );
				if ( $data_upgrade_courses ) {
					$data_upgrade_courses->set_last_run_info();
				}

				$data_upgrade_quizzes = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_User_Meta_Quizzes' );
				if ( $data_upgrade_quizzes ) {
					$data_upgrade_quizzes->set_last_run_info();
				}

				$data_upgrade_course_access_list = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_Course_Access_List_Convert' );
				if ( $data_upgrade_course_access_list ) {
					$data_upgrade_course_access_list->set_last_run_info();
				}

				$data_upgrade_quiz_questions = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_Quiz_Questions' );
				if ( $data_upgrade_quiz_questions ) {
					$data_upgrade_quiz_questions->set_last_run_info();
				}

				$data_upgrade_course_post_meta = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_Course_Post_Meta' );
				if ( $data_upgrade_course_post_meta ) {
					$data_upgrade_course_post_meta->set_last_run_info();
				}

				$data_upgrade_group_post_meta = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_Group_Post_Meta' );
				if ( $data_upgrade_group_post_meta ) {
					$data_upgrade_group_post_meta->set_last_run_info();
				}

				$data_upgrade_quiz_post_meta = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_Quiz_Post_Meta' );
				if ( $data_upgrade_quiz_post_meta ) {
					$data_upgrade_quiz_post_meta->set_last_run_info();
				}
			}

			$ld_admin_settings_data_upgrades_db = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_User_Activity_DB_Table' );
			$ld_admin_settings_data_upgrades_db->upgrade_data_settings();

			$ld_admin_data_upgrades = Learndash_Admin_Data_Upgrades::get_instance();
			$ld_admin_data_upgrades->set_data_settings( 'translations_installed', false );

			/**
			 * If the prior version is not empty we check if there are existing questions. If
			 * none found we set the questions data upgrade to completed.
			 */
			if ( 'new' !== $ld_prior_version ) {
				global $wpdb;

				$data_upgrade_quiz_questions = Learndash_Admin_Data_Upgrades::get_instance( 'Learndash_Admin_Data_Upgrades_Quiz_Questions' );
				if ( $data_upgrade_quiz_questions ) {
					$questions_data_settings = $data_upgrade_quiz_questions->get_data_settings( 'pro-quiz-questions' );

					$question_proquiz_count = $wpdb->get_var(
						$wpdb->prepare(
							'SELECT id FROM ' . esc_sql( LDLMS_DB::get_table_name( 'quiz_question' ) ) . ' LIMIT %d',
							1
						)
					);

					$question_post_count = $wpdb->get_var(
						$wpdb->prepare(
							'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_type=%s LIMIT %d',
							learndash_get_post_type_slug( 'question' ),
							1
						)
					);

					if ( ( empty( $question_proquiz_count ) ) && ( empty( $question_post_count ) ) ) {
						$data_upgrade_quiz_questions->set_last_run_info();
					} elseif ( ( ! empty( $question_proquiz_count ) ) && ( empty( $question_post_count ) ) ) {
						$data_upgrade_quiz_questions->set_data_settings( 'pro-quiz-questions', false );
					} elseif ( ( ! empty( $question_proquiz_count ) ) && ( ! empty( $question_post_count ) ) ) {
						if ( false === $questions_data_settings ) {
							$data_upgrade_quiz_questions->set_last_run_info();
						}
					}
				}

				// Only show notice if upgrading from 4.3.0.2 to 4.3.1.
				if ( '4.3.0.2' === $ld_prior_version ) {
					update_option( 'learndash_show_hub_upgrade_admin_notice', true );
				}
			}

			/**
			 * Secure the Assignments & Essay uploads directory from browsing
			 *
			 * @since 2.5.5
			 */
			$wp_upload_dir      = wp_upload_dir();
			$wp_upload_base_dir = str_replace( '\\', '/', $wp_upload_dir['basedir'] );

			$ld_dirs = array( 'assignments', 'essays' );
			foreach ( array( 'assignments', 'essays' ) as $ld_dir ) {

				$_dir = trailingslashit( $wp_upload_base_dir ) . $ld_dir;
				if ( ! file_exists( $_dir ) ) {
					if ( is_writable( dirname( $_dir ) ) ) {
						wp_mkdir_p( $_dir );
					}
				}

				if ( file_exists( $_dir ) ) {
					$_index = trailingslashit( $_dir ) . 'index.php';
					if ( ! file_exists( $_index ) ) {
						file_put_contents( $_index, '//LearnDash is THE Best LMS' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents -- It's okay here.
					}
				}
			}

			if ( file_exists( trailingslashit( LEARNDASH_LMS_PLUGIN_DIR ) . 'mu-plugins/setup.php' ) ) {
				include trailingslashit( LEARNDASH_LMS_PLUGIN_DIR ) . 'mu-plugins/setup.php';
			}

			/**
			 * Fires on LearnDash plugin activation.
			 *
			 * @since 2.1.0
			 */
			do_action( 'learndash_activated' );
		}

		/**
		 * Add 'sfwd-lms' to query vars
		 * Fired on filter 'query_vars'
		 *
		 * @since 2.1.0
		 *
		 * @param  array $vars  query vars.
		 * @return array    $vars  query vars
		 */
		public function add_query_vars( $vars ) {
			$paypal_email = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_PayPal', 'paypal_email' );
			if ( ! empty( $paypal_email ) ) {
				$vars = array_merge( array( 'sfwd-lms' ), $vars );
			}
			return $vars;
		}

		/**
		 * Adds paypal to already generated rewrite rules
		 * Fired on action 'generate_rewrite_rules'
		 *
		 * @since 2.1.0
		 *
		 * @param  object $wp_rewrite WP rewrite object.
		 */
		public function paypal_rewrite_rules( $wp_rewrite ) {
			$wp_rewrite->rules = array_merge( array( 'sfwd-lms/paypal' => 'index.php?sfwd-lms=paypal' ), $wp_rewrite->rules );
		}

		/**
		 * Sets up CPT's and creates a 'new SFWD_CPT_Instance()' of each
		 *
		 * @since 2.1.0
		 */
		public function add_post_types() {
			$post = 0;

			if ( is_admin() && ! empty( $_GET ) && ( isset( $_GET['post'] ) ) ) {
				$post_id = $_GET['post'];
			}

			if ( ! empty( $post_id ) ) {
				$this->quiz_json = get_post_meta( $post_id, '_quizdata', true );
				if ( ! empty( $this->quiz_json ) ) {
					$this->quiz_json = $this->quiz_json['workingJson'];
				}
			}

			$options = get_option( 'sfwd_cpt_options' );

			$level1 = '';
			$level2 = '';
			$level3 = '';
			$level4 = '';
			$level5 = '';

			if ( ! empty( $options['modules'] ) ) {
				$options = $options['modules'];
				if ( ! empty( $options['sfwd-quiz_options'] ) ) {
					$options = $options['sfwd-quiz_options'];
					foreach ( array( 'level1', 'level2', 'level3', 'level4', 'level5' ) as $level ) {
						$$level = '';
						if ( ! empty( $options[ "sfwd-quiz_{$level}" ] ) ) {
							$$level = $options[ "sfwd-quiz_{$level}" ];
						}
					}
				}
			}

			if ( empty( $this->quiz_json ) ) {
				$this->quiz_json = '{"info":{"name":"","main":"","results":"","level1":"' . $level1 . '","level2":"' . $level2 . '","level3":"' . $level3 . '","level4":"' . $level4 . '","level5":"' . $level5 . '"}}';
			}

			$posts_per_page = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );
			if ( empty( $posts_per_page ) ) {
				$posts_per_page = get_option( 'posts_per_page' );
				if ( empty( $posts_per_page ) ) {
					$posts_per_page = 5;
				}
			}

			learndash_init_admin_courses_capabilities();
			$course_capabilities = learndash_get_admin_courses_capabilities();

			$lcl_topic  = LearnDash_Custom_Label::get_label( 'topic' );
			$lcl_topics = LearnDash_Custom_Label::get_label( 'topics' );

			$lesson_topic_labels = array(
				'name'                     => $lcl_topics,
				'singular_name'            => $lcl_topic,
				'add_new'                  => esc_html_x( 'Add New', 'Add New Topic Label', 'learndash' ),
				// translators: placeholder: Topic.
				'add_new_item'             => sprintf( esc_html_x( 'Add New %s', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
				// translators: placeholder: Topic.
				'edit_item'                => sprintf( esc_html_x( 'Edit %s', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
				// translators: placeholder: Topic.
				'new_item'                 => sprintf( esc_html_x( 'New %s', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
				'all_items'                => $lcl_topics,
				// translators: placeholder: Topic.
				'view_item'                => sprintf( esc_html_x( 'View %s', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
				// translators: placeholder: Topics.
				'view_items'               => sprintf( esc_html_x( 'View %s', 'placeholder: Topics', 'learndash' ), $lcl_topics ),
				// translators: placeholder: Topics.
				'search_items'             => sprintf( esc_html_x( 'Search %s', 'placeholder: Topics', 'learndash' ), $lcl_topics ),
				// translators: placeholder: Topics.
				'not_found'                => sprintf( esc_html_x( 'No %s found', 'placeholder: Topics', 'learndash' ), $lcl_topics ),
				// translators: placeholder: Topic.
				'not_found_in_trash'       => sprintf( esc_html_x( 'No %s found in Trash', 'placeholder: Topic', 'learndash' ), $lcl_topics ),
				'parent_item_colon'        => '',
				'menu_name'                => $lcl_topics,
				// translators: placeholder: Topic.
				'item_published'           => sprintf( esc_html_x( '%s Published', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
				// translators: placeholder: Topic.
				'item_published_privately' => sprintf( esc_html_x( '%s Published Privately', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
				// translators: placeholder: Topic.
				'item_reverted_to_draft'   => sprintf( esc_html_x( '%s Reverted to Draft', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
				// translators: placeholder: Topic.
				'item_scheduled'           => sprintf( esc_html_x( '%s Scheduled', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
				// translators: placeholder: Topic.
				'item_updated'             => sprintf( esc_html_x( '%s Updated', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
			);

			$lcl_quiz    = LearnDash_Custom_Label::get_label( 'quiz' );
			$lcl_quizzes = LearnDash_Custom_Label::get_label( 'quizzes' );

			$quiz_labels = array(
				'name'                     => $lcl_quizzes,
				'singular_name'            => $lcl_quiz,
				'add_new'                  => esc_html_x( 'Add New', 'Add New Quiz Label', 'learndash' ),
				// translators: placeholder: Quiz.
				'add_new_item'             => sprintf( esc_html_x( 'Add New %s', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
				// translators: placeholder: Quiz.
				'edit_item'                => sprintf( esc_html_x( 'Edit %s', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
				// translators: placeholder: Quiz.
				'new_item'                 => sprintf( esc_html_x( 'New %s', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
				'all_items'                => $lcl_quizzes,
				// translators: placeholder: Quiz.
				'view_item'                => sprintf( esc_html_x( 'View %s', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
				// translators: placeholder: Quizzes.
				'view_items'               => sprintf( esc_html_x( 'View %s', 'placeholder: Quizzes', 'learndash' ), $lcl_quizzes ),
				// translators: placeholder: Quizzes.
				'search_items'             => sprintf( esc_html_x( 'Search %s', 'placeholder: Quizzes', 'learndash' ), $lcl_quizzes ),
				// translators: placeholder: Quizzes.
				'not_found'                => sprintf( esc_html_x( 'No %s found', 'placeholder: Quizzes', 'learndash' ), $lcl_quizzes ),
				// translators: placeholder: Quizzes.
				'not_found_in_trash'       => sprintf( esc_html_x( 'No %s found in Trash', 'placeholder: Quizzes', 'learndash' ), $lcl_quizzes ),
				'parent_item_colon'        => '',
				'menu_name'                => $lcl_quizzes,
				// translators: placeholder: Quiz.
				'item_published'           => sprintf( esc_html_x( '%s Published', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
				// translators: placeholder: Quiz.
				'item_published_privately' => sprintf( esc_html_x( '%s Published Privately', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
				// translators: placeholder: Quiz.
				'item_reverted_to_draft'   => sprintf( esc_html_x( '%s Reverted to Draft', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
				// translators: placeholder: Quiz.
				'item_scheduled'           => sprintf( esc_html_x( '%s Scheduled', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
				// translators: placeholder: Quiz.
				'item_updated'             => sprintf( esc_html_x( '%s Updated', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
			);

			$lcl_question  = LearnDash_Custom_Label::get_label( 'question' );
			$lcl_questions = LearnDash_Custom_Label::get_label( 'questions' );

			$question_labels = array(
				'name'                     => $lcl_questions,
				'singular_name'            => $lcl_question,
				'add_new'                  => esc_html_x( 'Add New', 'placeholder: Question', 'learndash' ),
				// translators: placeholder: Question.
				'add_new_item'             => sprintf( esc_html_x( 'Add New %s', 'placeholder: Question', 'learndash' ), $lcl_question ),
				// translators: placeholder: Question.
				'edit_item'                => sprintf( esc_html_x( 'Edit %s', 'placeholder: Question', 'learndash' ), $lcl_question ),
				// translators: placeholder: Question.
				'new_item'                 => sprintf( esc_html_x( 'New %s', 'placeholder: Question', 'learndash' ), $lcl_question ),
				'all_items'                => $lcl_questions,
				// translators: placeholder: Question.
				'view_item'                => sprintf( esc_html_x( 'View %s', 'placeholder: Question', 'learndash' ), $lcl_question ),
				// translators: placeholder: Questions.
				'view_items'               => sprintf( esc_html_x( 'View %s', 'placeholder: Questions', 'learndash' ), $lcl_questions ),
				// translators: placeholder: Questions.
				'search_items'             => sprintf( esc_html_x( 'Search %s', 'placeholder: Questions', 'learndash' ), $lcl_questions ),
				// translators: placeholder: Questions.
				'not_found'                => sprintf( esc_html_x( 'No %s found', 'placeholder: Questions', 'learndash' ), $lcl_questions ),
				// translators: placeholder: Questions.
				'not_found_in_trash'       => sprintf( esc_html_x( 'No %s found in Trash', 'placeholder: Questions', 'learndash' ), $lcl_questions ),
				'parent_item_colon'        => '',
				'menu_name'                => $lcl_questions,
				// translators: placeholder: Question.
				'item_published'           => sprintf( esc_html_x( '%s Published', 'placeholder: Question', 'learndash' ), $lcl_question ),
				// translators: placeholder: Question.
				'item_published_privately' => sprintf( esc_html_x( '%s Published Privately', 'placeholder: Question', 'learndash' ), $lcl_question ),
				// translators: placeholder: Question.
				'item_reverted_to_draft'   => sprintf( esc_html_x( '%s Reverted to Draft', 'placeholder: Question', 'learndash' ), $lcl_question ),
				// translators: placeholder: Question.
				'item_scheduled'           => sprintf( esc_html_x( '%s Scheduled', 'placeholder: Question', 'learndash' ), $lcl_question ),
				// translators: placeholder: Question.
				'item_updated'             => sprintf( esc_html_x( '%s Updated', 'placeholder: Question', 'learndash' ), $lcl_question ),
			);

			$lcl_lesson  = LearnDash_Custom_Label::get_label( 'lesson' );
			$lcl_lessons = LearnDash_Custom_Label::get_label( 'lessons' );

			$lesson_labels = array(
				'name'                     => $lcl_lessons,
				'singular_name'            => $lcl_lesson,
				// translators: placeholder: Lesson.
				'add_new'                  => esc_html_x( 'Add New', 'placeholder: Lesson', 'learndash' ),
				// translators: placeholder: Lesson.
				'add_new_item'             => sprintf( esc_html_x( 'Add New %s', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
				// translators: placeholder: Lesson.
				'edit_item'                => sprintf( esc_html_x( 'Edit %s', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
				// translators: placeholder: Lesson.
				'new_item'                 => sprintf( esc_html_x( 'New %s', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
				'all_items'                => $lcl_lessons,
				// translators: placeholder: Lesson.
				'view_item'                => sprintf( esc_html_x( 'View %s', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
				// translators: placeholder: Lessons.
				'view_items'               => sprintf( esc_html_x( 'View %s', 'placeholder: Lessons.', 'learndash' ), $lcl_lessons ),
				// translators: placeholder: Lessons.
				'search_items'             => sprintf( esc_html_x( 'Search %s', 'placeholder: Lessons.', 'learndash' ), $lcl_lessons ),
				// translators: placeholder: Lessons.
				'not_found'                => sprintf( esc_html_x( 'No %s found', 'placeholder: Lessons.', 'learndash' ), $lcl_lessons ),
				// translators: placeholder: Lessons.
				'not_found_in_trash'       => sprintf( esc_html_x( 'No %s found in Trash', 'placeholder: Lessons.', 'learndash' ), $lcl_lessons ),
				'parent_item_colon'        => '',
				'menu_name'                => $lcl_lessons,
				// translators: placeholder: Lesson.
				'item_published'           => sprintf( esc_html_x( '%s Published', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
				// translators: placeholder: Lesson.
				'item_published_privately' => sprintf( esc_html_x( '%s Published Privately', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
				// translators: placeholder: Lesson.
				'item_reverted_to_draft'   => sprintf( esc_html_x( '%s Reverted to Draft', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
				// translators: placeholder: Lesson.
				'item_scheduled'           => sprintf( esc_html_x( '%s Scheduled', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
				// translators: placeholder: Lesson.
				'item_updated'             => sprintf( esc_html_x( '%s Updated', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
			);

			$lcl_exam  = LearnDash_Custom_Label::get_label( 'exam' );
			$lcl_exams = LearnDash_Custom_Label::get_label( 'exams' );

			$exam_labels = array(
				'name'                     => $lcl_exams,
				'singular_name'            => $lcl_exam,
				// translators: placeholder: Exam.
				'add_new'                  => esc_html_x( 'Add New', 'placeholder: Exam', 'learndash' ),
				// translators: placeholder: Exam.
				'add_new_item'             => sprintf( esc_html_x( 'Add New %s', 'placeholder: Exam', 'learndash' ), $lcl_exam ),
				// translators: placeholder: Exam.
				'edit_item'                => sprintf( esc_html_x( 'Edit %s', 'placeholder: Exam', 'learndash' ), $lcl_exam ),
				// translators: placeholder: Exam.
				'new_item'                 => sprintf( esc_html_x( 'New %s', 'placeholder: Exam', 'learndash' ), $lcl_exam ),
				'all_items'                => $lcl_exams,
				// translators: placeholder: Exam.
				'view_item'                => sprintf( esc_html_x( 'View %s', 'placeholder: Exam', 'learndash' ), $lcl_exam ),
				// translators: placeholder: Exams.
				'view_items'               => sprintf( esc_html_x( 'View %s', 'placeholder: Lessons', 'learndash' ), $lcl_exams ),
				// translators: placeholder: Exams.
				'search_items'             => sprintf( esc_html_x( 'Search %s', 'placeholder: Lessons', 'learndash' ), $lcl_exams ),
				// translators: placeholder: Exams.
				'not_found'                => sprintf( esc_html_x( 'No %s found', 'placeholder: Lessons', 'learndash' ), $lcl_exams ),
				// translators: placeholder: Exams.
				'not_found_in_trash'       => sprintf( esc_html_x( 'No %s found in Trash', 'placeholder: Lessons', 'learndash' ), $lcl_exams ),
				'parent_item_colon'        => '',
				'menu_name'                => $lcl_exams,
				// translators: placeholder: Exam.
				'item_published'           => sprintf( esc_html_x( '%s Published', 'placeholder: Exam', 'learndash' ), $lcl_exam ),
				// translators: placeholder: Exam.
				'item_published_privately' => sprintf( esc_html_x( '%s Published Privately', 'placeholder: Exam', 'learndash' ), $lcl_exam ),
				// translators: placeholder: Exam.
				'item_reverted_to_draft'   => sprintf( esc_html_x( '%s Reverted to Draft', 'placeholder: Exam', 'learndash' ), $lcl_exam ),
				// translators: placeholder: Exam.
				'item_scheduled'           => sprintf( esc_html_x( '%s Scheduled', 'placeholder: Exam', 'learndash' ), $lcl_exam ),
				// translators: placeholder: Exam.
				'item_updated'             => sprintf( esc_html_x( '%s Updated', 'placeholder: Exam', 'learndash' ), $lcl_exam ),
			);

			$lcl_coupon  = LearnDash_Custom_Label::get_label( 'coupon' );
			$lcl_coupons = LearnDash_Custom_Label::get_label( 'coupons' );

			$coupon_labels = array(
				'name'                     => $lcl_coupons,
				'singular_name'            => $lcl_coupon,
				// translators: placeholder: Coupon.
				'add_new'                  => esc_html_x( 'Add New', 'placeholder: Coupon', 'learndash' ),
				// translators: placeholder: Coupon.
				'add_new_item'             => sprintf( esc_html_x( 'Add New %s', 'placeholder: Coupon', 'learndash' ), $lcl_coupon ),
				// translators: placeholder: Coupon.
				'edit_item'                => sprintf( esc_html_x( 'Edit %s', 'placeholder: Coupon', 'learndash' ), $lcl_coupon ),
				// translators: placeholder: Coupon.
				'new_item'                 => sprintf( esc_html_x( 'New %s', 'placeholder: Coupon', 'learndash' ), $lcl_coupon ),
				'all_items'                => $lcl_coupons,
				// translators: placeholder: Coupon.
				'view_item'                => sprintf( esc_html_x( 'View %s', 'placeholder: Coupon', 'learndash' ), $lcl_coupon ),
				// translators: placeholder: Coupons.
				'view_items'               => sprintf( esc_html_x( 'View %s', 'placeholder: Lessons', 'learndash' ), $lcl_coupons ),
				// translators: placeholder: Coupons.
				'search_items'             => sprintf( esc_html_x( 'Search %s', 'placeholder: Lessons', 'learndash' ), $lcl_coupons ),
				// translators: placeholder: Coupons.
				'not_found'                => sprintf( esc_html_x( 'No %s found', 'placeholder: Lessons', 'learndash' ), $lcl_coupons ),
				// translators: placeholder: Coupons.
				'not_found_in_trash'       => sprintf( esc_html_x( 'No %s found in Trash', 'placeholder: Lessons', 'learndash' ), $lcl_coupons ),
				'parent_item_colon'        => '',
				'menu_name'                => $lcl_coupons,
				// translators: placeholder: Coupon.
				'item_published'           => sprintf( esc_html_x( '%s Published', 'placeholder: Coupon', 'learndash' ), $lcl_coupon ),
				// translators: placeholder: Coupon.
				'item_published_privately' => sprintf( esc_html_x( '%s Published Privately', 'placeholder: Coupon', 'learndash' ), $lcl_coupon ),
				// translators: placeholder: Coupon.
				'item_reverted_to_draft'   => sprintf( esc_html_x( '%s Reverted to Draft', 'placeholder: Coupon', 'learndash' ), $lcl_coupon ),
				// translators: placeholder: Coupon.
				'item_scheduled'           => sprintf( esc_html_x( '%s Scheduled', 'placeholder: Coupon', 'learndash' ), $lcl_coupon ),
				// translators: placeholder: Coupon.
				'item_updated'             => sprintf( esc_html_x( '%s Updated', 'placeholder: Coupon', 'learndash' ), $lcl_coupon ),
			);

			$lcl_course  = LearnDash_Custom_Label::get_label( 'course' );
			$lcl_courses = LearnDash_Custom_Label::get_label( 'courses' );

			$course_labels = array(
				'name'                     => $lcl_courses,
				'singular_name'            => $lcl_course,
				'add_new'                  => esc_html_x( 'Add New', 'placeholder: Course', 'learndash' ),
				// translators: placeholder: Course.
				'add_new_item'             => sprintf( esc_html_x( 'Add New %s', 'placeholder: Course', 'learndash' ), $lcl_course ),
				// translators: placeholder: Course.
				'edit_item'                => sprintf( esc_html_x( 'Edit %s', 'placeholder: Course', 'learndash' ), $lcl_course ),
				// translators: placeholder: Course.
				'new_item'                 => sprintf( esc_html_x( 'New %s', 'placeholder: Course', 'learndash' ), $lcl_course ),
				'all_items'                => $lcl_courses,
				// translators: placeholder: Course.
				'view_item'                => sprintf( esc_html_x( 'View %s', 'placeholder: Course', 'learndash' ), $lcl_course ),
				// translators: placeholder: Courses.
				'view_items'               => sprintf( esc_html_x( 'View %s', 'placeholder: Courses', 'learndash' ), $lcl_courses ),
				// translators: placeholder: Courses.
				'search_items'             => sprintf( esc_html_x( 'Search %s', 'placeholder: Courses', 'learndash' ), $lcl_courses ),
				// translators: placeholder: Courses.
				'not_found'                => sprintf( esc_html_x( 'No %s found', 'placeholder: Courses', 'learndash' ), $lcl_courses ),
				// translators: placeholder: Courses.
				'not_found_in_trash'       => sprintf( esc_html_x( 'No %s found in Trash', 'placeholder: Courses', 'learndash' ), $lcl_courses ),
				'parent_item_colon'        => '',
				'menu_name'                => $lcl_courses,
				// translators: placeholder: Course.
				'item_published'           => sprintf( esc_html_x( '%s Published', 'placeholder: Course', 'learndash' ), $lcl_course ),
				// translators: placeholder: Course.
				'item_published_privately' => sprintf( esc_html_x( '%s Published Privately', 'placeholder: Course', 'learndash' ), $lcl_course ),
				// translators: placeholder: Course.
				'item_reverted_to_draft'   => sprintf( esc_html_x( '%s Reverted to Draft', 'placeholder: Course', 'learndash' ), $lcl_course ),
				// translators: placeholder: Course.
				'item_scheduled'           => sprintf( esc_html_x( '%s Scheduled', 'placeholder: Course', 'learndash' ), $lcl_course ),
				// translators: placeholder: Course.
				'item_updated'             => sprintf( esc_html_x( '%s Updated', 'placeholder: Course', 'learndash' ), $lcl_course ),
			);

			$course_taxonomies = array();
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Taxonomies', 'wp_post_category' ) == 'yes' ) {
				$course_taxonomies['category'] = 'category';
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Taxonomies', 'wp_post_tag' ) == 'yes' ) {
				$course_taxonomies['post_tag'] = 'post_tag';
			}

			$learndash_settings_permalinks_taxonomies = get_option( 'learndash_settings_permalinks_taxonomies' );
			if ( ! is_array( $learndash_settings_permalinks_taxonomies ) ) {
				$learndash_settings_permalinks_taxonomies = array();
			}
			$learndash_settings_permalinks_taxonomies = wp_parse_args(
				$learndash_settings_permalinks_taxonomies,
				array(
					'ld_course_category'   => 'course-category',
					'ld_course_tag'        => 'course-tag',
					'ld_lesson_category'   => 'lesson-category',
					'ld_lesson_tag'        => 'lesson-tag',
					'ld_topic_category'    => 'topic-category',
					'ld_topic_tag'         => 'topic-tag',
					'ld_quiz_category'     => 'quiz-category',
					'ld_quiz_tag'          => 'quiz-tag',
					'ld_question_category' => 'question-category',
					'ld_question_tag'      => 'question-tag',
					'ld_group_category'    => 'group-category',
					'ld_group_tag'         => 'group-tag',
				)
			);

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Taxonomies', 'ld_course_category' ) == 'yes' ) {
				$course_taxonomies['ld_course_category'] = array(
					'public'            => true,
					'hierarchical'      => true,
					'show_ui'           => true,
					'show_in_menu'      => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'      => LearnDash_REST_API::enabled( 'sfwd-courses' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-courses' ),
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_course_category'] ),
					'capabilities'      => array(
						'manage_terms' => 'manage_categories',
						'edit_terms'   => 'edit_categories',
						'delete_terms' => 'delete_categories',
						'assign_terms' => 'assign_categories',
					),

					'labels'            => array(
						// translators: placeholder: Course.
						'name'              => sprintf( esc_html_x( '%s Categories', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'singular_name'     => sprintf( esc_html_x( '%s Category', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'search_items'      => sprintf( esc_html_x( 'Search %s Categories', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'all_items'         => sprintf( esc_html_x( 'All %s Categories', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Category', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Category:', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Category', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'update_item'       => sprintf( esc_html_x( 'Update %s Category', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Category', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'new_item_name'     => sprintf( esc_html_x( 'New %s Category Name', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'menu_name'         => sprintf( esc_html_x( '%s Categories', 'placeholder: Course', 'learndash' ), $lcl_course ),
					),
				);
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Taxonomies', 'ld_course_tag' ) == 'yes' ) {
				$course_taxonomies['ld_course_tag'] = array(
					'public'            => true,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_in_menu'      => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'      => LearnDash_REST_API::enabled( 'sfwd-courses' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-courses' ),
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_course_tag'] ),
					'labels'            => array(
						// translators: placeholder: Course.
						'name'              => sprintf( esc_html_x( '%s Tags', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'singular_name'     => sprintf( esc_html_x( '%s Tag', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'search_items'      => sprintf( esc_html_x( 'Search %s Tag', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'all_items'         => sprintf( esc_html_x( 'All %s Tags', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Tag', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Tag:', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Tag', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'update_item'       => sprintf( esc_html_x( 'Update %s Tag', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Tag', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'new_item_name'     => sprintf( esc_html_x( 'New %s Tag Name', 'placeholder: Course', 'learndash' ), $lcl_course ),
						// translators: placeholder: Course.
						'menu_name'         => sprintf( esc_html_x( '%s Tags', 'placeholder: Course', 'learndash' ), $lcl_course ),
					),
				);
			}

			$lesson_taxonomies = array();
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Lessons_Taxonomies', 'wp_post_category' ) == 'yes' ) {
				$lesson_taxonomies['category'] = 'category';
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Lessons_Taxonomies', 'wp_post_tag' ) == 'yes' ) {
				$lesson_taxonomies['post_tag'] = 'post_tag';
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Lessons_Taxonomies', 'ld_lesson_category' ) == 'yes' ) {
				$lesson_taxonomies['ld_lesson_category'] = array(
					'public'            => true,
					'hierarchical'      => true,
					'show_ui'           => true,
					'show_in_menu'      => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'      => LearnDash_REST_API::enabled( 'sfwd-lessons' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-lessons' ),
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_lesson_category'] ),
					'capabilities'      => array(
						'manage_terms' => 'manage_categories',
						'edit_terms'   => 'edit_categories',
						'delete_terms' => 'delete_categories',
						'assign_terms' => 'assign_categories',
					),
					'labels'            => array(
						// translators: placeholder: Lesson.
						'name'              => sprintf( esc_html_x( '%s Categories', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'singular_name'     => sprintf( esc_html_x( '%s Category', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'search_items'      => sprintf( esc_html_x( 'Search %s Categories', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'all_items'         => sprintf( esc_html_x( 'All %s Categories', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Category', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Category:', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Category', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'update_item'       => sprintf( esc_html_x( 'Update %s Category', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Category', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'new_item_name'     => sprintf( esc_html_x( 'New %s Category Name', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'menu_name'         => sprintf( esc_html_x( '%s Categories', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
					),
				);
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Lessons_Taxonomies', 'ld_lesson_tag' ) == 'yes' ) {
				$lesson_taxonomies['ld_lesson_tag'] = array(
					'public'            => true,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_in_menu'      => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'      => LearnDash_REST_API::enabled( 'sfwd-lessons' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-lessons' ),
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_lesson_tag'] ),
					'labels'            => array(
						// translators: placeholder: Lesson.
						'name'              => sprintf( esc_html_x( '%s Tags', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'singular_name'     => sprintf( esc_html_x( '%s Tag', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'search_items'      => sprintf( esc_html_x( 'Search %s Tag', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'all_items'         => sprintf( esc_html_x( 'All %s Tags', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Tag', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Tag:', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Tag', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'update_item'       => sprintf( esc_html_x( 'Update %s Tag', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Tag', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'new_item_name'     => sprintf( esc_html_x( 'New %s Tag Name', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
						// translators: placeholder: Lesson.
						'menu_name'         => sprintf( esc_html_x( '%s Tags', 'placeholder: Lesson', 'learndash' ), $lcl_lesson ),
					),
				);
			}

			$topic_taxonomies = array();
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Topics_Taxonomies', 'wp_post_category' ) == 'yes' ) {
				$topic_taxonomies['category'] = 'category';
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Topics_Taxonomies', 'wp_post_tag' ) == 'yes' ) {
				$topic_taxonomies['post_tag'] = 'post_tag';
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Topics_Taxonomies', 'ld_topic_category' ) == 'yes' ) {
				$topic_taxonomies['ld_topic_category'] = array(
					'public'            => true,
					'hierarchical'      => true,
					'show_ui'           => true,
					'show_in_menu'      => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'      => LearnDash_REST_API::enabled( 'sfwd-topic' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-topic' ),
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_topic_category'] ),
					'capabilities'      => array(
						'manage_terms' => 'manage_categories',
						'edit_terms'   => 'edit_categories',
						'delete_terms' => 'delete_categories',
						'assign_terms' => 'assign_categories',
					),
					'labels'            => array(
						// translators: placeholder: Topic.
						'name'              => sprintf( esc_html_x( '%s Categories', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'singular_name'     => sprintf( esc_html_x( '%s Category', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'search_items'      => sprintf( esc_html_x( 'Search %s Categories', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'all_items'         => sprintf( esc_html_x( 'All %s Categories', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Category', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Category:', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Category', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'update_item'       => sprintf( esc_html_x( 'Update %s Category', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Category', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'new_item_name'     => sprintf( esc_html_x( 'New %s Category Name', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'menu_name'         => sprintf( esc_html_x( '%s Categories', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
					),
				);
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Topics_Taxonomies', 'ld_topic_tag' ) == 'yes' ) {
				$topic_taxonomies['ld_topic_tag'] = array(
					'public'            => true,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_in_menu'      => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'      => LearnDash_REST_API::enabled( 'sfwd-topic' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-topic' ),
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_topic_tag'] ),
					'labels'            => array(
						// translators: placeholder: Topic.
						'name'              => sprintf( esc_html_x( '%s Tags', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'singular_name'     => sprintf( esc_html_x( '%s Tag', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'search_items'      => sprintf( esc_html_x( 'Search %s Tag', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'all_items'         => sprintf( esc_html_x( 'All %s Tags', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Tag', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Tag:', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Tag', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'update_item'       => sprintf( esc_html_x( 'Update %s Tag', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Tag', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'new_item_name'     => sprintf( esc_html_x( 'New %s Tag Name', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
						// translators: placeholder: Topic.
						'menu_name'         => sprintf( esc_html_x( '%s Tags', 'placeholder: Topic', 'learndash' ), $lcl_topic ),
					),
				);
			}

			$quiz_taxonomies = array();
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Taxonomies', 'ld_quiz_category' ) == 'yes' ) {
				$quiz_taxonomies['ld_quiz_category'] = array(
					'public'            => true,
					'hierarchical'      => true,
					'show_ui'           => true,
					'show_in_menu'      => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'      => LearnDash_REST_API::enabled( 'sfwd-quiz' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-quiz' ),
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_quiz_category'] ),
					'capabilities'      => array(
						'manage_terms' => 'manage_categories',
						'edit_terms'   => 'edit_categories',
						'delete_terms' => 'delete_categories',
						'assign_terms' => 'assign_categories',
					),
					'labels'            => array(
						// translators: placeholder: Quiz.
						'name'              => sprintf( esc_html_x( '%s Categories', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'singular_name'     => sprintf( esc_html_x( '%s Category', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'search_items'      => sprintf( esc_html_x( 'Search %s Categories', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'all_items'         => sprintf( esc_html_x( 'All %s Categories', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Category', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Category:', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Category', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'update_item'       => sprintf( esc_html_x( 'Update %s Category', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Category', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'new_item_name'     => sprintf( esc_html_x( 'New %s Category Name', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'menu_name'         => sprintf( esc_html_x( '%s Categories', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
					),
				);
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Taxonomies', 'ld_quiz_tag' ) == 'yes' ) {
				$quiz_taxonomies['ld_quiz_tag'] = array(
					'public'            => true,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_in_menu'      => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'      => LearnDash_REST_API::enabled( 'sfwd-quiz' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-quiz' ),
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_quiz_tag'] ),
					'labels'            => array(
						// translators: placeholder: Quiz.
						'name'              => sprintf( esc_html_x( '%s Tags', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'singular_name'     => sprintf( esc_html_x( '%s Tag', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'search_items'      => sprintf( esc_html_x( 'Search %s Tag', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'all_items'         => sprintf( esc_html_x( 'All %s Tags', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Tag', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Tag:', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Tag', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'update_item'       => sprintf( esc_html_x( 'Update %s Tag', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Tag', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'new_item_name'     => sprintf( esc_html_x( 'New %s Tag Name', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
						// translators: placeholder: Quiz.
						'menu_name'         => sprintf( esc_html_x( '%s Tags', 'placeholder: Quiz', 'learndash' ), $lcl_quiz ),
					),
				);
			}
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Taxonomies', 'wp_post_category' ) == 'yes' ) {
				$quiz_taxonomies['category'] = 'category';
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Taxonomies', 'wp_post_tag' ) == 'yes' ) {
				$quiz_taxonomies['post_tag'] = 'post_tag';
			}

			$question_taxonomies = array();
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Questions_Taxonomies', 'ld_question_category' ) == 'yes' ) {
				$question_taxonomies['ld_question_category'] = array(
					'public'            => false,
					'hierarchical'      => true,
					'show_ui'           => true,
					'show_in_menu'      => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'      => LearnDash_REST_API::enabled( 'sfwd-question' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-question' ),
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_question_category'] ),
					'capabilities'      => array(
						'manage_terms' => 'manage_categories',
						'edit_terms'   => 'edit_categories',
						'delete_terms' => 'delete_categories',
						'assign_terms' => 'assign_categories',
					),
					'labels'            => array(
						// translators: placeholder: Question.
						'name'              => sprintf( esc_html_x( '%s Categories', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'singular_name'     => sprintf( esc_html_x( '%s Category', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'search_items'      => sprintf( esc_html_x( 'Search %s Categories', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'all_items'         => sprintf( esc_html_x( 'All %s Categories', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Category', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Category:', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Category', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'update_item'       => sprintf( esc_html_x( 'Update %s Category', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Category', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'new_item_name'     => sprintf( esc_html_x( 'New %s Category Name', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'menu_name'         => sprintf( esc_html_x( '%s Categories', 'placeholder: Question', 'learndash' ), $lcl_question ),
					),
				);
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Questions_Taxonomies', 'ld_question_tag' ) == 'yes' ) {
				$question_taxonomies['ld_question_tag'] = array(
					'public'            => false,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_in_menu'      => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'show_in_rest'      => LearnDash_REST_API::enabled( 'sfwd-question' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-question' ),
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_question_tag'] ),
					'labels'            => array(
						// translators: placeholder: Question.
						'name'              => sprintf( esc_html_x( '%s Tags', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'singular_name'     => sprintf( esc_html_x( '%s Tag', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'search_items'      => sprintf( esc_html_x( 'Search %s Tag', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'all_items'         => sprintf( esc_html_x( 'All %s Tags', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Tag', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Tag:', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Tag', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'update_item'       => sprintf( esc_html_x( 'Update %s Tag', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Tag', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'new_item_name'     => sprintf( esc_html_x( 'New %s Tag Name', 'placeholder: Question', 'learndash' ), $lcl_question ),
						// translators: placeholder: Question.
						'menu_name'         => sprintf( esc_html_x( '%s Tags', 'placeholder: Question', 'learndash' ), $lcl_question ),
					),
				);
			}
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Questions_Taxonomies', 'wp_post_category' ) == 'yes' ) {
				$question_taxonomies['category'] = 'category';
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Questions_Taxonomies', 'wp_post_tag' ) == 'yes' ) {
				$question_taxonomies['post_tag'] = 'post_tag';
			}

			$course_lessons_options_labels = array(
				'orderby' => LearnDash_Settings_Section::get_section_setting_select_option_label( 'LearnDash_Settings_Section_Lessons_Display_Order', 'orderby' ),
				'order'   => LearnDash_Settings_Section::get_section_setting_select_option_label( 'LearnDash_Settings_Section_Lessons_Display_Order', 'order' ),
			);

			$exam_post_type_slug   = learndash_get_post_type_slug( 'exam' );
			$coupon_post_type_slug = learndash_get_post_type_slug( LDLMS_Post_Types::COUPON );

			$this->post_args = array(
				'sfwd-courses'       => array(
					'plugin_name'        => LearnDash_Custom_Label::get_label( 'course' ),
					'slug_name'          => LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'courses' ),
					'post_type'          => 'sfwd-courses',
					'template_redirect'  => true,
					'taxonomies'         => $course_taxonomies,
					'cpt_options'        => array(
						'has_archive'         => learndash_post_type_has_archive( 'sfwd-courses' ),
						'hierarchical'        => false,
						'supports'            => array_merge(
							array( 'title', 'editor', 'author', 'page-attributes' ),
							LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_CPT', 'supports' )
						),
						'labels'              => $course_labels,
						'capability_type'     => 'course',
						'exclude_from_search' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_CPT', 'include_in_search' ) !== 'yes' ) ? true : false,
						'capabilities'        => $course_capabilities,
						'map_meta_cap'        => true,
						'show_in_rest'        => LearnDash_REST_API::enabled( 'sfwd-courses' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-courses' ),
					),
					'options_page_title' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'LearnDash %s Settings', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'fields'             => array(
						'course_materials'              => array(
							// translators: placeholder: Course.
							'name'         => sprintf( esc_html_x( '%s Materials', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type'         => 'textarea',
							// translators: placeholder: Course.
							'help_text'    => sprintf( esc_html_x( 'Options for %s materials', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
							'rest_args'    => array(
								'schema' => array(
									'type' => 'html',
								),
							),
						),
						'course_price_type'             => array(
							// translators: placeholder: Course.
							'name'            => sprintf( esc_html_x( '%s Price Type', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type'            => 'select',
							'initial_options' => array(
								'open'      => esc_html__( 'Open', 'learndash' ),
								'closed'    => esc_html__( 'Closed', 'learndash' ),
								'free'      => esc_html__( 'Free', 'learndash' ),
								'paynow'    => esc_html__( 'Buy Now', 'learndash' ),
								'subscribe' => esc_html__( 'Recurring', 'learndash' ),
							),
							'default'         => 'open',
							'help_text'       => esc_html__( 'Is it open to all, free join, one time purchase, or a recurring subscription?', 'learndash' ),
							'show_in_rest'    => LearnDash_REST_API::enabled(),
							'rest_args'       => array(
								'schema' => array(
									'type'    => 'string',
									'default' => 'open',
									'enum'    => array(
										'open',
										'closed',
										'free',
										'buynow',
										'subscribe',
									),
								),
							),
						),
						'custom_button_label'           => array(
							'name'         => esc_html__( 'Custom Button Label', 'learndash' ),
							'type'         => 'text',
							'placeholder'  => esc_html__( 'Optional', 'learndash' ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'custom_button_url'             => array(
							'name'         => esc_html__( 'Custom Button URL', 'learndash' ),
							'type'         => 'text',
							'placeholder'  => esc_html__( 'Optional', 'learndash' ),
							// translators: placeholder: "Take This Course" button label.
							'help_text'    => sprintf( esc_html_x( 'Entering a URL in this field will enable the "%s" button. The button will not display if this field is left empty. Relative URL beginning with a slash is acceptable.', 'placeholder: "Take This Course" button label', 'learndash' ), LearnDash_Custom_Label::get_label( 'button_take_this_course' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'course_price'                  => array(
							// translators: placeholder: Course.
							'name'         => sprintf( esc_html_x( '%s Price', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type'         => 'text',
							// translators: placeholders: Course, Course.
							'help_text'    => sprintf( esc_html_x( 'Enter %1$s price here. Leave empty if the %2$s is free.', 'placeholders: Course, Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'course_price_billing_cycle'    => array(
							'name'         => esc_html__( 'Billing Cycle', 'learndash' ),
							'type'         => 'html',
							'default'      => '',
							'help_text'    => esc_html__( 'Billing Cycle for the recurring payments in case of a subscription.', 'learndash' ),
							'show_in_rest' => false,
						),
						'course_access_list'            => array(
							// translators: placeholder: Course.
							'name'         => sprintf( esc_html_x( '%s Access List', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type'         => 'textarea',
							'help_text'    => esc_html__( 'This field is auto-populated with the UserIDs of those who have access to this course.', 'learndash' ),
							'show_in_rest' => false,
						),
						'course_lesson_orderby'         => array(
							// translators: placeholder: Lesson.
							'name'            => sprintf( esc_html_x( 'Sort %s By', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'type'            => 'select',
							'initial_options' => array(
								''           => esc_html__( 'Use Default', 'learndash' ) . ' ( ' . $course_lessons_options_labels['orderby'] . ' )',
								'title'      => esc_html__( 'Title', 'learndash' ),
								'date'       => esc_html__( 'Date', 'learndash' ),
								'menu_order' => esc_html__( 'Menu Order', 'learndash' ),
							),
							'default'         => '',
							// translators: placeholders: lessons, course.
							'help_text'       => sprintf( esc_html_x( 'Choose the sort order of %1$s in this %2$s.', 'placeholders: lessons, course', 'learndash' ), learndash_get_custom_label_lower( 'lessons' ), learndash_get_custom_label_lower( 'course' ) ),
							'show_in_rest'    => false,
						),
						'course_lesson_order'           => array(
							// translators: placeholder: Lesson.
							'name'            => sprintf( esc_html_x( 'Sort %s Direction', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'type'            => 'select',
							'initial_options' => array(
								''     => esc_html__( 'Use Default', 'learndash' ) . ' ( ' . $course_lessons_options_labels['order'] . ' )',
								'ASC'  => esc_html__( 'Ascending', 'learndash' ),
								'DESC' => esc_html__( 'Descending', 'learndash' ),
							),
							'default'         => '',
							// translators: placeholders: lessons, course.
							'help_text'       => sprintf( esc_html_x( 'Choose the sort order of %1$s in this %2$s.', 'placeholders: lessons, course', 'learndash' ), learndash_get_custom_label_lower( 'lessons' ), learndash_get_custom_label_lower( 'course' ) ),
							'show_in_rest'    => false,
						),

						'course_lesson_per_page'        => array(
							// translators: placeholder: Lessons.
							'name'            => sprintf( esc_html_x( '%s Per Page', 'placeholder: Lessons', 'learndash' ), LearnDash_Custom_Label::get_label( 'lessons' ) ),
							'type'            => 'select',
							'initial_options' => array(
								''       => esc_html__( 'Use Default', 'learndash' ) . ' ( ' . LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Lessons_Display_Order', 'posts_per_page' ) . ' )',
								'CUSTOM' => esc_html__( 'Custom', 'learndash' ),
							),
							'default'         => '',
							// translators: placeholders: lessons, course.
							'help_text'       => sprintf( esc_html_x( 'Choose the per page of %1$s in this %2$s.', 'placeholders: lessons, course', 'learndash' ), learndash_get_custom_label_lower( 'lessons' ), learndash_get_custom_label_lower( 'course' ) ),
							'show_in_rest'    => false,
						),
						'course_lesson_per_page_custom' => array(
							// translators: placeholder: Lessons.
							'name'         => sprintf( esc_html_x( 'Custom %s Per Page', 'placeholder: Lessons', 'learndash' ), LearnDash_Custom_Label::get_label( 'lessons' ) ),
							'type'         => 'number',
							'min'          => '0',
							// translators: placeholder: Lesson.
							'help_text'    => sprintf( esc_html_x( 'Enter %s per page value. Set to zero for no paging', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'default'      => 0,
							'show_in_rest' => false,
						),

						'course_prerequisite_enabled'   => array(
							// translators: placeholder: Course.
							'name'          => sprintf( esc_html_x( 'Enable %s Prerequisites', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type'          => 'checkbox',
							'checked_value' => 'on',
							'help_text'     => esc_html__( 'Leave this field unchecked if prerequisite not used.', 'learndash' ),
							'show_in_rest'  => LearnDash_REST_API::enabled(),
							'rest_args'     => array(
								'schema' => array(
									'type'    => 'boolean',
									'default' => false,
								),
							),
						),
						'course_prerequisite'           => array(
							// translators: placeholder: Course.
							'name'            => sprintf( esc_html_x( '%s Prerequisites', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type'            => 'multiselect',
							// translators: placeholders: course, course.
							'help_text'       => sprintf( esc_html_x( 'Select one or more %1$s as prerequisites to view this %2$s', 'placeholders: course, course', 'learndash' ), learndash_get_custom_label_lower( 'course' ), learndash_get_custom_label_lower( 'course' ) ),
							'lazy_load'       => true,
							'initial_options' => '',
							'default'         => '',
							'show_in_rest'    => LearnDash_REST_API::enabled(),
							'rest_args'       => array(
								'schema' => array(
									'default' => array(),
									'type'    => 'array',
								),
							),
						),
						'course_prerequisite_compare'   => array(
							// translators: placeholder: Course.
							'name'            => sprintf( esc_html_x( '%s Prerequisites Compare', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type'            => 'select',
							'initial_options' => array(
								'ANY' => esc_html__( 'ANY (default) - The student must complete at least one of the prerequisites', 'learndash' ),
								'ALL' => esc_html__( 'ALL - The student must complete all the prerequisites', 'learndash' ),
							),
							'default'         => 'ANY',
							// translators: placeholder: Course.
							'help_text'       => sprintf( esc_html_x( 'Select how to compare the selected prerequisite %s.', 'placeholder: Course', 'learndash' ), learndash_get_custom_label_lower( 'course' ) ),
							'show_in_rest'    => LearnDash_REST_API::enabled(),
						),
						'course_points_enabled'         => array(
							// translators: placeholder: Course.
							'name'         => sprintf( esc_html_x( 'Enable %s Points', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type'         => 'checkbox',
							'help_text'    => esc_html__( 'Leave this field unchecked if points not used.', 'learndash' ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
							'rest_args'    => array(
								'schema' => array(
									'type' => 'boolean',
								),
							),
						),
						'course_points'                 => array(
							// translators: placeholder: Course.
							'name'         => sprintf( esc_html_x( '%s Points', 'Course Points', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type'         => 'number',
							'step'         => 'any',
							'min'          => '0',
							// translators: placeholder: Course.
							'help_text'    => sprintf( esc_html_x( 'Enter the number of points a user will receive for this %s.', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'course_points_access'          => array(
							// translators: placeholder: Course.
							'name'         => sprintf( esc_html_x( '%s Points Access', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type'         => 'number',
							'step'         => 'any',
							'min'          => '0',
							// translators: placeholder: Course.
							'help_text'    => sprintf( esc_html_x( 'Enter the number of points a user must have to access this %s.', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'course_disable_lesson_progression' => array(
							// translators: placeholder: Lesson.
							'name'         => sprintf( esc_html_x( 'Disable %s Progression', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'type'         => 'checkbox',
							'default'      => 0,
							// translators: placeholder: lessons.
							'help_text'    => sprintf( esc_html_x( 'Disable the feature that allows attempting %s only in allowed order.', 'placeholder: lessons', 'learndash' ), learndash_get_custom_label_lower( 'lessons' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'expire_access'                 => array(
							'name'         => esc_html__( 'Expire Access', 'learndash' ),
							'type'         => 'checkbox',
							'help_text'    => esc_html__( 'Leave this field unchecked if access never expires.', 'learndash' ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'expire_access_days'            => array(
							'name'         => esc_html__( 'Expire Access After (days)', 'learndash' ),
							'type'         => 'number',
							'min'          => '0',
							// translators: placeholder: Course.
							'help_text'    => sprintf( esc_html_x( 'Enter the number of days a user has access to this %s.', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'expire_access_delete_progress' => array(
							// translators: placeholders: Course, Quiz.
							'name'         => sprintf( esc_html_x( 'Delete %1$s and %2$s Data After Expiration', 'placeholders: Course, Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
							'type'         => 'checkbox',
							// translators: placeholder: Course.
							'help_text'    => sprintf( esc_html_x( "Select this option if you want the user's %s progress to be deleted when their access expires.", 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'course_disable_content_table'  => array(
							// translators: placeholder: Course.
							'name'         => sprintf( esc_html_x( 'Hide %s Content Table', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type'         => 'checkbox',
							'default'      => 0,
							// translators: placeholder: Course.
							'help_text'    => sprintf( esc_html_x( 'Hide %s Content table when user is not enrolled.', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'show_in_rest' => false,
						),

						'certificate'                   => array(
							'name'         => esc_html__( 'Associated Certificate', 'learndash' ),
							'type'         => 'select',
							// translators: placeholder: course.
							'help_text'    => sprintf( esc_html_x( 'Select a certificate to be awarded upon %s completion (optional).', 'placeholder: course', 'learndash' ), learndash_get_custom_label_lower( 'course' ) ),
							'default'      => '',
							'show_in_rest' => false,
						),
					),
				),
				'sfwd-lessons'       => array(
					'plugin_name'        => LearnDash_Custom_Label::get_label( 'lesson' ),
					'slug_name'          => LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'lessons' ),
					'post_type'          => 'sfwd-lessons',
					'template_redirect'  => true,
					'taxonomies'         => $lesson_taxonomies,
					'cpt_options'        => array(
						'has_archive'         => learndash_post_type_has_archive( 'sfwd-lessons' ),
						'supports'            => array_merge(
							array( 'title', 'editor', 'author', 'page-attributes' ),
							LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Lessons_CPT', 'supports' )
						),
						'labels'              => $lesson_labels,
						'capability_type'     => 'course',
						'exclude_from_search' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Lessons_CPT', 'include_in_search' ) !== 'yes' ) ? true : false,
						'capabilities'        => $course_capabilities,
						'map_meta_cap'        => true,
						'show_in_rest'        => LearnDash_REST_API::enabled( 'sfwd-lessons' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-lessons' ),
					),
					'options_page_title' => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( 'LearnDash %s Settings', 'placeholder: Lesson', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lesson' )
					),
					'fields'             => array(
						'lesson_materials'                 => array(
							// translators: placeholder: Lesson.
							'name'         => sprintf( esc_html_x( '%s Materials', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'type'         => 'textarea',
							// translators: placeholder: Lesson.
							'help_text'    => sprintf( esc_html_x( 'Options for %s materials', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
							'rest_args'    => array(
								'schema' => array(
									'type' => 'html',
								),
							),
						),
						'course'                           => array(
							// translators: placeholder: Course.
							'name'         => sprintf( esc_html_x( 'Associated %s', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type'         => 'select',
							'lazy_load'    => true,
							// translators: placeholders: Lesson, Course.
							'help_text'    => sprintf( esc_html_x( 'Associate this %1$s with a %2$s.', 'placeholders: Lesson, Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'default'      => '',
							'required'     => true,
							'show_in_rest' => false,
						),
						'forced_lesson_time'               => array(
							// translators: placeholder: Lesson.
							'name'         => sprintf( esc_html_x( 'Forced %s Timer', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'type'         => 'text',
							// translators: placeholder: Lesson.
							'help_text'    => sprintf( esc_html_x( 'Minimum time a user has to spend on %s page before it can be marked complete. Examples: 40 (for 40 seconds), 20s, 45sec, 2m 30s, 2min 30sec, 1h 5m 10s, 1hr 5min 10sec', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'default'      => '',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'lesson_assignment_upload'         => array(
							'name'         => esc_html__( 'Upload Assignment', 'learndash' ),
							'type'         => 'checkbox',
							'help_text'    => esc_html__( 'Check this if you want to make it mandatory to upload assignment', 'learndash' ),
							'default'      => 0,
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'auto_approve_assignment'          => array(
							'name'         => esc_html__( 'Auto Approve Assignment', 'learndash' ),
							'type'         => 'checkbox',
							'help_text'    => esc_html__( 'Check this if you want to auto-approve the uploaded assignment', 'learndash' ),
							'default'      => 'on',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'assignment_upload_limit_count'    => array(
							'name'         => esc_html__( 'Limit number of uploaded files', 'learndash' ),
							'type'         => 'number',
							'placeholder'  => esc_html__( 'Default is 1', 'learndash' ),
							'help_text'    => esc_html__( 'Enter the maximum number of assignment uploads allowed. Default is 1. Use 0 to unlimited.', 'learndash' ),
							'default'      => '1',
							'class'        => 'small-text',
							'min'          => '1',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'lesson_assignment_deletion_enabled' => array(
							'name'         => esc_html__( 'Allow Student to Delete own Assignment(s)', 'learndash' ),
							'type'         => 'checkbox',
							'help_text'    => esc_html__( 'Allow Student to Delete own Assignment(s)', 'learndash' ),
							'default'      => 0,
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),

						'lesson_assignment_points_enabled' => array(
							'name'         => esc_html__( 'Award Points for Assignment', 'learndash' ),
							'type'         => 'checkbox',
							'help_text'    => esc_html__( 'Allow this assignment to be assigned points when it is approved.', 'learndash' ),
							'default'      => 0,
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'lesson_assignment_points_amount'  => array(
							'name'         => esc_html__( 'Set Number of Points for Assignment', 'learndash' ),
							'type'         => 'number',
							'min'          => 0,
							'help_text'    => esc_html__( 'Assign the max amount of points someone can earn for this assignment.', 'learndash' ),
							'default'      => 0,
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'assignment_upload_limit_extensions' => array(
							'name'         => esc_html__( 'Allowed File Extensions', 'learndash' ),
							'type'         => 'text',
							'placeholder'  => esc_html__( 'Example: pdf, xls, zip', 'learndash' ),
							'help_text'    => esc_html__( 'Enter comma-separated list of allowed file extensions: pdf, xls, zip or leave blank for any.', 'learndash' ),
							'default'      => '',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'assignment_upload_limit_size'     => array(
							'name'         => esc_html__( 'Allowed File Size', 'learndash' ),
							'type'         => 'text',
							// translators: placeholder: PHP file upload size.
							'placeholder'  => sprintf( esc_html_x( 'Maximum upload file size: %s', 'placeholder: PHP file upload size', 'learndash' ), ini_get( 'upload_max_filesize' ) ),
							// translators: placeholder: PHP file upload size.
							'help_text'    => sprintf( esc_html_x( 'Enter maximum file upload size. Example: 100KB, 2M, 2MB, 1G. Maximum upload file size: %s', 'placeholder: PHP file upload size', 'learndash' ), ini_get( 'upload_max_filesize' ) ),
							'default'      => '',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),

						'sample_lesson'                    => array(
							// translators: placeholder: Lesson.
							'name'      => sprintf( esc_html_x( 'Sample %s', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'type'      => 'checkbox',
							// translators: placeholders: lesson, topics.
							'help_text' => sprintf( esc_html_x( 'Check this if you want this %1$s and all its %2$s to be available for free.', 'placeholders: lesson, topics', 'learndash' ), learndash_get_custom_label_lower( 'lesson' ), learndash_get_custom_label_lower( 'topics' ) ),
							'default'   => 0,
						),
						'visible_after'                    => array(
							// translators: placeholder: Lesson.
							'name'         => sprintf( esc_html_x( 'Make %s visible X Days After Sign-up', 'Make Lesson Visible X Days After Sign-up', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'type'         => 'number',
							'class'        => 'small-text',
							'min'          => '0',
							// translators: placeholder: Lesson.
							'help_text'    => sprintf( esc_html_x( 'Make %s visible ____ days after sign-up', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'default'      => 0,
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'visible_after_specific_date'      => array(
							// translators: placeholder: Lesson.
							'name'         => sprintf( esc_html_x( 'Make %s Visible on Specific Date', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'type'         => 'wp_date_selector',
							'class'        => 'learndash-datepicker-field',
							// translators: placeholder: lesson.
							'help_text'    => sprintf( esc_html_x( 'Set the date that you would like this %s to become available.', 'placeholder: lesson', 'learndash' ), learndash_get_custom_label_lower( 'lesson' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
					),
				),
				'sfwd-topic'         => array(
					// translators: placeholders: Lesson, Topic.
					'plugin_name'        => sprintf( esc_html_x( '%1$s %2$s', 'placeholders: Lesson, Topic', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
					'slug_name'          => LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'topics' ),
					'post_type'          => 'sfwd-topic',
					'template_redirect'  => true,
					'taxonomies'         => $topic_taxonomies,
					'cpt_options'        => array(
						'supports'            => array_merge(
							array( 'title', 'editor', 'author', 'page-attributes' ),
							LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Topics_CPT', 'supports' )
						),
						'has_archive'         => learndash_post_type_has_archive( 'sfwd-topic' ),
						'labels'              => $lesson_topic_labels,
						'capability_type'     => 'course',
						'exclude_from_search' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Topics_CPT', 'include_in_search' ) !== 'yes' ) ? true : false,
						'capabilities'        => $course_capabilities,
						'map_meta_cap'        => true,
						'show_in_rest'        => LearnDash_REST_API::enabled( 'sfwd-topic' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-topic' ),
					),
					'options_page_title' => sprintf(
						// translators: placeholder: Topic.
						esc_html_x( 'LearnDash %s Settings', 'placeholder: Topic', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'topic' )
					),
					'fields'             => array(
						'topic_materials'                  => array(
							// translators: placeholder: Topic.
							'name'         => sprintf( esc_html_x( '%s Materials', 'placeholder: Topic', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
							'type'         => 'textarea',
							// translators: placeholder: Topic.
							'help_text'    => sprintf( esc_html_x( 'Options for %s materials', 'placeholder: Topic', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
							'rest_args'    => array(
								'schema' => array(
									'type' => 'html',
								),
							),
						),

						'course'                           => array(
							// translators: placeholder: Course.
							'name'         => sprintf( esc_html_x( 'Associated %s', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type'         => 'select',
							'lazy_load'    => true,
							// translators: placeholders: Topic, Course.
							'help_text'    => sprintf( esc_html_x( 'Associate this %1$s with a %2$s.', 'placeholders: topic, course', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'default'      => '',
							'show_in_rest' => false,
						),
						'lesson'                           => array(
							// translators: placeholder: Lesson.
							'name'         => sprintf( esc_html_x( 'Associated %s', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'type'         => 'select',
							'lazy_load'    => true,
							// translators: placeholders: Topic, Lesson.
							'help_text'    => sprintf( esc_html_x( 'Associate this %1$s with a %2$s.', 'placeholders: Topic, Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'default'      => '',
							'show_in_rest' => false,
						),
						'forced_lesson_time'               => array(
							// translators: placeholder: Topic.
							'name'         => sprintf( esc_html_x( 'Forced %s Timer', 'placeholder: Topic', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
							'type'         => 'text',
							// translators: placeholder: Topic.
							'help_text'    => sprintf( esc_html_x( 'Minimum time a user has to spend on %s page before it can be marked complete. Examples: 40 (for 40 seconds), 20s, 45sec, 2m 30s, 2min 30sec, 1h 5m 10s, 1hr 5min 10sec', 'placeholder: Topic', 'learndash' ), LearnDash_Custom_Label::get_label( 'topic' ) ),
							'default'      => '',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'lesson_assignment_upload'         => array(
							'name'         => esc_html__( 'Upload Assignment', 'learndash' ),
							'type'         => 'checkbox',
							'help_text'    => esc_html__( 'Check this if you want to make it mandatory to upload assignment', 'learndash' ),
							'default'      => 0,
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'auto_approve_assignment'          => array(
							'name'         => esc_html__( 'Auto Approve Assignment', 'learndash' ),
							'type'         => 'checkbox',
							'help_text'    => esc_html__( 'Check this if you want to auto-approve the uploaded assignment', 'learndash' ),
							'default'      => 'on',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'assignment_upload_limit_count'    => array(
							'name'         => esc_html__( 'Limit number of uploaded files', 'learndash' ),
							'type'         => 'number',
							'placeholder'  => esc_html__( 'Default is 1', 'learndash' ),
							'help_text'    => esc_html__( 'Enter the maximum number of assignment uploads allowed. Default is 1. Use 0 to unlimited.', 'learndash' ),
							'default'      => '1',
							'show_in_rest' => LearnDash_REST_API::enabled(),
							'class'        => 'small-text',
							'min'          => '1',
						),
						'lesson_assignment_deletion_enabled' => array(
							'name'      => esc_html__( 'Allow Student to Delete own Assignment(s)', 'learndash' ),
							'type'      => 'checkbox',
							'help_text' => esc_html__( 'Allow Student to Delete own Assignment(s)', 'learndash' ),
							'default'   => 0,
						),

						'lesson_assignment_points_enabled' => array(
							'name'         => esc_html__( 'Award Points for Assignment', 'learndash' ),
							'type'         => 'checkbox',
							'help_text'    => esc_html__( 'Allow this assignment to be assigned points when it is approved.', 'learndash' ),
							'default'      => 0,
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'lesson_assignment_points_amount'  => array(
							'name'         => esc_html__( 'Set Number of Points for Assignment', 'learndash' ),
							'type'         => 'number',
							'min'          => 0,
							'help_text'    => esc_html__( 'Assign the max amount of points someone can earn for this assignment.', 'learndash' ),
							'default'      => 0,
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),

						'assignment_upload_limit_extensions' => array(
							'name'         => esc_html__( 'Allowed File Extensions', 'learndash' ),
							'type'         => 'text',
							'placeholder'  => esc_html__( 'Example: pdf,xls,zip', 'learndash' ),
							'help_text'    => esc_html__( 'Enter comma-separated list of allowed file extensions: pdf,xls,zip or leave blank for any.', 'learndash' ),
							'default'      => '',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'assignment_upload_limit_size'     => array(
							'name'         => esc_html__( 'Allowed File Size', 'learndash' ),
							'type'         => 'text',
							// translators: placeholder: PHP file upload size.
							'placeholder'  => sprintf( esc_html_x( 'Maximum upload file size: %s', 'placeholder: PHP file upload size', 'learndash' ), ini_get( 'upload_max_filesize' ) ),
							// translators: placeholder: PHP file upload size.
							'help_text'    => sprintf( esc_html_x( 'Enter maximum file upload size. Example: 100KB, 2M, 2MB, 1G. Maximum upload file size: %s', 'placeholder: PHP file upload size', 'learndash' ), ini_get( 'upload_max_filesize' ) ),
							'default'      => '',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
					),
					'default_options'    => array(
						'orderby' => array(
							'name'            => esc_html__( 'Sort By', 'learndash' ),
							'type'            => 'select',
							'initial_options' => array(
								''           => esc_html__( 'Select a choice...', 'learndash' ),
								'title'      => esc_html__( 'Title', 'learndash' ),
								'date'       => esc_html__( 'Date', 'learndash' ),
								'menu_order' => esc_html__( 'Menu Order', 'learndash' ),
							),
							'default'         => 'date',
							'help_text'       => esc_html__( 'Choose the sort order.', 'learndash' ),
						),
						'order'   => array(
							'name'            => esc_html__( 'Sort Direction YYY', 'learndash' ),
							'type'            => 'select',
							'initial_options' => array(
								''     => esc_html__( 'Select a choice...', 'learndash' ),
								'ASC'  => esc_html__( 'Ascending', 'learndash' ),
								'DESC' => esc_html__( 'Descending', 'learndash' ),
							),
							'default'         => 'DESC',
							'help_text'       => esc_html__( 'Choose the sort order.', 'learndash' ),
						),
					),
				),
				'sfwd-quiz'          => array(
					'plugin_name'        => LearnDash_Custom_Label::get_label( 'quiz' ),
					'slug_name'          => LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'quizzes' ),
					'post_type'          => 'sfwd-quiz',
					'template_redirect'  => true,
					'taxonomies'         => $quiz_taxonomies,
					'cpt_options'        => array(
						'has_archive'         => learndash_post_type_has_archive( 'sfwd-quiz' ),
						'hierarchical'        => false,
						'supports'            => array_merge(
							array( 'title', 'editor', 'author', 'page-attributes' ),
							LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_CPT', 'supports' )
						),
						'labels'              => $quiz_labels,
						'capability_type'     => 'course',
						'exclude_from_search' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_CPT', 'include_in_search' ) !== 'yes' ) ? true : false,
						'capabilities'        => $course_capabilities,
						'map_meta_cap'        => true,
						'show_in_rest'        => LearnDash_REST_API::enabled( 'sfwd-quiz' ) || LearnDash_REST_API::gutenberg_enabled( 'sfwd-quiz' ),
					),
					'options_page_title' => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( 'LearnDash %s Settings', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'fields'             => array(
						'quiz_materials'    => array(
							// translators: placeholder: Quiz.
							'name'         => sprintf( esc_html_x( '%s Materials', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
							'type'         => 'textarea',
							// translators: placeholder: Quiz.
							'help_text'    => sprintf( esc_html_x( 'Options for %s materials', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
							'show_in_rest' => LearnDash_REST_API::enabled(),
							'rest_args'    => array(
								'schema' => array(
									'type' => 'html',
								),
							),
						),

						'repeats'           => array(
							'name'      => esc_html__( 'Repeats', 'learndash' ),
							'type'      => 'text',
							// translators: placeholder: quiz.
							'help_text' => sprintf( esc_html_x( 'Number of repeats allowed for %s. Blank = unlimited attempts. 0 = 1 attempt, 1 = 2 attempts, etc.', 'placeholder: quiz', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) ),
							'default'   => '',
						),
						'threshold'         => array(
							'name'         => esc_html__( 'Certificate Threshold', 'learndash' ),
							'type'         => 'text',
							'help_text'    => esc_html__( 'Minimum score required to award a certificate, between 0 and 1 where 1 = 100%.', 'learndash' ),
							'default'      => '0.8',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'passingpercentage' => array(
							'name'         => esc_html__( 'Passing Percentage', 'learndash' ),
							'type'         => 'text',
							// translators: placeholder: quiz.
							'help_text'    => sprintf( esc_html_x( 'Passing percentage required to pass the %s (number only). e.g. 80 for 80%%.', 'placeholder: quiz', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) ),
							'default'      => '80',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'course'            => array(
							// translators: placeholder: Course.
							'name'      => sprintf( esc_html_x( 'Associated %s', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'type'      => 'select',
							'lazy_load' => true,
							// translators: placeholders: Quiz, Course.
							'help_text' => sprintf( esc_html_x( 'Associate this %1$s with a %2$s.', 'placeholders: Quiz, Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ), LearnDash_Custom_Label::get_label( 'course' ) ),
							'default'   => '',
						),
						'lesson'            => array(
							// translators: placeholder: Lesson.
							'name'      => sprintf( esc_html_x( 'Associated %s', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'type'      => 'select',
							// translators: placeholders: Quiz, Lesson.
							'help_text' => sprintf( esc_html_x( 'Associate this %1$s with a %2$s.', 'placeholders: Quiz, Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
							'default'   => '',
						),
						'certificate'       => array(
							'name'         => esc_html__( 'Associated Certificate', 'learndash' ),
							'type'         => 'select',
							// translators: placeholder: quiz.
							'help_text'    => sprintf( esc_html_x( 'Optionally associate a %s with a certificate.', 'placeholder: quiz', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) ),
							'default'      => '',
							'show_in_rest' => LearnDash_REST_API::enabled(),
						),
						'quiz_pro'          => array(
							'name'      => esc_html__( 'Associated Settings', 'learndash' ),
							'type'      => 'select',
							// translators: placeholder: quiz.
							'help_text' => sprintf( esc_html_x( 'If you imported a %s, use this field to select it. Otherwise, create new settings below. After saving or publishing, you will be able to add questions.', 'placeholder: quiz.', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) ) . '<a style="display:none" id="advanced_quiz_preview" class="wpProQuiz_prview" href="#">' . esc_html__( 'Preview', 'learndash' ) . '</a>', // cspell:disable-line.
							'default'   => '',
						),
					),
					'default_options'    => array(),
				),
				'sfwd-question'      => array(
					'plugin_name'        => LearnDash_Custom_Label::get_label( 'question' ),
					'slug_name'          => 'sfwd-question',
					'post_type'          => 'sfwd-question',
					'template_redirect'  => false,
					'taxonomies'         => $question_taxonomies,
					'cpt_options'        => array(
						'public'              => false,
						'hierarchical'        => false,
						'supports'            => array( 'title', 'thumbnail', 'editor', 'author', 'revisions', 'page-attributes' ),
						'labels'              => $question_labels,
						'capability_type'     => 'course',
						'exclude_from_search' => true,
						'show_in_nav_menus'   => false,
						'capabilities'        => $course_capabilities,
						'map_meta_cap'        => true,
						'show_in_rest'        => true,
					),
					'options_page_title' => sprintf(
						// translators: placeholder: Question.
						esc_html_x( 'LearnDash %s Settings', 'placeholder: Question', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'Question' )
					),
					'fields'             => array(
						'quiz' => array(
							// translators: placeholder: Quiz.
							'name'         => sprintf( esc_html_x( 'Associated %s', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
							'type'         => 'select',
							'lazy_load'    => true,
							// translators: placeholders: Question, Quiz.
							'help_text'    => sprintf( esc_html_x( 'Associate this %1$s with a %2$s.', 'placeholder: Question, Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'question' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
							'default'      => '',
							'required'     => true,
							'show_in_rest' => false,
						),

					),
					'default_options'    => array(),
				),
				$exam_post_type_slug => array(
					'plugin_name'        => LearnDash_Custom_Label::get_label( 'exam' ),
					'slug_name'          => $exam_post_type_slug,
					'post_type'          => $exam_post_type_slug,
					'template_redirect'  => true,
					'taxonomies'         => array(),
					'cpt_options'        => array(
						'public'              => true,
						'hierarchical'        => false,
						'has_archive'         => false,
						'supports'            => array( 'title', 'editor', 'custom-fields', 'thumbnail', 'revisions' ),
						'labels'              => $exam_labels,
						'capability_type'     => 'course',
						'exclude_from_search' => true,
						'show_in_nav_menus'   => false,
						'capabilities'        => $course_capabilities,
						'map_meta_cap'        => true,
						'show_in_rest'        => LearnDash_REST_API::enabled( $exam_post_type_slug ) || LearnDash_REST_API::gutenberg_enabled( $exam_post_type_slug ),
						'template'            => array(
							array( 'learndash/ld-exam' ),
						),
					),
					'options_page_title' => sprintf(
						// translators: placeholder: Exam.
						esc_html_x( 'LearnDash %s Settings', 'placeholder: Exam', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'exam' )
					),
					'fields'             => array(),
				),
			);

			$registration_page = LearnDash_Settings_Section::get_section_setting(
				'LearnDash_Settings_Section_Registration_Pages',
				'registration'
			);

			if ( ! empty( $registration_page ) ) {
				$this->post_args[ $coupon_post_type_slug ] = array(
					'plugin_name'        => LearnDash_Custom_Label::get_label( LDLMS_Post_Types::COUPON ),
					'slug_name'          => $coupon_post_type_slug,
					'post_type'          => $coupon_post_type_slug,
					'template_redirect'  => false,
					'cpt_options'        => array(
						'public'              => false,
						'hierarchical'        => false,
						'has_archive'         => false,
						'supports'            => array( 'title' ),
						'labels'              => $coupon_labels,
						'exclude_from_search' => true,
						'show_in_nav_menus'   => false,
						'capabilities'        => learndash_get_admin_coupons_capabilities(),
						'show_in_rest'        => false,
					),
					'options_page_title' => sprintf(
					// translators: placeholder: Coupon.
						esc_html_x( 'LearnDash %s Settings', 'placeholder: Coupon', 'learndash' ),
						LearnDash_Custom_Label::get_label( LDLMS_Post_Types::COUPON )
					),
					'fields'             => array(),
				);
			}

			$cert_defaults = array(
				'shortcode_options' => array(
					'name'    => 'Shortcode Options',
					'type'    => 'html',
					'default' => '',
					'save'    => false,
					'label'   => 'none',
				),
			);

			$certificates_labels = array(
				'name'                     => esc_html_x( 'Certificates', 'Certificates Post Type Label', 'learndash' ),
				'singular_name'            => esc_html_x( 'Certificate', 'Certificates Post Type Singular Name', 'learndash' ),
				'add_new'                  => esc_html_x( 'Add New', 'Add New Certificate Label', 'learndash' ),
				'add_new_item'             => esc_html_x( 'Add New Certificate', 'Add New Item Certificate Label', 'learndash' ),
				'edit_item'                => esc_html_x( 'Edit Certificate', 'Edit Certificate Label', 'learndash' ),
				'new_item'                 => esc_html_x( 'New Certificate', 'Edit Certificate Label', 'learndash' ),
				'all_items'                => esc_html_x( 'Certificates', 'All Certificates Label', 'learndash' ),
				'view_item'                => esc_html_x( 'View Certificate', 'View Certificate Label', 'learndash' ),
				'view_items'               => esc_html_x( 'View Certificates', 'View Certificates Label', 'learndash' ),
				'search_items'             => esc_html_x( 'Search Certificates', 'View Certificates Label', 'learndash' ),
				'not_found'                => esc_html_x( 'No Certificates found', 'No Certificates found Label', 'learndash' ),
				'not_found_in_trash'       => esc_html_x( 'No Certificates found in Trash', 'No Certificates found in Trash Label', 'learndash' ),
				'parent_item_colon'        => '',
				'menu_name'                => esc_html_x( 'Certificates', 'Certificates Menu Label', 'learndash' ),
				'item_published'           => esc_html_x( 'Certificate Published', 'Certificate Published Label', 'learndash' ),
				'item_published_privately' => esc_html_x( 'Certificate Published Privately', 'Certificate Published Privately Label', 'learndash' ),
				'item_reverted_to_draft'   => esc_html_x( 'Certificate Reverted to Draft', 'Certificate Reverted to Draft Label', 'learndash' ),
				'item_scheduled'           => esc_html_x( 'Certificate Scheduled', 'Certificate Scheduled Label', 'learndash' ),
				'item_updated'             => esc_html_x( 'Certificate Updated', 'Certificate Updated Label', 'learndash' ),
			);

			$this->post_args['sfwd-certificates'] = array(
				'plugin_name'        => esc_html__( 'Certificates', 'learndash' ),
				'slug_name'          => 'certificates',
				'post_type'          => 'sfwd-certificates',
				'template_redirect'  => false,
				'fields'             => array(),
				'options_page_title' => esc_html__( 'LearnDash Certificates Options', 'learndash' ),
				'default_options'    => $cert_defaults,
				'cpt_options'        => array(
					'labels'              => $certificates_labels,
					'exclude_from_search' => true,
					'has_archive'         => false,
					'hierarchical'        => false,
					'supports'            => array( 'title', 'editor', 'thumbnail', 'author', 'revisions' ),
					'show_in_nav_menus'   => false,
					'capability_type'     => 'course',
					'capabilities'        => $course_capabilities,
					'map_meta_cap'        => true,
					'show_in_rest'        => false,
				),
			);

			$lcl_group  = LearnDash_Custom_Label::get_label( 'group' );
			$lcl_groups = LearnDash_Custom_Label::get_label( 'groups' );

			$group_labels = array(
				'name'                     => $lcl_groups,
				'singular_name'            => $lcl_group,
				'add_new'                  => esc_html_x( 'Add New', 'Add New Group Label', 'learndash' ),
				// translators: placeholder: Group.
				'add_new_item'             => sprintf( esc_html_x( 'Add New %s', 'placeholder: Group', 'learndash' ), $lcl_group ),
				// translators: placeholder: Group.
				'edit_item'                => sprintf( esc_html_x( 'Edit %s', 'placeholder: Group', 'learndash' ), $lcl_group ),
				// translators: placeholder: Group.
				'new_item'                 => sprintf( esc_html_x( 'New %s', 'placeholder: Group', 'learndash' ), $lcl_group ),
				'all_items'                => $lcl_groups,
				// translators: placeholder: Group.
				'view_item'                => sprintf( esc_html_x( 'View %s', 'placeholder: Group', 'learndash' ), $lcl_group ),
				// translators: placeholder: Groups.
				'view_items'               => sprintf( esc_html_x( 'View %s', 'placeholder: Groups', 'learndash' ), $lcl_groups ),
				// translators: placeholder: Groups.
				'search_items'             => sprintf( esc_html_x( 'Search %s', 'placeholder: Groups', 'learndash' ), $lcl_groups ),
				// translators: placeholder: Groups.
				'not_found'                => sprintf( esc_html_x( 'No %s found', 'placeholder: Groups', 'learndash' ), $lcl_groups ),
				// translators: placeholder: Groups.
				'not_found_in_trash'       => sprintf( esc_html_x( 'No %s found in Trash', 'placeholder: Groups', 'learndash' ), $lcl_groups ),
				'parent_item_colon'        => '',
				'menu_name'                => $lcl_groups,
				// translators: placeholder: Group.
				'item_published'           => sprintf( esc_html_x( '%s Published', 'placeholder: Group', 'learndash' ), $lcl_group ),
				// translators: placeholder: Group.
				'item_published_privately' => sprintf( esc_html_x( '%s Published Privately', 'placeholder: Group', 'learndash' ), $lcl_group ),
				// translators: placeholder: Group.
				'item_reverted_to_draft'   => sprintf( esc_html_x( '%s Reverted to Draft', 'placeholder: Group', 'learndash' ), $lcl_group ),
				// translators: placeholder: Group.
				'item_scheduled'           => sprintf( esc_html_x( '%s Scheduled', 'placeholder: Group', 'learndash' ), $lcl_group ),
				// translators: placeholder: Group.
				'item_updated'             => sprintf( esc_html_x( '%s Updated', 'placeholder: Group', 'learndash' ), $lcl_group ),
			);

			$group_taxonomies = array();
			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_Taxonomies', 'wp_post_category' ) == 'yes' ) {
				$group_taxonomies['category'] = 'category';
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_Taxonomies', 'wp_post_tag' ) == 'yes' ) {
				$group_taxonomies['post_tag'] = 'post_tag';
			}

			/**
			 * Filter Taxonomy Capability.
			 *
			 * @since 3.2.0
			 *
			 * @param array  $taxonomy_capability Array of taxonomy capabilities.
			 * @param string $post_type           Post Type slug.
			 */
			$group_taxonomy_capability = apply_filters(
				'learndash_taxonomy_capabilities',
				array(
					'manage_terms' => 'manage_terms_group_categories',
					'edit_terms'   => 'edit_terms_group_categories',
					'delete_terms' => 'delete_terms_group_categories',
					'assign_terms' => 'assign_terms_group_categories',
				),
				learndash_get_post_type_slug( 'group' )
			);

			$group_taxonomies_public = ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_CPT', 'public' ) === 'yes' ) ? true : false;

			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_Taxonomies', 'ld_group_category' ) ) {
				$group_taxonomies['ld_group_category'] = array(
					'public'            => $group_taxonomies_public,
					'hierarchical'      => true,
					'show_ui'           => true,
					'show_in_menu'      => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_group_category'] ),
					'capabilities'      => $group_taxonomy_capability,
					'show_in_rest'      => LearnDash_REST_API::enabled( 'groups' ) || LearnDash_REST_API::gutenberg_enabled( 'groups' ),
					'labels'            => array(
						// translators: placeholder: Group.
						'name'              => sprintf( esc_html_x( '%s Categories', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'singular_name'     => sprintf( esc_html_x( '%s Category', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'search_items'      => sprintf( esc_html_x( 'Search %s Categories', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'all_items'         => sprintf( esc_html_x( 'All %s Categories', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Category', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Category:', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Category', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'update_item'       => sprintf( esc_html_x( 'Update %s Category', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Category', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'new_item_name'     => sprintf( esc_html_x( 'New %s Category Name', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'menu_name'         => sprintf( esc_html_x( '%s Categories', 'placeholder: Group', 'learndash' ), $lcl_group ),
					),
				);
			}

			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_Taxonomies', 'ld_group_tag' ) ) {
				$group_taxonomies['ld_group_tag'] = array(
					'public'            => $group_taxonomies_public,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_in_menu'      => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'rewrite'           => array( 'slug' => $learndash_settings_permalinks_taxonomies['ld_group_tag'] ),
					'capabilities'      => $group_taxonomy_capability,
					'show_in_rest'      => LearnDash_REST_API::enabled( 'groups' ) || LearnDash_REST_API::gutenberg_enabled( 'groups' ),
					'labels'            => array(
						// translators: placeholder: Group.
						'name'              => sprintf( esc_html_x( '%s Tags', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'singular_name'     => sprintf( esc_html_x( '%s Tag', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'search_items'      => sprintf( esc_html_x( 'Search %s Tag', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'all_items'         => sprintf( esc_html_x( 'All %s Tags', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'parent_item'       => sprintf( esc_html_x( 'Parent %s Tag', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'parent_item_colon' => sprintf( esc_html_x( 'Parent %s Tag:', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'edit_item'         => sprintf( esc_html_x( 'Edit %s Tag', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'update_item'       => sprintf( esc_html_x( 'Update %s Tag', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'add_new_item'      => sprintf( esc_html_x( 'Add New %s Tag', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'new_item_name'     => sprintf( esc_html_x( 'New %s Tag Name', 'placeholder: Group', 'learndash' ), $lcl_group ),
						// translators: placeholder: Group.
						'menu_name'         => sprintf( esc_html_x( '%s Tags', 'placeholder: Group', 'learndash' ), $lcl_group ),
					),
				);
			}

			$group_capabilities = learndash_get_admin_groups_capabilities();

			if ( is_admin() ) {
				$admin_role = get_role( 'administrator' );
				if ( ( $admin_role ) && ( is_a( $admin_role, 'WP_Role' ) ) ) {
					foreach ( $group_capabilities as $key => $cap ) {
						$admin_role->add_cap( $cap, true );
					}

					foreach ( $group_taxonomies as $tax_key => $tax_set ) {
						if ( in_array( $tax_key, array( 'category', 'post_tag' ), true ) ) {
							continue;
						}
						if ( ( is_array( $tax_set ) ) && ( ! empty( $tax_set['capabilities'] ) ) ) {
							foreach ( $tax_set['capabilities'] as $key => $cap ) {
								$admin_role->add_cap( $cap, true );
							}
						}
					}
				}
			}

			$this->post_args['groups'] = array(
				'plugin_name'       => LearnDash_Custom_Label::get_label( 'group' ),
				'slug_name'         => LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'groups' ),
				'post_type'         => 'groups',
				'template_redirect' => true,
				'taxonomies'        => $group_taxonomies,
				'cpt_options'       => array(
					'supports'            => array_merge(
						array( 'title', 'editor', 'author' ),
						LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_CPT', 'supports' )
					),
					'has_archive'         => learndash_post_type_has_archive( 'groups' ),
					'labels'              => $group_labels,
					'capability_type'     => 'groups',
					'hierarchical'        => learndash_is_groups_hierarchical_enabled(),
					'public'              => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_CPT', 'public' ) === 'yes' ) ? true : false,
					'exclude_from_search' => ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_CPT', 'include_in_search' ) !== 'yes' ) ? true : false,
					'capabilities'        => $group_capabilities,
					'map_meta_cap'        => true,
					'show_in_rest'        => LearnDash_REST_API::enabled( 'groups' ) || LearnDash_REST_API::gutenberg_enabled( 'groups' ),
				),
				'default_options'   => array(),
				'fields'            => array(),
			);

			if ( ( has_filter( 'learndash_post_args_groups' ) ) || ( has_filter( 'learndash-cpt-options' ) ) ) {
				$group_args                = $this->post_args['groups']['cpt_options'];
				$group_args['description'] = $this->post_args['groups']['plugin_name'];

				/**
				 * Filters the post type registration arguments.
				 *
				 * @param array $group_args Post type arguments.
				 */
				if ( has_filter( 'learndash_post_args_groups' ) ) {
					$group_args = apply_filters_deprecated( 'learndash_post_args_groups', array( $group_args, 'groups' ), '3.1.7', 'learndash_post_args' );
				}

				/** This filter is documented in includes/ld-assignment-uploads.php */
				$group_args = apply_filters( 'learndash-cpt-options', $group_args, 'groups' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Better to keep it this way for now.

				if ( isset( $group_args['description'] ) ) {
					if ( $group_args['description'] !== $this->post_args['groups']['plugin_name'] ) {
						$this->post_args['groups']['plugin_name'] = $group_args['description'];
					}
					unset( $group_args['description'] );
				}
				$this->post_args['groups']['cpt_options'] = $group_args;
			}

			if ( learndash_is_admin_user() ) {
				$this->post_args['sfwd-transactions'] = array(
					'plugin_name'        => esc_html__( 'Transactions', 'learndash' ),
					'slug_name'          => 'transactions',
					'post_type'          => 'sfwd-transactions',
					'template_redirect'  => false,
					'options_page_title' => esc_html__( 'LearnDash Transactions Options', 'learndash' ),
					'cpt_options'        => array(
						'supports'            => array( 'title', 'custom-fields', 'page-attributes' ),
						'exclude_from_search' => true,
						'publicly_queryable'  => false,
						'show_in_nav_menus'   => false,
						'show_in_admin_bar'   => false,
						'hierarchical'        => true,
					),
					'fields'             => array(),
					'default_options'    => array(
						null => array(
							'type'    => 'html',
							'save'    => false,
							'default' => esc_html__( 'Click the Export button below to export the transaction list.', 'learndash' ),
						),
					),
				);

				add_action( 'admin_init', array( $this, 'trans_export_init' ) );
			}

			// Added in v2.5.4 to hide the lesson, topic and quiz post type from nav menu when shared steps enabled.
			if ( learndash_is_course_shared_steps_enabled() ) {
				$this->post_args['sfwd-lessons']['cpt_options']['show_in_nav_menus'] = false;
				$this->post_args['sfwd-topic']['cpt_options']['show_in_nav_menus']   = false;
				$this->post_args['sfwd-quiz']['cpt_options']['show_in_nav_menus']    = false;
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
				if ( isset( $this->post_args['sfwd-courses']['fields']['course_lesson_orderby'] ) ) {
					unset( $this->post_args['sfwd-courses']['fields']['course_lesson_orderby'] );
				}
				if ( isset( $this->post_args['sfwd-courses']['fields']['course_lesson_order'] ) ) {
					unset( $this->post_args['sfwd-courses']['fields']['course_lesson_order'] );
				}
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'shared_questions' ) === 'yes' ) {
				if ( isset( $this->post_args['sfwd-question']['fields']['quiz'] ) ) {
					unset( $this->post_args['sfwd-question']['fields']['quiz'] );
				}
			}

			// Remove the filter to prevent Course Grid from adding a 'Short Description' field to the legacy metabox.
			// See CG-118.
			remove_filter( 'learndash_post_args', 'learndash_course_grid_post_args' );

			/** This filter is documented in includes/class-ld-lms.php */
			$this->post_args = apply_filters( 'learndash_post_args', $this->post_args );

			add_action( 'admin_init', array( $this, 'quiz_export_init' ) );
			add_action( 'admin_init', array( $this, 'course_export_init' ) );

			foreach ( $this->post_args as $p ) {
				$this->post_types[ $p['post_type'] ] = new SFWD_CPT_Instance( $p );
			}

			add_action( 'init', array( $this, 'tax_registration' ), 11 );

			$sfwd_question   = $this->post_types['sfwd-question'];
			$question_prefix = $sfwd_question->get_prefix();
			add_filter( "{$question_prefix}display_settings", array( $this, 'question_display_settings' ), 10, 3 );
		}

		/**
		 * Returns output of users course information for bottom of profile
		 *
		 * @since 2.1.0
		 *
		 * @param  int   $user_id  user id.
		 * @param  array $atts     Attributes.
		 * @return string|array  Output of course information
		 */
		public static function get_course_info( $user_id, $atts = array() ) {

			/**
			 * Filters course list shortcode attribute defaults.
			 *
			 * @param array $shortcode_default An array of default shortcode attributes.
			 */
			$atts_defaults = apply_filters(
				'learndash_ld_course_list_shortcode_defaults',
				array(
					'return'                    => false, // Set to true to return the array data instead of calling the template for output.
					// This function essentially produces the output of three sections. Registered Courses,
					// Course Progress and Quiz Attempts. This parameters lets us control which section to
					// return or all.
					'type'                      => array( 'registered', 'course', 'quiz' ),

					// Defaults.
					'num'                       => LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' ),
					'orderby'                   => 'ID',
					'order'                     => 'ASC',
					'group_id'                  => null,

					// Registered Courses.
					'registered_num'            => false,
					'registered_show_thumbnail' => 'true',
					'registered_orderby'        => 'title',
					'registered_order'          => 'ASC',

					// Course Progress.
					'progress_num'              => false,
					'progress_orderby'          => 'title',
					'progress_order'            => 'ASC',

					// Quizzes.
					'quiz_num'                  => false,
					'quiz_filter_quiz'          => null,
					'quiz_filter_course'        => null,
					'quiz_filter_lesson'        => null,
					'quiz_filter_topic'         => null,
					'quiz_orderby'              => 'taken',
					'quiz_order'                => 'DESC',
				)
			);

			$atts = shortcode_atts( $atts_defaults, $atts );

			if ( ! empty( $atts['type'] ) ) {
				if ( is_string( $atts['type'] ) ) {
					$atts['type'] = explode( ',', $atts['type'] );
				}
				$atts['type'] = array_map( 'trim', $atts['type'] );
			}

			if ( ! empty( $atts['group_id'] ) ) {
				$atts['course_ids'] = learndash_group_enrolled_courses( $atts['group_id'] );
				$atts['quiz_ids']   = learndash_get_group_course_quiz_ids( $atts['group_id'] );
			} else {
				$atts['course_ids'] = null;
				$atts['quiz_ids']   = null;
			}

			if ( ! is_null( $atts['course_ids'] ) ) {
				if ( is_string( $atts['course_ids'] ) ) {
					$atts['course_ids'] = explode( ',', $atts['course_ids'] );
				}
				$atts['course_ids'] = array_map( 'trim', $atts['course_ids'] );
			}

			if ( ! is_null( $atts['quiz_ids'] ) ) {
				if ( is_string( $atts['quiz_ids'] ) ) {
					$atts['quiz_ids'] = explode( ',', $atts['quiz_ids'] );
				}
				$atts['quiz_ids'] = array_map( 'trim', $atts['quiz_ids'] );
			}

			if ( ! is_null( $atts['course_ids'] ) ) {
				$courses_registered_all = $atts['course_ids'];
			} else {
				$courses_registered_all = ld_get_mycourses( $user_id );
			}

			$courses_registered       = array();
			$courses_registered_pager = array();
			if ( in_array( 'registered', $atts['type'], true ) ) {

				if ( empty( $atts['registered_show_thumbnail'] ) ) {
					$atts['registered_show_thumbnail'] = $atts_defaults['registered_show_thumbnail'];
				}

				if ( ! empty( $courses_registered_all ) ) {
					if ( false === $atts['registered_num'] ) {
						$atts['registered_num'] = intval( $atts_defaults['num'] );
					} else {
						$atts['registered_num'] = intval( $atts['registered_num'] );
					}

					if ( ( ! isset( $atts['registered_orderby'] ) ) || ( empty( $atts['registered_orderby'] ) ) ) {
						$atts['registered_orderby'] = $atts_defaults['registered_orderby'];
					}

					if ( ( ! isset( $atts['registered_order'] ) ) || ( empty( $atts['registered_order'] ) ) ) {
						$atts['registered_order'] = $atts_defaults['registered_order'];
					}

					$courses_registered_query_args = array(
						'post_type' => 'sfwd-courses',
						'fields'    => 'ids',
						'orderby'   => $atts['registered_orderby'],
						'order'     => $atts['registered_order'],
						'post__in'  => $courses_registered_all,
					);

					/**
					 * Filters value of course information per page.
					 *
					 * @param int    $info_per_page Course info per page.
					 * @param string $context       The context of course info.
					 * @param int    $user_id       User ID.
					 * @param array  $atts          An array of shortcode attributes.
					 */
					$courses_registered_per_page = apply_filters( 'learndash_course_info_per_page', intval( $atts['registered_num'] ), 'registered', $user_id, $atts );
					if ( intval( $courses_registered_per_page ) > 0 ) {
						$courses_registered_query_args['posts_per_page'] = intval( $courses_registered_per_page );
						/**
						 * Filters paged query argument for course info.
						 *
						 * @param int    $paged   Number of Pages.
						 * @param string $context The context of course info.
						 */
						$courses_registered_query_args['paged'] = apply_filters( 'learndash_course_info_paged', 1, 'registered' );
					} else {
						$courses_registered_query_args['nopaging'] = true;
					}

					/**
					 * Filters query arguments for courses registered.
					 *
					 * @param array  $courses_registered_query_args An array of courses registered query arguments.
					 * @param string $context                       The context of course info.
					 * @param int    $user_id                       User ID.
					 * @param array  $atts                          An array of shortcode attributes.
					 */
					$courses_registered_query_args = apply_filters( 'learndash_course_info_query_args', $courses_registered_query_args, 'registered', $user_id, $atts );
					if ( ! empty( $courses_registered_query_args ) ) {
						$course_registered_query = new WP_Query( $courses_registered_query_args );
						if ( ( ! empty( $course_registered_query->posts ) ) ) {
							$courses_registered = $course_registered_query->posts;

							if ( isset( $course_registered_query->query_vars['paged'] ) ) {
								$courses_registered_pager['paged'] = $course_registered_query->query_vars['paged'];
							} else {
								$courses_registered_pager['paged'] = $courses_registered_query_args['paged'];
							}

							$courses_registered_pager['total_items'] = $course_registered_query->found_posts;
							$courses_registered_pager['total_pages'] = $course_registered_query->max_num_pages;
						} else {
							$courses_registered = array();
						}
					} else {
						$courses_registered = array();
					}
				}
			}

			$course_progress       = array();
			$course_progress_pager = array();

			if ( in_array( 'course', $atts['type'], true ) ) {

				$usermeta        = get_user_meta( $user_id, '_sfwd-course_progress', true );
				$course_progress = empty( $usermeta ) ? array() : $usermeta;

				if ( ! is_null( $atts['course_ids'] ) ) {
					$course_progress_tmp = array();
					foreach ( $atts['course_ids'] as $course_id ) {
						if ( isset( $course_progress[ $course_id ] ) ) {
							$course_progress_tmp[ $course_id ] = $course_progress[ $course_id ];
						}
					}
					$course_progress     = $course_progress_tmp;
					$course_progress_ids = array_keys( $course_progress );

				} else {
					$course_progress_ids = array_merge( $courses_registered_all, array_keys( $course_progress ) );

					/**
					 * Filters expired courses from course info query
					 *
					 * @since 3.5.0
					 *
					 * @param bool  $include    Whether to include the expired courses or not ( default: true )
					 * @param int   $user_id    User ID
					 */
					if ( true !== apply_filters( 'learndash_user_courseinfo_courses_include_expired', true, $user_id ) ) {
						$course_progress_ids = array_diff( $course_progress_ids, learndash_get_expired_user_courses_from_meta( $user_id ) );
					}
				}

				// The course_info_shortcode.php template is driven be the $courses_registered array.
				// We want to make sure we show ALL the courses from both the $courses_registered and
				// the course_progress. Also we want to run through WP_Query so we can ensure they still
				// exist as valid posts AND we want to sort these by title
				// $courses_registered = array_merge( $courses_registered, array_keys( $course_progress ) );.
				if ( ! empty( $course_progress_ids ) ) {

					if ( false === $atts['progress_num'] ) {
						$atts['progress_num'] = intval( $atts_defaults['num'] );
					} else {
						$atts['progress_num'] = intval( $atts['progress_num'] );
					}

					if ( ( ! isset( $atts['progress_orderby'] ) ) || ( empty( $atts['progress_orderby'] ) ) ) {
						$atts['progress_orderby'] = $atts_defaults['progress_orderby'];
					}

					if ( ( ! isset( $atts['progress_order'] ) ) || ( empty( $atts['progress_order'] ) ) ) {
						$atts['progress_order'] = $atts_defaults['progress_order'];
					}

					$course_progress_query_args = array(
						'post_type' => 'sfwd-courses',
						'fields'    => 'ids',
						'orderby'   => $atts['progress_orderby'],
						'order'     => $atts['progress_order'],
						'post__in'  => $course_progress_ids,
					);

					/** This filter is documented in includes/class-ld-lms.php */
					$courses_per_page = apply_filters( 'learndash_course_info_per_page', intval( $atts['progress_num'] ), 'courses', $user_id, $atts );
					if ( intval( $courses_per_page ) > 0 ) {
						$course_progress_query_args['posts_per_page'] = intval( $courses_per_page );

						/** This filter is documented in includes/class-ld-lms.php */
						$course_progress_query_args['paged'] = apply_filters( 'learndash_course_info_paged', 1, 'courses' );
					} else {
						$course_progress_query_args['nopaging'] = true;
					}
					/** This filter is documented in includes/class-ld-lms.php */
					$course_progress_query_args = apply_filters( 'learndash_course_info_query_args', $course_progress_query_args, 'courses', $user_id, $atts );

					if ( ! empty( $course_progress_query_args ) ) {
						$course_progress_query = new WP_Query( $course_progress_query_args );

						if ( ( ! empty( $course_progress_query->posts ) ) ) {
							$course_p        = $course_progress;
							$course_progress = array();
							foreach ( $course_progress_query->posts as $course_id ) {
								if ( isset( $course_p[ $course_id ] ) ) {
									$course_progress[ $course_id ] = $course_p[ $course_id ];
								} else {
									$course_progress[ $course_id ] = array();
								}
							}

							$course_progress_pager = array();
							if ( isset( $course_progress_query->query_vars['paged'] ) ) {
								$course_progress_pager['paged'] = $course_progress_query->query_vars['paged'];
							} else {
								$course_progress_pager['paged'] = $course_progress_query_args['paged'];
							}

							$course_progress_pager['total_items'] = $course_progress_query->found_posts;
							$course_progress_pager['total_pages'] = $course_progress_query->max_num_pages;
						}
					} else {
						$course_progress       = array();
						$course_progress_pager = array();
					}
				}
			}

			$quizzes       = array();
			$quizzes_pager = array();
			if ( in_array( 'quiz', $atts['type'], true ) ) {

				$usermeta = get_user_meta( $user_id, '_sfwd-quizzes', true );
				$quizzes  = empty( $usermeta ) ? false : $usermeta;

				// We need to re-query the quiz (posts). This is partly to validate the listing. We don't
				// want to pass old or outdated quiz items to externals.
				if ( ! empty( $quizzes ) ) {

					if ( false === $atts['quiz_num'] ) {
						$atts['quiz_num'] = intval( $atts_defaults['num'] );
					} else {
						$atts['quiz_num'] = intval( $atts['quiz_num'] );
					}

					if ( ( ! isset( $atts['quiz_orderby'] ) ) || ( empty( $atts['quiz_orderby'] ) ) ) {
						$atts['quiz_orderby'] = $atts_defaults['quiz_orderby'];
					}

					if ( ( ! isset( $atts['quiz_order'] ) ) || ( empty( $atts['quiz_order'] ) ) ) {
						$atts['quiz_order'] = $atts_defaults['quiz_order'];
					}

					if ( ! is_null( $atts['quiz_ids'] ) ) {
						$quiz_ids = $atts['quiz_ids'];
					} elseif ( ! is_null( $atts['quiz_filter_quiz'] ) ) {
						$quiz_ids = $atts['quiz_filter_quiz'];
					} else {
						$quiz_ids = wp_list_pluck( (array) $quizzes, 'quiz' );
					}

					if ( ! empty( $quiz_ids ) ) {
						if ( ! is_array( $quiz_ids ) ) {
							$quiz_ids = explode( ',', $quiz_ids );
						}
						$quiz_ids = array_map( 'absint', $quiz_ids );
					}

					if ( ! empty( $atts['quiz_filter_course'] ) ) {
						if ( ! is_array( $atts['quiz_filter_course'] ) ) {
							$atts['quiz_filter_course'] = explode( ',', $atts['quiz_filter_course'] );
						}
						$atts['quiz_filter_course'] = array_map( 'absint', $atts['quiz_filter_course'] );
					}

					if ( ! empty( $atts['quiz_filter_lesson'] ) ) {
						if ( ! is_array( $atts['quiz_filter_lesson'] ) ) {
							$atts['quiz_filter_lesson'] = explode( ',', $atts['quiz_filter_lesson'] );
						}
						$atts['quiz_filter_lesson'] = array_map( 'absint', $atts['quiz_filter_lesson'] );
					}

					if ( ! empty( $atts['quiz_filter_topic'] ) ) {
						if ( ! is_array( $atts['quiz_filter_topic'] ) ) {
							$atts['quiz_filter_topic'] = explode( ',', $atts['quiz_filter_topic'] );
						}
						$atts['quiz_filter_topic'] = array_map( 'absint', $atts['quiz_filter_topic'] );
					}

					$quiz_total_query_args = array(
						'post_type' => 'sfwd-quiz',
						'fields'    => 'ids',
						'orderby'   => 'title',
						'order'     => 'ASC',
						'nopaging'  => true,
						'post__in'  => $quiz_ids,
					);

					if ( 'taken' === $atts['quiz_orderby'] ) {
						$quiz_total_query_args['orderby'] = 'title';
					}

					$quiz_query = new WP_Query( $quiz_total_query_args );
					if ( is_a( $quiz_query, 'WP_Query' ) ) {
						if ( ( property_exists( $quiz_query, 'posts' ) ) && ( ! empty( $quiz_query->posts ) ) ) {
							$quizzes_tmp = array();
							foreach ( $quiz_query->posts as $post_idx => $quiz_id ) {
								foreach ( $quizzes as $quiz_idx => $quiz_attempt ) {
									if ( (int) $quiz_attempt['quiz'] == (int) $quiz_id ) {
										if ( ! empty( $atts['quiz_filter_course'] ) ) {
											if ( ( ! isset( $quiz_attempt['course'] ) ) || ( empty( $quiz_attempt['course'] ) ) ) {
												continue;
											}
											if ( ! in_array( absint( $quiz_attempt['course'] ), $atts['quiz_filter_course'] ) ) {
												continue;
											}
										}

										if ( ! empty( $atts['quiz_filter_lesson'] ) ) {
											if ( ( ! isset( $quiz_attempt['lesson'] ) ) || ( empty( $quiz_attempt['lesson'] ) ) ) {
												continue;
											}
											if ( ! in_array( absint( $quiz_attempt['lesson'] ), $atts['quiz_filter_lesson'] ) ) {
												continue;
											}
										}

										if ( ! empty( $atts['quiz_filter_topic'] ) ) {
											if ( ( ! isset( $quiz_attempt['topic'] ) ) || ( empty( $quiz_attempt['topic'] ) ) ) {
												continue;
											}
											if ( ! in_array( absint( $quiz_attempt['topic'] ), $atts['quiz_filter_topic'] ) ) {
												continue;
											}
										}

										if ( 'taken' === $atts['quiz_orderby'] ) {
											$quiz_key = $quiz_attempt['time'] . '-' . $quiz_attempt['quiz'];
										} elseif ( 'title' == $atts['quiz_orderby'] ) {
											$quiz_key = $post_idx . '-' . $quiz_attempt['time'];
										} elseif ( 'ID' == $atts['quiz_orderby'] ) {
											$quiz_key = str_pad( (string) $quiz_attempt['quiz'], 10, '0', STR_PAD_LEFT ) . '-' . $quiz_attempt['time'];
										} elseif ( 'date' == $atts['quiz_orderby'] ) { // Quiz Post date.
											$quiz_post = get_post( $quiz_attempt['quiz'] );
											if ( is_a( $quiz_post, 'WP_Post' ) ) {
												$quiz_key = $quiz_post->post_date . '-' . $quiz_attempt['time'];
											} else {
												$quiz_key = $post_idx . '-' . $quiz_attempt['time'];
											}
										} elseif ( 'menu_order' == $atts['quiz_orderby'] ) { // Quiz Post menu_order.
											$quiz_post = get_post( $quiz_attempt['quiz'] );
											if ( is_a( $quiz_post, 'WP_Post' ) ) {
												$quiz_key = $quiz_post->menu_order . '-' . $quiz_attempt['time'];
											} else {
												$quiz_key = $post_idx . '-' . $quiz_attempt['time'];
											}
										}
										if ( ! empty( $quiz_key ) ) {
											$quizzes_tmp[ $quiz_key ] = $quiz_attempt;
											unset( $quizzes[ $quiz_idx ] );
										}
									}
								}
							}

							$quizzes = $quizzes_tmp;

							if ( 'DESC' == $atts['quiz_order'] ) {
								krsort( $quizzes );
							} else {
								ksort( $quizzes );
							}

							/**
							 * Filters value of quiz information per page.
							 *
							 * @param int    $info_per_page Quiz info per page.
							 * @param string $context       The context of course info.
							 * @param int    $user_id       User ID.
							 */
							$quizzes_per_page = apply_filters( 'learndash_quiz_info_per_page', $atts['quiz_num'], 'quizzes', $user_id );
							if ( $quizzes_per_page > 0 ) {

								/**
								 * Filters paged query argument for quiz info.
								 *
								 * @param int $paged Number of Pages.
								 */
								$quizzes_pager['paged']       = apply_filters( 'learndash_quiz_info_paged', 1 );
								$quizzes_pager['total_items'] = count( $quizzes );
								$quizzes_pager['total_pages'] = ceil( count( $quizzes ) / $quizzes_per_page );

								$quizzes = array_slice( $quizzes, ( $quizzes_pager['paged'] * $quizzes_per_page ) - $quizzes_per_page, $quizzes_per_page, false );
							}
						}
					}
				}
			}

			/**
			 * Filter Courses and Quizzes is showing the Group Admin > Report page
			 * IF we are viewing the group_admin_page we want to filter the Courses and Quizzes listing
			 * to only include those items related to the Group
			 *
			 * @since 2.3.0
			 */
			global $pagenow;
			if ( ( ! empty( $pagenow ) ) && ( 'admin.php' === $pagenow ) ) {
				if ( ( isset( $_GET['page'] ) ) && ( 'group_admin_page' == $_GET['page'] ) ) {
					if ( ( isset( $_GET['group_id'] ) ) && ( ! empty( $_GET['group_id'] ) ) ) {
						$group_id = intval( $_GET['group_id'] );

						if ( ( isset( $_GET['user_id'] ) ) && ( ! empty( $_GET['user_id'] ) ) ) {
							$user_id = intval( $_GET['user_id'] );

							if ( learndash_is_group_leader_of_user( get_current_user_id(), $user_id ) ) {
								if ( learndash_is_user_in_group( intval( $_GET['user_id'] ), intval( $_GET['group_id'] ) ) ) {
									if ( isset( $_POST['learndash_course_points'] ) ) {
										update_user_meta( $user_id, 'course_points', intval( $_POST['learndash_course_points'] ) );
									}
								}
							}
						}
					}
				}
			}

			if ( ! empty( $atts['return'] ) ) {
				return array(
					'user_id'                  => $user_id,
					'courses_registered'       => $courses_registered,
					'courses_registered_pager' => $courses_registered_pager,
					'course_progress'          => $course_progress,
					'course_progress_pager'    => $course_progress_pager,
					'quizzes'                  => $quizzes,
					'quizzes_pager'            => $quizzes_pager,
				);
			} else {

				if ( is_admin() ) {
					if ( ! empty( $pagenow ) ) {
						if ( ( 'profile.php' === $pagenow ) || ( 'user-edit.php' === $pagenow ) ) {
							$atts['pagenow']       = $pagenow;
							$atts['pagenow_nonce'] = wp_create_nonce( $pagenow . '-' . $user_id );
						} elseif ( ( 'admin.php' === $pagenow ) && ( isset( $_GET['page'] ) ) && ( 'group_admin_page' == $_GET['page'] ) ) {
							$atts['pagenow'] = esc_attr( $_GET['page'] );

							if ( ( isset( $_GET['group_id'] ) ) && ( ! empty( $_GET['group_id'] ) ) ) {
								$atts['group_id'] = intval( $_GET['group_id'] );
							} else {
								$atts['group_id'] = 0;
							}
							$atts['pagenow_nonce'] = wp_create_nonce( esc_attr( $_GET['page'] ) . '-' . $atts['group_id'] . '-' . $user_id );
						} else {
							$atts['pagenow']       = 'learndash';
							$atts['pagenow_nonce'] = wp_create_nonce( $atts['pagenow'] . '-' . $user_id );
						}
					}
				} else {
					$atts['pagenow']       = 'learndash';
					$atts['pagenow_nonce'] = wp_create_nonce( $atts['pagenow'] . '-' . $user_id );
				}
				$atts['user_id'] = $user_id;

				unset( $atts['course_ids'] );
				unset( $atts['quiz_ids'] );

				return self::get_template(
					'course_info_shortcode',
					array(
						'user_id'                  => $user_id,
						'courses_registered'       => $courses_registered,
						'courses_registered_pager' => $courses_registered_pager,
						'course_progress'          => $course_progress,
						'course_progress_pager'    => $course_progress_pager,
						'quizzes'                  => $quizzes,
						'quizzes_pager'            => $quizzes_pager,
						'shortcode_atts'           => $atts,
					)
				);
			}
		}

		/**
		 * Updates course price billy cycle on save
		 * Fires on action 'save_post'
		 *
		 * @since 2.1.0
		 *
		 * @param int    $post_id Post ID for save.
		 * @param object $post    WP_Post object for save.
		 * @param bool   $update  If save is update (true).
		 */
		public function learndash_course_price_billing_cycle_save( $post_id, $post, $update = false ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( empty( $post_id ) || empty( $_POST['post_type'] ) ) {
				return '';
			}

			// Check permissions.
			if ( 'page' == $_POST['post_type'] ) {
				if ( ! current_user_can( 'edit_page', $post_id ) ) {
					return;
				}
			} else {
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return;
				}
			}

			if ( in_array( $post->post_type, array( learndash_get_post_type_slug( 'course' ), learndash_get_post_type_slug( 'group' ) ), true ) ) {

				if ( learndash_get_post_type_slug( 'course' ) === $post->post_type ) {
					$settings_prefix = 'course';
				} elseif ( learndash_get_post_type_slug( 'group' ) === $post->post_type ) {
					$settings_prefix = 'group';
				} else {
					// For phpstan check.
					return;
				}

				$price_billing_t3 = '';
				$price_billing_p3 = '';

				if ( isset( $_POST[ $settings_prefix . '_price_billing_t3' ] ) ) {
					$price_billing_t3 = strtoupper( esc_attr( $_POST[ $settings_prefix . '_price_billing_t3' ] ) );
					$price_billing_t3 = learndash_billing_cycle_field_frequency_validate( $price_billing_t3 );
				}

				if ( isset( $_POST[ $settings_prefix . '_price_billing_p3' ] ) ) {
					$price_billing_p3 = absint( $_POST[ $settings_prefix . '_price_billing_p3' ] );
					$price_billing_p3 = learndash_billing_cycle_field_interval_validate( $price_billing_p3, $price_billing_t3 );
				}

				if ( ( ! empty( $price_billing_t3 ) ) && ( ! empty( $price_billing_p3 ) ) ) {
					update_post_meta( $post_id, $settings_prefix . '_price_billing_p3', $price_billing_p3 );
					update_post_meta( $post_id, $settings_prefix . '_price_billing_t3', $price_billing_t3 );
				} else {
					delete_post_meta( $post_id, $settings_prefix . '_price_billing_p3' );
					delete_post_meta( $post_id, $settings_prefix . '_price_billing_t3' );
				}
			}
		}

		/**
		 * Billing Cycle field html output for courses
		 *
		 * @since 2.1.0
		 *
		 * @return string
		 */
		public function learndash_course_price_billing_cycle_html() {
			return learndash_billing_cycle_setting_field_html();
		}

		/**
		 * Course progress data
		 *
		 * @param int $course_id Course ID.
		 */
		public static function course_progress_data( $course_id = null ) {
			set_time_limit( 0 );
			global $wpdb;

			$current_user = wp_get_current_user();
			if ( ( ! learndash_is_admin_user( $current_user->ID ) ) && ( ! learndash_is_group_leader_user( $current_user->ID ) ) ) {
				return;
			}

			$group_id = 0;
			if ( isset( $_GET['group_id'] ) ) {
				$group_id = $_GET['group_id'];
			}

			if ( learndash_is_group_leader_user( $current_user->ID ) ) {

				$users_group_ids = learndash_get_administrators_group_ids( $current_user->ID );
				if ( ! count( $users_group_ids ) ) {
					return array();
				}

				if ( ! empty( $group_id ) ) {
					if ( ! in_array( $group_id, $users_group_ids ) ) {
						return;
					}
					$users_group_ids = array( $group_id );
				}

				$all_user_ids = array();
				// First get the user_ids for each group...
				foreach ( $users_group_ids as $users_group_id ) {
					$user_ids = learndash_get_groups_user_ids( $users_group_id );
					if ( ! empty( $user_ids ) ) {
						if ( ! empty( $all_user_ids ) ) {
							$all_user_ids = array_merge( $all_user_ids, $user_ids );
						} else {
							$all_user_ids = $user_ids;
						}
					}
				}

				// Then once we have all the groups user_id run a last query for the complete user ids.
				if ( ! empty( $all_user_ids ) ) {
					$user_query_args = array(
						'include' => $all_user_ids,
						'orderby' => 'display_name',
						'order'   => 'ASC',
					);

					$user_query = new WP_User_Query( $user_query_args );

					if ( ! empty( $user_query->get_results() ) ) {
						$users = $user_query->get_results();
					}
				}
			} elseif ( learndash_is_admin_user( $current_user->ID ) ) {
				if ( ! empty( $group_id ) ) {
					$users = learndash_get_groups_users( $group_id );
				} else {
					$users = get_users(
						array(
							'orderby' => 'display_name',
							'order'   => 'ASC',
						)
					);
				}
			} else {
				return array();
			}

			if ( empty( $users ) ) {
				return array();
			}

			$course_access_list = array();

			$course_progress_data = array();
			set_time_limit( 0 );

			$quiz_titles = array();
			$lessons     = array();

			if ( ! empty( $course_id ) ) {
				$courses = array( get_post( $course_id ) );
			} elseif ( ! empty( $group_id ) ) {
				$courses = learndash_group_enrolled_courses( $group_id );
				$courses = array_map( 'intval', $courses );
				$courses = ld_course_list(
					array(
						'post__in' => $courses,
						'array'    => true,
					)
				);
			} else {
				$courses = ld_course_list( array( 'array' => true ) );
			}

			if ( is_array( $users ) ) {

				foreach ( $users as $u ) {

					$user_id  = $u->ID;
					$usermeta = get_user_meta( $user_id, '_sfwd-course_progress', true );
					if ( ! empty( $usermeta ) ) {
						$usermeta = maybe_unserialize( $usermeta );
					}

					if ( is_array( $courses ) ) {
						foreach ( $courses as $course ) {
							if ( is_a( $course, 'WP_Post' ) ) {
								$c = $course->ID;

								if ( empty( $course->post_title ) || ! sfwd_lms_has_access( $c, $user_id ) ) {
									continue;
								}

								$cv = ! empty( $usermeta[ $c ] ) ? $usermeta[ $c ] : array(
									'completed' => '',
									'total'     => '',
								);

								$course_completed_meta                                       = get_user_meta( $user_id, 'course_completed_' . $course->ID, true );
								( empty( $course_completed_meta ) ) ? $course_completed_date = '' : $course_completed_date = date_i18n( 'F j, Y H:i:s', $course_completed_meta );

								$row = array(
									'user_id'             => $user_id,
									'name'                => $u->display_name,
									'email'               => $u->user_email,
									'course_id'           => $c,
									'course_title'        => $course->post_title,
									'total_steps'         => $cv['total'],
									'completed_steps'     => $cv['completed'],
									'course_completed'    => ( ! empty( $cv['total'] ) && $cv['completed'] >= $cv['total'] ) ? 'YES' : 'NO',
									'course_completed_on' => $course_completed_date,
								);

								$i = 1;
								if ( ! empty( $cv['lessons'] ) ) {
									foreach ( $cv['lessons'] as $lesson_id => $completed ) {
										if ( ! empty( $completed ) ) {
											if ( empty( $lessons[ $lesson_id ] ) ) {
												$lesson                = get_post( $lesson_id );
												$lessons[ $lesson_id ] = $lesson;
											} else {
												$lesson = $lessons[ $lesson_id ];
											}

											$row[ 'lesson_completed_' . $i ] = $lesson->post_title;
											$i++;
										}
									}
								}

								$course_progress_data[] = $row;
							}
						} // end foreach
					} else {
						$course_progress_data[] = array(
							'user_id' => $user_id,
							'name'    => $u->display_name,
							'email'   => $u->user_email,
							'status'  => esc_html__( 'No attempts', 'learndash' ),
						);
					} // end if
				} // end foreach
			}

			/**
			 * Filters course progress data to be displayed.
			 *
			 * @since 2.1.0
			 *
			 * @param array  $course_progress_data An array of course progress data.
			 * @param array  $users                An array of user list.
			 * @param int    $group_id             Group ID.
			 */
			$course_progress_data = apply_filters( 'course_progress_data', $course_progress_data, $users, (int) $group_id );

			return $course_progress_data;
		}



		/**
		 * Exports course progress data to CSV file
		 *
		 * @since 2.1.0
		 */
		public function course_export_init() {
			// @phpstan-ignore-next-line Constant may or may not be defined by user.
			if ( ( defined( 'LEARNDASH_ERROR_REPORTING_ZERO' ) ) && ( true === LEARNDASH_ERROR_REPORTING_ZERO ) ) {
				error_reporting( 0 ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting, WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting -- I hope they knew what they were doing.
			}

			if ( ! empty( $_REQUEST['courses_export_submit'] ) && ! empty( $_REQUEST['nonce-sfwd'] ) ) {
				set_time_limit( 0 );

				$default_tz = get_option( 'timezone_string' );
				if ( ! empty( $default_tz ) ) {
					date_default_timezone_set( $default_tz ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set -- I hope they knew what they were doing.
				}

				$nonce = $_REQUEST['nonce-sfwd'];

				if ( ! wp_verify_nonce( $nonce, 'sfwd-nonce' ) ) {
					die( esc_html__( 'Security Check - If you receive this in error, log out and back in to WordPress', 'learndash' ) );
				}

				$content = self::course_progress_data();

				if ( empty( $content ) ) {
					$content[] = array( 'status' => esc_html__( 'No attempts', 'learndash' ) );
				}

				/**
				 * Include parseCSV to write csv file.
				 */
				require_once LEARNDASH_LMS_LIBRARY_DIR . '/parsecsv.lib.php';

				$csv                  = new lmsParseCSV();
				$csv->file            = 'courses.csv';
				$csv->output_filename = 'courses.csv';
				/**
				 * Filters csv object.
				 *
				 * @since 2.3.2
				 *
				 * @param \lmsParseCSV $csv CSV object.
				 * @param string       $context The context of the csv object.
				 */
				$csv = apply_filters( 'learndash_csv_object', $csv, 'courses' );
				/**
				 * Filters the content will print onto the exported CSV
				 *
				 * @since 2.1.0
				 *
				 * @param void|array|mixed $content CSV content.
				 */
				$content = apply_filters( 'course_export_data', $content );

				$csv->output( 'courses.csv', $content, array_keys( reset( $content ) ) );
				die();
			}
		}



		/**
		 * Course Export Button submit data
		 *
		 * Apply_filters ran in display_settings_page() in sfwd_module_class.php
		 *
		 * @todo  currently no add_filter using this callback
		 *        consider for deprecation or implement add_filter
		 *
		 * @since 2.1.0
		 *
		 * @param  array $submit Submit.
		 * @return array $submit
		 */
		public function courses_filter_submit( $submit ) {
			$submit['courses_export_submit'] = array(
				'type'  => 'submit',
				'class' => 'button-primary',
				// translators: placeholder: Course.
				'value' => sprintf( esc_html_x( 'Export User %s Data &raquo;', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
			);
			return $submit;
		}

		/**
		 * Export quiz data to CSV
		 *
		 * @since 2.1.0
		 */
		public function quiz_export_init() {
			// @phpstan-ignore-next-line Constant may or may not be defined by user.
			if ( ( defined( 'LEARNDASH_ERROR_REPORTING_ZERO' ) ) && ( true === LEARNDASH_ERROR_REPORTING_ZERO ) ) {
				error_reporting( 0 ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting, WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting -- I hope they knew what they were doing.
			}

			global $wpdb;
			$current_user = wp_get_current_user();

			if ( ( ! learndash_is_admin_user( $current_user->ID ) ) && ( ! learndash_is_group_leader_user( $current_user->ID ) ) ) {
				return;
			}
			// Why are these 3 lines here??
			$sfwd_quiz   = $this->post_types['sfwd-quiz'];
			$quiz_prefix = $sfwd_quiz->get_prefix();
			add_filter( $quiz_prefix . 'submit_options', array( $this, 'quiz_filter_submit' ) );

			if ( ! empty( $_REQUEST['quiz_export_submit'] ) && ! empty( $_REQUEST['nonce-sfwd'] ) ) {
				$timezone_string = get_option( 'timezone_string' );
				if ( ! empty( $timezone_string ) ) {
					date_default_timezone_set( $timezone_string ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.timezone_change_date_default_timezone_set -- I hope they knew what they were doing.
				}

				if ( ! wp_verify_nonce( $_REQUEST['nonce-sfwd'], 'sfwd-nonce' ) ) {
					die( esc_html__( 'Security Check - If you receive this in error, log out and back in to WordPress', 'learndash' ) );
				}

				/**
				 * Include parseCSV to write csv file.
				 */
				require_once LEARNDASH_LMS_LIBRARY_DIR . '/parsecsv.lib.php';

				$content = array();
				set_time_limit( 0 );
				// Need ability to export quiz results for group to CSV.

				$group_id = null;
				if ( isset( $_GET['group_id'] ) ) {
					$group_id = $_GET['group_id'];
				}

				$users = array();
				if ( learndash_is_group_leader_user( $current_user->ID ) ) {

					$users_group_ids = learndash_get_administrators_group_ids( $current_user->ID );
					if ( ! count( $users_group_ids ) ) {
						return array();
					}

					if ( isset( $group_id ) ) {
						if ( ! in_array( $group_id, $users_group_ids ) ) {
							return;
						}
						$users_group_ids = array( $group_id );
					}

					$all_user_ids = array();
					// First get the user_ids for each group...
					foreach ( $users_group_ids as $users_group_id ) {
						$user_ids = learndash_get_groups_user_ids( $users_group_id );
						if ( ! empty( $user_ids ) ) {
							if ( ! empty( $all_user_ids ) ) {
								$all_user_ids = array_merge( $all_user_ids, $user_ids );
							} else {
								$all_user_ids = $user_ids;
							}
						}
					}

					// Then once we have all the groups user_id run a last query for the complete user ids.
					if ( ! empty( $all_user_ids ) ) {
						$user_query_args = array(
							'include'    => $all_user_ids,
							'orderby'    => 'display_name',
							'order'      => 'ASC',
							'meta_query' => array(
								array(
									'key'     => '_sfwd-quizzes',
									'compare' => 'EXISTS',
								),
							),
						);

						$user_query = new WP_User_Query( $user_query_args );

						if ( ! empty( $user_query->get_results() ) ) {
							$users = $user_query->get_results();
						}
					}
				} elseif ( learndash_is_admin_user( $current_user->ID ) ) {
					if ( ! empty( $group_id ) ) {
						$user_ids = learndash_get_groups_user_ids( $group_id );
						if ( ! empty( $user_ids ) ) {
							$user_query_args = array(
								'include'    => $user_ids,
								'orderby'    => 'display_name',
								'order'      => 'ASC',
								'meta_query' => array(
									array(
										'key'     => '_sfwd-quizzes',
										'compare' => 'EXISTS',
									),
								),
							);

							$user_query = new WP_User_Query( $user_query_args );
							if ( ! empty( $user_query->get_results() ) ) {
								$users = $user_query->get_results();
							} else {
								$users = array();
							}
						}
					} else {

						$user_query_args = array(
							'orderby'    => 'display_name',
							'order'      => 'ASC',
							'meta_query' => array(
								array(
									'key'     => '_sfwd-quizzes',
									'compare' => 'EXISTS',
								),
							),
						);

						$user_query = new WP_User_Query( $user_query_args );
						if ( ! empty( $user_query->get_results() ) ) {
							$users = $user_query->get_results();
						} else {
							$users = array();
						}
					}
				} else {
					return array();
				}

				$quiz_titles = array();

				if ( ! empty( $users ) ) {

					foreach ( $users as $u ) {

						$user_id  = $u->ID;
						$usermeta = get_user_meta( $user_id, '_sfwd-quizzes', true );

						if ( ! empty( $usermeta ) ) {

							foreach ( $usermeta as $k => $v ) {

								if ( ! empty( $group_id ) ) {
									$course_id = learndash_get_course_id( intval( $v['quiz'] ) );
									if ( ! learndash_group_has_course( $group_id, $course_id ) ) {
										continue;
									}
								}

								if ( empty( $quiz_titles[ $v['quiz'] ] ) ) {

									if ( ! empty( $v['quiz'] ) ) {
										$quiz = get_post( $v['quiz'] );

										if ( empty( $quiz ) ) {
											continue;
										}

										$quiz_titles[ $v['quiz'] ] = $quiz->post_title;

									} elseif ( ! empty( $v['pro_quizid'] ) ) {

										$quiz = get_post( $v['pro_quizid'] );

										if ( empty( $quiz ) ) {
											continue;
										}

										$quiz_titles[ $v['quiz'] ] = $quiz->post_title;

									} else {
										$quiz_titles[ $v['quiz'] ] = '';
									}
								}

								// After LD v2.2.1.2 we made a changes to the quiz user meta 'count' value output. Up to that point if the quiz showed only partial
								// questions, like 5 of 10 total then the value of $v[count] would be 10 instead of only the shown count 5.
								// After LD v2.2.1.2 we added a new field 'question_show_count' to hold the number of questions shown to the user during
								// the quiz.
								// But on legacy quiz user meta we needed a way to pull that information from the quiz...

								if ( ! isset( $v['question_show_count'] ) ) {
									$v['question_show_count'] = $v['count'];

									// ...If we have the statistics ref ID then we can pull the number of questions from there.
									if ( ( isset( $v['statistic_ref_id'] ) ) && ( ! empty( $v['statistic_ref_id'] ) ) ) {
										global $wpdb;

										$count = $wpdb->get_var(
											$wpdb->prepare( ' SELECT count(*) as count FROM ' . esc_sql( LDLMS_DB::get_table_name( 'quiz_statistic' ) ) . ' WHERE statistic_ref_id = %d', $v['statistic_ref_id'] )
										);
										if ( ! $count ) {
											$count = 0;
										}
										$v['question_show_count'] = intval( $count );
									} else {
										// .. or if the statistics is not enabled for this quiz then we get the question show count from the
										// quiz data. Note there is a potential hole in the logic here. If this quiz setting changes then existing
										// quiz user meta reports will also be effected.
										$pro_quiz_id = get_post_meta( $v['quiz'], 'quiz_pro_id', true );
										if ( ! empty( $pro_quiz_id ) ) {
											$quiz_mapper = new WpProQuiz_Model_QuizMapper();
											$quiz        = $quiz_mapper->fetch( $pro_quiz_id );

											if ( ( $quiz->isShowMaxQuestion() ) && ( $quiz->getShowMaxQuestionValue() > 0 ) ) {
												$v['question_show_count'] = $quiz->getShowMaxQuestionValue();
											}
										}
									}
								}

								$content[] = array(
									'user_id'    => $user_id,
									'name'       => $u->display_name,
									'email'      => $u->user_email,
									'quiz_id'    => $v['quiz'],
									'quiz_title' => $quiz_titles[ $v['quiz'] ],
									'rank'       => $v['rank'],
									'score'      => $v['score'],
									'total'      => $v['question_show_count'],
									'date'       => date_i18n( DATE_RSS, $v['time'] ),
								);
							}
						} else {
							$content[] = array(
								'user_id'    => $user_id,
								'name'       => $u->display_name,
								'email'      => $u->user_email,
								'quiz_id'    => esc_html__(
									'No attempts',
									'learndash'
								),
								'quiz_title' => '',
								'rank'       => '',
								'score'      => '',
								'total'      => '',
								'date'       => '',
							);
						} // end if
					} // end foreach
				} // end if

				if ( empty( $content ) ) {
					$content[] = array( 'status' => esc_html__( 'No attempts', 'learndash' ) );
				}

				/**
				 * Filters quiz data that will print to CSV.
				 *
				 * @since 2.1.0
				 *
				 * @param array $content   CSV content.
				 * @param array $users     An array of users list.
				 * @param int   $group_id Group ID.
				 */
				$content = apply_filters( 'quiz_export_data', $content, $users, (int) $group_id );

				$csv                  = new lmsParseCSV();
				$csv->file            = 'quizzes.csv';
				$csv->output_filename = 'quizzes.csv';
				/** This filter is documented in includes/class-ld-lms.php */
				$csv = apply_filters( 'learndash_csv_object', $csv, 'quizzes' );

				$csv->output( 'quizzes.csv', $content, array_keys( reset( $content ) ) );
				die();

			}
		}

		/**
		 * Quiz Export Button submit data
		 *
		 * Filter callback for $quiz_prefix . 'submit_options'
		 * apply_filters ran in display_settings_page() in sfwd_module_class.php
		 *
		 * @since 2.1.0
		 *
		 * @param  array $submit Submit.
		 * @return array
		 */
		public function quiz_filter_submit( $submit ) {
			$submit['quiz_export_submit'] = array(
				'type'  => 'submit',
				'class' => 'button-primary',
				// translators: placeholder: Quiz.
				'value' => sprintf( esc_html_x( 'Export %s Data &raquo;', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
			);
			return $submit;
		}



		/**
		 * Export transactions to CSV file
		 *
		 * Not currently being used in plugin
		 *
		 * @todo consider for deprecation or implement in plugin
		 *
		 * @since 2.1.0
		 */
		public function trans_export_init() {
			$sfwd_trans   = $this->post_types['sfwd-transactions'];
			$trans_prefix = $sfwd_trans->get_prefix();
			add_filter( $trans_prefix . 'submit_options', array( $this, 'trans_filter_submit' ) );

			if ( ! empty( $_REQUEST['export_submit'] ) && ! empty( $_REQUEST['nonce-sfwd'] ) ) {
				$nonce = $_REQUEST['nonce-sfwd'];

				if ( ! wp_verify_nonce( $nonce, 'sfwd-nonce' ) ) {
					die( esc_html__( 'Security Check - If you receive this in error, log out and back in to WordPress', 'learndash' ) );
				}

				/**
				 * Include parseCSV to write csv file
				 */
				require_once LEARNDASH_LMS_LIBRARY_DIR . '/parsecsv.lib.php';

				$content = array();
				set_time_limit( 0 );

				// phpcs:ignore WordPress.WP.DiscouragedFunctions.query_posts_query_posts -- Main file, better not to touch.
				$locations = query_posts(
					array(
						'post_status'    => 'publish',
						'post_type'      => 'sfwd-transactions',
						'posts_per_page' => -1,
					)
				);

				foreach ( $locations as $key => $location ) {
					$location_data = get_post_custom( $location->ID );
					foreach ( $location_data as $k => $v ) {
						if ( '_' == $k[0] ) {
							unset( $location_data[ $k ] );
						} else {
							$location_data[ $k ] = $v[0];
						}
					}
					$content[] = $location_data;
				}

				if ( ! empty( $content ) ) {
					$csv                  = new lmsParseCSV();
					$csv->file            = 'transactions.csv';
					$csv->output_filename = 'transactions.csv';
					/** This filter is documented in includes/class-ld-lms.php */
					$csv = apply_filters( 'learndash_csv_object', $csv, 'transactions' );

					$csv->output( true, 'transactions.csv', $content, array_keys( reset( $content ) ) );
				}

				die();
			}
		}



		/**
		 * Transaction Export Button submit data
		 *
		 * Filter callback for $trans_prefix . 'submit_options'
		 * apply_filters ran in display_settings_page() in sfwd_module_class.php
		 *
		 * @since 2.1.0
		 *
		 * @param  array $submit Submit.
		 * @return array
		 */
		public function trans_filter_submit( $submit ) {
			unset( $submit['Submit'] );
			unset( $submit['Submit_Default'] );

			$submit['export_submit'] = array(
				'type'  => 'submit',
				'class' => 'button-primary',
				'value' => esc_html__( 'Export &raquo;', 'learndash' ),
			);

			return $submit;
		}

		/**
		 * Set up quiz display settings
		 *
		 * Filter callback for '{$quiz_prefix}display_settings'
		 * apply_filters in display_options() in swfd_module_class.php
		 *
		 * @since 2.1.0
		 * @deprecated 3.4.0
		 *
		 * @param  array  $settings        quiz settings.
		 * @param  string $location        where these settings are being displayed.
		 * @param  array  $current_options current options stored for a given location.
		 * @return array                   quiz settings
		 */
		public function quiz_display_settings( $settings, $location, $current_options ) {
			if ( function_exists( '_deprecated_function' ) ) {
				_deprecated_function( __FUNCTION__, '3.4.0' );
			}

			return $settings;
		}

		/**
		 * Set up question display settings
		 *
		 * Filter callback for '{$question_prefix}display_settings'
		 * apply_filters in display_options() in swfd_module_class.php
		 *
		 * @since 2.1.0
		 *
		 * @param  array  $settings        quiz settings.
		 * @param  string $location        where these settings are being displayed.
		 * @param  array  $current_options current options stored for a given location.
		 * @return array                   quiz settings
		 */
		public function question_display_settings( $settings, $location, $current_options ) {
			global $sfwd_lms;
			$sfwd_question   = $sfwd_lms->post_types['sfwd-question'];
			$question_prefix = $sfwd_question->get_prefix();

			$prefix_len       = strlen( $question_prefix );
			$question_options = $sfwd_question->get_current_options();

			if ( ! empty( $location ) ) {
				global $pagenow;
				if ( ( 'post.php' == $pagenow ) || ( 'post-new.php' == $pagenow ) ) {
					$current_screen = get_current_screen();
					if ( 'sfwd-question' === $current_screen->post_type ) {

						if ( ( isset( $settings[ "{$question_prefix}quiz" ] ) ) && ( ! empty( $settings[ "{$question_prefix}quiz" ] ) ) ) {

							$_settings = $settings[ "{$question_prefix}quiz" ];

							$query_options = array(
								'post_type'      => 'sfwd-quiz',
								'post_status'    => 'any',
								'posts_per_page' => -1,
								'exclude'        => get_the_id(),
								'orderby'        => 'title',
								'order'          => 'ASC',
							);

							/** This filter is documented in includes/class-ld-lms.php */
							$lazy_load = apply_filters( 'learndash_element_lazy_load_admin', true );
							if ( ( true == $lazy_load ) && ( isset( $_settings['lazy_load'] ) ) && ( true == $_settings['lazy_load'] ) ) {
								$query_options['paged'] = 1;
								/** This filter is documented in includes/class-ld-lms.php */
								$query_options['posts_per_page'] = apply_filters( 'learndash_element_lazy_load_per_page', LEARNDASH_LMS_DEFAULT_LAZY_LOAD_PER_PAGE, "{$question_prefix}quiz" );
							}

							/**
							 * Filters quiz question query arguments.
							 *
							 * @since 2.1.0
							 *
							 * @param array $query_options Query arguments.
							 * @param array $settings      Quiz question settings.
							 */
							$query_options = apply_filters( 'learndash_question_quiz_post_options', $query_options, $_settings );

							$query_posts = new WP_Query( $query_options );

							// translators: placeholder: Quiz.
							$post_array = array( '0' => sprintf( esc_html_x( '-- Select a %s --', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'Quiz' ) ) );

							if ( ! empty( $query_posts->posts ) ) {
								if ( count( $query_posts->posts ) >= $query_posts->found_posts ) {
									// If the number of returned posts is equal or greater then found_posts then no need to run lazy load.
									$_settings['lazy_load'] = false;
								}

								foreach ( $query_posts->posts as $p ) {
									if ( get_the_id() !== $p->ID ) {
										$post_array[ $p->ID ] = $p->post_title;
									}
								}
							} else {
								// If we don't have any items then override the lazy load flag.
								$_settings['lazy_load'] = false;
							}
							$settings[ "{$question_prefix}quiz" ]['initial_options'] = $post_array;

							if ( ( isset( $_settings['lazy_load'] ) ) && ( true == $_settings['lazy_load'] ) ) {
								$lazy_load_data               = array();
								$lazy_load_data['query_vars'] = $query_options;
								$lazy_load_data['query_type'] = 'WP_Query';
								$lazy_load_data['value']      = ( isset( $_settings['value'] ) ) ? $_settings['value'] : '';
								$settings[ "{$question_prefix}quiz" ]['lazy_load_data'] = $lazy_load_data;
							}
						}
					}
				}
			}

			return $settings;
		}

		/**
		 * Select a course
		 *
		 * @param string $current_post_type  Current post type.
		 *
		 * @return array
		 */
		public function select_a_course( $current_post_type = null ) {

			$opt = array(
				'post_type'   => 'sfwd-courses',
				'post_status' => 'any',
				'numberposts' => -1,
				'orderby'     => 'title',
				'order'       => 'ASC',
			);

			$posts      = get_posts( $opt );
			$post_array = array();

			if ( ! empty( $posts ) ) {
				foreach ( $posts as $p ) {
					$post_array[ $p->ID ] = $p->post_title;
				}
			}

			return $post_array;
		}

		/**
		 * Select a group
		 *
		 * @param string $current_post_type Current post type.
		 *
		 * @return array
		 */
		public function select_a_group( $current_post_type = null ) {

			$opt = array(
				'post_type'   => 'groups',
				'post_status' => 'any',
				'numberposts' => -1,
				'orderby'     => 'title',
				'order'       => 'ASC',
			);

			$posts      = get_posts( $opt );
			$post_array = array();

			if ( ! empty( $posts ) ) {
				foreach ( $posts as $p ) {
					$post_array[ $p->ID ] = $p->post_title;
				}
			}

			return $post_array;
		}

		/**
		 * Select a certificate
		 *
		 * @param string $current_post_type Current post type.
		 *
		 * @return array
		 */
		public function select_a_certificate( $current_post_type = null ) {

			$opt = array(
				'post_type'   => 'sfwd-certificates',
				'post_status' => 'any',
				'numberposts' => -1,
				'orderby'     => 'title',
				'order'       => 'ASC',
			);

			$posts      = get_posts( $opt );
			$post_array = array();

			if ( ! empty( $posts ) ) {
				foreach ( $posts as $p ) {
					$post_array[ $p->ID ] = $p->post_title;
				}
			}

			return $post_array;
		}


		/**
		 * Retrieves lessons or topics for a course to populate dropdown on edit screen
		 *
		 * Ajax action callback for wp_ajax_select_a_lesson_or_topic
		 *
		 * @since 2.1.0
		 */
		public function select_a_lesson_or_topic_ajax() {
			$data        = array();
			$data['opt'] = array();

			if ( ( isset( $_POST['ld_selector_nonce'] ) ) && ( ! empty( $_POST['ld_selector_nonce'] ) ) && ( wp_verify_nonce( $_POST['ld_selector_nonce'], learndash_get_post_type_slug( 'lesson' ) ) ) ) {

				if ( ( isset( $_POST['ld_selector_default'] ) ) && ( ! empty( $_POST['ld_selector_default'] ) ) ) {
					$ld_selector_default = true;
				} else {
					$ld_selector_default = false;
				}
				$post_array = $this->select_a_lesson_or_topic(
					isset( $_REQUEST['course_id'] ) ? intval( $_REQUEST['course_id'] ) : null,
					true,
					$ld_selector_default
				);
				if ( ! empty( $post_array ) ) {
					$i = 0;
					foreach ( $post_array as $key => $value ) {
						$opt[ $i ]['key']   = $key;
						$opt[ $i ]['value'] = $value;
						$i++;
					}
					$data['opt'] = $opt;
				}
			}

			echo wp_json_encode( $data );
			exit;
		}



		/**
		 * Makes wp_query to retrieve lessons or topics for a course
		 *
		 * @since 2.1.0
		 *
		 * @param int  $course_id       Course ID.
		 * @param bool $include_topics  Whether to include topics.
		 * @param bool $include_default Whether to include default.
		 *
		 * @return array    array of lessons or topics
		 */
		public function select_a_lesson_or_topic( $course_id = null, $include_topics = true, $include_default = true ) {
			if ( ! is_admin() ) {
				return array();
			}
			$post_array = array();

			if ( ! is_null( $course_id ) ) {
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
					$lesson_ids = learndash_course_get_children_of_step( $course_id, $course_id, 'sfwd-lessons' );
					if ( ! empty( $lesson_ids ) ) {
						foreach ( $lesson_ids as $lesson_id ) {
							$post_array[ $lesson_id ] = get_the_title( $lesson_id );
							if ( $include_topics ) {
								$topic_ids = learndash_course_get_children_of_step( $course_id, $lesson_id, 'sfwd-topic' );
								if ( ! empty( $topic_ids ) ) {
									foreach ( $topic_ids as $topic_id ) {
										$post_array[ $topic_id ] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . get_the_title( $topic_id );
									}
								}
							}
						}
					}
				} else {
					$lessons_options     = sfwd_lms_get_post_options( 'sfwd-lessons' );
					$course_lessons_args = learndash_get_course_lessons_order( $course_id );
					$orderby             = isset( $course_lessons_args['orderby'] ) ? $course_lessons_args['orderby'] : $lessons_options['orderby'];
					$order               = isset( $course_lessons_args['order'] ) ? $course_lessons_args['order'] : $lessons_options['order'];
					$opt                 = array(
						'post_type'   => 'sfwd-lessons',
						'post_status' => 'any',
						'numberposts' => -1,
						'orderby'     => $orderby,
						'order'       => $order,
					);

					if ( empty( $course_id ) && isset( $_GET['post'] ) ) {
						$course_id = learndash_get_course_id( $_GET['post'] );
					}

					if ( ! empty( $course_id ) ) {
						$opt['meta_key']   = 'course_id';
						$opt['meta_value'] = $course_id;
					}

					$posts = get_posts( $opt );

					if ( true === $include_default ) {
						if ( true == $include_topics ) {
							if ( learndash_use_select2_lib() ) {
								$post_array = array(
									'-1' => sprintf(
										// translators: placeholders: Lesson, Topic.
										esc_html_x( 'Search or select a %1$s or %2$s…', 'placeholders: Lesson, Topic', 'learndash' ),
										LearnDash_Custom_Label::get_label( 'lesson' ),
										LearnDash_Custom_Label::get_label( 'topic' )
									),
								);
							} else {
								$post_array = array(
									'0' => sprintf(
										// translators: placeholders: Lesson, Topic Labels.
										esc_html_x( 'Select a %1$s or %2$s', 'placeholders: Lesson, Topic Labels', 'learndash' ),
										LearnDash_Custom_Label::get_label( 'lesson' ),
										LearnDash_Custom_Label::get_label( 'topic' )
									),
								);
							}
						} else {
							if ( learndash_use_select2_lib() ) {
								$post_array = array(
									'-1' => sprintf(
										// translators: placeholder: Lesson.
										esc_html_x( 'Search or select a %s…', 'placeholder: Lesson', 'learndash' ),
										LearnDash_Custom_Label::get_label( 'lesson' )
									),
								);
							} else {
								$post_array = array(
									'0' => sprintf(
										// translators: placeholder: Lesson.
										esc_html_x( 'Select a %s', 'placeholder: Lesson', 'learndash' ),
										LearnDash_Custom_Label::get_label( 'lesson' )
									),
								);
							}
						}
					}

					if ( ! empty( $posts ) ) {
						foreach ( $posts as $p ) {
							$lesson_post_title = learndash_format_step_post_title_with_status_label( $p );
							if ( empty( $lesson_post_title ) ) {
								$lesson_post_title = $p->ID . ' - /' . $p->post_name;
							}
							$post_array[ $p->ID ] = $lesson_post_title;
							if ( true == $include_topics ) {
								$topics_array = learndash_get_topic_list( $p->ID, $course_id );
								if ( ! empty( $topics_array ) ) {
									foreach ( $topics_array as $topic ) {
										$topic_post_title = learndash_format_step_post_title_with_status_label( $topic );
										if ( empty( $topic_post_title ) ) {
											$topic_post_title = $topic->ID . ' - /' . $topic->post_name;
										}
										$post_array[ $topic->ID ] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $topic_post_title;
									}
								}
							}
						}
					}
				}
			}
			return $post_array;
		}


		/**
		 * Retrieves lessons for a course to populate dropdown on edit screen
		 *
		 * Ajax action callback for wp_ajax_select_a_lesson
		 *
		 * @since 2.1.0
		 */
		public function select_a_lesson_ajax() {
			$data        = array();
			$data['opt'] = array();

			if ( ( isset( $_POST['ld_selector_nonce'] ) ) && ( ! empty( $_POST['ld_selector_nonce'] ) ) && ( wp_verify_nonce( $_POST['ld_selector_nonce'], 'sfwd-lessons' ) ) ) {
				if ( ( isset( $_POST['ld_selector_default'] ) ) && ( ! empty( $_POST['ld_selector_default'] ) ) ) {
					$ld_selector_default = true;
				} else {
					$ld_selector_default = false;
				}
				$post_array = $this->select_a_lesson_or_topic(
					isset( $_REQUEST['course_id'] ) ? intval( $_REQUEST['course_id'] ) : null,
					false,
					$ld_selector_default
				);
				if ( ! empty( $post_array ) ) {
					$i = 0;
					foreach ( $post_array as $key => $value ) {
						$opt[ $i ]['key']   = $key;
						$opt[ $i ]['value'] = $value;
						$i++;
					}
					$data['opt'] = $opt;
				}
			}

			echo wp_json_encode( $data );
			exit;
		}



		/**
		 * Makes wp_query to retrieve lessons a course
		 *
		 * @since 2.1.0
		 *
		 * @param  int $course_id Course ID.
		 * @return array    array of lessons
		 */
		public function select_a_lesson( $course_id = null ) {
			if ( ! is_admin() ) {
				return array();
			}

			if ( ! empty( $_REQUEST['ld_action'] ) || ! empty( $_GET['post'] ) && is_array( $_GET['post'] ) ) {
				return array();
			}

			$opt = array(
				'post_type'   => 'sfwd-lessons',
				'post_status' => 'any',
				'numberposts' => -1,
				'orderby'     => learndash_get_option( 'sfwd-lessons', 'orderby' ),
				'order'       => learndash_get_option( 'sfwd-lessons', 'order' ),
			);

			if ( empty( $course_id ) ) {
				if ( empty( $_GET['post'] ) ) {
					$course_id = learndash_get_course_id();
				} else {
					$course_id = learndash_get_course_id( $_GET['post'] );
				}
			}

			if ( ! empty( $course_id ) ) {
				$opt['meta_key']   = 'course_id';
				$opt['meta_value'] = $course_id;
			}

			$posts = get_posts( $opt );
			if ( learndash_use_select2_lib() ) {
				$post_array = array(
					'-1' => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( 'Search or select a %s', 'placeholder: Lesson', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lesson' )
					),
				);
			} else {
				$post_array = array(
					'0' => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( 'Select a %s', 'placeholder: Lesson', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lesson' )
					),
				);
			}

			if ( ! empty( $posts ) ) {
				foreach ( $posts as $p ) {
					$post_array[ $p->ID ] = $p->post_title;
				}
			}

			return $post_array;
		}


		/**
		 * Retrieves quizzes for a course to populate dropdown on edit screen
		 *
		 * Ajax action callback for wp_ajax_select_a_lesson
		 *
		 * @since 2.5.0
		 */
		public function select_a_quiz_ajax() {
			$data        = array();
			$data['opt'] = array();

			if ( ( isset( $_POST['ld_selector_nonce'] ) ) && ( ! empty( $_POST['ld_selector_nonce'] ) ) && ( wp_verify_nonce( $_POST['ld_selector_nonce'], 'sfwd-quiz' ) ) ) {
				$post_array = $this->select_a_quiz(
					isset( $_REQUEST['course_id'] ) ? intval( $_REQUEST['course_id'] ) : 0,
					isset( $_REQUEST['lesson_id'] ) ? intval( $_REQUEST['lesson_id'] ) : 0
				);
				if ( ! empty( $post_array ) ) {
					$i = 0;
					foreach ( $post_array as $key => $value ) {
						$opt[ $i ]['key']   = $key;
						$opt[ $i ]['value'] = $value;
						$i++;
					}
					$data['opt'] = $opt;
				}
			}
			echo wp_json_encode( $data );
			exit;
		}

		/**
		 * Makes wp_query to retrieve quizzes a course
		 *
		 * @since 2.5.0
		 *
		 * @param  int $course_id       Course ID.
		 * @param  int $lesson_topic_id Step ID.
		 * @return array    array of lessons
		 */
		public function select_a_quiz( $course_id = 0, $lesson_topic_id = 0 ) {

			$post_array = array();

			if ( ! empty( $course_id ) ) {
				if ( ! empty( $lesson_topic_id ) ) {
					$quiz_ids = learndash_course_get_children_of_step( $course_id, $lesson_topic_id, 'sfwd-quiz' );
				} else {
					$quiz_ids = learndash_course_get_steps_by_type( $course_id, 'sfwd-quiz' );
				}
				if ( ! empty( $quiz_ids ) ) {
					foreach ( $quiz_ids as $quiz_id ) {
						$post_array[ $quiz_id ] = get_the_title( $quiz_id );
					}
				}
			} else {
				$opt = array(
					'post_type'   => 'sfwd-quiz',
					'post_status' => 'any',
					'numberposts' => -1,
					'orderby'     => 'title',
					'order'       => 'ASC',
				);

				$posts      = get_posts( $opt );
				$post_array = array();

				if ( ! empty( $posts ) ) {
					foreach ( $posts as $p ) {
						$post_array[ $p->ID ] = $p->post_title;
					}
				}
			}
			return $post_array;
		}


		/**
		 * Set up course display settings
		 *
		 * Filter callback for '{$courses_prefix}display_settings'
		 * apply_filters in display_options() in swfd_module_class.php
		 *
		 * @since 2.1.0
		 * @deprecated 3.4.0
		 *
		 * @param  array $settings  quiz settings.
		 *
		 * @return array quiz settings
		 */
		public function course_display_settings( $settings ) {
			if ( function_exists( '_deprecated_function' ) ) {
				_deprecated_function( __FUNCTION__, '3.4.0' );
			}

			return $settings;

		}

		/**
		 * Set up lesson display settings
		 *
		 * Filter callback for '{$lessons_prefix}display_settings'
		 * apply_filters in display_options() in swfd_module_class.php
		 *
		 * @since 2.2.0.2
		 * @deprecated 3.4.0
		 *
		 * @param  array $settings        lesson settings.
		 * @return array                   lesson settings
		 */
		public function lesson_display_settings( $settings ) {

			if ( function_exists( '_deprecated_function' ) ) {
				_deprecated_function( __FUNCTION__, '3.4.0' );
			}

			return $settings;
		}


		/**
		 * Set up topic display settings
		 *
		 * Filter callback for '{$topics_prefix}display_settings'
		 * apply_filters in display_options() in swfd_module_class.php
		 *
		 * @since 2.2.0.2
		 * @deprecated 3.4.0
		 *
		 * @param  array $settings        topic settings.
		 * @return array                   topic settings
		 */
		public function topic_display_settings( $settings ) {
			if ( function_exists( '_deprecated_function' ) ) {
				_deprecated_function( __FUNCTION__, '3.4.0' );
			}

			return $settings;
		}

		/**
		 * Insert course name as a term on course publish
		 *
		 * Action callback for 'publish_sfwd-courses' (wp core filter action)
		 *
		 * @todo  consider for deprecation, action is commented
		 *
		 * @since 2.1.0
		 *
		 * @param int    $post_id Post ID.
		 * @param object $post    Post object.
		 */
		public function add_course_tax_entry( $post_id, $post ) {
			$term    = get_term_by( 'slug', $post->post_name, 'courses' );
			$term_id = isset( $term->term_id ) ? $term->term_id : 0;

			if ( ! $term_id ) {
				$term    = wp_insert_term( $post->post_title, 'courses', array( 'slug' => $post->post_name ) );
				$term_id = $term['term_id'];
			}

			wp_set_object_terms( (int) $post_id, (int) $term_id, 'courses', true );
		}



		/**
		 * Register taxonomies for each custom post type
		 *
		 * Action callback for 'init'
		 *
		 * @since 2.1.0
		 */
		public function tax_registration() {

			/**
			 * Filters list of taxonomies to be registered.
			 *
			 * Add_filters are currently added during the add_post_type() method in swfd_cpt.php
			 *
			 * @since 2.1.0
			 *
			 * @param array $taxonomies An array of taxonomy lists to be registered.
			 */
			$taxes = apply_filters( 'sfwd_cpt_register_tax', array() );

			/**
			 * The expected return form of the array is:
			 *  array(
			 *      'tax_slug1' =>  array(
			 *                          'post_types' => array('sfwd-courses', 'sfwd-lessons'),
			 *                          'tax_args' => array() // See register_taxonomy() third parameter for valid args options
			 *                      ),
			 *      'tax_slug2' =>  array(
			 *                          'post_types' => array('sfwd-lessons'),
			 *                          'tax_args' => array()
			 *                      ),
			 *  )
			 */

			if ( ! empty( $taxes ) ) {
				foreach ( $taxes as $tax_slug => $tax_options ) {
					if ( ! taxonomy_exists( $tax_slug ) ) {
						if ( ( isset( $tax_options['post_types'] ) ) && ( ! empty( $tax_options['post_types'] ) ) ) {
							if ( ( isset( $tax_options['tax_args'] ) ) && ( ! empty( $tax_options['tax_args'] ) ) ) {

								// Via the LD post type setup when the 'taxonomies' option is defined we can associate other taxonomies
								// with our custom post types by setting the tax slug and value as the same.
								if ( $tax_slug !== $tax_options['tax_args']['rewrite']['slug'] ) {
									/**
									 * Filters taxonomy arguments.
									 *
									 * @param array $tax_options An array of taxonomy arguments.
									 * @param string $tax_slug Taxonomy slug.
									 */
									$tax_options = apply_filters( 'learndash_taxonomy_args', $tax_options, $tax_slug );
									if ( ! empty( $tax_options ) ) {
										register_taxonomy( $tax_slug, $tax_options['post_types'], $tax_options['tax_args'] );
									}
								}
							}
						}
					} else {

						// If the taxonomy already exists we only need to then associated the post_types.
						if ( ( isset( $tax_options['post_types'] ) ) && ( ! empty( $tax_options['post_types'] ) ) ) {
							foreach ( $tax_options['post_types'] as $post_type ) {
								register_taxonomy_for_object_type( $tax_slug, $post_type );
							}
						}
					}
				}
			} // endif
		}

		/**
		 * Get template paths
		 *
		 * @param string $filename  File name.
		 */
		public static function get_template_paths( $filename = '' ) {
			$template_filenames = array();
			$template_paths     = array();

			$active_template_key = LearnDash_Theme_Register::get_active_theme_key();
			$active_template_dir = LearnDash_Theme_Register::get_active_theme_template_dir();
			$file_pathinfo       = pathinfo( $filename );

			if ( ! isset( $file_pathinfo['dirname'] ) ) {
				$file_pathinfo['dirname'] = '';
			} elseif ( ! empty( $file_pathinfo['dirname'] ) ) {
				if ( '.' === $file_pathinfo['dirname'] ) {
					$file_pathinfo['dirname'] = '';
				} else {
					$file_pathinfo['dirname'] .= '/';
				}
			}

			if ( empty( $file_pathinfo['filename'] ) || ( ! is_string( $file_pathinfo['filename'] ) ) ) {
				$file_pathinfo['filename'] = '';
			}

			if ( ! isset( $file_pathinfo['extension'] ) ) {
				$file_pathinfo['extension'] = '';
			}

			if ( in_array( $file_pathinfo['extension'], array( 'js', 'css' ), true ) ) {
				if ( ( defined( 'LEARNDASH_SCRIPT_DEBUG' ) ) && ( LEARNDASH_SCRIPT_DEBUG == true ) ) {
					$template_filenames[] = $file_pathinfo['dirname'] . $file_pathinfo['filename'] . '.' . $file_pathinfo['extension'];
				}

				$template_filenames[] = $file_pathinfo['dirname'] . $file_pathinfo['filename'] . '.min.' . $file_pathinfo['extension'];
			} else {
				// add index suffix to filename.
				$template_file_name = $file_pathinfo['dirname'] . $file_pathinfo['filename'] . '.' . $file_pathinfo['extension'];
				if ( ! is_file( trailingslashit( $active_template_dir ) . $template_file_name ) ) {
					$template_file_dir = $file_pathinfo['dirname'] . $file_pathinfo['filename'];
					if ( is_dir( trailingslashit( $active_template_dir ) . $template_file_dir ) ) {
						$template_file_name = $file_pathinfo['dirname'] . $file_pathinfo['filename'] . '/index.' . $file_pathinfo['extension'];
					}
				}

				$template_filenames[] = $template_file_name;
			}

			$template_paths['theme'] = array();
			foreach ( $template_filenames as $template_filename ) {
				$template_paths['theme'][] = 'learndash/' . $active_template_key . '/' . $template_filename;
			}

			if ( LEARNDASH_LEGACY_THEME === $active_template_key ) {
				foreach ( $template_filenames as $template_filename ) {
					$template_paths['theme'][] = 'learndash/' . $template_filename;
				}

				foreach ( $template_filenames as $template_filename ) {
					$template_paths['theme'][] = $template_filename;
				}
			}

			$template_paths['templates'] = array();
			if ( defined( 'LEARNDASH_TEMPLATES_DIR' ) ) {
				$template_dir = trailingslashit( LEARNDASH_TEMPLATES_DIR );
				foreach ( $template_filenames as $template_filename ) {
					$template_paths['templates'][] = $template_dir . $active_template_key . '/' . $template_filename;
				}
				if ( 'learndash_template_functions.php' === $file_pathinfo['filename'] ) {
					$template_paths['templates'][] = $template_dir . $active_template_key . '/functions.php';
				}
				if ( LEARNDASH_LEGACY_THEME === $active_template_key ) {
					foreach ( $template_filenames as $template_filename ) {
						$template_paths['templates'][] = $template_dir . $template_filename;
					}
					if ( 'learndash_template_functions.php' === $file_pathinfo['filename'] ) {
						$template_paths['templates'][] = $template_dir . 'functions.php';
					}
				}
			}

			if ( ! empty( $active_template_dir ) ) {
				foreach ( $template_filenames as $template_filename ) {
					$template_paths['templates'][] = trailingslashit( $active_template_dir ) . $template_filename;
				}
			}

			if ( LEARNDASH_LEGACY_THEME !== $active_template_key ) {
				$legacy_theme_instance = LearnDash_Theme_Register::get_theme_instance( LEARNDASH_LEGACY_THEME );

				if ( ! empty( $legacy_theme_instance ) ) {
					$legacy_theme_dir = $legacy_theme_instance->get_theme_template_dir();

					if ( ! empty( $legacy_theme_dir ) ) {
						foreach ( $template_filenames as $template_filename ) {
							$template_paths['templates'][] = $legacy_theme_dir . '/' . $template_filename;
						}
					}
				}
			}

			return $template_paths;
		}

		/**
		 * Get LearnDash template and pass data to be used in template
		 *
		 * Checks to see if user has a 'learndash' directory in their current theme
		 * and uses the template if it exists.
		 *
		 * @since 2.1.0
		 *
		 * @param  string     $name             Template name.
		 * @param  array|null $args             Data for template.
		 * @param  bool|null  $echo             echo or return.
		 * @param  bool       $return_file_path Return just file path instead of output.
		 */
		public static function get_template( $name, $args, $echo = false, $return_file_path = false ) {
			$template_paths = array();

			$template_filename = $name;

			// Ensure the template has a proper extension.
			$file_pathinfo = pathinfo( $template_filename );
			if ( ( ! isset( $file_pathinfo['extension'] ) ) || ( empty( $file_pathinfo['extension'] ) ) ) {
				$template_filename .= '.php';
			}

			/**
			 * Filters template file name.
			 *
			 * @since 3.0.0
			 *
			 * @param string     $template_filename Template file name.
			 * @param string     $name              Template name.
			 * @param array|null $args              Template data.
			 * @param bool|null  $echo              Whether to echo the template output or not.
			 * @param bool       $return_file_path  Whether to return file or path or not.
			 */
			$template_filename = apply_filters( 'learndash_template_filename', $template_filename, $name, $args, $echo, $return_file_path );

			if ( empty( $template_filename ) ) {
				return;
			}

			$template_paths = self::get_template_paths( $template_filename );

			$filepath = '';
			if ( ( isset( $template_paths['theme'] ) ) && ( ! empty( $template_paths['theme'] ) ) ) {
				$filepath = locate_template( $template_paths['theme'] );
			}

			if ( empty( $filepath ) ) {
				if ( ( isset( $template_paths['templates'] ) ) && ( ! empty( $template_paths['templates'] ) ) ) {
					foreach ( $template_paths['templates'] as $template ) {
						if ( file_exists( $template ) ) {
							$filepath = $template;
							break;
						}
					}
				}
			}

			/**
			 * Filters file path for the learndash template being called.
			 *
			 * @since 2.1.0
			 * @since 3.0.3 - Allow override of empty or other checks.
			 *
			 * @param string     $filepath         Template file path.
			 * @param string     $name             Template name.
			 * @param array|null $args             Template data.
			 * @param bool|null  $echo             Whether to echo the template output or not.
			 * @param bool       $return_file_path Whether to return file or path or not.
			 */
			$filepath = apply_filters( 'learndash_template', $filepath, $name, $args, $echo, $return_file_path );
			if ( ! $filepath ) {
				return false;
			}

			if ( $return_file_path ) {
				return $filepath;
			}

			// Added check to ensure external hooks don't return empty or non-accessible filenames.
			if ( ( file_exists( $filepath ) ) && ( is_file( $filepath ) ) ) {

				/**
				 * Filters template arguments.
				 *
				 * The dynamic part of the hook refers to the name of the template.
				 *
				 * @param array|null $args     Template data.
				 * @param string     $filepath Template file path.
				 * @param bool|null  $echo     Whether to echo the template output or not.
				 */
				$args = apply_filters( 'ld_template_args_' . $name, $args, $filepath, $echo );
				if ( ( ! empty( $args ) ) && ( is_array( $args ) ) ) {
					extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Bad idea, but better keep it for now.
				}
				$level = ob_get_level();
				ob_start();
				include $filepath;
				$contents = learndash_ob_get_clean( $level );

				if ( ! $echo ) {
					return $contents;
				}

				echo $contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in template.
			}
		}

		/**
		 * Get or output view template file.
		 *
		 * @since 4.4.0
		 *
		 * @param string $name View template name.
		 * @param array  $args Template arguments.
		 * @param bool   $echo Whether to output or return the template.
		 *
		 * @return void|string
		 */
		public static function get_view( string $name, array $args = array(), bool $echo = false ) {
			extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Bad idea, but better keep it for now.

			$template = LEARNDASH_LMS_PLUGIN_DIR . '/includes/views/' . $name . '.php';

			if ( file_exists( $template ) ) {
				$level = ob_get_level();
				ob_start();
				include $template;
				$contents = learndash_ob_get_clean( $level );

				if ( ! $echo ) {
					return $contents;
				}

				echo $contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		/**
		 * Called from the 'all_plugins' filter. This is called from the Plugins listing screen and will let us
		 * set our internal flag 'all_plugins_called' so we know when (and when not) to add the learndash plugin path
		 *
		 * @since 2.3.0.3
		 *
		 * @param array $all_plugins The array of plugins to be displayed on the Plugins listing.
		 * @return array $all_plugins
		 */
		public function all_plugins_proc( $all_plugins ) {
			$this->all_plugins_called = true;
			return $all_plugins;
		}

		/**
		 * Called from the 'pre_current_active_plugins' action. This is called after the Plugins listing checks for
		 * valid plugins. The will let us unset our internal flag 'ALL_PLUGINS_CALLED'.
		 *
		 * @since 2.3.0.3
		 */
		public function pre_current_active_plugins_proc() {
			$this->all_plugins_called = false;
		}

		/**
		 * This is called from the get_options() function for the option 'active_plugins'. Using this filter
		 * we can append our LearnDash plugin path, allowing other plugins to check via is_plugin_active()
		 * even if learndash is installed in a non-standard plugin directory.
		 *
		 * @since 2.3.0.3
		 *
		 * @param array $active_plugins An array of the current active plugins.
		 * @return array $active_plugins
		 */
		public function option_active_plugins_proc( $active_plugins ) {
			global $pagenow;

			if ( empty( $active_plugins ) ) {
				return $active_plugins;
			}

			// we don't need to add the plugin path for that call.
			if ( 'plugins.php' === $pagenow && $this->all_plugins_called ) {
				return $active_plugins;
			}

			// the current plugin is not active.
			if ( ! in_array( LEARNDASH_LMS_PLUGIN_KEY, $active_plugins, true ) ) {
				return $active_plugins;
			}

			// plugin is in the standard location.
			if ( LEARNDASH_LMS_PLUGIN_KEY === $this->learndash_standard_plugin_path ) {
				return $active_plugins;
			}

			if ( ! in_array( $this->learndash_standard_plugin_path, $active_plugins, true ) ) {
				$active_plugins[] = $this->learndash_standard_plugin_path;
			}

			return $active_plugins;
		}

		/**
		 * This is called from the update_options() function for the option 'active_plugins'. Using this filter
		 * we can remove our plugin path we added via the option_active_plugins_proc filter.
		 *
		 * @since 2.3.0.3
		 *
		 * @param array $active_plugins An array of the current active plugins.
		 * @return array $active_plugins
		 */
		public function pre_update_option_active_plugins( $active_plugins ) {
			if ( empty( $active_plugins ) ) {
				return $active_plugins;
			}

			// plugin is in the standard location.
			if ( LEARNDASH_LMS_PLUGIN_KEY === $this->learndash_standard_plugin_path ) {
				return $active_plugins;
			}

			$key = array_search( $this->learndash_standard_plugin_path, $active_plugins );
			if ( $key !== false ) {
				unset( $active_plugins[ $key ] );
			}

			return $active_plugins;
		}

		/**
		 * Site option active sitewide plugins
		 *
		 * @param array $active_plugins Array of active plugins.
		 *
		 * @return array
		 */
		public function site_option_active_sitewide_plugins_proc( $active_plugins ) {
			global $pagenow;

			if ( empty( $active_plugins ) ) {
				return $active_plugins;
			}

			// we don't need to add the plugin path for that call.
			if ( 'plugins.php' === $pagenow && $this->all_plugins_called ) {
				return $active_plugins;
			}

			// the current plugin is not active.
			if ( ! isset( $active_plugins[ LEARNDASH_LMS_PLUGIN_KEY ] ) ) {
				return $active_plugins;
			}

			// plugin is in the standard location.
			if ( LEARNDASH_LMS_PLUGIN_KEY === $this->learndash_standard_plugin_path ) {
				return $active_plugins;
			}

			if ( ! isset( $active_plugins[ $this->learndash_standard_plugin_path ] ) ) {
				$active_plugins[ $this->learndash_standard_plugin_path ] = $active_plugins[ LEARNDASH_LMS_PLUGIN_KEY ];
			}

			return $active_plugins;
		}

		/**
		 * Pre Update site option active sitewide plugins
		 *
		 * @param array $active_plugins Active plugins.
		 *
		 * @return array
		 */
		public function pre_update_site_option_active_sitewide_plugins( $active_plugins ) {
			if ( empty( $active_plugins ) ) {
				return $active_plugins;
			}

			// plugin is in the standard location.
			if ( LEARNDASH_LMS_PLUGIN_KEY === $this->learndash_standard_plugin_path ) {
				return $active_plugins;
			}

			if ( isset( $active_plugins[ $this->learndash_standard_plugin_path ] ) ) {
				unset( $active_plugins[ $this->learndash_standard_plugin_path ] );
			}

			return $active_plugins;
		}


		/**
		 * Add support for alternate templates directory.
		 * Normally LD will load template files from the active theme directory
		 * or if not found via the plugin templates directory. We now support
		 * a neutral directory wp-content/uploads/learndash/templates/
		 *
		 * If the site uses a functions.php it will be loaded from that directory
		 * This is the recommended place to add actions/filters to prevent theme updates
		 * from erasing them.
		 *
		 * @since 2.4.0
		 */
		public function init_ld_templates_dir() {
			if ( ! defined( 'LEARNDASH_TEMPLATES_DIR' ) ) {
				$wp_upload_dir    = wp_upload_dir();
				$ld_templates_dir = trailingslashit( $wp_upload_dir['basedir'] ) . 'learndash/templates/';

				/**
				 * Define LearnDash LMS - Set the Template override path.
				 *
				 * Will be set within the wp-content/uploads/learndash directory.
				 *
				 * @since 2.4.0
				 */
				define( 'LEARNDASH_TEMPLATES_DIR', $ld_templates_dir );

				if ( ! file_exists( $ld_templates_dir ) ) {
					if ( wp_mkdir_p( $ld_templates_dir ) !== false ) {
						// To prevent security browsing add an index.php file.
						file_put_contents( trailingslashit( $ld_templates_dir ) . 'index.php', '// nothing to see here' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
					}
				}
			}

			// Piggy back to this logic and cleanup the reports directory.
			if ( ( is_admin() ) && ( ( ! defined( 'DOING_AJAX' ) ) || ( DOING_AJAX !== true ) ) ) {

				$wp_upload_dir  = wp_upload_dir();
				$ld_reports_dir = trailingslashit( $wp_upload_dir['basedir'] ) . 'learndash/';

				if ( file_exists( $ld_reports_dir ) ) {
					$filenames = array();

					$filenames_csv = glob( $ld_reports_dir . '*.csv' );
					if ( ( is_array( $filenames_csv ) ) && ( ! empty( $filenames_csv ) ) ) {
						$filenames = array_merge( $filenames, $filenames_csv );
					}

					$filenames_csv = glob( $ld_reports_dir . '/reports/*.csv' );
					if ( ( is_array( $filenames_csv ) ) && ( ! empty( $filenames_csv ) ) ) {
						$filenames = array_merge( $filenames, $filenames_csv );
					}

					if ( ! empty( $filenames ) ) {
						foreach ( $filenames as $filename ) {
							if ( filemtime( $filename ) < ( time() - 60 * 60 ) ) {
								$file = basename( $filename );

								if ( substr( $file, 0, strlen( 'learndash_reports_user_courses_' ) ) == 'learndash_reports_user_courses_' ) {
									$transient_hash = str_replace( array( 'learndash_reports_user_courses_', '.csv' ), '', $file );

									$options_key = 'learndash_reports_user_courses_' . $transient_hash;
									delete_option( $options_key );

									$options_key = '_transient_user-courses_' . $transient_hash;
									delete_option( $options_key );

									$options_key = '_transient_timeout_user-courses_' . $transient_hash;
									delete_option( $options_key );

									@unlink( $filename ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Let it be.

								} elseif ( substr( $file, 0, strlen( 'learndash_reports_user_quizzes' ) ) == 'learndash_reports_user_quizzes' ) {
									$transient_hash = str_replace( array( 'learndash_reports_user_quizzes', '.csv' ), '', $file );

									$options_key = 'learndash_reports_user_quizzes_' . $transient_hash;
									delete_option( $options_key );

									$options_key = '_transient_user-quizzes_' . $transient_hash;
									delete_option( $options_key );

									$options_key = '_transient_timeout_user-quizzes_' . $transient_hash;
									delete_option( $options_key );

									@unlink( $filename ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Let it be.
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Course category row actions
		 *
		 * If on the Course, Lessons, Topics section we display the
		 * WP Post Categories or Post Tags. We want to hide the row action 'view' links.
		 *
		 * @param array  $actions Actions.
		 * @param string $tag     Tag.
		 */
		public function ld_course_category_row_actions( $actions, $tag ) {
			global $learndash_post_types;
			global $pagenow, $taxnow;

			if ( ( 'edit-tags.php' === $pagenow ) && ( ( 'category' == $taxnow ) || ( 'post_tag' == $taxnow ) ) ) {
				if ( in_array( get_current_screen()->post_type, $learndash_post_types, true ) !== false ) {
					if ( isset( $actions['view'] ) ) {
						$current_href_old = get_term_link( $tag );
						$current_href_new = add_query_arg( 'post_type', get_current_screen()->post_type, $current_href_old );
						$actions['view']  = str_replace( $current_href_old, $current_href_new, $actions['view'] );
					}
				}
			}

			return $actions;
		}

		/**
		 * Function to dynamically control the 'the_content' filtering for this post_type instance.
		 * This is needed for example when using the 'the_content' filters manually and do not want the
		 * normal filters recursively applied.
		 *
		 * @since 2.5.9
		 *
		 * @param boolean $filter_check True if the_content filter is to be enabled.
		 * @param array   $post_types Limit change to specific instance post types. default is all.
		 */
		public static function content_filter_control( $filter_check = true, $post_types = array() ) {

			if ( empty( $post_types ) ) {
				$post_types = array_keys( SFWD_CPT_Instance::$instances );
			}
			foreach ( SFWD_CPT_Instance::$instances as $post_type => $instance ) {
				if ( in_array( $post_type, $post_types, true ) ) {
					$instance->content_filter_control( $filter_check );
				}
			}
		}

		/**
		 * Show admin notice message after 4.3.0.2 hub upgrade.
		 *
		 * @since 4.3.1
		 */
		public function hub_after_upgrade_admin_notice() {
			$current_screen = get_current_screen();
			if ( 'admin_page_learndash_hub_licensing' === $current_screen->base ) {
				return;
			}

			$hub_upgrade_notice = get_option( 'learndash_show_hub_upgrade_admin_notice' );
			if ( ! $hub_upgrade_notice ) {
				return;
			}

			if ( ! learndash_is_learndash_hub_active() ) {
				return;
			}

			?>
			<div class="notice notice-info is-dismissible learndash_hub_upgrade_dismiss" data-notice-dismiss-nonce="<?php echo esc_attr( wp_create_nonce( 'notice-dismiss-nonce-' . get_current_user_id() ) ); ?>">
				<p>
					<?php
					$hub_admin_page = 'admin.php?page=learndash_hub_licensing';
					echo sprintf(
						// translators: Message for hub plugin upgrade from 4.3.0.2 to 4.3.1.
						esc_html__( 'The LearnDash licensing system has changed locations! You\'ll now find your licenses in the %s section under the LearnDash settings menu.', 'learndash' ),
						sprintf(
							'<a href="%s">%s</a>',
							esc_url( $hub_admin_page ),
							esc_html__( 'LMS License', 'learndash' )
						)
					);
					?>
				</p>
			</div>
			<?php
		}

		/**
		 * Shows Telemetry modal.
		 *
		 * @since 4.5.0
		 * @since 4.5.1 - Added $current_screen param.
		 *
		 * @param WP_Screen $current_screen Current screen.
		 *
		 * @return void
		 */
		public function add_telemetry_modal( WP_Screen $current_screen ): void {
			if (
				(
					! empty( $current_screen->post_type )
					&& in_array( $current_screen->post_type, learndash_get_post_types(), true )
				)
				|| (
					! empty( $current_screen->parent_file )
					&& 'learndash-lms' === $current_screen->parent_file
				)
				|| (
					is_admin()
					&& isset( $_GET['page'] )
					&& false !== strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'learndash' )
					&& $_GET['page'] !== 'learndash-setup-wizard'
					&& $_GET['page'] !== 'learndash-design-wizard'
				)
			) {
				add_filter(
					'stellarwp/telemetry/learndash/optin_args', // cspell:disable-line.
					function( $args ) {
						$args['plugin_logo']        = LEARNDASH_LMS_PLUGIN_URL . 'assets/images/logo_black.svg';
						$args['plugin_logo_width']  = 205;
						$args['plugin_logo_height'] = 33;
						$args['plugin_logo_alt']    = 'LearnDash Logo';

						$args['heading'] = esc_html__( 'We hope you love LearnDash.', 'learndash' );

						$args['intro'] = sprintf(
							// translators: placeholder: username.
							esc_html__(
								'Hi, %1$s! This is an invitation to help us improve LearnDash products by sharing product usage data with StellarWP. LearnDash is part of the StellarWP family of brands. If you opt-in we\'ll share some helpful WordPress and StellarWP product info with you from time to time. And if you skip this, that\'s okay! Our products will continue to work.',
								'learndash'
							),
							$args['user_name']
						);

						$args['permissions_url'] = 'https://www.learndash.com/telemetry-tracking/';
						$args['tos_url']         = 'https://www.learndash.com/terms-and-conditions/';

						return $args;
					}
				);

				// cspell:disable-next-line.
				do_action( 'stellarwp/telemetry/learndash/optin' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.ValidHookName.UseUnderscores
			}
		}
	}
}

global $sfwd_lms;
$sfwd_lms = new SFWD_LMS();
