<?php
/**
 * LearnDash `[user_groups]` shortcode processing.
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
 * Builds the `[user_groups]` shortcode output.
 *
 * @since 2.1.0
 *
 * @global boolean $learndash_shortcode_used
 *
 * @param array  $attr {
 *    An array of shortcode attributes.
 *
 *    @type int $user_id User ID. Default to current user ID.
 * }
 * @param string $content The shortcode content. Default empty.
 * @param string $shortcode_slug The shortcode slug. Default 'user_groups'.
 *
 * @return string The output for `user_groups` shortcode.
 */
function learndash_user_groups( $attr = array(), $content = '', $shortcode_slug = 'user_groups' ) {

	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	$shortcode_atts = shortcode_atts(
		array(
			'user_id' => '',
		),
		$attr
	);

	/** This filter is documented in includes/shortcodes/ld_course_resume.php */
	$shortcode_atts = apply_filters( 'learndash_shortcode_atts', $shortcode_atts, $shortcode_slug );

	if ( ! empty( $shortcode_atts['user_id'] ) ) {
		// Override the user ID if the current user can't access the passed user ID's data.
		$shortcode_atts['user_id'] = learndash_shortcode_protect_user( Cast::to_int( $shortcode_atts['user_id'] ) );
	} else {
		$shortcode_atts['user_id'] = get_current_user_id();
	}

	if ( $shortcode_atts['user_id'] <= 0 ) {
		return '';
	}

	$admin_groups     = learndash_get_administrators_group_ids( $shortcode_atts['user_id'] );
	$user_groups      = learndash_get_users_group_ids( $shortcode_atts['user_id'] );
	$has_admin_groups = ! empty( $admin_groups ) && is_array( $admin_groups ) && ! empty( $admin_groups[0] );
	$has_user_groups  = ! empty( $user_groups ) && is_array( $user_groups ) && ! empty( $user_groups[0] );

	if ( ! $has_admin_groups && ! $has_user_groups ) {
		return '';
	}

	return SFWD_LMS::get_template(
		'user_groups_shortcode',
		array(
			'admin_groups'     => $admin_groups,
			'user_groups'      => $user_groups,
			'has_admin_groups' => $has_admin_groups,
			'has_user_groups'  => $has_user_groups,
		)
	);
}
add_shortcode( 'user_groups', 'learndash_user_groups', 10, 3 );
