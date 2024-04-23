<?php
/**
 * TutorLMS integration actions
 *
 * @package BuddyBoss\TutorLMS
 * @since 2.4.40
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'save_tutor_course', 'bb_save_tutor_course', 10, 2 );
add_action( 'tutor_after_enroll', 'bb_tutorlms_group_activity_after_enroll' );
add_action( 'tutor/course/started', 'bb_tutorlms_group_activity_started_course' );
add_action( 'tutor_course_complete_after', 'bb_tutorlms_group_activity_course_complete' );
add_action( 'tutor/lesson/created', 'bb_tutorlms_group_activity_creates_lesson' );
add_action( 'tutor/lesson_update/after', 'bb_tutorlms_group_activity_lesson_update' );
add_action( 'tutor_quiz/start/before', 'bb_tutorlms_group_activity_quiz_start', 10, 2 );
add_action( 'tutor_quiz_finished', 'bb_tutorlms_group_activity_quiz_finished', 10, 2 );
add_action( 'tutor_quiz/attempt_ended', 'bb_tutorlms_group_activity_quiz_attempt_ended' );

/**
 * Function to add activity record once course published from front side.
 *
 * @since 2.4.40
 *
 * @param int   $post_ID   Post ID.
 * @param array $post_data Post Data.
 *
 * @return void
 */
function bb_save_tutor_course( $post_ID, $post_data ) {
	if ( ! tutor_utils()->is_instructor( bp_loggedin_user_id() ) || ! current_user_can( 'administrator' ) ) {
		return;
	}

	$can_publish_course = (bool) tutor_utils()->get_option( 'instructor_can_publish_course' );
	if ( $can_publish_course && function_exists( 'bp_activity_post_type_publish' ) ) {
		/**
		 * We need to fetch post data from post_id.
		 */
		$post_data = get_post( $post_ID );
		bp_activity_post_type_publish( $post_ID, $post_data );
	}
}

/**
 * Function to add group activity record when any user enrolled in a course.
 *
 * @since 2.4.40
 *
 * @param int $course_id Course ID.
 *
 * @return void
 */
function bb_tutorlms_group_activity_after_enroll( $course_id ) {
	if ( empty( bp_loggedin_user_id() ) ) {
		return;
	}

	$bb_tutorlms_groups = bb_load_tutorlms_group()->get(
		array(
			'course_id' => $course_id,
			'fields'    => 'group_id',
		)
	);

	if ( empty( $bb_tutorlms_groups['courses'] ) ) {
		return;
	}

	$user_id     = bp_loggedin_user_id();
	$user_link   = bp_core_get_userlink( $user_id );
	$course_url  = "<a href='" . get_the_permalink( $course_id ) . "' target='_blank'>" . get_the_title( $course_id ) . "</a>";
	$group_ids   = $bb_tutorlms_groups['courses'];
	$action_type = 'bb_tutorlms_user_enrolled_course';

	if ( bp_is_active( 'activity' ) && bp_is_active( 'groups' ) && tutor_utils()->count( $group_ids ) ) {
		foreach ( $group_ids as $group_id ) {
			$activity_enable = bb_tutorlms_group_courses_is_enable( $group_id ) && bb_tutorlms_group_course_activity_enable( $group_id, 'bb_tutorlms_user_enrolled_course' );
			if ( $activity_enable && groups_is_user_member( $user_id, $group_id ) ) {
				do_action( 'bb_tutorlms_group_activity_after_enroll_before', $action_type, $group_id, $course_id );

				$activity_args = apply_filters(
					'bb_tutorlms_group_activity_after_enroll_args',
					array(
						'user_id'           => $user_id,
						'action'            => $action_type,
						'content'           => sprintf( __( '%s just enrolled in %s.', 'buddyboss-pro' ), $user_link, $course_url ),
						'type'              => 'activity_update',
						'item_id'           => $group_id,
						'secondary_item_id' => $course_id,
					)
				);

				remove_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );
				$activity_id = groups_record_activity( $activity_args );
				add_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );

				do_action( 'bb_tutorlms_group_activity_after_enroll_after', $action_type, $group_id, $course_id, $activity_id );
			}
		}
	}
}

/**
 * Function to add group activity record when any user started a course.
 *
 * @since 2.4.40
 *
 * @param int $course_id Course ID.
 *
 * @return void
 */
