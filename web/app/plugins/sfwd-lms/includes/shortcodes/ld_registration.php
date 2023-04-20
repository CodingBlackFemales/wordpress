<?php
/**
 * LearnDash `[ld_registration]` shortcode processing.
 *
 * @since 3.6.0
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[ld_registration]` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 3.6.0
 *
 * @param array  $attr {
 *    An array of shortcode attributes.
 *
 *    @type string $width Width of the registration form. Default empty.
 * }.
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'ld_registration'.
 *
 * @return string The `ld_registration` shortcode output.
 */
function learndash_registration( $attr = array(), $content = '', $shortcode_slug = 'ld_registration' ) {

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

	learndash_registration_output( $attr );

	$content .= learndash_ob_get_clean( $level );
	return $content;

}

add_shortcode( 'ld_registration', 'learndash_registration', 10, 3 );
