<?php
/**
 * LearnDash LD30 Displays the lesson progression message
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $previous_item ) && $previous_item instanceof WP_Post ) {

	$alert = array(
		'icon'    => 'alert',
		'message' => '',
		'type'    => 'warning',
		'button'  => array(
			'url'           => learndash_get_step_permalink( $previous_item->ID, $course_id ),
			'class'         => 'learndash-link-previous-incomplete',
			'label'         => esc_html__( 'Back', 'learndash' ),
			'icon'          => 'arrow-left',
			'icon-location' => 'left',
		),
	);

	switch ( $previous_item->post_type ) {
		case ( 'sfwd-quiz' ):
			$alert['message'] = sprintf(
				// translators: placeholder: Quiz label.
				esc_html_x( 'Please go back and complete the previous %s.', 'placeholders: Quiz label', 'learndash' ),
				esc_html( learndash_get_custom_label_lower( 'quiz' ) )
			);
			break;
		case ( 'sfwd-topic' ):
			if ( ( isset( $sub_context ) ) && ( 'video_progression' === $sub_context ) ) {
				$alert['message'] = sprintf(
					// translators: placeholder: Topic label.
					esc_html_x( 'Please go back and watch the video for the previous %s.', 'placeholders: topic label', 'learndash' ),
					esc_html( learndash_get_custom_label_lower( 'topic' ) )
				);
			} else {
				$alert['message'] = sprintf(
					// translators: placeholder: Topic label.
					esc_html_x( 'Please go back and complete the previous %s.', 'placeholders: topic label', 'learndash' ),
					esc_html( learndash_get_custom_label_lower( 'topic' ) )
				);
			}
			break;
		default:
			if ( ( isset( $sub_context ) ) && ( 'video_progression' === $sub_context ) ) {
				$alert['message'] = sprintf(
					// translators: placeholder: Lesson label.
					esc_html_x( 'Please go back and watch the video for the previous %s.', 'placeholders: lesson label', 'learndash' ),
					esc_html( learndash_get_custom_label_lower( 'lesson' ) )
				);
			} else {
				$alert['message'] = sprintf(
					// translators: placeholder: Lesson Label.
					esc_html_x( 'Please go back and complete the previous %s.', 'placeholders: lesson label', 'learndash' ),
					esc_html( learndash_get_custom_label_lower( 'lesson' ) )
				);
			}
			break;
	}
} else {

	$alert['message'] = sprintf(
		// translators: placeholder: Lesson.
		esc_html_x( 'Please go back and complete the previous %s.', 'placeholders lesson', 'learndash' ),
		esc_html( learndash_get_custom_label_lower( 'lesson' ) )
	);

}

/**
 * Filters the progress alert arguments.
 *
 * The dynamic portion of the hook name, `$context`, refers to the context of progress,
 * such as `course`, `lesson`, `topic`, `quiz`, etc.
 *
 * @since 3.0.0
 *
 * @param array $alert An array of Progress alert arguments.
 */
$alert = apply_filters( 'learndash_' . $context . '_progress_alert', $alert, get_the_ID(), $course_id );

/**
 * Fires before the lesson progression alert.
 *
 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
 * such as `course`, `lesson`, `topic`, `quiz`, etc.
 *
 * @since 3.0.0
 *
 * @param int|false $post_id   Post ID.
 * @param int       $course_id Course ID.
 */
do_action( 'learndash-' . $context . '-progession-alert-before', get_the_ID(), $course_id ); // cspell:disable-line.

learndash_get_template_part( 'modules/alert.php', $alert, true );

/**
 * Fires after the lesson progression alert.
 *
 * The dynamic portion of the hook name, `$context`, refers to the context for which the hook is fired,
 * such as `course`, `lesson`, `topic`, `quiz`, etc.
 *
 * @since 3.0.0
 *
 * @param int|false $post_id   Post ID.
 * @param int       $course_id Course ID.
 */
do_action( 'learndash-' . $context . '-progession-alert-after', get_the_ID(), $course_id ); // cspell:disable-line.