function bb_tutorlms_group_activity_started_course( $course_id ) {
	if ( empty( bp_loggedin_user_id() ) ) {
		return;
	}

	$bb_tutorlms_groups = bb_load_tutorlms_group()->get(
		array(
			'course_id' => $course_id,
			'fields'    => 'group_id',
		)
	);
	if ( empty( $bb_tutorlms_groups['courses'] ) ) {
		return;
	}

	$user_id     = bp_loggedin_user_id();
	$user_link   = bp_core_get_userlink( $user_id );
	$course_url  = "<a href='" . get_the_permalink( $course_id ) . "' target='_blank'>" . get_the_title( $course_id ) . "</a>";
	$group_ids   = $bb_tutorlms_groups['courses'];
	$action_type = 'bb_tutorlms_user_course_start';

	if ( bp_is_active( 'activity' ) && bp_is_active( 'groups' ) && tutor_utils()->count( $group_ids ) ) {
		foreach ( $group_ids as $group_id ) {
			$activity_enable = bb_tutorlms_group_courses_is_enable( $group_id ) && bb_tutorlms_group_course_activity_enable( $group_id, 'bb_tutorlms_user_course_start' );
			if ( $activity_enable && groups_is_user_member( $user_id, $group_id ) ) {
				do_action( 'bb_tutorlms_group_activity_started_course_before', $action_type, $group_id, $course_id );

				$activity_args = apply_filters(
					'bb_tutorlms_group_activity_started_course_args',
					array(
						'user_id'           => $user_id,
						'action'            => $action_type,
						'content'           => sprintf( __( '%s started on %s.', 'buddyboss-pro' ), $user_link, $course_url ),
						'type'              => 'activity_update',
						'item_id'           => $group_id,
						'secondary_item_id' => $course_id,
					)
				);

				remove_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );
				$activity_id = groups_record_activity( $activity_args );
				add_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );

				do_action( 'bb_tutorlms_group_activity_started_course_after', $action_type, $group_id, $course_id, $activity_id );
			}
		}
	}
}

/**
 * Function to add group activity record when any user completes a course.
 *
 * @since 2.4.40
 *
 * @param int $course_id Course ID.
 *
 * @return void
 */
function bb_tutorlms_group_activity_course_complete( $course_id ) {
	if ( empty( bp_loggedin_user_id() ) ) {
		return;
	}

	$bb_tutorlms_groups = bb_load_tutorlms_group()->get(
		array(
			'course_id' => $course_id,
			'fields'    => 'group_id',
		)
	);
	if ( empty( $bb_tutorlms_groups['courses'] ) ) {
		return;
	}

	$user_id     = bp_loggedin_user_id();
	$user_link   = bp_core_get_userlink( $user_id );
	$group_ids   = $bb_tutorlms_groups['courses'];
	$action_type = 'bb_tutorlms_user_completed_course';
	$course_url  = "<a href='" . get_the_permalink( $course_id ) . "' target='_blank'>" . get_the_title( $course_id ) . "</a>";

	if ( bp_is_active( 'activity' ) && bp_is_active( 'groups' ) && tutor_utils()->count( $group_ids ) ) {
		foreach ( $group_ids as $group_id ) {
			$activity_enable = bb_tutorlms_group_courses_is_enable( $group_id ) && bb_tutorlms_group_course_activity_enable( $group_id, 'bb_tutorlms_user_completed_course' );
			if ( $activity_enable && groups_is_user_member( $user_id, $group_id ) ) {
				do_action( 'bb_tutorlms_group_activity_completes_course_before', $action_type );

				$activity_args = apply_filters(
					'bb_tutorlms_group_activity_completes_course_args',
					array(
						'user_id'           => $user_id,
						'action'            => $action_type,
						'content'           => sprintf( __( '%s successfully completed %s.', 'buddyboss-pro' ), $user_link, $course_url ),
						'type'              => 'activity_update',
						'item_id'           => $group_id,
						'secondary_item_id' => $course_id,
					)
				);

				remove_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );
				$activity_id = groups_record_activity( $activity_args );
				add_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );

				do_action( 'bb_tutorlms_group_activity_completes_course_after', $action_type, $activity_id );
			}
		}
	}
}

/**
 * Function to add group activity record when lesson created.
 *
 * @since 2.4.40
 *
 * @param int $lesson_id Lesson ID.
 *
 * @return void
 */
