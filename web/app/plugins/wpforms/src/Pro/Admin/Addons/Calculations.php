<?php

namespace WPForms\Pro\Admin\Addons;

use WPForms_Updater;

/**
 * Calculations addon.
 *
 * @since 1.8.7
 */
class Calculations {

	/**
	 * WPForms updater class instance.
	 *
	 * @since 1.8.7
	 *
	 * @var WPForms_Updater
	 */
	public $updater;

	/**
	 * Indicate if the current class is allowed to load.
	 *
	 * @since 1.8.7
	 *
	 * @return bool
	 */
	private function allow_load(): bool {

		if ( ! is_admin() && ! wpforms_doing_wp_cli() ) {
			return false;
		}

		// Addon is activated.
		if ( ! function_exists( 'wpforms_calculations' ) || ! defined( 'WPFORMS_CALCULATIONS_VERSION' ) ) {
			return false;
		}

		// Only up to v1.1.0.
		if ( version_compare( WPFORMS_CALCULATIONS_VERSION, '1.1.0', '>' ) ) {
			return false;
		}

		global $pagenow;

		// Load only on certain admin pages OR when running WP-CLI.
		return in_array( $pagenow, [ 'update-core.php', 'plugins.php' ], true ) || wpforms_doing_wp_cli();
	}

	/**
	 * Init.
	 *
	 * @since 1.8.7
	 */
	public function init() {

		if ( ! $this->allow_load() ) {
			return;
		}

		$this->hooks();
	}

	/**
	 * Plugin hooks.
	 *
	 * @since 1.8.7
	 */
	private function hooks() {

		add_action( 'wpforms_updater', [ $this, 'updater' ] );
	}

	/**
	 * Load the addon updater.
	 *
	 * @since 1.8.7
	 *
	 * @param string $key License key.
	 */
	public function updater( $key ) {

		// Skip if updater is already initialized.
		if ( ! function_exists( 'wpforms_calculations' ) || ! empty( wpforms_calculations()->updater ) ) {
			return;
		}

		// Initialize the addon updater class.
		$this->updater = new WPForms_Updater(
			[
				'plugin_name' => 'WPForms Calculations',
				'plugin_slug' => 'wpforms-calculations',
				'plugin_path' => plugin_basename( WPFORMS_CALCULATIONS_FILE ),
				'plugin_url'  => trailingslashit( WPFORMS_CALCULATIONS_URL ),
				'remote_url'  => WPFORMS_UPDATER_API,
				'version'     => WPFORMS_CALCULATIONS_VERSION,
				'key'         => $key,
			]
		);
	}
}
