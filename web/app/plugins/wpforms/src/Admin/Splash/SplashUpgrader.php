<?php

namespace WPForms\Admin\Splash;

/**
 * Splash upgrader.
 *
 * @since 1.8.7
 */
class SplashUpgrader {

	use SplashTrait;

	/**
	 * Available plugins.
	 *
	 * @since 1.8.7
	 *
	 * @var array
	 */
	const AVAILABLE_PLUGINS = [
		'wpforms-lite',
		'wpforms',
	];

	/**
	 * Initialize class.
	 *
	 * @since 1.8.7
	 */
	public function init() {

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.8.7
	 */
	private function hooks() {

		// Update splash data after plugin update. Not run for new installs.
		add_action( 'upgrader_process_complete', [ $this, 'update_splash_data' ] );
	}

	/**
	 * Update splash modal data.
	 *
	 * @since 1.8.7
	 *
	 * @param object $upgrader Upgrader object.
	 */
	public function update_splash_data( $upgrader ) {

		$result = $upgrader->result ?? null;

		// Check if plugin was updated successfully.
		if ( ! $result ) {
			return;
		}

		// Check if WPForms plugin was updated.
		$wpforms_updated = $this->is_wpforms_updated( $upgrader );

		if ( ! $wpforms_updated ) {
			return;
		}

		// Retrieve plugin version after update.
		$version = $this->get_plugin_updated_version( $upgrader );

		if ( empty( $version ) ) {
			return;
		}

		// Skip if plugin wasn't updated.
		// Continue if plugin was upgraded to the PRO version.
		if ( version_compare( $version, WPFORMS_VERSION, '<' ) ) {
			return;
		}

		$version = $this->get_major_version( $version );

		// Store updated plugin major version.
		$this->update_splash_data_version( $version );

		// Force update splash data cache.
		wpforms()->get( 'splash_cache' )->update( true );

		// Reset hide_welcome_block widget meta for all users.
		$this->remove_hide_welcome_block_widget_meta();
	}

	/**
	 * Check if WPForms plugin was updated.
	 *
	 * @since 1.8.7
	 *
	 * @param object $upgrader Upgrader object.
	 *
	 * @return bool True if WPForms plugin was updated, false otherwise.
	 */
	private function is_wpforms_updated( $upgrader ): bool {

		// Check if updated plugin is WPForms.
		if ( ! in_array( $upgrader->result['destination_name'] ?? '', self::AVAILABLE_PLUGINS, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get plugin updated version.
	 *
	 * @since 1.8.7
	 *
	 * @param object $upgrader Upgrader object.
	 *
	 * @return string Plugin updated version.
	 */
	private function get_plugin_updated_version( $upgrader ): string { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		// Get plugin data after update.
		$new_plugin_data = $upgrader->new_plugin_data ?? null;

		if ( ! $new_plugin_data ) {
			return '';
		}

		return $new_plugin_data['Version'] ?? '';
	}
}
