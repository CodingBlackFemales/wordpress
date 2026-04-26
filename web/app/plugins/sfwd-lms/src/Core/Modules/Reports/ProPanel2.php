<?php
/**
 * Reports Legacy ProPanel Support.
 *
 * @since 4.17.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports;

use LearnDash\Core\Utilities\Cast;

/**
 * Reports Legacy ProPanel Support.
 *
 * @since 4.17.0
 */
class ProPanel2 {
	/**
	 * Legacy plugin directory Path Constant.
	 *
	 * @since 4.17.0
	 *
	 * @var string
	 */
	private const PLUGIN_DIRECTORY_CONSTANT = 'LD_PP_PLUGIN_DIR';

	/**
	 * Legacy plugin directory name default.
	 *
	 * @since 4.17.0
	 *
	 * @var string
	 */
	private const PLUGIN_DIRECTORY_DEFAULT = 'learndash-propanel';

	/**
	 * Legacy plugin file name.
	 *
	 * @since 4.17.0
	 *
	 * @var string
	 */
	private const PLUGIN_FILE = 'learndash_propanel.php';

	/**
	 * If a plugin exists at the legacy plugin location and it is at least this version, it should not be deactivated.
	 *
	 * @since 4.17.0
	 *
	 * @var string
	 */
	private const PLUGIN_SAFE_VERSION = '3.0.0-dev';

	/**
	 * If a legacy installation of ProPanel is found on the site, deactivate it.
	 *
	 * @since 4.17.0
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
	 * If the Reports module has not been loaded (via the legacy plugin, or otherwise) load it.
	 *
	 * @since 4.17.0
	 *
	 * @return void
	 */
	public function load(): void {
		if ( $this->is_loaded() ) {
			return;
		}

		$reports_module_path = LEARNDASH_LMS_PLUGIN_DIR . '/includes/reports/' . self::PLUGIN_FILE;

		if ( ! file_exists( $reports_module_path ) ) {
			return;
		}

		require_once $reports_module_path;
	}

	/**
	 * Determines whether the Legacy Plugin is active.
	 *
	 * @since 4.17.0
	 *
	 * @return bool
	 */
	private function is_legacy_plugin_active(): bool {
		if ( $this->is_safe_plugin_version() ) {
			return false;
		}

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
	 * @since 4.17.0
	 *
	 * @return bool
	 */
	private function is_loaded(): bool {
		return defined( self::PLUGIN_DIRECTORY_CONSTANT );
	}

	/**
	 * Returns the Legacy Plugin path, relative to /wp-content/plugins.
	 *
	 * @since 4.17.0
	 *
	 * @return string
	 */
	private function get_legacy_plugin_path(): string {
		return Cast::to_string(
			preg_replace(
				'/^' .
				preg_quote(
					trailingslashit(
						wp_normalize_path( constant( 'WP_PLUGIN_DIR' ) )
					),
					'/'
				) .
				'/',
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
	 * Check if the installed plugin version is new enough to be safe.
	 *
	 * @since 4.17.0
	 *
	 * @return bool
	 */
	private function is_safe_plugin_version(): bool {
		$full_legacy_plugin_path = trailingslashit(
			wp_normalize_path( constant( 'WP_PLUGIN_DIR' ) )
		) . $this->get_legacy_plugin_path();

		if ( ! file_exists( $full_legacy_plugin_path ) ) {
			// Fallback if the plugin is not installed.
			return false;
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data = get_plugin_data( $full_legacy_plugin_path, false, false );

		return Cast::to_bool(
			version_compare(
				$plugin_data['Version'],
				self::PLUGIN_SAFE_VERSION,
				'>='
			)
		);
	}

	/**
	 * Given a referrer, determine where they should be redirected to after deactivating the legacy ProPanel plugin.
	 *
	 * @since 4.17.0
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