function bb_tutorlms_group_activity_creates_lesson( $lesson_id ) {
	if ( empty( bp_loggedin_user_id() ) ) {
		return;
	}

	$course_id = tutor_utils()->get_course_id_by_content( $lesson_id );
	if ( empty( $course_id ) ) {
		return;
	}

	$bb_tutorlms_groups = bb_load_tutorlms_group()->get(
		array(
			'course_id' => $course_id,
			'fields'    => 'group_id',
		)
	);
	if ( empty( $bb_tutorlms_groups['courses'] ) ) {
		return;
	}

	$user_id     = bp_loggedin_user_id();
	$group_ids   = $bb_tutorlms_groups['courses'];
	$action_type = 'bb_tutorlms_user_creates_lesson';
	$course_url  = "<a href='" . get_the_permalink( $course_id ) . "' target='_blank'>" . get_the_title( $course_id ) . "</a>";
	$lesson_url  = "<a href='" . get_the_permalink( $lesson_id ) . "' target='_blank'>" . get_the_title( $lesson_id ) . "</a>";

	if ( bp_is_active( 'activity' ) && bp_is_active( 'groups' ) && tutor_utils()->count( $group_ids ) ) {

		foreach ( $group_ids as $group_id ) {
			$activity_enable = bb_tutorlms_group_courses_is_enable( $group_id ) && bb_tutorlms_group_course_activity_enable( $group_id, 'bb_tutorlms_user_creates_lesson' );
			if (
				$activity_enable &&
				(
					current_user_can( 'administrator' ) ||
					groups_is_user_member( $user_id, $group_id )
				)
			) {
				do_action( 'bb_tutorlms_group_activity_creates_lesson_before', $action_type );

				$activity_args = apply_filters(
					'bb_tutorlms_group_activity_creates_lesson_args',
					array(
						'user_id'           => $user_id,
						'action'            => $action_type,
						'content'           => sprintf( __( 'A new lesson has been published for %s. Go check it out %s', 'buddyboss-pro' ), $course_url, $lesson_url ),
						'type'              => 'activity_update',
						'item_id'           => $group_id,
						'secondary_item_id' => $course_id,
					)
				);

				remove_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );
				$activity_id = groups_record_activity( $activity_args );
				add_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );

				do_action( 'bb_tutorlms_group_activity_creates_lesson_after', $action_type, $activity_id );
			}
		}
	}
}

/**
 * Function to add group activity record when lesson updated.
 *
 * @since 2.4.40
 *
 * @param int $lesson_id Lesson ID.
 *
 * @return void
 */
function bb_tutorlms_group_activity_lesson_update( $lesson_id ) {
	if ( empty( bp_loggedin_user_id() ) ) {
		return;
	}

	$course_id = tutor_utils()->get_course_id_by_content( $lesson_id );
	if ( empty( $course_id ) ) {
		return;
	}

	$bb_tutorlms_groups = bb_load_tutorlms_group()->get(
		array(
			'course_id' => $course_id,
			'fields'    => 'group_id',
		)
	);
	if ( empty( $bb_tutorlms_groups['courses'] ) ) {
		return;
	}

	$user_id     = bp_loggedin_user_id();
	$group_ids   = $bb_tutorlms_groups['courses'];
	$action_type = 'bb_tutorlms_user_updated_lesson';
	$lesson_url  = "<a href='" . get_the_permalink( $lesson_id ) . "' target='_blank'>" . get_the_title( $lesson_id ) . "</a>";

	if ( bp_is_active( 'activity' ) && bp_is_active( 'groups' ) && tutor_utils()->count( $group_ids ) ) {
		foreach ( $group_ids as $group_id ) {
			$activity_enable = bb_tutorlms_group_courses_is_enable( $group_id ) && bb_tutorlms_group_course_activity_enable( $group_id, 'bb_tutorlms_user_updated_lesson' );
			if (
				$activity_enable &&
				(
					current_user_can( 'administrator' ) ||
					groups_is_user_member( $user_id, $group_id )
				)
			) {
				do_action( 'bb_tutorlms_group_activity_lesson_update_before', $action_type );

				$activity_args = apply_filters(
					'bb_tutorlms_group_activity_lesson_update_args',
					array(
						'user_id'           => $user_id,
						'action'            => $action_type,
						'content'           => sprintf( __( 'I updated %s. See what\'s new!', 'buddyboss-pro' ), $lesson_url ),
						'type'              => 'activity_update',
						'item_id'           => $group_id,
						'secondary_item_id' => $course_id,
					)
				);

				remove_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );
				$activity_id = groups_record_activity( $activity_args );
				add_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );

				do_action( 'bb_tutorlms_group_activity_lesson_update_after', $action_type, $activity_id );
			}
		}
	}
}

/**
 * Function to add group activity record when start a quiz.
 *
 * @since 2.4.40
 *
 * @param int $quiz_id Quiz ID.
 * @param int $user_id User ID.
 *
 * @return void
 */
