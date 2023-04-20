<?php
/**
 * LearnDash LD30 Displays the Course Lesson Not Available message
 *
 * Available Variables:
 * user_id                    : (integer) The user_id whose points to show
 * course_id                  : (integer) The ID of the couse shown
 * lesson_id                  : (integer) The ID of the lesson/topic/quiz not available
 * ld_lesson_access_from_int  : (integer) timestamp when lesson will become available
 * ld_lesson_access_from_date : (string) Formatted human readable date/time of ld_lesson_access_from_int
 * context                    : (string) The context will be set based on where this message is shown. course, lesson, loop, etc.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// First generate the message.
$message = sprintf(
	wp_kses_post(
		// translators: Date when content will be available.
		__( '<span class="ld-display-label">Available on:</span> <span class="ld-display-date">%s</span>', 'learndash' )
	),
	esc_html( learndash_adjust_date_time_display( $lesson_access_from_int ) )
);

$button = false;

// The figure out how to display it.
if ( ( 'lesson' === $context ) || ( 'topic' === $context ) || ( 'quiz' === $context ) ) {

	if ( ( ! isset( $course_id ) ) || ( empty( $course_id ) ) ) {
		$course_id = learndash_get_course_id( $lesson_id );
	}
	if ( ! empty( $course_id ) ) {
		$button = array(
			'url'           => get_permalink( $course_id ),
			'label'         => learndash_get_label_course_step_back( learndash_get_post_type_slug( 'course' ) ),
			'icon'          => 'arrow-left',
			'icon-location' => 'left',
		);
	}
}
?>

<div class="learndash-wrapper">
	<?php
	learndash_get_template_part(
		'modules/alert.php',
		array(
			'type'    => 'info',
			'icon'    => 'calendar',
			'button'  => $button,
			/**
			 * Filters the message markup for when the lesson will be available.
			 *
			 * @since 2.2.1
			 *
			 * @param string $message Markup for lesson available message.
			 * @param object $lesson  Lesson Object.
			 * @param int    $name    The timestamp when the lesson will become available.
			 */
			'message' => apply_filters( 'learndash_lesson_available_from_text', $message, get_post( $lesson_id ), $lesson_access_from_int ),
		),
		true
	);
	?>
</div>
