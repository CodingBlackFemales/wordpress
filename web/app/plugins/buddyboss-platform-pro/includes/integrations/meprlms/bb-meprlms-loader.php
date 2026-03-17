<?php
/**
 * BuddyBoss MemberpressLMS Integration Loader.
 *
 * @package BuddyBossPro\Integration\MemberpressLMS
 *
 * @since 2.6.30
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the BB MemberpressLMS integration.
 *
 * @since 2.6.30
 */
function bb_register_meprlms_integration() {
	require_once __DIR__ . '/includes/class-bb-meprlms-integration.php';
	buddypress()->integrations['meprlms'] = new BB_MeprLMS_Integration();
}
add_action( 'bp_setup_integrations', 'bb_register_meprlms_integration', 20 );
