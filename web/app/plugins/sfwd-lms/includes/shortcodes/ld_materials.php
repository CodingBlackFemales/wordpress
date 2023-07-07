<?php
/**
 * LearnDash `[ld_materials]` shortcode processing.
 *
 * @since 4.0.0
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[ld_materials]` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 4.0.0
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int $post_id ID of the post for displaying the materials.
 *
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'ld_materials'.
 *
 * @return string The `ld_materials` shortcode output.
 */
function learndash_materials_shortcode_function( $atts = array(), $content = '', $shortcode_slug = 'ld_materials' ) {
	if ( learndash_is_active_theme( 'legacy' ) ) {
		return $content;
	}

	global $learndash_shortcode_used;

	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	if ( ( ! isset( $atts['autop'] ) ) || ( true === $atts['autop'] ) || ( 'true' === $atts['autop'] ) || ( '1' === $atts['autop'] ) ) {
		$atts['autop'] = 'true';
	} else {
		$atts['autop'] = 'false';
	}

	$atts_defaults = array(
		'post_id' => '',
		'autop'   => 'true',
	);
	$atts          = shortcode_atts( $atts_defaults, $atts );

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

	if ( ! empty( $atts['post_id'] ) ) {
		$atts['post_id'] = absint( $atts['post_id'] );
	} else {
		$atts['post_id'] = absint( get_the_ID() );
	}

	$post = get_post( $atts['post_id'] );

	if ( in_array( $post->post_type, learndash_get_post_types(), true ) ) {
		$materials_out = '';

		$context   = learndash_get_post_type_key( $post->post_type );
		$materials = learndash_get_setting( $atts['post_id'] );
		if ( isset( $materials[ $context . '_materials_enabled' ] ) && 'on' === $materials[ $context . '_materials_enabled' ] ) {
			if ( ( isset( $materials[ $context . '_materials' ] ) ) && ( ! empty( $materials[ $context . '_materials' ] ) ) ) {
				$materials_out = wp_specialchars_decode( strval( $materials[ $context . '_materials' ] ), ENT_QUOTES );
				if ( 'true' === $atts['autop'] ) {
					$materials_out = wpautop( $materials_out );
				}
			}
		}

		if ( ! empty( $materials_out ) ) {
			$learndash_shortcode_used = true;

			$content .= '<div class="learndash-wrapper learndash-wrap learndash-shortcode-wrap">' . $materials_out . '</div>';
		}
	}

	return $content;
}
add_shortcode( 'ld_materials', 'learndash_materials_shortcode_function', 10, 3 );
