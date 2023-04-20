<?php
/**
 * Gutenberg Customization.
 *
 * Used to customize Gutenberg behavior.
 *
 * @since 3.0.0
 * @package LearnDash
 */

namespace LearnDash\Admin\Gutenberg;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Disables the Gutenberg editor on specific custom post types.
 *
 * Fires on `use_block_editor_for_post_type` and `gutenberg_can_edit_post_type` hook.
 *
 * @since 3.0.0
 *
 * @param boolean $is_enabled Whether the Gutenberg editor is enabled or not.
 * @param string  $post_type  Current post type slug.
 *
 * @return boolean Returns true to enable Gutenberg editor otherwise false.
 */
function disable_on_cpts( $is_enabled, $post_type ) {
	// Disable Gutenberg on the following CPTs.
	$disabled_cpts = array(
		'sfwd-question',
		'sfwd-certificates',
		'sfwd-essays',
		// 'groups',
	);

	if ( in_array( $post_type, $disabled_cpts, true ) ) {
		return false;
	}

	return $is_enabled;

}
add_filter( 'use_block_editor_for_post_type', '\LearnDash\Admin\Gutenberg\disable_on_cpts', 10, 2 );
add_filter( 'gutenberg_can_edit_post_type', '\LearnDash\Admin\Gutenberg\disable_on_cpts', 10, 2 );
