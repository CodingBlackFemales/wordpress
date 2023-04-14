<?php
/**
 * BuddyBoss OneSignal Integration Class.
 *
 * @package BuddyBossPro/Integration
 * @since 2.0.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the bb OneSignal class.
 *
 * @since 2.0.3
 */
class BB_OneSignal_Integration extends BP_Integration {

	/**
	 * BB_OneSignal_Integration constructor.
	 */
	public function __construct() {
		$this->start(
			'onesignal',
			__( 'OneSignal', 'buddyboss-pro' ),
			'onesignal',
			array(
				'required_plugin' => array(),
			)
		);

		// Include the code.
		$this->includes();

		if ( bbp_pro_is_license_valid() ) {
			// Register the template stack for buddyboss so that theme can override.
			bp_register_template_stack( array( $this, 'register_template' ) );
		}
	}

	/**
	 * Setup actions for integration.
	 *
	 * @since 2.0.3
	 */
	public function setup_actions() {
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );

		parent::setup_actions();
	}

	/**
	 * Enqueue admin related scripts and styles.
	 *
	 * @since 2.0.3
	 */
	public function enqueue_scripts_styles() {
		$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$rtl_css = is_rtl() ? '-rtl' : '';
		wp_enqueue_style( 'bb-onesignal-admin', bb_onesignal_integration_url( '/assets/css/bb-onesignal-admin' . $rtl_css . $min . '.css' ), false, bb_platform_pro()->version );

		$active_tab = bp_core_get_admin_active_tab();

		if ( 'bp-notifications' === $active_tab ) {
			wp_enqueue_style( 'thickbox' );
			wp_enqueue_script( 'media-upload' );

			// Get Avatar Uploader.
			if ( class_exists( 'BP_Attachment_Avatar' ) ) {
				bp_attachments_enqueue_scripts( 'BP_Attachment_Avatar' );
			}
		}
	}

	/**
	 * Register template path for BB.
	 *
	 * @since 2.0.3
	 * @return string template path
	 */
	public function register_template() {
		return bb_onesignal_integration_path( '/templates' );
	}

	/**
	 * Includes.
	 *
	 * @param array $includes Array of file paths to include.
	 * @since 2.0.3
	 */
	public function includes( $includes = array() ) {
		$slashed_path = trailingslashit( bb_platform_pro()->integration_dir ) . $this->id . '/';

		$includes = array(
			'functions',
			'actions',
			'filters',
			'cache',
			'template',
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
	}

	/**
	 * Register OneSignal setting tab.
	 *
	 * @since 2.0.3
	 */
	public function setup_admin_integration_tab() {

		require_once trailingslashit( bb_platform_pro()->integration_dir ) . $this->id . '/bb-onesignal-admin-tab.php';

		new BB_OneSignal_Admin_Integration_Tab(
			"bb-{$this->id}",
			$this->name,
			array(
				'root_path'       => trailingslashit( bb_platform_pro()->integration_dir ) . $this->id,
				'root_url'        => trailingslashit( bb_platform_pro()->integration_url ) . $this->id,
				'required_plugin' => $this->required_plugin,
			)
		);
	}
}
