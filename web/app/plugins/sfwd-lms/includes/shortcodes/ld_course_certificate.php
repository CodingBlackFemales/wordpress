<?php
/**
 * LearnDash `[ld_course_certificate]` shortcode processing.
 *
 * @since 2.1.0
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[ld_course_certificate]` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 2.1.0
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int $course_id Course ID. Default 0.
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'ld_course_certificate'.
 *
 * @return string Shortcode output.
 */
function ld_course_certificate_shortcode( $atts = array(), $content = '', $shortcode_slug = 'ld_course_certificate' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

	$course_id = 0;
	if ( isset( $atts['course_id'] ) ) {
		$course_id = $atts['course_id'];
	}

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id();
	}

	$user_id = get_current_user_id();
	$link    = learndash_get_course_certificate_link( $course_id, $user_id );

	if ( empty( $link ) ) {
		return '';
	}

	/**
	 * Filters the output of course certificate shortcode.
	 *
	 * @since 2.1.0
	 *
	 * @param string $certificate_content Course certificate shortcode markup.
	 * @param string $link               Certificate Link.
	 * @param int    $course_id          Course ID.
	 * @param int    $user_id            User ID.
	 */
	return apply_filters( // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		'ld_course_certificate',
		"<div id='learndash_course_certificate'><a href='" . $link . "' class='btn-blue' target='_blank'>" . apply_filters( 'ld_certificate_link_label', esc_html__( 'PRINT YOUR CERTIFICATE', 'learndash' ), $user_id, $course_id ) . '</a></div>',
		$link,
		$course_id,
		$user_id
	);
}
add_shortcode( 'ld_course_certificate', 'ld_course_certificate_shortcode', 10, 3 );
