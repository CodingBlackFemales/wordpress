<?php
/**
 * BuddyBoss TutorLMS Integration Class.
 *
 * @package BuddyBossPro\TutorLMS
 *
 * @since 2.4.40
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the BB TutorLMS class.
 *
 * @since 2.4.40
 */
class BB_TutorLMS_Integration extends BP_Integration {

	/**
	 * BB_TutorLMS_Integration constructor.
	 *
	 * @since 2.4.40
	 */
	public function __construct() {
		$this->start(
			'tutorlms',
			__( 'TutorLMS', 'buddyboss-pro' ),
			'tutorlms',
			array(
				'required_plugin' => array(),
			)
		);

		// Include the code.
		$this->includes();

		if ( function_exists( 'tutor' ) ) {

			if ( bp_is_active( 'groups' ) ) {
				add_action( 'bp_init', array( $this, 'bb_remove_tutorlms_buddypress_integration' ), 9 );

				bb_load_tutorlms_group();

				$extension = new BB_TutorLMS_Group_Setting();
				add_action( 'bp_actions', array( $extension, '_register' ), 8 );
			}

			// Register the template stack for buddyboss so that theme can override.
			bp_register_template_stack( array( $this, 'register_template' ) );
		}
	}

	/**
	 * Function to remove TutorLMS buddypress integration.
	 *
	 * @since 2.4.40
	 */
	public function bb_remove_tutorlms_buddypress_integration() {
		if ( function_exists( 'bb_remove_class_action' ) ) {
			bb_remove_class_action( 'bp_init', 'TUTOR_BP\init', 'load_group_extension' );
		}
	}

	/**
	 * Includes.
	 *
	 * @param array $includes Array of file paths to include.
	 *
	 * @since 2.4.40
	 */
	public function includes( $includes = array() ) {
		$slashed_path = trailingslashit( bb_platform_pro()->integration_dir ) . $this->id . '/';

		$includes = array(
			'cache',
			'actions',
			'filters',
			'functions',
		);

		// Loop through files to be included.
		foreach ( (array) $includes as $file ) {

			$paths = array(

				// Passed with no extension.
				'bb-' . $this->id . '/bb-' . $this->id . '-' . $file . '.php',
				'bb-' . $this->id . '-' . $file . '.php',
				'bb-' . $this->id . '/' . $file . '.php',

				// Passed with extension.
				$file,
				'bb-' . $this->id . '-' . $file,
				'bb-' . $this->id . '/' . $file,
			);

			foreach ( $paths as $path ) {
				if ( @is_file( $slashed_path . $path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					require $slashed_path . $path;
					break;
				}
			}
		}

		// exclude specific files explicitly.
		if ( function_exists( 'tutor' ) ) {
			if ( bp_is_active( 'groups' ) ) {
				require_once dirname( __FILE__ ) . '/includes/class-bb-tutorlms-groups.php';
				require_once dirname( __FILE__ ) . '/includes/class-bb-tutorlms-group-settings.php';
			}
			require_once dirname( __FILE__ ) . '/includes/class-bb-tutorlms-profile.php';
		}
	}

	/**
	 * Register template path for BP.
	 *
	 * @since 2.4.40
	 * @return string template path
	 */
	public function register_template() {
		return bb_tutorlms_integration_path( '/templates' );
	}

	/**
	 * Register TutorLMS setting tab.
	 *
	 * @since 2.4.40
	 */
	public function setup_admin_integration_tab() {

		require_once trailingslashit( bb_platform_pro()->integration_dir ) . $this->id . '/bb-tutorlms-admin-tab.php';

		new BB_TutorLMS_Admin_Integration_Tab(
			"bb-{$this->id}",
			$this->name,
			array(
				'root_path'       => trailingslashit( bb_platform_pro()->integration_dir ) . $this->id,
				'root_url'        => trailingslashit( bb_platform_pro()->integration_url ) . $this->id,
				'required_plugin' => $this->required_plugin,
			)
		);
	}

	/**
	 * Setup actions for integration.
	 *
	 * @since 2.4.40
	 */
	public function setup_actions() {
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
		add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
		add_action( 'bp_setup_cache_groups', array( $this, 'setup_cache_groups' ), 10 );
		parent::setup_actions();
	}

	/**
	 * Enqueue admin related scripts and styles.
	 *
	 * @since 2.4.40
	 */
	public function enqueue_scripts_styles() {
		$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$rtl_css = is_rtl() ? '-rtl' : '';
		wp_enqueue_style( 'bb-tutorlms-admin', bb_tutorlms_integration_url( '/assets/css/bb-tutorlms-admin' . $rtl_css . $min . '.css' ), false, buddypress()->version );
		wp_enqueue_script( 'bb-tutorlms-admin', bb_tutorlms_integration_url( '/assets/js/bb-tutorlms-admin' . $min . '.js' ), false, buddypress()->version );
		wp_localize_script(
			'bb-tutorlms-admin',
			'bbTutorLMSVars',
			array(
				'ajax_url'                  => admin_url( 'admin-ajax.php' ),
				'select_course_placeholder' => __( 'Start typing a course name to associate with this group.', 'buddyboss-pro' ),
			)
		);
	}

	/**
	 * Setup cache.
	 *
	 * @since 2.4.40
	 */
	public function setup_cache_groups() {
		// Global groups.
		wp_cache_add_global_groups(
			array(
				'bb_tutorlms',
			)
		);
	}
}
