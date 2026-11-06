<?php
/**
 * Hide Jetpack's Subscriptions panel from content staff.
 *
 * Jetpack's Subscriptions extension adds a newsletter panel to the block
 * editor, which requests /wpcom/v2/memberships/status as soon as the editor
 * opens. That endpoint needs edit_posts, which Content Editors and Reviewers
 * don't have, so every editor screen shows a "Sorry, you are not allowed to
 * do that" snackbar. The newsletter also needs a WordPress.com connection,
 * which this site doesn't have, so the panel does nothing for anyone.
 *
 * This removes the extension in the admin for anyone who can edit LearnDash
 * courses (edit_courses) but not posts (edit_posts). Jetpack only registers
 * an editor panel or block when its extension is listed, and front-end
 * rendering is left unchanged.
 *
 * @package CBF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'jetpack_set_available_extensions',
	static function ( $extensions ) {
		if ( ! is_array( $extensions ) || ! is_admin() || ! current_user_can( 'edit_courses' ) || current_user_can( 'edit_posts' ) ) {
			return $extensions;
		}

		return array_values( array_diff( $extensions, array( 'subscriptions' ) ) );
	}
);
