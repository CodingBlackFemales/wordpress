<?php
/**
 * Let staff preview unpublished lessons, topics and quizzes.
 *
 * With nested course URLs turned on, LearnDash sends anyone who opens a
 * lesson, topic or quiz outside its course URL to the step's permalink within
 * its course, unless they're a LearnDash admin (manage_options). A Draft or
 * Pending step has no slug, so that permalink is the preview URL itself, and
 * Reviewers and Content Editors are redirected to the page they're on until
 * the browser reports too many redirects.
 *
 * WordPress only treats a request as a preview of an unpublished post when the
 * viewer can edit it, so this skips LearnDash's check for those requests.
 * Learners never reach a preview, so their course URL checks are unchanged.
 *
 * @package CBF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp',
	static function () {
		if ( ! function_exists( 'learndash_check_course_step' ) || ! is_preview() || ! is_singular( array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) {
			return;
		}

		if ( current_user_can( 'edit_post', get_queried_object_id() ) ) {
			remove_action( 'wp', 'learndash_check_course_step' );
		}
	},
	// Before LearnDash's check, which runs at the default priority.
	9
);
