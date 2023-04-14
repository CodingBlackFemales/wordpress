<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * BuddyBoss Zoom Integration Class.
 *
 * @package BuddyBossPro/Integration
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp zoom class.
 *
 * @since 1.0.0
 */
class BP_Zoom_Integration extends BP_Integration {

	/**
	 * BP_Zoom_Integration constructor.
	 */
	public function __construct() {
		$this->start(
			'zoom',
			__( 'Zoom', 'buddyboss-pro' ),
			'zoom',
			array(
				'required_plugin' => array(),
			)
		);

		// Include the code.
		$this->includes();

		if ( bbp_pro_is_license_valid() ) {
			new BP_Zoom_Conference_Api();
			new BP_Zoom_Group();
			new BP_Zoom_Ajax();
			new BP_Zoom_Blocks();

			// Register the template stack for buddyboss so that theme can overrride.
			bp_register_template_stack( array( $this, 'register_template' ) );
		}
	}

	/**
	 * Setup actions for integration.
	 *
	 * @since 1.0.0
	 */
	public function setup_actions() {
		add_action( 'bp_init', array( $this, 'register_meta_table' ), 9 );
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
		parent::setup_actions();
	}

	/**
	 * Enqueue admin related scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts_styles() {
		$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$rtl_css = is_rtl() ? '-rtl' : '';
		wp_enqueue_style( 'bp-zoom-admin', bp_zoom_integration_url( '/assets/css/bp-zoom-admin' . $rtl_css . $min . '.css' ), false, bb_platform_pro()->version );
	}

	/**
	 * Register template path for BP.
	 *
	 * @since 1.0.0
	 * @return string template path
	 */
	public function register_template() {
		return bp_zoom_integration_path( '/templates' );
	}

	/**
	 * Register meta table for integration.
	 *
	 * @since 1.0.0
	 */
	public function register_meta_table() {
		global $wpdb;
		$wpdb->meetingmeta = bp_core_get_table_prefix() . 'bp_zoom_meeting_meta';
		$wpdb->webinarmeta = bp_core_get_table_prefix() . 'bp_zoom_webinar_meta';
	}

	/**
	 * Includes.
	 *
	 * @param array $includes Array of file paths to include.
	 * @since 1.0.0
	 */
	public function includes( $includes = array() ) {
		$slashed_path = trailingslashit( bb_platform_pro()->integration_dir ) . $this->id . '/';

		$includes = array(
			'lib/firebase/php-jwt/src/JWT.php',
			'cache',
			'actions',
			'filters',
			'template',
			'functions',
			'recording-functions',
		);

		// Loop through files to be included.
		foreach ( (array) $includes as $file ) {

			$paths = array(

				// Passed with no extension.
				'bp-' . $this->id . '/bp-' . $this->id . '-' . $file . '.php',
				'bp-' . $this->id . '-' . $file . '.php',
				'bp-' . $this->id . '/' . $file . '.php',

				// Passed with extension.
				$file,
				'bp-' . $this->id . '-' . $file,
				'bp-' . $this->id . '/' . $file,
			);

			foreach ( $paths as $path ) {
				if ( @is_file( $slashed_path . $path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					require $slashed_path . $path;
					break;
				}
			}
		}
	}

	/**
	 * Register zoom setting tab
	 *
	 * @since 1.0.0
	 */
	public function setup_admin_integration_tab() {

		require_once trailingslashit( bb_platform_pro()->integration_dir ) . $this->id . '/bp-zoom-admin-tab.php';

		new BP_Zoom_Admin_Integration_Tab(
			"bp-{$this->id}",
			$this->name,
			array(
				'root_path'       => trailingslashit( bb_platform_pro()->integration_dir ) . $this->id,
				'root_url'        => trailingslashit( bb_platform_pro()->integration_url ) . $this->id,
				'required_plugin' => $this->required_plugin,
			)
		);
	}
}
