<?php
/**
 * Helper functions.
 *
 * @since 1.0.0
 *
 * @package PluginUpdater
 * @category Core
 * @author Astoundify
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helpers Functions.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
class Astoundify_PluginUpdater_Helpers {

	/**
	 * Create a deactivation link.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $plugin_file Plugin File.
	 * @param bool|string $redirect    Redirect URL. False to use default admin url.
	 * @return string URL to deactivate license.
	 */
	public static function deactivate_license_link( $plugin_file, $redirect = false ) {
		if ( ! $redirect ) {
			$redirect = admin_url();
		}

		$query_args = array(
			'astoundify-pluginupdater' => 'deactivate-license',
			'plugin_file'              => $plugin_file,
		);

		$url = add_query_arg( $query_args, $redirect );

		return esc_url( wp_nonce_url( $url, 'deactivate-license' ) );
	}

}
