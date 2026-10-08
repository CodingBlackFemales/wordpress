<?php
/**
 * Functions for uninstall LearnDash LMS - Zapier Integration
 *
 * @since 2.3.2
 *
 * @package LearnDash\Zapier
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

require_once plugin_dir_path( __FILE__ ) . 'vendor-prefixed/autoload.php';

/**
 * Fires on plugin uninstall.
 *
 * @since 2.3.2
 *
 * @return void
 */
do_action( 'learndash_zapier_uninstall' );
