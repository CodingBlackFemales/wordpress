<?php
/**
 * Schedule posts filters.
 *
 * @since   2.5.20
 *
 * @package BuddyBossPro
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Filters for the REST settings.
add_filter( 'bp_rest_platform_settings', 'bb_rest_schedule_posts_platform_settings' );
add_filter( 'bb_is_enabled_activity_schedule_posts', 'bb_is_enabled_activity_schedule_posts_filter', 999 );
add_filter( 'bp_core_get_js_strings', 'bb_schedule_posts_localize_scripts', 11 );
add_filter( 'bb_is_enabled_activity_schedule_posts', 'bb_is_enabled_activity_schedule_posts_admin_only', PHP_INT_MAX );
add_filter( 'bp_rest_activity_get_items_permissions_check', 'bb_rest_activity_get_items_schedule_posts_permissions_check', 10, 2 );
add_filter( 'bp_rest_activity_get_item_permissions_check', 'bb_rest_activity_get_item_schedule_posts_permissions_check', 10, 2 );
add_filter( 'bp_rest_activity_update_item_permissions_check', 'bb_rest_activity_update_item_schedule_posts_permissions_check', 10, 2 );
add_filter( 'bp_rest_activity_delete_item_permissions_check', 'bb_rest_activity_delete_item_schedule_posts_permissions_check', 10, 2 );


/**
 * Add schedule posts settings into API.
 *
 * @since 2.5.20
 *
 * @param array $settings Array settings.
 *
 * @return array Array of settings.
 */
function bb_rest_schedule_posts_platform_settings( $settings ) {

	if (
		! function_exists( 'bp_is_active' ) ||
		! bp_is_active( 'activity' ) ||
		! function_exists( 'bb_is_enabled_activity_schedule_posts' )
	) {
		return $settings;
	}

	$settings['bb_enable_activity_schedule_post'] = bb_is_enabled_activity_schedule_posts();

	return $settings;
}

/**
 * Filter to check platform pro active with valid license for scheduled posts.
 *
 * @since 2.5.20
 *
 * @return bool $value Filtered schedule posts setting value.
 */
function bb_is_enabled_activity_schedule_posts_filter() {

	// Return false if platform pro has not valid license.
	if (
		bb_pro_should_lock_features() ||
		version_compare( bb_platform_pro()->version, '2.5.20', '<' )
	) {
		return false;
	}

	return (bool) bp_get_option( '_bb_enable_activity_schedule_posts', false );
}

/**
 * Localize the strings needed for the schedule posts.
 *
 * @since 2.5.20
 *
 * @param array $params Associative array containing the js strings needed by scripts.
 *
 * @return array The same array with specific strings for the schedule posts if needed.
 */
function bb_schedule_posts_localize_scripts( $params ) {

	if ( ! bb_is_enabled_activity_schedule_posts() ) {
		return $params;
	}

	$activity_params = array(
		'scheduled_post_nonce'   => wp_create_nonce( 'scheduled_post_nonce' ),
		'scheduled_post_enabled' => function_exists( 'bb_is_enabled_activity_schedule_posts' ) && bb_is_enabled_activity_schedule_posts(),
		'can_schedule_in_feed'   => bb_can_user_schedule_activity(),
	);

	$activity_strings = array(
		'schedulePostButton'        => esc_html__( 'Schedule', 'buddyboss-pro' ),
		'confirmDeletePost'         => esc_html__( 'Are you sure you want to delete that permanently?', 'buddyboss-pro' ),
		'scheduleWarning'           => esc_html__( 'Schedule Outdated', 'buddyboss-pro' ),
		'successDeletionTitle'      => esc_html__( 'Scheduled Post Deleted', 'buddyboss-pro' ),
		'successDeletionDesc'       => esc_html__( 'Your scheduled post has been deleted.', 'buddyboss-pro' ),
		'successScheduleTitle'      => esc_html__( 'Successfully Scheduled Post', 'buddyboss-pro' ),
		'successScheduleDesc'       => esc_html__( 'Your post has been scheduled.', 'buddyboss-pro' ),
		'EditSuccessScheduleTitle'  => esc_html__( 'Successfully Updated Post', 'buddyboss-pro' ),
		'EditSuccessScheduleDesc'   => esc_html__( 'Your post schedule has been updated.', 'buddyboss-pro' ),
		'EditViewSchedulePost'      => esc_html__( 'View now', 'buddyboss-pro' ),
		'viewSchedulePosts'         => esc_html__( 'View all posts', 'buddyboss-pro' ),
		'activity_schedule_enabled' => function_exists( 'bb_is_enabled_activity_schedule_posts' ) && bb_is_enabled_activity_schedule_posts(),
		'notAllowScheduleWarning'   => esc_html__( 'Unable to schedule post as you are not the owner or moderator of this group', 'buddyboss-pro' ),
	);

	if ( ! empty( $params['activity_schedule']['params'] ) ) {
		$params['activity_schedule']['params'] = array_merge( $params['activity_schedule']['params'], $activity_params );
	} else {
		$params['activity_schedule']['params'] = $activity_params;
	}

	if ( ! empty( $params['activity_schedule']['strings'] ) ) {
		$params['activity_schedule']['strings'] = array_merge( $params['activity_schedule']['strings'], $activity_strings );
	} else {
		$params['activity_schedule']['strings'] = $activity_strings;
	}

	return $params;
}

