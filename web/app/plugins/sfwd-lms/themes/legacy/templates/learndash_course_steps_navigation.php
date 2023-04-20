<?php
/**
 * Displays a Course Prev/Next navigation.
 *
 * Available Variables:
 *
 * $course_id       : (int) ID of Course
 * $course_step_post : (int) ID of the lesson/topic post
 * $user_id         : (int) ID of User
 * $course_settings : (array) Settings specific to current course
 *
 * @since 2.5.8
 *
 * @package LearnDash\Templates\Legacy\Course
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$learndash_previous_nav = learndash_previous_post_link();
$learndash_next_nav     = '';

/*
 * See details for filter 'learndash_show_next_link' https://developers.learndash.com/hook/learndash_show_next_link
 *
 * @since version 2.3
 */

$current_complete = false;

if ( ( isset( $course_settings['course_disable_lesson_progression'] ) ) && ( $course_settings['course_disable_lesson_progression'] === 'on' ) ) {
	$current_complete = true;
} else {

	if ( $course_step_post->post_type == 'sfwd-topic' ) {
		$current_complete = learndash_is_topic_complete( $user_id, $course_step_post->ID, $course_id );
	} elseif ( $course_step_post->post_type == 'sfwd-lessons' ) {
		$current_complete = learndash_is_lesson_complete( $user_id, $course_step_post->ID, $course_id );
	}

	if ( $current_complete !== true ) {
		$bypass_course_limits_admin_users = learndash_can_user_bypass( $user_id, 'learndash_course_progression', $course_step_post->ID );
		if ( true === $bypass_course_limits_admin_users ) {
			$current_complete = true;
		}
	}
}

/** This filter is documented in themes/ld30/templates/modules/course-steps.php */
if ( apply_filters( 'learndash_show_next_link', $current_complete, $user_id, $course_step_post->ID ) ) {
	 $learndash_next_nav = learndash_next_post_link();
}

if ( ( ! empty( $learndash_previous_nav ) ) || ( ! empty( $learndash_next_nav ) ) ) {
	?><p id="learndash_next_prev_link"><?php echo $learndash_previous_nav; ?> <?php echo $learndash_next_nav; ?></p>
	<?php
}
