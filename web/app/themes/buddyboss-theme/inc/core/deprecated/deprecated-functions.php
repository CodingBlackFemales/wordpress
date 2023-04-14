<?php
/**
 * Deprecated Functions of BuddyBoss Theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove Header/Footer for AppBoss
 */
if ( ! function_exists( 'bb_theme_remove_header_footer_for_appboss' ) ) {

	function bb_theme_remove_header_footer_for_appboss() {

		_deprecated_function( __FUNCTION__, '1.6.4', 'bb_theme_remove_header_footer_for_buddyboss_app()' );
		bb_theme_remove_header_footer_for_buddyboss_app();
	}
}

/**
 * Followers Count
 *
 * @deprecated 1.8.7
 *
 * @param mixed $user_id user id.
 */
function buddyboss_theme_followers_count( $user_id = false ) {
	_deprecated_function( __FUNCTION__, '1.8.7', 'bb_get_followers_count()' );

	if ( function_exists( 'bb_get_followers_count' ) ) {
		bb_get_followers_count( $user_id );
	}
}

/**
 * Following Count
 *
 * @deprecated 1.8.7
 *
 * @param mixed $user_id user id.
 */
function buddyboss_theme_following_count( $user_id = false ) {
	_deprecated_function( __FUNCTION__, '1.8.7', 'bb_get_following_count()' );

	if ( function_exists( 'bb_get_following_count' ) ) {
		bb_get_following_count( $user_id );
	}
}

/**
 * Reset Cover image for Member Profiles when changes backend setting(width and height).
 *
 * @deprecated 1.8.7
 *
 * @return bool
 */
function buddyboss_theme_reset_profile_cover_position() {
	_deprecated_function( __FUNCTION__, '1.8.7', false );

	return false;
}

/**
 * Reset Cover image for Social Groups when changes backend setting(width and height).
 *
 * @deprecated 1.8.7
 *
 * @return bool
 */
function buddyboss_theme_reset_bb_group_cover_position() {
	_deprecated_function( __FUNCTION__, '1.8.7', false );

	return false;
}

/**
 * Function which will return the member id if $id > 0 then it will return the original displayed id
 * else it will return the member loop member id.
 *
 * @deprecated 1.8.7
 *
 * @param int $id Member ID.
 *
 * @return bool
 */
function buddyboss_theme_member_loop_set_member_id( $id = false ) {
	_deprecated_function( __FUNCTION__, '1.8.7', 'bb_member_loop_set_member_id' );

	if ( function_exists( 'bb_member_loop_set_member_id' ) ) {
		return bb_member_loop_set_member_id( $id );
	}

	return $id;
}

/**
 * Function which will return the member id if $id > 0 then it will return the original displayed id
 * else it will return the member loop member id.
 *
 * @deprecated 1.8.7
 *
 * @param bool $my_profile The current page is profile page or not.
 *
 * @return bool
 */
function buddyboss_theme_member_loop_set_my_profile( $my_profile ) {
	_deprecated_function( __FUNCTION__, '1.8.7', 'bb_member_loop_set_my_profile' );

	if ( function_exists( 'bb_member_loop_set_my_profile' ) ) {
		return bb_member_loop_set_my_profile( $my_profile );
	}

	return $my_profile;
}

/**
 * BuddyPress user status
 *
 * @param $user_id
 */
if ( ! function_exists( 'bb_user_status' ) ) {

	/**
	 * BuddyPress user status
	 *
	 * @param int $user_id User id.
	 */
	function bb_user_status( $user_id ) {

		_deprecated_function( __FUNCTION__, '1.8.9', 'bb_current_user_status' );

		bb_current_user_status( $user_id );
	}
}

if ( ! function_exists( 'buddyboss_notification_avatar' ) ) {

	/**
	 * Get avatar for notification user.
	 *
	 * @return void
	 */
	function buddyboss_notification_avatar() {

		_deprecated_function( __FUNCTION__, '1.8.9', 'bb_notification_avatar' );

		bb_notification_avatar();

	}
}
