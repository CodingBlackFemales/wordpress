<?php
/**
 * LearnDash WooCommerce integration module main included file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define LearnDash LMS - WooCommerce Integration add-on version.
 *
 * The value is the last version the add-on shipped as a standalone plugin. The bundled copy ships with
 * LearnDash, so the number no longer tracks the code it versions.
 *
 * Still defined so third-party code that checks it to detect the add-on keeps working.
 *
 * @since 5.2.0
 * @deprecated 5.2.0 Use {@see 'LEARNDASH_SCRIPT_VERSION_TOKEN'} for asset versions and
 *             {@see 'LEARNDASH_VERSION'} for the plugin version.
 *
 * @var string $value Default is `2.0.3`.
 */
define( 'LEARNDASH_WOOCOMMERCE_VERSION', '2.0.3' );
define( 'LEARNDASH_WOOCOMMERCE_FILE', __FILE__ );
define( 'LEARNDASH_WOOCOMMERCE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'LEARNDASH_WOOCOMMERCE_DIR', plugin_dir_path( __FILE__ ) );
define( 'LEARNDASH_WOOCOMMERCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LEARNDASH_WOOCOMMERCE_VIEWS_PATH', plugin_dir_path( __FILE__ ) . 'src/views/' );
define( 'LEARNDASH_WOOCOMMERCE_VIEWS_URL', plugin_dir_url( __FILE__ ) . 'src/views/' );
define( 'LEARNDASH_WOOCOMMERCE_ADMIN_VIEWS_PATH', plugin_dir_path( __FILE__ ) . 'src/admin-views/' );
define( 'LEARNDASH_WOOCOMMERCE_ADMIN_VIEWS_URL', plugin_dir_url( __FILE__ ) . 'src/admin-views/' );

use LearnDash\Core\Autoloader;
use LearnDash\WooCommerce\Plugin;

/**
 * The initialization of the plugin requires `plugins_loaded` hook because some required WooCommerce hooks
 * are not available in `learndash_init` or `init` hook.
 */
add_action(
	'plugins_loaded',
	function () {
		learndash_woocommerce_extra_autoloading();

		learndash_register_provider( Plugin::class );

		require_once LEARNDASH_WOOCOMMERCE_PLUGIN_PATH . 'includes/class-learndash-woocommerce.php';
		new Learndash_WooCommerce();
	},
	50
);


/**
 * Sets up the autoloader for extra classes, which are not in the src/WooCommerce directory.
 *
 * @since 5.2.0
 *
 * @return void
 */
function learndash_woocommerce_extra_autoloading(): void {
	$autoloader = Autoloader::instance();

	foreach ( (array) glob( LEARNDASH_WOOCOMMERCE_PLUGIN_PATH . 'src/deprecated/*.php' ) as $file ) {
		$autoloader->register_class( basename( (string) $file, '.php' ), (string) $file );
	}

	$autoloader->register_autoloader();
}
