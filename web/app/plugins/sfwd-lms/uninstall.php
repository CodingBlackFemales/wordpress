<?php
/**
 * Functions for uninstall LearnDash
 *
 * @since 2.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'vendor-prefixed/autoload.php';

// Autoload licensing classes if not already loaded by a legacy plugin.
if ( ! class_exists( 'LearnDash\Hub\Boot', false ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/licensing/vendor/autoload.php';
}

use LearnDash\Hub\Boot;

/**
 * Remove our Multisite support file(s) to the /wp-content/mu-plugins directory.
 */
$learndash_wpmu_plugin_dir = ( defined( 'WPMU_PLUGIN_DIR' ) && defined( 'WPMU_PLUGIN_URL' ) ) ? WPMU_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins';
if ( is_writable( $learndash_wpmu_plugin_dir ) ) {
	$learndash_wpmu_plugin_dir_file = trailingslashit( $learndash_wpmu_plugin_dir ) . 'learndash-multisite.php';
	if ( file_exists( $learndash_wpmu_plugin_dir_file ) ) {
		unlink( $learndash_wpmu_plugin_dir_file );
	}
}

/**
 * Fires on plugin uninstall.
 */
do_action( 'learndash_uninstall' );

/**
 * Uninstalls Telemetry.
 *
 * @since 4.5.0
 */
StellarWP\Learndash\StellarWP\Telemetry\Uninstall::run( 'learndash' );

/**
 * Removes capabilities related to Reports.
 *
 * @since 4.17.0
 */
LearnDash\Core\Modules\Reports\Capabilities::remove();

/**
 * Removes caches stored for licensing.
 *
 * @since 4.18.0
 */
$learndash_licensing_boot = new Boot();
$learndash_licensing_boot->deactivate();
