<?php
/**
 * LearnDash `[learndash_payment_buttons]` shortcode processing.
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[learndash_payment_buttons]` shortcode output.
 *
 * @since 2.1.0
 *
 * @global boolean $learndash_shortcode_used
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int $course_id Course ID. Default 0.
 * }
 * @param string $content        The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'learndash_payment_buttons'.
 *
 * @return string Returns the `learndash_payment_buttons` shortcode output.
 */
function learndash_payment_buttons_shortcode( $atts = array(), $content = '', $shortcode_slug = 'learndash_payment_buttons' ) {
	global $learndash_shortcode_used;

	$atts_defaults = array(
		'course_id' => '',
		'group_id'  => '',
	);

	$atts = shortcode_atts( $atts_defaults, $atts );

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

	if ( ( empty( $atts['course_id'] ) ) && ( empty( $atts['course_id'] ) ) ) {
		$viewed_post_id = (int) get_the_ID();
		if ( ! empty( $viewed_post_id ) ) {
			if ( in_array( get_post_type( $viewed_post_id ), learndash_get_post_types( 'course' ), true ) ) {
				$atts['course_id'] = learndash_get_course_id( $viewed_post_id );
			} elseif ( get_post_type( $viewed_post_id ) === learndash_get_post_type_slug( 'group' ) ) {
				$atts['group_id'] = $viewed_post_id;
			}
		}
	}

	$atts['group_id']  = absint( $atts['group_id'] );
	$atts['course_id'] = absint( $atts['course_id'] );

	$shortcode_out = '';

	if ( ! empty( $atts['course_id'] ) ) {
		$shortcode_out = learndash_payment_buttons( $atts['course_id'] );
	} elseif ( ! empty( $atts['group_id'] ) ) {
		$shortcode_out = learndash_payment_buttons( $atts['group_id'] );
	}

	if ( ! empty( $shortcode_out ) ) {
		$learndash_shortcode_used = true;

		$content .= '<div class="learndash-wrapper learndash-wrap learndash-shortcode-wrap">' . $shortcode_out . '</div>';
	}

	return $content;
}
add_shortcode( 'learndash_payment_buttons', 'learndash_payment_buttons_shortcode', 10, 3 );
