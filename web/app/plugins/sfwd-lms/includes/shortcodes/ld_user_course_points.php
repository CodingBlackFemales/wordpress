<?php
/**
 * LearnDash `[ld_user_course_points]` shortcode processing.
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnDash\Core\Utilities\Cast;

/**
 * Builds the `[ld_user_course_points]` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int    $user_id User ID. Default to current user ID.
 *    @type string $context The shortcode context. Default empty.
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'ld_user_course_points'.
 *
 * @return void|string The `ld_user_course_points` shortcode output.
 */
function learndash_user_course_points_shortcode( $atts = array(), $content = '', $shortcode_slug = 'ld_user_course_points' ) {
	global $learndash_shortcode_used;

	$defaults = array(
		'user_id' => get_current_user_id(),
		'context' => 'ld_user_course_points',
	);
	$atts     = wp_parse_args( $atts, $defaults );

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

	// Override the user ID if the current user can't access the passed user ID's data.
	$atts['user_id'] = learndash_shortcode_protect_user( Cast::to_int( $atts['user_id'] ) );

	if ( $atts['user_id'] <= 0 ) {
		return;
	}

	$learndash_shortcode_used = true;

	$user_course_points = learndash_get_user_course_points( $atts['user_id'] );

	$content = SFWD_LMS::get_template(
		'learndash_course_points_user_message',
		array(
			'user_course_points' => $user_course_points,
			'user_id'            => $atts['user_id'],
			'shortcode_atts'     => $atts,
		),
		false
	);
	return $content;
}
add_shortcode( 'ld_user_course_points', 'learndash_user_course_points_shortcode', 10, 3 );
