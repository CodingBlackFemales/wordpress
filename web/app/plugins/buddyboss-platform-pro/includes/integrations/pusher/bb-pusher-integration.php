<?php
/**
 * BuddyBoss Pusher Integration Class.
 *
 * @package BuddyBossPro\Integration
 * @since 2.1.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the BB Pusher class.
 *
 * @since 2.1.6
 */
class BB_Pusher_Integration extends BP_Integration {

	/**
	 * BB_Pusher_Integration constructor.
	 */
	public function __construct() {
		$this->start(
			'pusher',
			__( 'Pusher', 'buddyboss-pro' ),
			'pusher',
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
	 * @since 2.1.6
	 */
	public function setup_actions() {
		parent::setup_actions();

		add_action( 'bp_rest_api_init', array( $this, 'rest_api_init' ), 10 );
	}

	/**
	 * Register template path for BB.
	 *
	 * @since 2.1.6
	 * @return string template path
	 */
	public function register_template() {
		return bb_pusher_integration_path( '/templates' );
	}

	/**
	 * Includes.
	 *
	 * @param array $includes Array of file paths to include.
	 * @since 2.1.6
	 */
	public function includes( $includes = array() ) {
		$slashed_path = trailingslashit( bb_platform_pro()->integration_dir ) . $this->id . '/';

		$includes = array(
			'functions',
			'actions',
			'filters',
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
	 * Register Pusher setting tab.
	 *
	 * @since 2.1.6
	 */
	public function setup_admin_integration_tab() {

		require_once trailingslashit( bb_platform_pro()->integration_dir ) . $this->id . '/bb-pusher-admin-tab.php';

		new BB_Pusher_Admin_Integration_Tab(
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
	 * Init the BuddyBoss REST API.
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for description.
	 *
	 * @since 2.1.6
	 */
	public function rest_api_init( $controllers = array() ) {

		if ( ! class_exists( 'BB_REST_Pusher_Endpoint' ) ) {
			$file_path = bb_pusher_integration_path( '/includes/class-bb-rest-pusher-endpoint.php' );
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}
		}

		if ( class_exists( 'BB_REST_Pusher_Endpoint' ) ) {
			parent::rest_api_init(
				array(
					'BB_REST_Pusher_Endpoint',
				)
			);
		}

	}

}
