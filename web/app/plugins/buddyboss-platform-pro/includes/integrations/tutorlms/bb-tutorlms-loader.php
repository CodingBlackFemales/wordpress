<?php
/**
 * BuddyBoss TutorLMS Integration Loader.
 *
 * @package BuddyBossPro/Integration/TutorLMS
 *
 * @since 2.4.40
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the BB TutorLMS integration.
 *
 * @since 2.4.40
 */
function bb_register_tutorlms_integration() {
	require_once dirname( __FILE__ ) . '/bb-tutorlms-integration.php';
	buddypress()->integrations['tutorlms'] = new BB_TutorLMS_Integration();
}
add_action( 'bp_setup_integrations', 'bb_register_tutorlms_integration', 20 );
