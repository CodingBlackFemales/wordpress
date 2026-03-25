<?php
/**
 * BuddyBoss Platform Pro Core Actions.
 *
 * @package BuddyBossPro/Actions
 * @since 1.0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'bp_admin_init', 'bbp_pro_setup_updater', 1001 );

/**
 * Function will run after plugin successfully update.
 *
 * @since 2.1.7
 *
 * @param object $upgrader_object WP_Upgrader instance.
 * @param array  $options         Array of bulk item update data.
 */
function bb_pro_plugin_upgrade_function_callback( $upgrader_object, $options ) {
	$show_display_popup = false;
	// The path to our plugin's main file.
	$our_plugin = 'buddyboss-platform-pro/buddyboss-platform-pro.php';
	if ( ! empty( $options ) && 'update' === $options['action'] && 'plugin' === $options['type'] && isset( $options['plugins'] ) ) {
		foreach ( $options['plugins'] as $plugin ) {
			if ( ! empty( $plugin ) && $plugin === $our_plugin ) {
				update_option( '_bb_pro_is_update', $show_display_popup );
				flush_rewrite_rules(); // Flush rewrite rules when update the Buddyboss platform pro plugin.
			}
		}
	}
}

add_action( 'upgrader_process_complete', 'bb_pro_plugin_upgrade_function_callback', 10, 2 );

/**
 * Filter the activity action for ReadyLaunch.
 *
 * @since 2.7.50
 *
 * @param string $action   The activity action.
 * @param object $activity The activity object.
 *
 * @return string The filtered activity action.
 */
function bb_readylaunch_meeting_activity_action( $action, $activity ) {
	if ( ! function_exists( 'bb_get_enabled_readylaunch' ) ) {
		return $action;
	}

	$enabled = bb_get_enabled_readylaunch();
	if ( empty( $enabled['activity'] ) ) {
		return $action;
	}

	if ( 'zoom_meeting_create' === $activity->type || 'zoom_meeting_notify' === $activity->type ) {
		$group_id   = $activity->item_id;
		$meeting_id = $activity->secondary_item_id;
		$meeting    = new BP_Zoom_Meeting( $meeting_id );

		if ( empty( $meeting->id ) ) {
			return $action;
		}

		// User link.
		$user_link = bp_core_get_userlink( $activity->user_id );

		// Meeting.
		$meeting_permalink = bp_get_zoom_meeting_url( $group_id, $meeting_id );
		$meeting_title     = $meeting->title;
		$meeting_link      = '<a href="' . $meeting_permalink . '">' . $meeting_title . '</a>';
		$action            = sprintf(
			/* translators: %1$s - user link, %2$s - meeting link.*/
			esc_html__( '%1$s scheduled a Zoom meeting %2$s', 'buddyboss-pro' ),
			$user_link,
			$meeting_link
		);

		if ( 'zoom_meeting_notify' === $activity->type ) {
			$action = sprintf(
				/* translators: %1$s - user link, %2$s - meeting link.*/
				esc_html__( '%1$s scheduled Zoom meeting %2$s starting soon', 'buddyboss-pro' ),
				$user_link,
				$meeting_link
			);
		}
	}

	return $action;
}

add_filter( 'bb_meeting_activity_action', 'bb_readylaunch_meeting_activity_action', 10, 2 );
