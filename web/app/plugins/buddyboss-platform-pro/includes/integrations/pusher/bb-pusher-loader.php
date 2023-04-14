<?php
/**
 * BuddyBoss Pusher Integration Loader.
 *
 * @package BuddyBossPro\Integration\Pusher
 * @since 2.1.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the BB Pusher integration.
 *
 * @since 2.1.6
 */
function bb_pro_register_pusher_integration() {
	if (
		! defined( 'BP_PLATFORM_VERSION' ) ||
		version_compare( BP_PLATFORM_VERSION, '2.2', '<' ) ||
		! function_exists( 'bb_platform_pro' ) ||
		version_compare( bb_platform_pro()->version, '2.1.6', '<' )
	) {
		return;
	}

	require_once dirname( __FILE__ ) . '/bb-pusher-integration.php';
	buddypress()->integrations['pusher'] = new BB_Pusher_Integration();
}
add_action( 'bp_setup_integrations', 'bb_pro_register_pusher_integration' );
