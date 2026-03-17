<?php
/**
 * BuddyBoss MemberpressLMS Integration Class.
 *
 * @package BuddyBossPro\Integration\MemberpressLMS
 *
 * @since 2.6.30
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the BB MemberpressLMS class.
 *
 * @since 2.6.30
 */
class BB_MeprLMS_Integration extends BP_Integration {

	/**
	 * BB_MeprLMS_Integration constructor.
	 *
	 * @since 2.6.30
	 */
	public function __construct() {
		$this->start(
			'meprlms',
			'MemberPress',
			'meprlms',
			array(
				'required_plugin' => array(),
			)
		);

		// Include the code.
		$this->includes();

		add_action( 'bp_init', array( $this, 'load_init' ), 5 );
	}

	/**
	 * BB_MeprLMS_Integration init.
	 *
	 * @since 2.6.70
	 */
	public function load_init() {
		if ( class_exists( 'memberpress\courses\helpers\Courses' ) && ! bb_pro_should_lock_features() ) {

			if ( bp_is_active( 'groups' ) ) {

				bb_load_meprlms_group();

				// Register the group extension.
				$this->load_group_extension();

				// Register the group extension for REST API.
				add_action( 'bp_rest_group_detail', array( $this, 'load_group_extension' ), 10, 2 );
			}

			// Register the template stack for buddyboss so that theme can override.
			bp_register_template_stack( array( $this, 'register_template' ) );
		}

		// Always register the activity filter to exclude MemberPress LMS activities when features are locked.
		add_filter( 'bp_activity_get_where_conditions', array( $this, 'exclude_meprlms_activities_when_locked' ), 10, 5 );
	}

	/**
	 * Includes.
	 *
	 * @param array $includes Array of file paths to include.
	 *
	 * @since 2.6.30
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
		if ( class_exists( 'memberpress\courses\helpers\Courses' ) ) {
			if ( bp_is_active( 'groups' ) ) {
				require_once $slashed_path . 'includes/class-bb-meprlms-groups.php';
				require_once $slashed_path . 'includes/class-bb-meprlms-group-settings.php';
			}
			require_once $slashed_path . 'includes/class-bb-meprlms-profile.php';
			require_once $slashed_path . 'includes/class-bb-meprlms-rest.php';
		}
	}

	/**
	 * Register template path for BP.
	 *
	 * @since 2.6.30
	 *
	 * @return string template path
	 */
	public function register_template() {
		return bb_meprlms_integration_path( '/templates' );
	}

	/**
	 * Exclude MemberPress LMS activities when features are locked.
	 *
	 * @since 2.11.0
	 *
	 * @param array  $where_conditions Current WHERE conditions.
	 * @param array  $r                Query arguments.
	 * @param string $select_sql       SELECT clause.
	 * @param string $from_sql         FROM clause.
	 * @param string $join_sql         JOIN clause.
	 *
	 * @return array Modified WHERE conditions.
	 */
	public function exclude_meprlms_activities_when_locked( $where_conditions, $r, $select_sql, $from_sql, $join_sql ) {
		if ( bb_pro_should_lock_features() ) {
			$where_conditions['excluded_meprlms_actions'] = "a.action NOT LIKE 'bb_meprlms_%'";
		}

		return $where_conditions;
	}

	/**
	 * Register MemberpressLMS setting tab.
	 *
	 * @since 2.6.30
	 */
	public function setup_admin_integration_tab() {

		require_once trailingslashit( bb_platform_pro()->integration_dir ) . $this->id . '/includes/admin/class-bb-meprlms-admin-integration-tab.php';
		new BB_MeprLMS_Admin_Integration_Tab(
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
	 * @since 2.6.30
	 */
	public function setup_actions() {
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
		add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );
		add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
		add_action( 'bp_setup_cache_groups', array( $this, 'setup_cache_groups' ), 10 );
		parent::setup_actions();
	}

	/**
	 * Enqueue frontend related styles.
	 *
	 * @since 2.6.30
	 */
	public function enqueue_frontend_styles() {
		$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$rtl_css = is_rtl() ? '-rtl' : '';
		wp_enqueue_style( 'bb-meprlms-frontend', bb_meprlms_integration_url( '/assets/css/meprlms-frontend' . $rtl_css . $min . '.css' ), false, buddypress()->version );
		wp_enqueue_script( 'bb-meprlms-frontend', bb_meprlms_integration_url( '/assets/js/bb-meprlms-frontend' . $min . '.js' ), false, buddypress()->version );
	}

	/**
	 * Enqueue admin related scripts and styles.
	 *
	 * @since 2.6.30
	 */
	public function enqueue_scripts_styles() {
		// On the admin, get the group id out of the $_GET params.
		if (
			! ( // Edit group page.
				is_admin() &&
				isset( $_GET['page'] ) &&
				'bp-groups' === $_GET['page'] &&
				! empty( $_GET['gid'] )
			) &&
			! bp_is_group_admin_page() && // Manage group page.
			! ( bp_is_group_create() && bp_is_group_creation_step( 'courses' ) ) // create group page.
		) {
			return;
		}

		$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$rtl_css = is_rtl() ? '-rtl' : '';

		wp_enqueue_script( 'bp-select2' );
		wp_enqueue_style( 'bp-select2' );
		wp_enqueue_style( 'bb-meprlms-admin', bb_meprlms_integration_url( '/assets/css/bb-meprlms-admin' . $rtl_css . $min . '.css' ), false, buddypress()->version );
		wp_enqueue_script( 'bb-meprlms-admin', bb_meprlms_integration_url( '/assets/js/bb-meprlms-admin' . $min . '.js' ), false, buddypress()->version );
		wp_localize_script(
			'bb-meprlms-admin',
			'bbMeprLMSVars',
			array(
				'ajax_url'                  => admin_url( 'admin-ajax.php' ),
				'security'                  => wp_create_nonce( 'bb-meprlms-security-nonce' ),
				'select_course_placeholder' => __( 'Start typing a course name to associate with this group.', 'buddyboss-pro' ),
			)
		);
	}

	/**
	 * Setup cache.
	 *
	 * @since 2.6.30
	 */
	public function setup_cache_groups() {
		// Global groups.
		wp_cache_add_global_groups(
			array(
				'bb_meprlms',
			)
		);
	}

	/**
	 * Register the group extension.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	public function load_group_extension() {
		$extension = new BB_MeprLMS_Group_Settings();
		add_action( 'bp_actions', array( $extension, '_register' ), 8 );
	}
}
