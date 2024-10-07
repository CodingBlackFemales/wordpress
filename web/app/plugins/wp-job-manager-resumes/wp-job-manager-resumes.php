<?php
/**
 * Plugin Name: WP Job Manager - Resume Manager
 * Plugin URI: https://wpjobmanager.com/add-ons/resume-manager/
 * Description: Manage candidate resumes from the WordPress admin panel, and allow candidates to post their resumes directly to your site.
 * Version: 2.2.0
 * Author: Automattic
 * Author URI: https://wpjobmanager.com
 * Requires at least: 6.2
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * Text Domain: wp-job-manager-resumes
 * Domain Path: /languages/
 *
 * WPJM-Product: wp-job-manager-resumes
 *
 * Copyright: 2024 Automattic
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package wp-job-manager-resumes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Resume_Manager class.
 */
class WP_Resume_Manager {
	const JOB_MANAGER_CORE_MIN_VERSION = '1.31.1';

	/**
	 * Post types class.
	 *
	 * @var WP_Resume_Manager_Post_Types
	 */
	public WP_Resume_Manager_Post_Types $post_types;

	/**
	 * Apply with resume class.
	 *
	 * @var WP_Resume_Manager_Apply
	 */
	public WP_Resume_Manager_Apply $apply;

	/**
	 * Form helper class.
	 *
	 * @var WP_Resume_Manager_Forms
	 */
	public WP_Resume_Manager_Forms $forms;

