<?php
/**
 * LearnDash LD30 Displays the Course Points Access message
 *
 * Available Variables:
 * current_post         : (WP_Post Object) Current Post object being display. Equal to global $post in most cases.
 * content_type         : (string) Will contain the singlar lowercase common label 'course', 'lesson', 'topic', 'quiz'
 * course_access_points : (integer) Points required to access this course.
 * user_course_points   : (integer) the user's current total course points.
 * course_settings      : (array) Settings specific to current course
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30\Modules
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<div class="learndash-wrapper">
	<?php
	$message = sprintf(
		// translators: placehodes: %1$s: Course, %2$s: course access points, %3$s: user course points.
		esc_html_x(
			'To take this %1$s you need at least %2$.01f total points. You currently have %3$.01f points.',
			'placeholders: (1) will be Course. (2) course_access_points. (3) user_course_points ',
			'learndash'
		),
		$content_type,
		$course_access_points,
		$user_course_points
	);

	learndash_get_template_part(
		'modules/alert.php',
		array(
			'type'    => 'warning',
			'icon'    => 'alert',
			'message' => $message,
		),
		true
	);

	?>
</div>
