<?php
/**
 * LearnDash `[ld_reset_password]` shortcode processing.
 *
 * @since 4.4.0
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Builds the `[ld_reset_password]` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 4.4.0
 *
 * @param array  $attr {
 *    An array of shortcode attributes.
 *
 *    @type string $width Width of the reset password form. Default empty.
 * }.
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'ld_reset_password'.
 *
 * @return string The `ld_reset_password` shortcode output.
 */
function learndash_reset_password( $attr = array(), $content = '', $shortcode_slug = 'ld_reset_password' ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;
	if ( ! is_array( $attr ) ) {
		$attr = array();
	}
	$attr = shortcode_atts(
		array(
			'width' => '',
		),
		$attr
	);
	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$attr = apply_filters( 'learndash_shortcode_atts', $attr, $shortcode_slug );

	$level = ob_get_level();

	ob_start();
	learndash_reset_password_output( $attr );
	$content .= learndash_ob_get_clean( $level );
	return $content;
}
add_shortcode( 'ld_reset_password', 'learndash_reset_password' );
