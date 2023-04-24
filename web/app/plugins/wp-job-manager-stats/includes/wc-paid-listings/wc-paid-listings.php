<?php
if ( ! defined( 'ABSPATH' ) ) { exit;
}
/**
 * WP Job Manager - WC Paid Listing Support
 *
 * @link https://wpjobmanager.com/add-ons/wc-paid-listings/
*/
$path = trailingslashit( WPJMS_PATH . 'includes/wc-paid-listings' );

/* Settings */
require_once( $path . 'settings.php' );

/* WooCommerce Setup */
require_once( $path . 'woocommerce-setup.php' );

/* Setup */
require_once( $path . 'setup.php' );
