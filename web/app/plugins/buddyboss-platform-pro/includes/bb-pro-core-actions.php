<?php
/**
 * BuddyBoss Platform Pro Core Actions.
 *
 * @package BuddyBossPro/Actions
 * @since 1.0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'bp_admin_init', 'bbp_pro_setup_updater', 1001 );

/**
 * Function will run after plugin successfully update.
 *
 * @since 2.1.7
 *
 * @param object $upgrader_object WP_Upgrader instance.
 * @param array  $options         Array of bulk item update data.
 */
function bb_pro_plugin_upgrade_function_callback( $upgrader_object, $options ) {
	$show_display_popup = false;
	// The path to our plugin's main file.
	$our_plugin = 'buddyboss-platform-pro/buddyboss-platform-pro.php';
	if ( ! empty( $options ) && 'update' === $options['action'] && 'plugin' === $options['type'] && isset( $options['plugins'] ) ) {
		foreach ( $options['plugins'] as $plugin ) {
			if ( ! empty( $plugin ) && $plugin === $our_plugin ) {
				update_option( '_bb_pro_is_update', $show_display_popup );
				flush_rewrite_rules(); // Flush rewrite rules when update the Buddyboss platform pro plugin.
			}
		}
	}
}

add_action( 'upgrader_process_complete', 'bb_pro_plugin_upgrade_function_callback', 10, 2 );
