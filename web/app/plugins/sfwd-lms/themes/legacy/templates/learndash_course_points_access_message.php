<?php
/**
 * Displays the Course Points Access message
 *
 * Available Variables:
 * current_post : (WP_Post Object) Current Post object being display. Equal to global $post in most cases.
 * content_type : (string) Will contain the singlar lowercase common label 'course', 'lesson', 'topic', 'quiz'
 * course_access_points : (integer) Points required to access this course.
 * user_course_points : (integer) the user's current total course points.
 * course_settings : (array) Settings specific to current course
 *
 * @since 2.4.0
 *
 * @package LearnDash\Templates\Legacy\Course
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div id="learndash_course_points_access_message">
<?php
	echo sprintf(
	// translators: placeholders: (1) will be Course. (2) course_access_points. (3) user_course_points.
		esc_html_x(
			'To take this %1$s you need at least %2$.01f total points. You currently have %3$.01f points.',
			'placeholders: (1) will be Course. (2) course_access_points. (3) user_course_points',
			'learndash'
		),
		$content_type,
		$course_access_points,
		$user_course_points
	);

	echo '<br>';

	?>
	</div>
