<?php
if ( ! defined( 'ABSPATH' ) ) { exit;
}
/**
 * WP Job Manager - WC Advanced Paid Listing Support
 *
 * @link https://astoundify.com/downloads/wc-advanced-paid-listings/
*/
$path = trailingslashit( WPJMS_PATH . 'includes/listing-payments' );

/* Settings */
require_once( $path . 'settings.php' );

/* WooCommerce Setup */
require_once( $path . 'woocommerce-setup.php' );

/* Setup */
require_once( $path . 'setup.php' );
