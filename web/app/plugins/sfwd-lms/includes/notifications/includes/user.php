<?php
/**
 * User related functions.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Notifications
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Delete LearnDash related user data from scheduled cron
 *
 * @param int $user_id ID of a user
 */
function learndash_notifications_delete_learndash_user_data_cron() {
	$schedules = get_option( 'learndash_notifications_delete_user_data', [] );

	foreach ( $schedules as $user_id => $args ) {
		delete_user_meta( $user_id, '_ld_notifications_last_login' );

		$courses = $args['courses'];
		$groups  = $args['groups'];

		foreach ( $courses as $course_id ) {
			delete_user_meta( $user_id, 'ld_sent_notification_enroll_course_' . $course_id );

			$lessons = learndash_get_lesson_list( $course_id );

			foreach ( $groups as $group_id ) {
				delete_user_meta( $user_id, 'ld_sent_notification_enroll_group_course_' . $course_id . '_' . $group_id );
			}

			foreach ( $lessons as $lesson ) {
				delete_user_meta( $user_id, 'ld_sent_notification_lesson_available_' . $lesson->ID );
			}
		}

		unset( $schedules[ $user_id ] );
	}

	update_option( 'learndash_notifications_delete_user_data', $schedules );
}

/**
 * Schedule cron delete user data in DB
 *
 * @param int $user_id User ID
 */
function learndash_notifications_schedule_delete_user_data( $user_id ) {
	$courses = ld_get_mycourses( $user_id );
	$groups  = learndash_get_groups( $id_only = true, $user_id ) ?: [];

	$schedules = get_option( 'learndash_notifications_delete_user_data', [] );

	$schedules[ $user_id ] = [
		'courses' => $courses,
		'groups'  => $groups,
	];

	update_option( 'learndash_notifications_delete_user_data', $schedules );
}

add_action( 'learndash_delete_user_data', 'learndash_notifications_schedule_delete_user_data' );
