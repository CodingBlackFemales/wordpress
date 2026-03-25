<?php
/**
 * Memberpress LMS integration actions
 *
 * @package BuddyBossPro\Integration\MemberpressLMS
 * @since 2.6.30
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'mpcs_started_course', 'bb_meprlms_group_activity_started_course' );
add_action( 'mpcs_completed_course', 'bb_meprlms_group_activity_course_complete' );
add_action( 'mpcs_completed_lesson', 'bb_meprlms_group_activity_lesson_complete' );

/**
 * Function to add group activity record when any user started a course.
 *
 * @since 2.6.30
 *
 * @param object $user_progress User progress.
 *
 * @return void
 */
function bb_meprlms_group_activity_started_course( $user_progress ) {

	if ( ! is_user_logged_in() ) {
		return;
	}

	$course_id = $user_progress->course_id;
	$user_id   = $user_progress->user_id;

	$bb_meprlms_groups = bb_load_meprlms_group()->get(
		array(
			'course_id' => $course_id,
			'fields'    => 'group_id',
		)
	);

	if ( empty( $bb_meprlms_groups['courses'] ) ) {
		return;
	}

	$user_link   = bp_core_get_userlink( $user_id );
	$course_url  = "<a href='" . get_the_permalink( $course_id ) . "' target='_blank'>" . get_the_title( $course_id ) . '</a>';
	$group_ids   = $bb_meprlms_groups['courses'];
	$action_type = 'bb_meprlms_user_started_course';

	if ( bp_is_active( 'activity' ) && bp_is_active( 'groups' ) && count( $group_ids ) ) {
		foreach ( $group_ids as $group_id ) {
			$activity_enable = bb_meprlms_group_courses_is_enable( $group_id ) && bb_meprlms_group_course_activity_enable( $group_id, $action_type );
			if ( $activity_enable && groups_is_user_member( $user_id, $group_id ) ) {
				do_action( 'bb_meprlms_group_activity_started_course_before', $action_type, $group_id, $course_id );

				$activity_args = apply_filters(
					'bb_meprlms_group_activity_started_course_args',
					array(
						'user_id'           => $user_id,
						'action'            => $action_type,
						/* translators: %1$s is the user link, %2$s is the course URL. */
						'content'           => sprintf( __( '%1$s started on %2$s.', 'buddyboss-pro' ), $user_link, $course_url ),
						'type'              => 'activity_update',
						'item_id'           => $group_id,
						'secondary_item_id' => $course_id,
					)
				);

				remove_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );
				$activity_id = groups_record_activity( $activity_args );
				add_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );

				do_action( 'bb_meprlms_group_activity_started_course_after', $action_type, $group_id, $course_id, $activity_id );
			}
		}
	}
}

/**
 * Function to add group activity record when any user completes a course.
 *
 * @since 2.6.30
 *
 * @param object $user_progress User progress.
 *
 * @return void
 */
