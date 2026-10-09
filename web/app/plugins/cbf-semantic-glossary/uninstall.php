<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Drops the reference index and removes options and per-post glossary data.
 * Glossary entries are kept.
 *
 * @link    https://codingblackfemales.com
 * @package CodingBlackFemales/SemanticGlossary
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

CodingBlackFemales\SemanticGlossary\Install::uninstall();
