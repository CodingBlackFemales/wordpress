<?php
/**
 * Plugin Name: LearnDash Licensing & Management
 * Plugin URI:
 * Description: LearnDash Licensing & Management allows you to connect your license and handles all updates related to the LearnDash family of products.
 * Author:      LearnDash
 * Author URI: https://learndash.com
 * Version:     1.2
 * Text Domain: learndash-hub
 * Domain Path: includes/languages/
 * Network:     true
 */

const HUB_VERSION    = '1.2';
const HUB_DB_VERSION = '1.0';
const HUB_SLUG       = 'learndash-hub/learndash-hub.php';
define( 'HUB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
if ( ! defined( 'LEARNDASH_UPDATES_ENABLED' ) ) {
	$enable = false;
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		$action = $_GET['action'] ?? '';
		if ( $action === 'learndash_setup_wizard_verify_license' ) {
			$enable = true;
		}
	}

	define( 'LEARNDASH_UPDATES_ENABLED', $enable );
}
// autoload.
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/configs.php';
require_once __DIR__ . '/src/functions.php';

$boot = new \LearnDash\Hub\Boot();
add_action( 'init', array( $boot, 'start' ) );
add_action( 'plugins_loaded', array( $boot, 'load_plugin_textdomain' ) );
add_action( 'delete_user', array( $boot, 'disallow_user' ) );
add_action( 'set_user_role', array( $boot, 'update_access_list_after_role_update' ), 10, 2 );


add_action(
	'plugins_loaded',
	function () use ( $boot ) {
		require_once __DIR__ . '/src/controller/class-licensing-settings-page.php';

		if ( ! $boot->is_user_allowed() || ! $boot->is_signed_on() ) {
			return;
		}
		require_once __DIR__ . '/src/controller/class-licensing-settings-section.php';
	}
);

	/**
	 * LearnDash Hub Activate/Install function.
	 */
function learndash_hub_install( $network_wide = null ) {
	// Nothing to see here.
}

	register_activation_hook( __FILE__, 'learndash_hub_install' );

	register_deactivation_hook( __FILE__, array( $boot, 'deactivate' ) );
