<?php
/**
 * LearnDash Shortcodes Functions.
 *
 * @since 4.11.0
 *
 * @package LearnDash\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnDash\Core\Utilities\Cast;

/**
 * Checks if the current user can access the post.
 *
 * If the post ID is not set, the current user can access the post. It allows the shortcode's optional parameters to be skipped.
 * If the post is password protected, then only admins can access the post.
 * If the current user is a guest, the user can only access published posts.
 * If the current user is logged in, they can only access posts they have access to.
 *
 * @since 4.11.0
 *
 * @param int $post_id Post ID.
 *
 * @return bool
 */
function learndash_shortcode_can_current_user_access_post( int $post_id ): bool {
	/**
	 * If post ID is not set, assume the user can access it. It allows shortcode's optional parameters to be skipped.
	 *
	 * Some shortcodes have optional post IDs parameters, such as course_id, group_id, step_id, etc.
	 * This check allows us to pass the post IDs without checking if they're set.
	 *
	 * See includes/shortcodes/ld_course_content.php for example. Users can pass course_id, group_id, and post_id, but they're optional.
	 */

	if ( $post_id <= 0 ) {
		return true;
	}

	$current_user_id = get_current_user_id();

	// Admins can access any post.

	if ( learndash_is_admin_user( $current_user_id ) ) {
		return true;
	}

	// Protect password protected posts.

	if ( post_password_required( $post_id ) ) {
		return false;
	}

	// If guest user, check if the post is published.

	if ( $current_user_id <= 0 ) {
		return get_post_status( $post_id ) === 'publish';
	}

	// If logged in user, check if the user has access to the post.

	$post_type_object = get_post_type_object(
		Cast::to_string(
			get_post_type( $post_id )
		)
	);

	return (
		$post_type_object instanceof WP_Post_Type
		&& user_can( $current_user_id, $post_type_object->cap->read_post, $post_id )
	);
}

/**
 * Protects a user ID from being accessed by other users.
 *
 * @since 4.11.0
 *
 * If the current user is not logged in, then the user ID is set to 0.
 * If the current user is an admin, then they can access any user.
 * If the current user is a group leader, then they can only access users in their group.
 * If the current user is not an admin or group leader, then they can only access themselves.
 * Otherwise, the user ID is set to 0.
 *
 * @param int $user_id User ID.
 *
 * @return int The user ID.
 */
function learndash_shortcode_protect_user( int $user_id ): int {
	if ( ! is_user_logged_in() ) {
		return 0;
	}

	$current_user_id = get_current_user_id();

	// If the current user is an admin, then they can access any user.
	if ( learndash_is_admin_user( $current_user_id ) ) {
		return $user_id;
	}

	// If the current user is a group leader, then they can only access users in their group
	// or everyone if the advanced setting is enabled.
	if (
		learndash_is_group_leader_user( $current_user_id )
		&& (
			learndash_get_group_leader_manage_users() === 'advanced'
			|| learndash_is_group_leader_of_user( $current_user_id, $user_id )
		)
	) {
		return $user_id;
	}

	return $current_user_id === $user_id
		? $user_id
		: 0;
}