/**
 * Function to allow always schedule post for admin.
 *
 * @since 2.5.21
 *
 * @param bool $retval Value of schedule post.
 *
 * @return bool
 */
function bb_is_enabled_activity_schedule_posts_admin_only( $retval ) {

	if ( bp_current_user_can( 'administrator' ) ) {
		return true;
	}

	return $retval;
}

/**
 * Permissions check for the scheduled posts.
 *
 * @since 2.8.0
 *
 * @param bool|WP_Error   $retval  The return value.
 * @param WP_REST_Request $request The request object.
 *
 * @return bool|WP_Error
 */
function bb_rest_activity_get_items_schedule_posts_permissions_check( $retval, $request ) {
	$activity_status = $request->get_param( 'activity_status' );

	if (
		empty( $activity_status ) ||
		bb_get_activity_scheduled_status() !== $activity_status
	) {
		return $retval;
	}

	// If the user is not logged in, return false.
	if ( ! is_user_logged_in() ) {
		return new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, Restrict access to only logged-in members.', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	$user_id = $request->get_param( 'user_id' );
	if ( bp_loggedin_user_id() !== $user_id ) {
		return new WP_Error(
			'bp_rest_forbidden',
			__( 'You are not allowed to view scheduled posts for this user.', 'buddyboss-pro' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	return $retval;
}

/**
 * Permissions check for the scheduled posts item.
 *
 * @since 2.8.0
 *
 * @param bool|WP_Error   $retval  The return value.
 * @param WP_REST_Request $request The request object.
 *
 * @return bool|WP_Error
 */
function bb_rest_activity_get_item_schedule_posts_permissions_check( $retval, $request ) {
	$activity_id = $request->get_param( 'id' );
	if ( empty( $activity_id ) ) {
		return $retval;
	}

	$activity = new BP_Activity_Activity( $activity_id );

	if (
		empty( $activity->id ) ||
		bb_get_activity_scheduled_status() !== $activity->status
	) {
		return $retval;
	}

	// If the user is not logged in, return false.
	if ( ! is_user_logged_in() ) {
		return new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, Restrict access to only logged-in members.', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	$user_id = $activity->user_id;
	if ( bp_loggedin_user_id() !== $user_id ) {
		return new WP_Error(
			'bp_rest_authorization_required',
			__( 'You are not allowed to view scheduled posts for this user.', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	return $retval;
}

/**
 * Permissions check for updating scheduled post item.
 *
 * @since 2.8.0
 *
 * @param bool|WP_Error   $retval  The return value.
 * @param WP_REST_Request $request The request object.
 *
 * @return bool|WP_Error
 */
function bb_rest_activity_update_item_schedule_posts_permissions_check( $retval, $request ) {
	$activity_id = $request->get_param( 'id' );
	if ( empty( $activity_id ) ) {
		return $retval;
	}

	$activity = new BP_Activity_Activity( $activity_id );

	if (
		empty( $activity->id ) ||
		bb_get_activity_scheduled_status() !== $activity->status
	) {
		return $retval;
	}

	// If the user is not logged in, return false.
	if ( ! is_user_logged_in() ) {
		return new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, Restrict access to only logged-in members.', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	$user_id = $activity->user_id;
	if ( bp_loggedin_user_id() !== $user_id ) {
		return new WP_Error(
			'bp_rest_authorization_required',
			__( 'You are not allowed to update scheduled posts for this user.', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	return $retval;
}

/**
 * Permissions check for deleting scheduled post item.
 *
 * @since 2.8.0
 *
 * @param bool|WP_Error   $retval  The return value.
 * @param WP_REST_Request $request The request object.
 *
 * @return bool|WP_Error
 */
function bb_rest_activity_delete_item_schedule_posts_permissions_check( $retval, $request ) {
	$activity_id = $request->get_param( 'id' );
	if ( empty( $activity_id ) ) {
		return $retval;
	}

	$activity = new BP_Activity_Activity( $activity_id );

	if (
		empty( $activity->id ) ||
		bb_get_activity_scheduled_status() !== $activity->status
	) {
		return $retval;
	}

	// If the user is not logged in, return false.
	if ( ! is_user_logged_in() ) {
		return new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, Restrict access to only logged-in members.', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	$user_id = $activity->user_id;
	if ( bp_loggedin_user_id() !== $user_id ) {
		return new WP_Error(
			'bp_rest_authorization_required',
			__( 'You are not allowed to delete scheduled posts for this user.', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	return $retval;
}
