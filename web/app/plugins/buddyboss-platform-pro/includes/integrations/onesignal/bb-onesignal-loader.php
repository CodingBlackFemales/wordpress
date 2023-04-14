<?php
/**
 * BuddyBoss OneSignal Integration Loader.
 *
 * @package BuddyBossPro/Integration/OneSignal
 * @since 2.0.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the BB OneSignal integration.
 *
 * @since 2.0.3
 */
function bb_register_onesignal_integration() {
	if (
		! function_exists( 'bp_is_labs_notification_preferences_support_enabled' ) ||
		! bp_is_labs_notification_preferences_support_enabled() ||
		! defined( 'BP_PLATFORM_VERSION' ) ||
		version_compare( BP_PLATFORM_VERSION, '2.0.2', '<' )
	) {
		return;
	}
	require_once dirname( __FILE__ ) . '/bb-onesignal-integration.php';
	buddypress()->integrations['onesignal'] = new BB_OneSignal_Integration();
}
add_action( 'bp_setup_integrations', 'bb_register_onesignal_integration' );
