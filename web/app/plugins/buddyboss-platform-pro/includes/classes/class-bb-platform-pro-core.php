<?php
/**
 * BuddyBoss Platform Pro Core Loader.
 *
 * @package BuddyBossPro/Classes
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates the Platform Core.
 *
 * @since 1.0.0
 */
class BB_Platform_Pro_Core {

	/**
	 * Construct.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->bootstrap();
	}

	/**
	 * Populate the global data needed before BuddyPress can continue.
	 *
	 * This involves figuring out the currently required, activated, deactivated,
	 * and optional components.
	 *
	 * @since 1.0.0
	 */
	private function bootstrap() {

		// Load SSO.
		$this->load_sso();

		// Load Access Control.
		$this->load_access_control();

		// Load Reactions.
		$this->load_reactions();

		// Load Polls.
		$this->load_polls();

		// Load Schedule Posts.
		$this->load_schedule_posts();

		// Load Activity Topics.
		$this->load_activity_topics();

		// Load Platform Settings.
		$this->load_platform_settings();

		// Load Integrations.
		$this->load_integrations();

		/**
		 * Fires before the loading of individual integrations and after BuddyBoss Platform Pro Core.
		 *
		 * @since 1.0.0
		 */
		do_action( 'bb_platform_pro_core_loaded' );
	}

	/**
	 * Load integrations files
	 *
	 * @since 1.0.0
	 */
	private function load_integrations() {
		$bb_platform_pro = bb_platform_pro();

		$integration_dirs = glob( $bb_platform_pro->integration_dir . '/*', GLOB_ONLYDIR );

		$integrations = array();
		if ( ! empty( $integration_dirs ) ) {
			foreach ( $integration_dirs as $integration_dir ) {
				$integrations[] = basename( $integration_dir );
			}
		}

		/**
		 * Filters the included and optional integrations.
		 *
		 * @since 1.0.0
		 *
		 * @param array $value Array of included and optional integrations.
		 */
		$bb_platform_pro->integrations = apply_filters(
			'bb_platform_pro_integrations',
			$integrations
		);

		foreach ( $bb_platform_pro->integrations as $integration ) {
			$file = "{$bb_platform_pro->integration_dir}/{$integration}/bp-{$integration}-loader.php";
			if ( file_exists( $file ) ) {
				require $file;
			}

			$file = "{$bb_platform_pro->integration_dir}/{$integration}/bb-{$integration}-loader.php";
			if ( file_exists( $file ) ) {
				require $file;
			}
		}

		/**
		 * Fires after the loading of individual integrations.
		 *
		 * @since 1.0.0
		 */
		do_action( 'bb_platform_pro_core_integrations_included' );
	}

	/**
	 * Load access control files
	 *
	 * @since 1.1.0
	 */
	private function load_access_control() {
		$bb_platform_pro = bb_platform_pro();

		$file = "{$bb_platform_pro->access_control_dir}/bb-access-control-loader.php";
		if ( file_exists( $file ) ) {
			require $file;
		}

		/**
		 * Fires after the loading of individual access control.
		 *
		 * @since 1.1.0
		 */
		do_action( 'bb_platform_pro_core_access_control_included' );
	}

	/**
	 * Load SSO files.
	 *
	 * @since 2.6.30
	 */
	private function load_sso() {
		$bb_platform_pro = bb_platform_pro();

		$file = "{$bb_platform_pro->sso_dir}/bb-sso-loader.php";
		if ( file_exists( $file ) ) {
			require $file;
		}

		do_action( 'bb_platform_pro_core_sso_included' );
	}

	/**
	 * Load reactions files.
	 *
	 * @since 2.4.50
	 */
	private function load_reactions() {
		$bb_platform_pro = bb_platform_pro();

		$file = "{$bb_platform_pro->reactions_dir}/bb-reactions-loader.php";
		if ( file_exists( $file ) ) {
			require $file;
		}

		/**
		 * Fires after the loading reactions.
		 *
		 * @since 2.4.50
		 */
		do_action( 'bb_platform_pro_core_reactions_included' );
	}

	/**
	 * Load platform settings files.
	 *
	 * @since 1.2.0
	 */
	private function load_platform_settings() {
		$bb_platform_pro = bb_platform_pro();

		$file = "{$bb_platform_pro->platform_settings_dir}/bp-platform-settings-loader.php";
		if ( file_exists( $file ) ) {
			require $file;
		}

		/**
		 * Fires after the loading of individual platform settings.
		 *
		 * @since 1.2.0
		 */
		do_action( 'bb_platform_pro_platform_settings_included' );
	}

	/**
	 * Load schedule posts files.
	 *
	 * @since 2.5.20
	 */
	private function load_schedule_posts() {
		$bb_platform_pro = bb_platform_pro();

		$file = "{$bb_platform_pro->schedule_posts_dir}/bb-schedule-posts-loader.php";
		if ( file_exists( $file ) ) {
			require $file;
		}

		/**
		 * Fires after the loading schedule posts.
		 *
		 * @since 2.5.20
		 */
		do_action( 'bb_platform_pro_activity_schedule_posts_included' );
	}

	/**
	 * Load polls files.
	 *
	 * @since 2.6.00
	 */
	private function load_polls() {
		$bb_platform_pro = bb_platform_pro();

		$file = "{$bb_platform_pro->polls_dir}/bb-polls-loader.php";
		if ( file_exists( $file ) ) {
			require $file;
		}

		/**
		 * Fires after the loading polls.
		 *
		 * @since 2.6.00
		 */
		do_action( 'bb_platform_pro_core_polls_included' );
	}

	/**
	 * Load activity topics files.
	 *
	 * @since 2.7.40
	 */
	private function load_activity_topics() {
		$bb_platform_pro = bb_platform_pro();

		$file = "{$bb_platform_pro->topics_dir}/bb-topics-loader.php";
		if ( file_exists( $file ) ) {
			require $file;
		}

		/**
		 * Fires after the loading activity topics.
		 *
		 * @since 2.7.40
		 */
		do_action( 'bb_platform_pro_core_activity_topics_included' );
	}
}
