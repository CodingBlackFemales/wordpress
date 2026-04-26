<?php
/**
 * Plugin Name: LearnDash LMS
 * Plugin URI: http://www.learndash.com
 * Update URI: learndash
 * Description: LearnDash LMS Plugin - Turn your WordPress site into a learning management system.
 * Version: 5.0.5
 * Requires PHP: 7.4
 * Requires at least: 6.7
 * Tested up to: 6.9.4
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

use LearnDash\Core\App;
use LearnDash\Core\Autoloader;
use LearnDash\Core\Container;
use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\StellarWP\AdminNotices\AdminNotices;
use StellarWP\Learndash\StellarWP\Telemetry\Config as TelemetryConfig;
use StellarWP\Learndash\StellarWP\Telemetry\Core as Telemetry;
use StellarWP\Learndash\StellarWP\DB\DB;
use StellarWP\Learndash\StellarWP\Validation\Config as ValidationConfig;

// CONSTANTS.

/**
* Define LearnDash LMS - Set the current version constant.
*
* @since 2.1.0
*
* @internal Will be set by LearnDash LMS. Semantic versioning is used.
*/
define( 'LEARNDASH_VERSION', '5.0.5' );

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
		$hook_prefix = 'learndash';

		// Telemetry.

		$telemetry_server_url = defined( 'STELLARWP_TELEMETRY_SERVER' ) && ! empty( STELLARWP_TELEMETRY_SERVER )
			? STELLARWP_TELEMETRY_SERVER
			: 'https://telemetry.stellarwp.com/api/v1';

		App::set_container( new Container() );

		TelemetryConfig::set_container( App::container() );
		TelemetryConfig::set_server_url( $telemetry_server_url );
		TelemetryConfig::set_hook_prefix( $hook_prefix );
		TelemetryConfig::set_stellar_slug( $hook_prefix );

		Telemetry::instance()->init( __FILE__ );

		// DB.

		DB::init();

		// Validation.

		ValidationConfig::setServiceContainer( App::container() );
		ValidationConfig::setHookPrefix( $hook_prefix );

		ValidationConfig::initialize();

		// Admin Notices.

		AdminNotices::initialize( 'learndash', plugin_dir_url( __FILE__ ) . 'vendor-prefixed/stellarwp/admin-notices' );
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
		learndash_extra_autoloading();
		require_once __DIR__ . '/learndash-includes.php';
		require_once __DIR__ . '/learndash-constants.php';
		require_once __DIR__ . '/learndash-globals.php';
		require_once __DIR__ . '/learndash-features-constants.php';

		/**
		 * Fires after LearnDash plugin files are included.
		 *
		 * @since 4.6.0
		 */
		do_action( 'learndash_files_included' );
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

/**
 * Registers a LearnDash service provider implementation.
 *
 * @since 4.6.0
 *
 * @param class-string $service_provider_class The fully-qualified Service Provider class name.
 * @param string       ...$alias               A list of aliases the provider should be registered with.
 *
 * @throws ContainerException If the Service Provider is not correctly configured or there's an issue reflecting on it.
 *
 * @return void
 */
function learndash_register_provider( string $service_provider_class, string ...$alias ): void {
	App::register( $service_provider_class, ...$alias );
}

/**
 * Setup the autoloader for extra classes, which are not in the src/Core directory.
 *
 * @since 4.6.0
 * @since 4.20.1 Support autoload from subdirectories in the src/deprecated directory.
 *
 * @return void
 */
function learndash_extra_autoloading(): void {
	$autoloader = Autoloader::instance();

	// Iterate through all files under ./src/deprecated.
	$iterator = new RecursiveDirectoryIterator( LEARNDASH_LMS_PLUGIN_DIR . 'src/deprecated/' );
	$files    = new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::SELF_FIRST );

	foreach ( $files as $file ) {
		if (
			! $file instanceof SplFileInfo
			|| ! $file->isFile()
			|| $file->getExtension() !== 'php'
		) {
			continue;
		}

		if ( strstr( $file->getRealPath(), 'functions' ) ) {
			// If this was named functions.php in any directory, load it.
			include_once $file->getRealPath();
		} else {
			// Construct the proper Class Name based on the file path.
			$class_name = str_replace(
				'/',
				'\\',
				(string) preg_replace(
					'/.*?src\/deprecated\/(.*?)\.php/',
					'$1',
					$file->getRealPath()
				)
			);

			if ( strpos( $class_name, '\\' ) !== false ) {
				$class_name = 'LearnDash\\' . $class_name;
			}

			$autoloader->register_class( $class_name, $file->getRealPath() );
		}
	}

	$autoloader->register_autoloader();
}
