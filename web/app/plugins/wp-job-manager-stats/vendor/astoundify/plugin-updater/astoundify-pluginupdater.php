<?php
/**
 * Plugin Name: Astoundify Plugin Updater
 * Plugin URI: https://astoundify.com
 * Description: Manage plugin licenses in the WordPress dashboard and allow automatic updates.
 * Version: 1.1.0
 * Author: Astoundify
 * Author URI: https://astoundify.com
 * Requires at least: 4.8.0
 * Tested up to: 4.8
 * Text Domain: astoundify-pluginupdater
 * Domain Path: resources/languages/
 *
 *    Copyright: 2017 Astoundify
 *    License: GNU General Public License v3.0
 *    License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package PluginUpdater
 * @category Core
 * @author Astoundify
 */

// Do not access this file directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Require the app.
require_once( dirname( __FILE__ ) . '/app/class-astoundify-pluginupdater.php' );

if ( ! function_exists( 'astoundify_pluginupdater' ) ) {

	/**
	 * Create a new instance of Astoundify_PluginUpdater
	 *
	 * @since 1.1.0
	 *
	 * @param string $file Plugin File.
	 * @return object
	 */
	function astoundify_pluginupdater( $file ) {
		return new Astoundify_PluginUpdater( $file );
	}
}
