<?php
/**
 * This file is called in two instances.
 * 1 - When the plugin is activated it will be called from the activation hook in src/boot.php install().
 * 2 - When LD core (4.1.1) includes a zip copy of this plugin it will move to the mu-plugins directory. Then call this file to perform needed setup.
 */
$installed_dir = plugin_dir_path( __DIR__ );
$installed_dir = str_replace( '\\', '/', $installed_dir );
$installed_dir = strtolower( $installed_dir );
$installed_dir = trailingslashit( $installed_dir );

$wp_plugin_dir = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'plugins';
$wp_plugin_dir = str_replace( '\\', '/', $wp_plugin_dir );
$wp_plugin_dir = strtolower( $wp_plugin_dir );
$wp_plugin_dir = trailingslashit( $wp_plugin_dir );

$hub_plugin_dir = basename( __DIR__ );

if ( ( $installed_dir !== $wp_plugin_dir ) && ( is_writable( $wp_plugin_dir ) ) ) {
	if ( ! file_exists( $installed_dir . $hub_plugin_dir ) ) {
		return;
	}

		// plugin is already installed.
	if ( file_exists( $wp_plugin_dir . $hub_plugin_dir ) ) {
		// compare the installed version with the version in the mu-plugins directory.
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$wp_plugin_version = get_plugin_data( $wp_plugin_dir . $hub_plugin_dir . DIRECTORY_SEPARATOR . 'learndash-hub.php', false, false );
		$wp_plugin_version = $wp_plugin_version['Version'];

		$mu_plugin_version = get_plugin_data( $installed_dir . $hub_plugin_dir . DIRECTORY_SEPARATOR . 'learndash-hub.php', false, false );
		$mu_plugin_version = $mu_plugin_version['Version'];

		// if the wp plugin version is greater than the version in the mu plugin dir then we don't need to do anything.
		if ( version_compare( $wp_plugin_version, $mu_plugin_version, '>=' ) ) {
			return;
		}

		// delete the old version.
		WP_Filesystem();
		global $wp_filesystem;
		$wp_filesystem->delete( $wp_plugin_dir . $hub_plugin_dir, true );

		// remove cached plugin data.
		delete_site_option( 'learndash_hub_fetch_projects' );
		delete_site_option( 'learndash_hub_update_plugins_cache' );
	}

	// Move the plugin from the current directory to the plugins/learndash-hub/ directory.
	rename( $installed_dir . $hub_plugin_dir, $wp_plugin_dir . $hub_plugin_dir );
}
