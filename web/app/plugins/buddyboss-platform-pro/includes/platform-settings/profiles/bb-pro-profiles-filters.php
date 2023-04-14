<?php
/**
 * Platform Profiles Functions.
 *
 * @package BuddyBossPro/Platform Profiles
 * @since 1.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Insert default value to new pro fields.
add_action( 'bbp_pro_update_to_1_2_0', 'bb_profiles_pro_update_to_1_2_0' );

// Reset profile cover position when change the cover sizes.
add_action( 'update_option_bb-pro-cover-profile-width', 'bb_reset_profile_cover_position_on_change_sizes', 10, 3 );
add_action( 'update_option_bb-pro-cover-profile-height', 'bb_reset_profile_cover_position_on_change_sizes', 10, 3 );

// Reset primary action in member directories if change follow option from the BuddyBoss > Settings > Activity.
add_action( 'update_option__bp_enable_activity_follow', 'bb_reset_primary_action_on_change_activity_follow', 10, 3 );
// Reset primary action in member directories if deactivate components 'Activity Feeds', 'Member Connections', 'Private Messaging'.
add_action( 'bp_core_install', 'bb_reset_primary_action_on_change_component', 10, 1 );

add_filter( 'bp_rest_platform_settings', 'bb_rest_profiles_platform_settings', 10, 1 );

/**
 * Add new pro fields value insert into database.
 *
 * @since 1.2.0
 */
function bb_profiles_pro_update_to_1_2_0() {
	// Member header elements.
	$profile_headers_style    = 'left';
	$profile_headers_elements = array( 'online-status', 'profile-type', 'member-handle', 'joined-date', 'last-active', 'followers', 'following', 'social-networks' );

	// Member directories elements and actions.
	$member_directory_elements        = array( 'online-status', 'profile-type', 'followers', 'last-active', 'joined-date' );
	$member_directory_profile_actions = array( 'follow', 'connect', 'message' );
	$member_directory_primary_action  = '';

	// If enabled follow component then save follow as primary action.
	if ( function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ) && function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active() ) {
		$member_directory_primary_action = 'follow';
	}

	bp_update_option( 'bb-pro-profile-headers-layout-style', $profile_headers_style );
	bp_update_option( 'bb-pro-profile-headers-layout-elements', $profile_headers_elements );

	bp_update_option( 'bb-pro-member-directory-elements', $member_directory_elements );
	bp_update_option( 'bb-pro-member-profile-actions', $member_directory_profile_actions );
	bp_update_option( 'bb-pro-member-profile-primary-action', $member_directory_primary_action );
}

/**
 * Reset Cover image for Member Profiles when changes backend setting(width and height).
 *
 * @since 1.2.0
 *
 * @param mixed  $old_value The old option value.
 * @param mixed  $value     The new option value.
 * @param string $option    Option name.
 */
function bb_reset_profile_cover_position_on_change_sizes( $old_value, $value, $option ) {
	if ( bbp_pro_is_license_valid() && ( 'bb-pro-cover-profile-width' === $option || 'bb-pro-cover-profile-height' === $option ) ) {
		$all_users = get_users(
			array(
				'fields'   => 'ids',
				'meta_key' => 'bp_cover_position', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);

		if ( ! empty( $all_users ) ) {
			foreach ( $all_users as $id ) {
				delete_user_meta( $id, 'bp_cover_position' );
			}
		}
	}
}

/**
 * Reset primary action in member directories when disabled the 'Follow' feature.
 *
 * @since 1.2.0
 *
 * @param mixed  $old_value The old option value.
 * @param mixed  $value     The new option value.
 * @param string $option    Option name.
 */
function bb_reset_primary_action_on_change_activity_follow( $old_value, $value, $option ) {
	$primary_action = function_exists( 'bb_platform_pro_get_member_directory_primary_action' ) ? bb_platform_pro_get_member_directory_primary_action() : '';

	if ( '_bp_enable_activity_follow' === $option && 'follow' === $primary_action ) {
		if ( function_exists( 'bp_is_activity_follow_active' ) && ! bp_is_activity_follow_active() ) {
			$primary_action = '';
		}
	}

	bp_update_option( 'bb-pro-member-profile-primary-action', $primary_action );
}

/**
 * Reset primary action in member directories when change the components('Activity Feeds', 'Member Connections', 'Private Messaging') status.
 *
 * @since 1.2.0
 *
 * @param array $active_components Components to install.
 */
function bb_reset_primary_action_on_change_component( $active_components = array() ) {
	$primary_action = function_exists( 'bb_platform_pro_get_member_directory_primary_action' ) ? bb_platform_pro_get_member_directory_primary_action() : '';

	if ( ! empty( $active_components ) ) {
		$active_components = array_keys( $active_components );
	}

	// Primary action should be 'None' if deactivate 'Activity Feeds' component and primary action is 'Follow'.
	if ( 'follow' === $primary_action && ! in_array( 'activity', $active_components, true ) ) {
		$primary_action = '';

		// Primary action should be 'None' if deactivate 'Member Connections' component and primary action is 'Connect'.
	} elseif ( 'connect' === $primary_action && ! in_array( 'friends', $active_components, true ) ) {
		$primary_action = '';

		// Primary action should be 'None' if deactivate 'Private Messaging' component and primary action is 'Send Message'.
	} elseif ( 'message' === $primary_action && ! in_array( 'messages', $active_components, true ) ) {
		$primary_action = '';
	}

	bp_update_option( 'bb-pro-member-profile-primary-action', $primary_action );
}

/**
 * Adding the profile settings into api.
 *
 * @since 2.2.6
 *
 * @param array $settings Array settings.
 *
 * @return mixed
 */
function bb_rest_profiles_platform_settings( $settings ) {
	if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'xprofile' ) ) {
		return $settings;
	}

	$settings['profile_cover_width']     = bb_get_profile_cover_image_width();
	$settings['profile_cover_height']    = bb_get_profile_cover_image_height();
	$settings['profile_header_style']    = bb_platform_pro_profile_headers_style();
	$settings['profile_header_elements'] = bp_get_option( 'bb-pro-profile-headers-layout-elements', array( 'online-status', 'profile-type', 'member-handle', 'joined-date', 'last-active', 'followers', 'following', 'social-networks' ) );

	return $settings;
}
