<?php
/**
 * LearnDash `[learndash_course_progress]` shortcode processing.
 *
 * @since 2.1.0
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[learndash_course_progress]` shortcode output.
 *
 * @since 2.1.0
 *
 * @global boolean $learndash_shortcode_used
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int     $course_id Course ID. Default 0.
 *    @type int     $user_id   User ID. Default 0.
 *    @type boolean $array     Whether to return array. Default false.
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'learndash_course_progress'.
 *
 * @return string|array The `learndash_course_progress` shortcode output.
 */
function learndash_course_progress( $atts = array(), $content = '', $shortcode_slug = 'learndash_course_progress' ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	$atts = shortcode_atts(
		array(
			'course_id' => 0,
			'user_id'   => 0,
			'array'     => false,
		),
		$atts
	);

	if ( empty( $atts['user_id'] ) ) {
		if ( is_user_logged_in() ) {
			$atts['user_id'] = get_current_user_id();
		}
	}

	if ( empty( $atts['course_id'] ) ) {
		$atts['course_id'] = learndash_get_course_id();
	}

	if ( ( empty( $atts['user_id'] ) ) || ( empty( $atts['course_id'] ) ) ) {
		if ( $atts['array'] ) {
			return array(
				'percentage' => 0,
				'completed'  => 0,
				'total'      => 0,
			);
		} else {
			return '';
		}
	}

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

	$completed  = 0;
	$total      = 0;
	$percentage = 0;

	$course_progress = learndash_user_get_course_progress( $atts['user_id'], $atts['course_id'] );

	if ( isset( $course_progress['completed'] ) ) {
		$completed = absint( $course_progress['completed'] );
	}

	if ( isset( $course_progress['total'] ) ) {
		$total = absint( $course_progress['total'] );
	}

	if ( ( isset( $course_progress['status'] ) ) && ( 'completed' === $course_progress['status'] ) ) {
		$completed = $total;
	}

	if ( $total > 0 ) {
		$percentage = intval( $completed * 100 / $total );
		$percentage = ( $percentage > 100 ) ? 100 : $percentage;
	}

	// translators: placeholders: completed steps, total steps.
	$message = sprintf( esc_html_x( '%1$d out of %2$d steps completed', 'placeholders: completed steps, total steps', 'learndash' ), $completed, $total );

	if ( $atts['array'] ) {
		return array(
			'percentage' => $percentage,
			'completed'  => $completed,
			'total'      => $total,
		);
	}

	return SFWD_LMS::get_template(
		'course_progress_widget',
		array(
			'user_id'    => $atts['user_id'],
			'course_id'  => $atts['course_id'],
			'message'    => $message,
			'percentage' => $percentage,
			'completed'  => $completed,
			'total'      => $total,
		)
	);
}
add_shortcode( 'learndash_course_progress', 'learndash_course_progress', 10, 3 );
