<?php
/**
 * Plugin Name: LearnDash LMS
 * Plugin URI: http://www.learndash.com
 * Description: LearnDash LMS Plugin - Turn your WordPress site into a learning management system.
 * Version: 4.5.3
 * Author: LearnDash
 * Author URI: http://www.learndash.com
 * Text Domain: learndash
 * Domain Path: /languages/
 *
 * @since 2.1.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'vendor-prefixed/autoload.php';

use LearnDash\Core\Container;
use StellarWP\Learndash\StellarWP\Telemetry\Config;
use StellarWP\Learndash\StellarWP\Telemetry\Core as Telemetry;
use StellarWP\Learndash\StellarWP\DB\DB;

// CONSTANTS.

/**
* Define LearnDash LMS - Set the current version constant.
*
* @since 2.1.0
*
* @internal Will be set by LearnDash LMS. Semantic versioning is used.
*/
define( 'LEARNDASH_VERSION', '4.5.3' );

if ( ! defined( 'LEARNDASH_LMS_PLUGIN_DIR' ) ) {
	/**
	 * Define LearnDash LMS - Set the plugin install path.
	 *
	 * Will be set based on the WordPress define `WP_PLUGIN_DIR`.
	 *
	 * @since 2.1.4
	 * @uses WP_PLUGIN_DIR
	 *
	 * Directory path to plugin install directory.
	 */
	define( 'LEARNDASH_LMS_PLUGIN_DIR', trailingslashit( str_replace( '\\', '/', WP_PLUGIN_DIR ) . '/' . basename( dirname( __FILE__ ) ) ) );
}

if ( ! defined( 'LEARNDASH_LMS_PLUGIN_URL' ) ) {
	$learndash_plugin_url = trailingslashit( WP_PLUGIN_URL . '/' . basename( dirname( __FILE__ ) ) );
	$learndash_plugin_url = str_replace( array( 'https://', 'http://' ), array( '//', '//' ), $learndash_plugin_url );

	/**
	 * Define LearnDash LMS - Set the plugin relative URL.
	 *
	 * Will be set based on the WordPress define `WP_PLUGIN_URL`.
	 *
	 * @since 2.1.4
	 * @uses WP_PLUGIN_URL
	 *
	 * URL to plugin install directory.
	 */
	define( 'LEARNDASH_LMS_PLUGIN_URL', $learndash_plugin_url );
}

if ( ! defined( 'LEARNDASH_LMS_PLUGIN_KEY' ) ) {
	$learndash_plugin_dir = LEARNDASH_LMS_PLUGIN_DIR;
	$learndash_plugin_dir = basename( $learndash_plugin_dir ) . '/' . basename( __FILE__ );

	/**
	 * Define LearnDash LMS - Set the plugin key.
	 *
	 * This define is the plugin directory and filename.
	 * directory.
	 *
	 * @since 2.3.1
	 *
	 * Default value is `sfwd-lms/sfwd_lms.php`.
	 */
	define( 'LEARNDASH_LMS_PLUGIN_KEY', $learndash_plugin_dir );
}

// Defining other scalar constants.
require_once __DIR__ . '/learndash-scalar-constants.php';

/**
 * Configures packages.
 *
 * @since 4.5.0
 */
add_action(
	'plugins_loaded',
	function () {
		// Telemetry.

		$telemetry_server_url = defined( 'LEARNDASH_TELEMETRY_URL' ) && ! empty( LEARNDASH_TELEMETRY_URL )
			? LEARNDASH_TELEMETRY_URL
			: 'https://telemetry.stellarwp.com/api/v1';

		Config::set_container( new Container() );
		Config::set_server_url( $telemetry_server_url );
		Config::set_hook_prefix( 'learndash' );
		Config::set_stellar_slug( 'learndash' );

		Telemetry::instance()->init( __FILE__ );

		// DB.

		DB::init();
	},
	0
);

/**
 * Action Scheduler
 */
add_action(
	'plugins_loaded',
	static function () {
		require_once __DIR__ . '/includes/lib/action-scheduler/action-scheduler.php';
	},
	-10
);

add_action(
	'plugins_loaded',
	static function() {
		require_once __DIR__ . '/learndash-includes.php';
		require_once __DIR__ . '/learndash-constants.php';
		require_once __DIR__ . '/learndash-globals.php';
	},
	0
);

// Activation and deactivation hooks.

register_activation_hook(
	__FILE__,
	function () {
		// Save a flag in the DB to allow later activation tasks (legacy stuff).
		update_option( 'learndash_activation', true );
	}
);

register_deactivation_hook( __FILE__, 'learndash_deactivated' );

/**
 * Deactivate LearnDash LMS.
 *
 * @since 4.5.0
 *
 * @return void
 */
function learndash_deactivated() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	/**
	 * Fires on LearnDash plugin deactivation.
	 *
	 * @since 2.1.0
	 */
	do_action( 'learndash_deactivated' );
}