	/**
	 * __construct function.
	 */
	public function __construct() {
		// Define constants.
		define( 'RESUME_MANAGER_VERSION', '2.2.0' );
		define( 'RESUME_MANAGER_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'RESUME_MANAGER_PLUGIN_URL', untrailingslashit( plugins_url( '', ( __FILE__ ) ) ) );

		// Includes.
		include_once dirname( __FILE__ ) . '/includes/wp-resume-manager-functions.php';
		include_once dirname( __FILE__ ) . '/includes/wp-resume-manager-template.php';
		include_once dirname( __FILE__ ) . '/includes/class-wp-resume-manager-post-types.php';
		include_once dirname( __FILE__ ) . '/includes/class-wp-resume-manager-deprecated-hooks.php';

		// Load 3rd party customizations.
		include_once dirname( __FILE__ ) . '/includes/3rd-party/3rd-party.php';

		// Init class needed for activation.
		$this->post_types = new WP_Resume_Manager_Post_Types();

		// Activation - works with symlinks.
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( $this->post_types, 'register_post_types' ), 10 );
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), array( $this, 'install' ), 10 );
		register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), 'flush_rewrite_rules', 15 );

		// Set up startup actions.
		WP_Resume_Manager_Deprecated_Hooks::init();
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ), 12 );
		add_action( 'plugins_loaded', array( $this, 'init_plugin' ), 13 );
		add_action( 'plugins_loaded', array( $this, 'admin' ), 14 );
		add_action( 'admin_notices', array( $this, 'version_check' ) );
	}

	/**
	 * Initializes plugin.
	 */
	public function init_plugin() {
		if ( ! class_exists( 'WP_Job_Manager' ) ) {
			return;
		}

		// Includes.
		include_once dirname( __FILE__ ) . '/includes/class-wp-resume-manager-shortcodes.php';
		include_once dirname( __FILE__ ) . '/includes/class-wp-resume-manager-ajax.php';
		include_once dirname( __FILE__ ) . '/includes/class-wp-resume-manager-geocode.php';
		include_once dirname( __FILE__ ) . '/includes/class-wp-resume-manager-forms.php';
		include_once dirname( __FILE__ ) . '/includes/class-wp-resume-manager-apply.php';
		include_once dirname( __FILE__ ) . '/includes/class-wp-resume-manager-resume-lifecycle.php';
		include_once dirname( __FILE__ ) . '/includes/class-wp-resume-manager-privacy.php';
		include_once dirname( __FILE__ ) . '/includes/class-wp-resume-manager-file-cleaner.php';
		include_once dirname( __FILE__ ) . '/includes/abstracts/abstract-wp-resume-manager-email.php';
		include_once dirname( __FILE__ ) . '/includes/class-wp-resume-manager-email-notifications.php';

		// Init classes.
		$this->apply = new WP_Resume_Manager_Apply();
		$this->forms = new WP_Resume_Manager_Forms();
		WP_Resume_Manager_Privacy::init();
		WP_Resume_Manager_File_Cleaner::init();
		add_action( 'init', array( 'WP_Resume_Manager_Email_Notifications', 'init' ) );

		// Initialize post types.
		$this->post_types->init_post_types();

		// Actions.
		add_action( 'widgets_init', array( $this, 'widgets_init' ), 12 );
		add_action( 'switch_theme', array( $this->post_types, 'register_post_types' ), 10 );
		add_action( 'switch_theme', 'flush_rewrite_rules', 15 );
		add_action( 'admin_init', array( $this, 'updater' ) );
		add_action( 'rest_api_init', [ $this, 'rest_init' ] );

		add_filter( 'job_manager_enhanced_select_enabled', array( $this, 'is_enhanced_select_required_on_page' ) );
		add_filter( 'job_manager_enqueue_frontend_style', array( $this, 'is_frontend_style_required_on_page' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );

		add_action( 'template_redirect', array( $this, 'disable_resume_post_type_page' ) );
	}

	/**
	 * Checks WPJM core version.
	 */
	public function version_check() {
		if ( ! class_exists( 'WP_Job_Manager' ) || ! defined( 'JOB_MANAGER_VERSION' ) ) {
			$screen = get_current_screen();
			if ( null !== $screen && 'plugins' === $screen->id ) {
				$this->display_error( __( '<em>WP Job Manager - Resume Manager</em> requires WP Job Manager to be installed and activated.', 'wp-job-manager-resumes' ) );
			}
		} elseif (
			/**
			 * Filters if WPJM core's version should be checked.
			 *
			 * @since 1.16.0
			 *
			 * @param bool   $do_check                       True if the add-on should do a core version check.
			 * @param string $minimum_required_core_version  Minimum version the plugin is reporting it requires.
			 */
			apply_filters( 'job_manager_addon_core_version_check', true, self::JOB_MANAGER_CORE_MIN_VERSION )
			&& version_compare( JOB_MANAGER_VERSION, self::JOB_MANAGER_CORE_MIN_VERSION, '<' )
		) {
			// translators:  %1$s is the version that is required; %2$s is the current version of WPJM.
			$this->display_error( sprintf( __( '<em>WP Job Manager - Resume Manager</em> requires WP Job Manager %1$s (you are using %2$s).', 'wp-job-manager-resumes' ), self::JOB_MANAGER_CORE_MIN_VERSION, JOB_MANAGER_VERSION ) );
		}
	}

	/**
	 * Display error message notice in the admin.
	 *
	 * @param string $message
	 */
	private function display_error( $message ) {
		echo '<div class="error">';
		echo '<p>' . wp_kses_post( $message ) . '</p>';
		echo '</div>';
	}

	/**
	 * Handle Updates
	 */
	public function updater() {
		if ( version_compare( RESUME_MANAGER_VERSION, get_option( 'wp_resume_manager_version' ), '>' ) ) {
			include_once dirname( __FILE__ ) . '/includes/class-wp-resume-manager-install.php';
		}
	}

	/**
	 * Loads the REST API functionality.
	 */
	public function rest_init() {
		include_once RESUME_MANAGER_PLUGIN_DIR . '/includes/class-wp-resume-manager-rest-api.php';
		WP_Resume_Manager_REST_API::init();
	}

	/**
	 * Handles install.
	 */
	public function install() {
		include_once dirname( __FILE__ ) . '/includes/class-wp-resume-manager-install.php';
	}

	/**
	 * Include admin
	 */
	public function admin() {
		if ( is_admin() && class_exists( 'WP_Job_Manager' ) ) {
			include_once 'includes/admin/class-wp-resume-manager-admin.php';
		}
	}

	/**
	 * Includes once plugins are loaded
	 */
	public function widgets_init() {
		include_once dirname( __FILE__ ) . '/includes/class-wp-resume-manager-widgets.php';
	}

	/**
	 * Localisation
	 *
	 * @access private
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wp-job-manager-resumes' );

		load_textdomain( 'wp-job-manager-resumes', WP_LANG_DIR . "/wp-job-manager-resumes/wp-job-manager-resumes-$locale.mo" );
		load_plugin_textdomain( 'wp-job-manager-resumes', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}


	/**
	 * Filters if enhanced select is needed on this page.
	 *
	 * @param bool $enhanced_select_used_on_page
	 *
	 * @return bool
	 */
	public function is_enhanced_select_required_on_page( $enhanced_select_used_on_page ) {
		$enhanced_select_shortcodes = array( 'submit_resume_form', 'resumes', 'candidate_dashboard' );
		if ( $enhanced_select_used_on_page || has_wp_resume_manager_shortcode( null, $enhanced_select_shortcodes ) ) {
			return true;
		}
		return $enhanced_select_used_on_page;
	}

	/**
	 * Filters if WPJM's frontend styles is need on this page.
	 *
	 * @param bool $is_frontend_style_enabled
	 * @return bool
	 */
	public function is_frontend_style_required_on_page( $is_frontend_style_enabled ) {
		if ( $is_frontend_style_enabled || is_wp_resume_manager() ) {
			return true;
		}
		return $is_frontend_style_enabled;
	}

	/**
	 * Frontend_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() {
		global $post;
		$ajax_url         = admin_url( 'admin-ajax.php', 'relative' );
		$ajax_filter_deps = array( 'jquery' );

		// WPML workaround until this is standardized.
		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			$ajax_url = add_query_arg( 'lang', ICL_LANGUAGE_CODE, $ajax_url );
		}

		if ( wp_script_is( 'select2', 'registered' ) ) {
			$ajax_filter_deps[] = 'select2';
			wp_enqueue_style( 'select2' );
		} elseif ( wp_script_is( 'chosen', 'registered' ) && apply_filters( 'job_manager_chosen_enabled', true ) ) {
			// Support for themes and plugins not using WP Job Manager 1.32.0's select2 support.
			$ajax_filter_deps[] = 'chosen';
		}

		wp_register_script( 'wp-resume-manager-ajax-filters', RESUME_MANAGER_PLUGIN_URL . '/assets/dist/js/ajax-filters.js', $ajax_filter_deps, RESUME_MANAGER_VERSION, true );
		wp_register_script( 'wp-resume-manager-candidate-dashboard', RESUME_MANAGER_PLUGIN_URL . '/assets/dist/js/candidate-dashboard.js', array( 'jquery' ), RESUME_MANAGER_VERSION, true );
		wp_register_script( 'wp-resume-manager-resume-submission', RESUME_MANAGER_PLUGIN_URL . '/assets/dist/js/resume-submission.js', array( 'jquery', 'jquery-ui-sortable' ), RESUME_MANAGER_VERSION, true );
		wp_register_script( 'wp-resume-manager-resume-contact-details', RESUME_MANAGER_PLUGIN_URL . '/assets/dist/js/contact-details.js', array( 'jquery' ), RESUME_MANAGER_VERSION, true );

		wp_localize_script(
			'wp-resume-manager-resume-submission',
			'resume_manager_resume_submission',
			array(
				'i18n_navigate'       => __( 'If you wish to edit the posted details use the "edit resume" button instead, otherwise changes may be lost.', 'wp-job-manager-resumes' ),
				'i18n_confirm_remove' => __( 'Are you sure you want to remove this item?', 'wp-job-manager-resumes' ),
				'i18n_remove'         => __( 'remove', 'wp-job-manager-resumes' ),
			)
		);
		wp_localize_script(
			'wp-resume-manager-ajax-filters',
			'resume_manager_ajax_filters',
			array(
				'ajax_url' => $ajax_url,
				'is_rtl'   => is_rtl() ? 1 : 0,
			)
		);
		wp_localize_script(
			'wp-resume-manager-candidate-dashboard',
			'resume_manager_candidate_dashboard',
			array(
				'i18n_confirm_delete' => __( 'Are you sure you want to delete this resume?', 'wp-job-manager-resumes' ),
			)
		);

		wp_enqueue_style( 'wp-job-manager-resume-frontend', RESUME_MANAGER_PLUGIN_URL . '/assets/dist/css/frontend.css', [ 'dashicons' ], RESUME_MANAGER_VERSION );
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'submit_resume_form' ) ) {
			wp_enqueue_style( 'wp-resume-manager-resume-submission', RESUME_MANAGER_PLUGIN_URL . '/assets/dist/css/resume-submission.css', array(), RESUME_MANAGER_VERSION );
		}
	}

	public function disable_resume_post_type_page() {
		if ( empty( $_GET['post_type'] ) || 'resume' !== $_GET['post_type'] ) {
			return;
		}

		if ( resume_manager_user_can_browse_resumes() ) {
			return;
		}

		wp_safe_redirect( home_url() );
		exit;
	}
}

$GLOBALS['resume_manager'] = new WP_Resume_Manager();
