<?php
/**
 * Legacy course grid loader class.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Course_Grid\Legacy;

use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;

/**
 * Legacy Course Grid loader class.
 *
 * @since 4.21.4
 */
class Loader {
	/**
	 * Legacy plugin directory path constant.
	 *
	 * @since 4.21.4
	 *
	 * @var string
	 */
	private const PLUGIN_DIRECTORY_CONSTANT = 'LEARNDASH_COURSE_GRID_PLUGIN_PATH';

	/**
	 * Legacy plugin directory name default.
	 *
	 * @since 4.21.4
	 *
	 * @var string
	 */
	private const PLUGIN_DIRECTORY_DEFAULT = 'learndash-course-grid';

	/**
	 * Legacy plugin file name.
	 *
	 * @since 4.21.4
	 *
	 * @var string
	 */
	private const PLUGIN_FILE = 'learndash_course_grid.php';

	/**
	 * If a legacy installation of Course Grid is found on the site, deactivate it.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function deactivate(): void {
		if ( ! $this->is_legacy_plugin_active() ) {
			return;
		}

		deactivate_plugins( $this->get_legacy_plugin_path() );

		$referrer             = wp_get_referer();
		$redirect_destination = $this->get_redirect( $referrer );

		if ( $redirect_destination === false ) {
			return;
		}

		// Force a redirect to unload the legacy plugin right away.
		wp_safe_redirect( $redirect_destination );

		exit;
	}

	/**
	 * If the course grid module has not been loaded (via the legacy plugin, or otherwise), load it.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function load(): void {
		if ( $this->is_loaded() ) {
			return;
		}

		$course_grid_module_path = LEARNDASH_LMS_PLUGIN_DIR . '/includes/course-grid/' . self::PLUGIN_FILE;

		if ( ! file_exists( $course_grid_module_path ) ) {
			return;
		}

		require_once $course_grid_module_path;
	}

	/**
	 * Updates the legacy plugin activation error notice.
	 *
	 * @since 4.21.4
	 *
	 * @param string $markup The notice markup.
	 *
	 * @return string The modified notice markup.
	 */
	public function update_legacy_plugin_activation_notice( $markup ) {
		if (
			! is_admin()
			|| ! function_exists( 'get_current_screen' )
		) {
			return $markup;
		}

		$screen = get_current_screen();

		if (
			! $screen
			|| $screen->id !== 'plugins'
		) {
			return $markup;
		}

		$plugin = SuperGlobals::get_get_var( 'plugin' );

		if (
			! $plugin
			|| $plugin !== self::PLUGIN_DIRECTORY_DEFAULT . '/' . self::PLUGIN_FILE
		) {
			return $markup;
		}

		$error_nonce = SuperGlobals::get_get_var( '_error_nonce' );

		if (
			! is_string( $error_nonce )
			|| ! wp_verify_nonce(
				$error_nonce,
				'plugin-activation-error_' . self::PLUGIN_DIRECTORY_DEFAULT . '/' . self::PLUGIN_FILE
			)
		) {
			return $markup;
		}

		// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- We need to use the default WordPress text domain here because we want to override the WordPress default error message.
		$default_message = __( 'Plugin could not be activated because it triggered a <strong>fatal error</strong>.', 'default' );

		$message = __( 'LearnDash LMS - Course Grid addon plugin has been merged to LearnDash Core, and it is no longer necessary to be activated.', 'learndash' );

		return str_replace( $default_message, $message, $markup );
	}

	/**
	 * Determines whether the Legacy Plugin is active.
	 *
	 * @since 4.21.4
	 *
	 * @return bool
	 */
	private function is_legacy_plugin_active(): bool {
		$legacy_plugin_path = $this->get_legacy_plugin_path();

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $legacy_plugin_path )
			|| is_plugin_active_for_network( $legacy_plugin_path );
	}

	/**
	 * Determines whether the code is already loaded.
	 *
	 * @since 4.21.4
	 *
	 * @return bool
	 */
	private function is_loaded(): bool {
		return defined( self::PLUGIN_DIRECTORY_CONSTANT );
	}

	/**
	 * Returns the Legacy Plugin path, relative to /wp-content/plugins.
	 *
	 * @since 4.21.4
	 *
	 * @return string
	 */
	private function get_legacy_plugin_path(): string {
		return Cast::to_string(
			preg_replace(
				'/^' . preg_quote(
					trailingslashit(
						wp_normalize_path( constant( 'WP_PLUGIN_DIR' ) )
					),
					'/'
				) . '/',
				'',
				trailingslashit(
					wp_normalize_path(
						defined( self::PLUGIN_DIRECTORY_CONSTANT )
							? constant( self::PLUGIN_DIRECTORY_CONSTANT )
							: self::PLUGIN_DIRECTORY_DEFAULT
					)
				)
			)
		) . self::PLUGIN_FILE;
	}

	/**
	 * Given a referrer, determine where they should be redirected to after deactivating the legacy Course Grid plugin.
	 *
	 * @since 4.21.4
	 *
	 * @param string|false $referrer Referrer. Should match valid output of wp_get_referer().
	 *
	 * @return string|false Redirect destination. Empty string to refresh the page, false when they should not be redirected at all.
	 */
	private function get_redirect( $referrer ) {
		if ( $referrer === false ) {
			return '';
		}

		$redirect_to_plugins_list = [
			admin_url( 'update.php' ), // Updating by uploading a ZIP.
			admin_url( 'update-core.php' ), // Updating via the "Update Plugins" workflow in the WordPress Dashboard.
		];

		/**
		 * Some cases are special and we don't want to redirect.
		 *
		 * Particularly, any time when an update is applied via AJAX (LearnDash LMS -> Add-ons and inline updates on
		 * the Plugins List page) could cause the user to be "trapped" until they attempt to navigate away twice unless
		 * we specifically ensure these referrers are not redirected.
		 */
		$never_redirect = [
			add_query_arg( // Updating via LearnDash LMS -> Add-ons.
				[
					'page' => 'learndash-hub',
				],
				admin_url( 'admin.php' )
			),
			admin_url( 'plugins.php' ), // Updating inline or via bulk actions on the Plugins List page.
		];

		$check_array_for_referrer = function ( $search_array ) use ( $referrer ) {
			return ! empty(
				array_filter(
					array_map(
						function ( $current_referrer ) use ( $referrer ) {
							return strpos( $referrer, $current_referrer ) !== false;
						},
						$search_array
					)
				)
			);
		};

		if ( $check_array_for_referrer( $redirect_to_plugins_list ) ) {
			return admin_url( 'plugins.php' );
		}

		if ( $check_array_for_referrer( $never_redirect ) ) {
			return false;
		}

		return $referrer;
	}
}
