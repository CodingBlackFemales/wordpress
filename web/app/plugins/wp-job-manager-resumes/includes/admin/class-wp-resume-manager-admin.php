<?php
/**
 * File containing the class WP_Resume_Manager_Admin.
 *
 * @package wp-job-manager-resumes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Resume_Manager_Admin class.
 */
class WP_Resume_Manager_Admin {

	/**
	 * The settings page.
	 *
	 * @var WP_Resume_Manager_Settings
	 */
	private WP_Resume_Manager_Settings $settings_page;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		include_once 'class-wp-resume-manager-cpt.php';
		include_once 'class-wp-resume-manager-writepanels.php';
		include_once 'class-wp-resume-manager-settings.php';
		include_once 'class-wp-resume-manager-setup.php';

		add_filter( 'job_manager_admin_screen_ids', [ $this, 'add_screen_ids' ] );
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 12 );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ], 20 );

		WP_Resume_Manager_Writepanels::instance()->init();

		$this->settings_page = new WP_Resume_Manager_Settings();
	}

	/**
	 * Add screen ids
	 *
	 * @param array $screen_ids
	 * @return  array
	 */
	public function add_screen_ids( $screen_ids ) {
		$screen_ids[] = 'edit-resume';
		$screen_ids[] = 'resume';
		$screen_ids[] = 'resume_page_resume-manager-settings';
		return $screen_ids;
	}

	/**
	 * admin_enqueue_scripts function.
	 *
	 * @param string $hook Hook suffix for the current admin page.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook ) {
		global $wp_scripts;
		global $post_type;

		// Only load scripts on Resume admin pages.
		if ( 'resume_page_resume-manager-settings' !== $hook && 'resume' !== $post_type ) {
			return;
		}

		$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';
		$jquery_version = preg_replace( '/-wp/', '', $jquery_version );
		wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css' );
		wp_enqueue_style( 'resume_manager_admin_css', RESUME_MANAGER_PLUGIN_URL . '/assets/dist/css/admin.css', [ 'dashicons' ], RESUME_MANAGER_VERSION );
		wp_enqueue_script( 'resume_manager_admin_js', RESUME_MANAGER_PLUGIN_URL . '/assets/dist/js/admin.js', [ 'jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable' ], RESUME_MANAGER_VERSION, true );
	}

	/**
	 * admin_menu function.
	 *
	 * @access public
	 * @return void
	 */
	public function admin_menu() {
		add_submenu_page( 'edit.php?post_type=resume', __( 'Settings', 'wp-job-manager-resumes' ), __( 'Settings', 'wp-job-manager-resumes' ), 'manage_options', 'resume-manager-settings', [ $this->settings_page, 'output' ] );
	}
}

new WP_Resume_Manager_Admin();
