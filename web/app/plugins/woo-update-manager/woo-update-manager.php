<?php
/**
 * Plugin Name: WooCommerce.com Update Manager
 * Description: Receive updates and streamlined support included in your Woo.com subscriptions.
 * Author: Automattic
 * Author URI: https://woocommerce.com/
 * Text Domain: woo-update-manager
 * Domain Path: /languages
 * WC requires at least: 8.6
 * WC tested up to: 8.7.0
 * Woo: 18734003407318:e4367d0e8d424278fd7049e7d7b567a6
 * Requires at least: 6.0
 * Requires PHP: 7.3
 * Version: 1.0.3
 *
 * @package Woo\UpdateManager
 */

namespace Automattic\WooUpdateManager;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/class-woo-subscription-data-updater.php';

/**
 * Clear WP update transients for plugins and themes.
 *
 * @return void
 */
function clear_update_transients() {
	delete_site_transient( 'update_plugins' );
	delete_site_transient( 'update_themes' );
}

/**
 * Register activation hook to clear update transients.
 */
register_activation_hook( __FILE__, __NAMESPACE__ . '\clear_update_transients' );

/**
 * Register de-activation hook to clear update transients.
 */
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\clear_update_transients' );


/**
 * Declare compatibility for HPOS compatibility.
 */
function declare_wc_feature_compatibility() {
	if ( class_exists( FeaturesUtil::class ) ) {
		FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
}

add_action( 'before_woocommerce_init', __NAMESPACE__ . '\declare_wc_feature_compatibility' );
