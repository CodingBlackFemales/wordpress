<?php
/**
 * BuddyBoss Membership Loader.
 *
 * @package BuddyBossPro
 *
 * @since   1.0.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp access control.
 *
 * @since 1.1.0
 */
function bp_register_access_control() {
	bb_platform_pro()->access_control = new BB_Access_Control();
}
add_action( 'bp_setup_components', 'bp_register_access_control' );
