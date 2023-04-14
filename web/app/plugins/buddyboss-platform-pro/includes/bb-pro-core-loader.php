<?php
/**
 * BuddyBoss Platform Pro Core Loader.
 *
 * @package BuddyBossPro/Core
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bb platform pro core.
 *
 * @since 1.0.0
 */
function bb_pro_setup_core() {
	bb_platform_pro()->core = new BB_Platform_Pro_Core();
}
add_action( 'bp_loaded', 'bb_pro_setup_core', 0 );
