<?php
/**
 * Plugin Name: Astoundify Plugin Setup
 * Plugin URI: https://astoundify.com
 * Description: A reusable setup wizard for plugins.
 * Version: 1.0.0
 * Author: Astoundify
 * Author URI: https://astoundify.com
 * Requires at least: 4.8.0
 * Tested up to: 4.8.0
 * Text Domain: astoundify-pluginsetup
 * Domain Path: resources/languages/
 *
 *    Copyright: 2017 Astoundify
 *    License: GNU General Public License v3.0
 *    License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package PluginSetup
 * @category Core
 * @author Astoundify
 */

// Do not access this file directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load main plugin file.
require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'app/class-pluginsetup.php' );

// Load Example (include for testing).
//require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'resources/examples/example-1.php' );
