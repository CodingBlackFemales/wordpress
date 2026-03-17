<?php
/**
 * BuddyBoss Pro Polls Loader.
 *
 * @package BuddyBossPro
 *
 * @since   2.6.00
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp poll class.
 *
 * @since 2.6.00
 */
function bb_register_poll() {
	if (
		! defined( 'BP_PLATFORM_VERSION' ) ||
		version_compare( BP_PLATFORM_VERSION, bb_platform_poll_version(), '<' ) ||
		! bp_is_active( 'activity' )
	) {
		return;
	}

	bb_platform_pro()->poll = BB_Polls::instance();
}

add_action( 'bp_setup_components', 'bb_register_poll' );
