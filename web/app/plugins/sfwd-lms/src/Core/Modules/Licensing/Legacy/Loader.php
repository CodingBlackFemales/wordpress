<?php
/**
 * Loader for the legacy Licensing and Management plugin.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Licensing\Legacy;

/**
 * Legacy Licensing and Management plugin loader class.
 *
 * @since 4.18.0
 */
class Loader {
	/**
	 * Transient key to know when we should show a notice saying we deactivated the legacy plugin.
	 *
	 * @since 4.18.0
	 *
	 * @var string
	 */
	private const DEACTIVATED_NOTICE_TRANSIENT = 'learndash_legacy_licensing_plugin_deactivated';

	/**
	 * If the Licensing module has not been loaded (via the legacy plugin, or otherwise) load it.
	 *
	 * @since 4.18.0
	 *
	 * @return void
	 */
	public function load(): void {
		if ( $this->is_loaded() ) {
			return;
		}

		$licensing_module_path = LEARNDASH_LMS_PLUGIN_DIR . '/includes/licensing/' . Resolver::$plugin_file;

		if ( ! file_exists( $licensing_module_path ) ) {
			return;
		}

		require_once $licensing_module_path;
	}

	/**
	 * If a legacy installation of Licensing and Management is found on the site, deactivate it.
	 *
	 * @since 4.18.0
	 *
	 * @return void
	 */
	public function deactivate(): void {
		if ( ! Resolver::is_plugin_active() ) {
			return;
		}

		deactivate_plugins( Resolver::get_plugin_path() );

		set_transient(
			self::DEACTIVATED_NOTICE_TRANSIENT,
			true,
			HOUR_IN_SECONDS
		);

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
	 * Show a notice after deactivating the legacy Licensing and Management plugin.
	 *
	 * @since 4.18.0
	 *
	 * @return void
	 */
	public function show_deactivated_notice(): void {
		if ( ! get_transient( self::DEACTIVATED_NOTICE_TRANSIENT ) ) {
			return;
		}

		printf(
			'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
			wp_kses(
				sprintf(
					// translators: HTML for a link to documentation.
					__( "It looks like you had LearnDash Licensing & Management active. We've deactivated it for you because it is no longer needed. %1\$sLearn more%2\$s", 'learndash' ),
					'<a href="https://go.learndash.com/lm" target="_blank">',
					'</a>'
				),
				[
					'a' => [
						'href'   => true,
						'target' => true,
					],
				]
			)
		);

		delete_transient( self::DEACTIVATED_NOTICE_TRANSIENT );
	}

	/**
	 * Determines whether the code is already loaded.
	 *
	 * @since 4.18.0
	 *
	 * @return bool
	 */
	private function is_loaded(): bool {
		return defined( Resolver::$plugin_basename_constant );
	}

	/**
	 * Given a referrer, determine where they should be redirected to after deactivating
	 * the legacy Licensing and Management plugin.
	 *
	 * @since 4.18.0
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
