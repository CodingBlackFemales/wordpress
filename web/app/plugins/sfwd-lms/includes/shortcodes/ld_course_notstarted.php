<?php
/**
 * LearnDash `[course_notstarted]` shortcode processing.
 *
 * @since 2.1.0
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[course_notstarted]` shortcode output.
 *
 * Shortcode that shows the content if the user has not started the course.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 2.1.0
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type string  $content   The shortcode content. Default empty.
 *    @type int     $course_id Course ID. Default false.
 *    @type int     $user_id   User ID. Default false.
 *    @type boolean $autop     Whether to replace line breaks with paragraph elements. Default true.
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'course_notstarted'.
 *
 * @return string The `course_notstarted` shortcode output.
 */
function learndash_course_notstarted_shortcode( $atts = array(), $content = '', $shortcode_slug = 'course_notstarted' ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	if ( ! empty( $content ) ) {

		if ( ! is_array( $atts ) ) {
			if ( ! empty( $atts ) ) {
				$atts = array( $atts );
			} else {
				$atts = array();
			}
		}

		$defaults = array(
			'content'   => $content,
			'course_id' => false,
			'user_id'   => false,
			'autop'     => true,
		);
		$atts     = wp_parse_args( $atts, $defaults );

		if ( ( true === $atts['autop'] ) || ( 'true' === $atts['autop'] ) || ( '1' === $atts['autop'] ) ) {
			$atts['autop'] = true;
		} else {
			$atts['autop'] = false;
		}

		/** This filter is documented in includes/shortcodes/ld_course_resume.php */
		$atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

		/**
		 * Filters `course_notstarted` shortcode attributes.
		 *
		 * @param array $attributes An array of course_notstarted shortcode attributes.
		 */
		$atts = apply_filters( 'learndash_course_notstarted_shortcode_atts', $atts );

		$atts['content'] = learndash_course_status_content_shortcode( $atts, $atts['content'], esc_html__( 'Not Started', 'learndash' ) );
		return SFWD_LMS::get_template(
			'learndash_course_not_started_message',
			array(
				'shortcode_atts' => $atts,
			),
			false
		);
	}
	return '';
}
add_shortcode( 'course_notstarted', 'learndash_course_notstarted_shortcode', 10, 3 );
