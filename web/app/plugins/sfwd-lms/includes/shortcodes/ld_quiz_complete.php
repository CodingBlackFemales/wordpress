<?php
/**
 * LearnDash `[ld_quiz_complete]` shortcode processing.
 *
 * @since 3.1.4
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[ld_quiz_complete]` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 3.1.4
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int $course_id Course ID. Default 0.
 *    @type int $quiz_id   Quiz ID. Default 0.
 *    @type int $user_id   User ID. Default current user ID.
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'ld_quiz_complete'.
 *
 * @return string The `ld_quiz_complete` shortcode output.
 */
function ld_quiz_complete_shortcode( $atts = array(), $content = '', $shortcode_slug = 'ld_quiz_complete' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	global $learndash_shortcode_used;

	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$defaults = array(
		'course_id' => 0,
		'quiz_id'   => 0,
		'user_id'   => get_current_user_id(),
	);
	$atts     = shortcode_atts( $defaults, $atts );

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

	$atts['course_id'] = absint( $atts['course_id'] );
	$atts['quiz_id']   = absint( $atts['quiz_id'] );
	$atts['user_id']   = absint( $atts['user_id'] );

	if ( empty( $atts['course_id'] ) ) {
		$atts['course_id'] = learndash_get_course_id();
	}
	if ( empty( $atts['quiz_id'] ) ) {
		$atts['quiz_id'] = learndash_get_quiz_id();
	}

	$learndash_shortcode_used = true;
	if ( ( ! empty( $atts['quiz_id'] ) ) && ( ! empty( $atts['user_id'] ) ) && ( get_current_user_id() === $atts['user_id'] ) ) {
		if ( learndash_is_quiz_complete( $atts['user_id'], $atts['quiz_id'], $atts['course_id'] ) ) {
			$content = do_shortcode( $content );
		} else {
			$content = '';
		}
	} else {
		$content = '';
	}

	return $content;
}
add_shortcode( 'ld_quiz_complete', 'ld_quiz_complete_shortcode', 10, 3 );
