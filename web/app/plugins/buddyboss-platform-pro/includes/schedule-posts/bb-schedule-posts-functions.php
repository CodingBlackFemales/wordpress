<?php
/**
 * Schedule post helper functions.
 *
 * @since   2.5.20
 *
 * @package BuddyBossPro
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Return the schedule posts path.
 *
 * @since 2.5.20
 *
 * @param string $path path of schedule posts.
 *
 * @return string path.
 */
function bb_schedule_posts_path( $path = '' ) {
	return trailingslashit( bb_platform_pro()->schedule_posts_dir ) . trim( $path, '/\\' );
}

/**
 * Return the schedule posts url.
 *
 * @since 2.5.20
 *
 * @param string $path url of schedule posts.
 *
 * @return string url.
 */
function bb_schedule_posts_url( $path = '' ) {
	return trailingslashit( bb_platform_pro()->schedule_posts_url ) . trim( $path, '/\\' );
}

/**
 * Check whether user can schedule activity or not.
 *
 * @since 2.5.20
 *
 * @param array $args Array of Arguments.
 *
 * @return bool true if user can post schedule posts, otherwise false.
 */
function bb_can_user_schedule_activity( $args = array() ) {
	if (
		! defined( 'BP_PLATFORM_VERSION' ) ||
		version_compare( BP_PLATFORM_VERSION, '2.6.10', '<' ) ||
		! bp_is_active( 'activity' ) ||
		! is_user_logged_in()
	) {
		return false;
	}

	$r = bp_parse_args(
		$args,
		array(
			'user_id'  => bp_loggedin_user_id(),
			'object'   => '',
			'group_id' => 0,
		)
	);

	$retval = false;
	if (
		bp_is_active( 'groups' ) &&
		(
			'group' === $r['object'] ||
			bp_is_group()
		)
	) {
		$group_id = 'group' === $r['object'] && ! empty( $r['group_id'] ) ? $r['group_id'] : bp_get_current_group_id();
		$is_admin = groups_is_user_admin( $r['user_id'], $group_id );
		$is_mod   = groups_is_user_mod( $r['user_id'], $group_id );
		if (
			bb_is_enabled_activity_schedule_posts_filter() &&
			( $is_admin || $is_mod )
		) {
			$retval = true;
		}
	} elseif ( bp_user_can( $r['user_id'], 'administrator' ) ) {
		$retval = true;
	}

	/**
	 * Filters whether user can schedule activity posts.
	 *
	 * @since 2.5.20
	 *
	 * @param bool  $retval Return value for schedule post.
	 * @param array $args   Array of Arguments.
	 */
	return apply_filters( 'bb_can_user_schedule_activity', $retval, $args );
}
