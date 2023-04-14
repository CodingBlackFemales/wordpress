<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LearnDash LD30 Displays the infobar in course context
 *
 * Will have access to same variables as course.php
 *
 * Available Variables:
 * $course_id                  : (int) ID of the course
 * $course                     : (object) Post object of the course
 * $course_settings            : (array) Settings specific to current course
 *
 * $courses_options            : Options/Settings as configured on Course Options page
 * $lessons_options            : Options/Settings as configured on Lessons Options page
 * $quizzes_options            : Options/Settings as configured on Quiz Options page
 *
 * $user_id                    : Current User ID
 * $logged_in                  : User is logged in
 * $current_user               : (object) Currently logged in user object
 *
 * $course_status              : Course Status
 * $has_access                 : User has access to course or is enrolled.
 * $materials                  : Course Materials
 * $has_course_content         : Course has course content
 * $lessons                    : Lessons Array
 * $quizzes                    : Quizzes Array
 * $lesson_progression_enabled : (true/false)
 * $has_topics                 : (true/false)
 * $lesson_topics              : (array) lessons topics
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30\Modules
 */

$course_pricing = learndash_get_course_price( $course_id );

if ( is_user_logged_in() && isset( $has_access ) && $has_access ) :
	?>

	<div class="ld-course-status ld-course-status-enrolled">

		<?php
		/**
		 * Fires inside the breadcrumbs (before).
		 *
		 * @since 3.0.0
		 *
		 * @param string|false $post_type Post type slug.
		 * @param int          $course_id Course ID.
		 * @param int          $user_id   User ID.
		 */
		do_action( 'learndash-course-infobar-access-progress-before', get_post_type(), $course_id, $user_id );

		learndash_get_template_part(
			'modules/progress.php',
			array(
				'context'   => 'course',
				'user_id'   => $user_id,
				'course_id' => $course_id,
			),
			true
		);

		/**
		 * Fires inside the breadcrumbs after the progress bar.
		 *
		 * @since 3.0.0
		 *
		 * @param string|false $post_type Post type slug.
		 * @param int          $course_id Course ID.
		 * @param int          $user_id   User ID.
		 */
		do_action( 'learndash-course-infobar-access-progress-after', get_post_type(), $course_id, $user_id );

		$course_status_string = learndash_course_status( $course_id, $user_id, true );
		if ( 'in-progress' === $course_status_string ) {
			$course_status_string = 'progress';
		}
		learndash_status_bubble( $course_status_string );

		/**
		 * Fires inside the breadcrumbs after the status.
		 *
		 * @since 3.0.0
		 *
		 * @param string|false $post_type Post type slug.
		 * @param int          $course_id Course ID.
		 * @param int          $user_id   User ID.
		 */
		do_action( 'learndash-course-infobar-access-status-after', get_post_type(), $course_id, $user_id );
		?>

	</div> <!--/.ld-course-status-->

	<?php
endif;
