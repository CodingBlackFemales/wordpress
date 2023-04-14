<?php
/**
 * BuddyBoss Zoom Integration Loader.
 *
 * @package BuddyBossPro/Integration/Zoom
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp zoom integration.
 *
 * @since 1.0.0
 */
function bp_register_zoom_integration() {
	require_once dirname( __FILE__ ) . '/bp-zoom-integration.php';
	buddypress()->integrations['zoom'] = new BP_Zoom_Integration();
}
add_action( 'bp_setup_integrations', 'bp_register_zoom_integration' );
