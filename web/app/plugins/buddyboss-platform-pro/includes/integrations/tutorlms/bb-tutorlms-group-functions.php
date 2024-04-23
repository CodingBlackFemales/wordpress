<?php
/**
 * TutorLMS group integration helpers.
 *
 * @since 2.4.40
 *
 * @package BuddyBoss\TutorLMS
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Checks if TutorLMS course visibility enable.
 *
 * @since 2.4.40
 *
 * @param integer $default TutorLMS course visibility enabled by default.
 *
 * @return bool Is TutorLMS course visibility enabled or not.
 */
function bb_tutorlms_course_visibility( $default = 1 ) {

	/**
	 * Filters TutorLMS course visibility enabled settings.
	 *
	 * @since 2.4.40
	 *
	 * @param integer $default TutorLMS course visibility enabled by default.
	 */
	return (bool) apply_filters( 'bb_tutorlms_course_visibility', bb_get_tutorlms_settings( 'bb-tutorlms-course-visibility', $default ) );
}

/**
 * Function to get enabled TutorLMS courses activities.
 *
 * @since 2.4.40
 *
 * @param string $key TutorLMS course activity slug.
 *
 * @return array Is any TutorLMS courses activities enabled?
 */
function bb_get_enabled_tutorlms_course_activities( $key = '' ) {

	$option_name = ! empty( $key ) ? 'bb-tutorlms-course-activity.' . $key : 'bb-tutorlms-course-activity';

	/**
	 * Filters to get enabled TutorLMS courses activities.
	 *
	 * @since 2.4.40
	 *
	 * @param array|string $activites TutorLMS course activities.
	 */
	return apply_filters( 'bb_tutorlms_course_activities', bb_get_tutorlms_settings( $option_name ) );
}

/**
 * TutorLMS course activities.
 *
 * @since 2.4.40
 *
 * @param array $keys Optionals.
 *
 * @return array
 */
function bb_tutorlms_course_activities( $keys = array() ) {
	$activities = array(
		'bb_tutorlms_user_enrolled_course'  => esc_html__( 'Group member enrolled in a course', 'buddyboss-pro' ),
		'bb_tutorlms_user_course_start'     => esc_html__( 'Group member started a course', 'buddyboss-pro' ),
		'bb_tutorlms_user_completed_course' => esc_html__( 'Group member completes a course', 'buddyboss-pro' ),
		'bb_tutorlms_user_creates_lesson'   => esc_html__( 'Group member creates a lesson', 'buddyboss-pro' ),
		'bb_tutorlms_user_updated_lesson'   => esc_html__( 'Group member updates a lesson', 'buddyboss-pro' ),
		'bb_tutorlms_user_started_quiz'     => esc_html__( 'Group member started a quiz', 'buddyboss-pro' ),
		'bb_tutorlms_user_finished_quiz'    => esc_html__( 'Group member finished a quiz', 'buddyboss-pro' ),
	);

	return ! empty( $keys ) ? array_intersect_key( $activities, $keys ) : $activities;
}

/**
 * Function to return course activities which is selected in the group.
 *
 * @since 2.4.40
 *
 * @param int $group_id Group ID.
 *
 * @return array|mixed|void
 */
function bb_tutorlms_get_group_course_activities( $group_id ) {
	if ( empty( $group_id ) ) {
		return;
	}

	$bb_tutorlms_groups = groups_get_groupmeta( $group_id, 'bb-tutorlms-groups-courses-activities' );

	return ! empty( $bb_tutorlms_groups ) ? $bb_tutorlms_groups : array();
}

/**
 * Function to check individual course activity enable or not.
 *
 * @since 2.4.40
 *
 * @param int    $group_id Group ID.
 * @param string $key      Group course activity key.
 *
 * @return bool
 */
function bb_tutorlms_group_course_activity_enable( $group_id, $key ) {
	if ( empty( $group_id ) || empty( $key ) ) {
		return false;
	}

	$global_course_activity_enable = bb_get_enabled_tutorlms_course_activities( $key );
	if ( ! $global_course_activity_enable ) {
		return false;
	}

	$bb_tutorlms_groups = bb_tutorlms_get_group_course_activities( $group_id );

	if ( isset( $bb_tutorlms_groups[ $key ] ) ) {
		return true;
	}

	return false;
}

/**
 * Determine who can manage the course tab for manage group.
 *
 * @since 2.4.40
 *
 * @return bool
 */
function bb_tutorlms_manage_tab() {
	if ( ! bb_tutorlms_enable() || ! bb_tutorlms_course_visibility() ) {
		return false;
	}
	if ( ! current_user_can( 'administrator' ) && ! current_user_can( tutor()->instructor_role ) ) {
		return false;
	}

	return true;
}

/**
 * Get if TutorLMS courses are enabled or not.
 *
 * @since 2.4.40
 *
 * @param int $group_id Group ID.
 *
 * @return bool Whether TutorLMS courses are enabled for editing in the group.
 */
function bb_tutorlms_group_courses_is_enable( $group_id ) {
	if ( empty( $group_id ) ) {
		return false;
	}

	return (bool) groups_get_groupmeta( $group_id, 'bb-tutorlms-group-course-is-enable' );
}