function bb_meprlms_group_activity_course_complete( $user_progress ) {

	if ( ! is_user_logged_in() ) {
		return;
	}

	$course_id = $user_progress->course_id;
	$user_id   = $user_progress->user_id;

	$bb_meprlms_groups = bb_load_meprlms_group()->get(
		array(
			'course_id' => $course_id,
			'fields'    => 'group_id',
		)
	);

	if ( empty( $bb_meprlms_groups['courses'] ) ) {
		return;
	}

	$user_link   = bp_core_get_userlink( $user_id );
	$course_url  = "<a href='" . get_the_permalink( $course_id ) . "' target='_blank'>" . get_the_title( $course_id ) . '</a>';
	$group_ids   = $bb_meprlms_groups['courses'];
	$action_type = 'bb_meprlms_user_completed_course';

	if ( bp_is_active( 'activity' ) && bp_is_active( 'groups' ) && count( $group_ids ) ) {
		foreach ( $group_ids as $group_id ) {
			$activity_enable = bb_meprlms_group_courses_is_enable( $group_id ) && bb_meprlms_group_course_activity_enable( $group_id, $action_type );
			if ( $activity_enable && groups_is_user_member( $user_id, $group_id ) ) {
				do_action( 'bb_meprlms_group_activity_completed_course_before', $action_type, $group_id, $course_id );

				$activity_args = apply_filters(
					'bb_meprlms_group_activity_completed_course_args',
					array(
						'user_id'           => $user_id,
						'action'            => $action_type,
						/* translators: %1$s is the user link, %2$s is the course URL. */
						'content'           => sprintf( __( '%1$s completed %2$s.', 'buddyboss-pro' ), $user_link, $course_url ),
						'type'              => 'activity_update',
						'item_id'           => $group_id,
						'secondary_item_id' => $course_id,
					)
				);

				remove_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );
				$activity_id = groups_record_activity( $activity_args );
				add_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );

				do_action( 'bb_meprlms_group_activity_completed_course_after', $action_type, $group_id, $course_id, $activity_id );
			}
		}
	}
}

/**
 * Function to add group activity record when any user completes a lesson/assignment/quiz.
 *
 * @since 2.6.30
 *
 * @param object $user_progress User progress.
 *
 * @return void
 */
function bb_meprlms_group_activity_lesson_complete( $user_progress ) {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$course_id = $user_progress->course_id;
	$lesson_id = $user_progress->lesson_id;
	$user_id   = $user_progress->user_id;
	$post_type = get_post_type( $user_progress->lesson_id );

	// Get group ids linked to the course.
	$bb_meprlms_groups = bb_load_meprlms_group()->get(
		array(
			'course_id' => $course_id,
			'fields'    => 'group_id',
		)
	);

	if ( empty( $bb_meprlms_groups['courses'] ) ) {
		return;
	}

	$user_link  = bp_core_get_userlink( $user_id );
	$lesson_url = "<a href='" . get_the_permalink( $lesson_id ) . "' target='_blank'>" . get_the_title( $lesson_id ) . '</a>';
	$group_ids  = $bb_meprlms_groups['courses'];

	if ( class_exists( 'memberpress\assignments\models\Assignment' ) && memberpress\assignments\models\Assignment::$cpt === $post_type ) {
		$action_type = 'bb_meprlms_user_completed_assignment';
	} elseif ( class_exists( 'memberpress\quizzes\models\Quiz' ) && memberpress\quizzes\models\Quiz::$cpt === $post_type ) {
		$action_type = 'bb_meprlms_user_completed_quiz';
	} else {
		$action_type = 'bb_meprlms_user_completed_lesson';
	}

	if ( bp_is_active( 'activity' ) && bp_is_active( 'groups' ) && count( $group_ids ) ) {
		foreach ( $group_ids as $group_id ) {
			$activity_enable = bb_meprlms_group_courses_is_enable( $group_id ) && bb_meprlms_group_course_activity_enable( $group_id, $action_type );
			if ( $activity_enable && groups_is_user_member( $user_id, $group_id ) ) {
				do_action( 'bb_meprlms_group_activity_completed_lesson_before', $action_type, $group_id, $course_id, $lesson_id );

				$activity_args = apply_filters(
					'bb_meprlms_group_activity_completed_lesson_args',
					array(
						'user_id'           => $user_id,
						'action'            => $action_type,
						/* translators: %1$s is the user link, %2$s is the lesson URL. */
						'content'           => sprintf( __( '%1$s just completed %2$s', 'buddyboss-pro' ), $user_link, $lesson_url ),
						'type'              => 'activity_update',
						'item_id'           => $group_id,
						'secondary_item_id' => $course_id,
					)
				);

				remove_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );
				$activity_id = groups_record_activity( $activity_args );
				add_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );

				do_action( 'bb_meprlms_group_activity_completed_lesson_after', $action_type, $group_id, $course_id, $lesson_id, $activity_id );
			}
		}
	}
}
