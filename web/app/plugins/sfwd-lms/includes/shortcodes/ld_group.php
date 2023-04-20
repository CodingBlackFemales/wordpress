<?php
/**
 * LearnDash `[ld_group]` shortcode processing.
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `[ld_group]` shortcode output.
 *
 * Shortcode to display content to users that have access to current group id.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 2.3.0
 *
 * @param array  $atts {
 *    An array of shortcode attributes.
 *
 *    @type int     $group_id Course ID.
 *    @type int     $user_id  User ID.
 *    @type string  $content  The shortcode content.
 *    @type boolean $autop    Whether to replace line breaks with paragraph elements.
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'ld_group'.
 *
 * @return string The `ld_group` shortcode output.
 */
function learndash_ld_group_check_shortcode( $atts = array(), $content = '', $shortcode_slug = 'ld_group' ) {
	global $learndash_shortcode_used;

	if ( ( is_singular() ) && ( ! is_null( $content ) ) && ( is_user_logged_in() ) ) {

		$defaults = array(
			'group_id' => 0,
			'user_id'  => get_current_user_id(),
			'content'  => $content,
			'autop'    => true,
		);
		$atts     = wp_parse_args( $atts, $defaults );

		$atts['user_id']  = absint( $atts['user_id'] );
		$atts['group_id'] = absint( $atts['group_id'] );

		if ( ( true === $atts['autop'] ) || ( 'true' === $atts['autop'] ) || ( '1' === $atts['autop'] ) ) {
			$atts['autop'] = true;
		} else {
			$atts['autop'] = false;
		}

		/** This filter is documented in includes/shortcodes/ld_course_resume.php */
		$atts = apply_filters( 'learndash_shortcode_atts', $atts, $shortcode_slug );

		/**
		 * Filters ld_group shortcode attributes.
		 *
		 * @param array  $attribute An array of ld_group shortcode attributes.
		 * @param string $content   Shortcode Content.
		 */
		$atts = apply_filters( 'learndash_ld_group_shortcode_atts', $atts, $content );

		if ( ( ! empty( $atts['content'] ) ) && ( ! empty( $atts['user_id'] ) ) && ( ! empty( $atts['group_id'] ) ) && ( get_current_user_id() == $atts['user_id'] ) ) {
			if ( learndash_is_user_in_group( $atts['user_id'], $atts['group_id'] ) ) {
				$learndash_shortcode_used = true;
				$atts['content']          = do_shortcode( $atts['content'] );
				return SFWD_LMS::get_template(
					'learndash_group_message',
					array(
						'shortcode_atts' => $atts,
					),
					false
				);
			}
		}
	}

	return '';
}
add_shortcode( 'ld_group', 'learndash_ld_group_check_shortcode', 10, 3 );
