<?php
/**
 * BuddyBoss Pro Reaction Loader.
 *
 * @package BuddyBossPro
 *
 * @since   2.4.50
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp reaction class.
 *
 * @since 2.4.50
 */
function bp_register_reaction() {
	bb_platform_pro()->reaction = BB_Reactions::instance();
}
add_action( 'bp_setup_components', 'bp_register_reaction' );