function bb_tutorlms_group_activity_quiz_start( $quiz_id, $user_id ) {
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	$course_id = tutor_utils()->get_course_id_by_content( $quiz_id );
	if ( empty( $course_id ) ) {
		return;
	}

	$bb_tutorlms_groups = bb_load_tutorlms_group()->get(
		array(
			'course_id' => $course_id,
			'fields'    => 'group_id',
		)
	);
	if ( empty( $bb_tutorlms_groups['courses'] ) ) {
		return;
	}

	$user_link   = bp_core_get_userlink( $user_id );
	$group_ids   = $bb_tutorlms_groups['courses'];
	$action_type = 'bb_tutorlms_user_started_quiz';
	$quiz_url    = "<a href='" . get_the_permalink( $quiz_id ) . "' target='_blank'>" . get_the_title( $quiz_id ) . "</a>";

	if ( bp_is_active( 'activity' ) && bp_is_active( 'groups' ) && tutor_utils()->count( $group_ids ) ) {
		foreach ( $group_ids as $group_id ) {
			$activity_enable = bb_tutorlms_group_courses_is_enable( $group_id ) && bb_tutorlms_group_course_activity_enable( $group_id, 'bb_tutorlms_user_started_quiz' );
			if ( $activity_enable && groups_is_user_member( $user_id, $group_id ) ) {
				do_action( 'bb_tutorlms_group_activity_quiz_start_before', $action_type );

				$activity_args = apply_filters(
					'bb_tutorlms_group_activity_quiz_start_args',
					array(
						'user_id'           => $user_id,
						'action'            => $action_type,
						'content'           => sprintf( __( '%s just started %s', 'buddyboss-pro' ), $user_link, $quiz_url ),
						'type'              => 'activity_update',
						'item_id'           => $group_id,
						'secondary_item_id' => $course_id,
					)
				);

				remove_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );
				$activity_id = groups_record_activity( $activity_args );
				add_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );

				do_action( 'bb_tutorlms_group_activity_quiz_start_after', $action_type, $activity_id );
			}
		}
	}
}

/**
 * Function to add group activity record when finished a quiz.
 *
 * @since 2.4.40
 *
 * @param int $quiz_id Quiz ID.
 * @param int $user_id User ID.
 *
 * @return void
 */
function bb_tutorlms_group_activity_quiz_finished( $quiz_id, $user_id ) {
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	$course_id = tutor_utils()->get_course_id_by_content( $quiz_id );
	if ( empty( $course_id ) ) {
		return;
	}

	$bb_tutorlms_groups = bb_load_tutorlms_group()->get(
		array(
			'course_id' => $course_id,
			'fields'    => 'group_id',
		)
	);
	if ( empty( $bb_tutorlms_groups['courses'] ) ) {
		return;
	}

	$user_link   = bp_core_get_userlink( $user_id );
	$group_ids   = $bb_tutorlms_groups['courses'];
	$action_type = 'bb_tutorlms_user_finished_quiz';
	$quiz_url    = "<a href='" . get_the_permalink( $quiz_id ) . "' target='_blank'>" . get_the_title( $quiz_id ) . "</a>";

	if ( bp_is_active( 'activity' ) && bp_is_active( 'groups' ) && tutor_utils()->count( $group_ids ) ) {
		foreach ( $group_ids as $group_id ) {
			$activity_enable = bb_tutorlms_group_courses_is_enable( $group_id ) && bb_tutorlms_group_course_activity_enable( $group_id, 'bb_tutorlms_user_finished_quiz' );
			if ( $activity_enable && groups_is_user_member( $user_id, $group_id ) ) {
				do_action( 'bb_tutorlms_group_activity_quiz_finished_before', $action_type );

				$activity_args = apply_filters(
					'bb_tutorlms_group_activity_quiz_finished_args',
					array(
						'user_id'           => $user_id,
						'action'            => $action_type,
						'content'           => sprintf( __( '%s just completed %s', 'buddyboss-pro' ), $user_link, $quiz_url ),
						'type'              => 'activity_update',
						'item_id'           => $group_id,
						'secondary_item_id' => $course_id,
					)
				);

				remove_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );
				$activity_id = groups_record_activity( $activity_args );
				add_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );

				do_action( 'bb_tutorlms_group_activity_quiz_finished_after', $action_type, $activity_id );
			}
		}
	}
}

/**
 * Function to add group activity record when attempt a quiz.
 *
 * @since 2.4.40
 *
 * @param int $attempt_id Attempt ID.
 *
 * @return void
 */
function bb_tutorlms_group_activity_quiz_attempt_ended( $attempt_id ) {
	if ( empty( $attempt_id ) ) {
		return;
	}

	$attempt = tutor_utils()->get_attempt( $attempt_id );
	if ( $attempt && isset( $attempt->quiz_id ) && isset( $attempt->user_id ) ) {
		bb_tutorlms_group_activity_quiz_finished( $attempt->quiz_id, $attempt->user_id );
	}
}
