<?php
/**
 * LearnDash `[learndash_user_status]` shortcode processing.
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `learndash_user_status` shortcode output.
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type string $user_id User ID.
 *
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'learndash_user_status'.
 *
 * @return string The `learndash_user_status` shortcode output.
 */
function learndash_user_status_shortcode( $atts = array(), $content = '', $shortcode_slug = 'learndash_user_status' ) {
	if ( learndash_is_active_theme( 'legacy' ) ) {
		return $content;
	}

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

	if ( isset( $atts['user_id'] ) && ! empty( $atts['user_id'] ) ) {

		$user_id = intval( $atts['user_id'] );
		unset( $atts['user_id'] );

	} else {

		$current_user = wp_get_current_user();

		if ( empty( $current_user->ID ) ) {
			return '';
		}

		$user_id = $current_user->ID;

	}

	if ( empty( $atts ) ) {
		$atts = array( 'return' => true );
	} elseif ( ! isset( $atts['return'] ) ) {
		$atts['return'] = true;
	}

	$atts['isblock'] = true;

	$course_info = SFWD_LMS::get_course_info( $user_id, $atts );

	ob_start();

	SFWD_LMS::get_template(
		'shortcodes/user-status.php',
		array(
			'course_info'    => $course_info,
			'shortcode_atts' => $atts,
		),
		true
	);

	$content .= ob_get_clean();

	return $content;

}
add_shortcode( 'learndash_user_status', 'learndash_user_status_shortcode', 3 );
