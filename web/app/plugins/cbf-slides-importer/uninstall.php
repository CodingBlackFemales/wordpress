<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Drops custom DB tables, removes all wp_options keys, and removes all
 * per-user OAuth tokens from wp_usermeta.
 *
 * @link    https://codingblackfemales.com
 * @package CodingBlackFemales/SlidesImporter
 */

// If uninstall is not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$autoloader = __DIR__ . '/vendor/autoload.php';
if ( ! is_readable( $autoloader ) ) {
	return;
}
require $autoloader;

CodingBlackFemales\SlidesImporter\Install::uninstall();
