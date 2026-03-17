<?php
/**
 * MemberpressLMS group integration helpers.
 *
 * @since 2.6.30
 *
 * @package BuddyBossPro\Integration\MemberpressLMS
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Function to return course activities which is selected in the group.
 *
 * @since 2.6.30
 *
 * @param int $group_id Group ID.
 *
 * @return array|mixed|void
 */
function bb_meprlms_get_group_course_activities( $group_id ) {
	if ( empty( $group_id ) ) {
		return;
	}

	$bb_meprlms_groups = groups_get_groupmeta( $group_id, 'bb-meprlms-groups-courses-activities' );

	return ! empty( $bb_meprlms_groups ) ? $bb_meprlms_groups : array();
}

/**
 * Function to check individual course activity enable or not.
 *
 * @since 2.6.30
 *
 * @param int    $group_id Group ID.
 * @param string $key      Group course activity key.
 *
 * @return bool
 */
function bb_meprlms_group_course_activity_enable( $group_id, $key ) {
	if ( empty( $group_id ) || empty( $key ) ) {
		return false;
	}

	$global_course_activity_enable = bb_get_enabled_meprlms_course_activities( $key );
	if ( ! $global_course_activity_enable ) {
		return false;
	}

	$bb_meprlms_groups = bb_meprlms_get_group_course_activities( $group_id );

	if ( isset( $bb_meprlms_groups[ $key ] ) ) {
		return true;
	}

	return false;
}

/**
 * Determine who can manage the course tab for manage group.
 *
 * @since 2.6.30
 *
 * @return bool
 */
function bb_meprlms_manage_tab() {
	if ( ! bb_meprlms_enable() || ! bb_meprlms_course_visibility() ) {
		return false;
	}
	if ( ! current_user_can( 'administrator' ) ) {
		return false;
	}

	return true;
}

/**
 * Get if MemberpressLMS courses are enabled or not.
 *
 * @since 2.6.30
 *
 * @param int $group_id Group ID.
 *
 * @return bool Whether MemberpressLMS courses are enabled for editing in the group.
 */
function bb_meprlms_group_courses_is_enable( $group_id ) {
	if ( empty( $group_id ) ) {
		return false;
	}

	return (bool) groups_get_groupmeta( $group_id, 'bb-meprlms-group-course-is-enable' );
}
