<?php
/**
 * BuddyBoss Pro Activity Topics Loader.
 *
 * @since   2.7.40
 * @package BuddyBossPro
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp activity topics class.
 *
 * @since 2.7.40
 */
function bb_register_topics() {
	if (
		! defined( 'BP_PLATFORM_VERSION' ) ||
		version_compare( BP_PLATFORM_VERSION, bb_platform_topics_version(), '<' ) ||
		(
			! bp_is_active( 'groups' ) &&
			! bp_is_active( 'activity' )
		) ||
		bb_pro_should_lock_features() ||
		! function_exists( 'bb_topics_manager_instance' )
	) {
		return;
	}

	bb_platform_pro()->topics = BB_Topics::instance();
}

add_action( 'bp_setup_components', 'bb_register_topics' );
