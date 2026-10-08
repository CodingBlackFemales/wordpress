<?php
/**
 * Displays the course exam challenge admin details.
 *
 * @since 4.0.0
 *
 * @package LearnDash\Templates\Legacy\Course
 *
 * @param integer $user_id User ID currently displayed.
 * @param integer $course_id Course ID currently displayed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure the user has access to the course.
if ( ( ! empty( $course_id ) ) && ( ! empty( $user_id ) ) && ( sfwd_lms_has_access( $course_id, $user_id ) ) ) {
	$exam_id = learndash_get_course_exam_challenge( $course_id );
	if ( ! empty( $exam_id ) ) {

		$exam_activity     = learndash_get_user_course_exam_activity( $user_id, $course_id, $exam_id );
		$exam_status_slug  = learndash_grade_user_course_exam_activity( $exam_activity );
		$exam_status_label = learndash_course_exam_challenge_status_label( $exam_status_slug );

		$link_data     = htmlspecialchars(
			json_encode(
				array(
					'user_id'    => $user_id,
					'course_id'  => $course_id,
					'exam_id'    => $exam_id,
					'exam_nonce' => wp_create_nonce( 'learndash-exam_nonce-' . $user_id . '-' . $exam_id . '-' . $course_id ),
				),
				JSON_FORCE_OBJECT
			)
		);
		$exam_reset    = '<a href="#" class="learndash-course-exam-challenge-reset" id="learndash-course-exam-challenge-reset-' . absint( $course_id ) . '" data-exam="' . $link_data . '">' .
			esc_html_x( '(reset)', 'Reset Exam', 'learndash' ) . '</a>';
		$exam_complete = '<a href="#" class="learndash-course-exam-challenge-complete" id="learndash-course-exam-challenge-complete-' . absint( $course_id ) . '" data-exam="' . $link_data . '">' . esc_html_x( '(complete)', 'Complete Exam', 'learndash' ) . '</a>';

		if ( 'passed' === $exam_status_slug ) {
			$exam_date         = learndash_adjust_date_time_display( $exam_activity->activity_completed );
			$exam_status_label = '<span class="leardash-course-status leardash-course-status-completed">' . $exam_status_label . '</span>';

			$exam_complete = '';

		} elseif ( 'failed' === $exam_status_slug ) {
			$exam_date         = learndash_adjust_date_time_display( $exam_activity->activity_started );
			$exam_status_label = '<span style=" color: red;">' . $exam_status_label . '</span>';
		} else {
			$exam_date  = '';
			$exam_reset = '';
		}

		echo '<span class="learndash-course-exam-challenge-status learndash-course-exam-challenge-status-' . $exam_status_slug . '">';
		echo sprintf(
			// translators: placeholders: Exam label, Exam post title, Exam status, Exam reset link, Exam Complete link.
			esc_html_x( '%1$s : %2$s - Status: %3$s %4$s %5$s %6$s', 'placeholders: Exam label, Exam post title, Exam status, Exam reset link, Exam Complete link', 'learndash' ),
			esc_html( learndash_get_custom_label( 'exams' ) ),
			'<strong><a href="' . esc_url( get_permalink( $exam_id ) ) . '">' . wp_kses_post( get_the_title( $exam_id ) ) . '</a></strong>',
			$exam_status_label,
			$exam_date,
			$exam_reset,
			$exam_complete
		);

		echo '</span><br />';
		?>
		<?php
	}
}
