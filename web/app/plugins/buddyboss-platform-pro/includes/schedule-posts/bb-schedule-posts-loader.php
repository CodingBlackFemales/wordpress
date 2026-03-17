<?php
/**
 * BuddyBoss Pro schedule post Loader.
 *
 * @since   2.5.20
 *
 * @package BuddyBossPro
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bb schedule posts class.
 *
 * @since 2.5.20
 */
function bb_register_schedule_posts() {
	if (
		! defined( 'BP_PLATFORM_VERSION' ) ||
		version_compare( BP_PLATFORM_VERSION, '2.6.10', '<' ) ||
		! bp_is_active( 'activity' )
	) {
		return;
	}

	bb_platform_pro()->schedule_posts = BB_Schedule_Posts::instance();
}

add_action( 'bp_setup_components', 'bb_register_schedule_posts' );
