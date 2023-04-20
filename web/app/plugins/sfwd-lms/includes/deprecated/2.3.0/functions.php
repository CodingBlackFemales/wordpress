<?php
/**
 * Deprecated functions from LD 2.3.0
 * The functions will be removed in a later version.
 *
 * @package LearnDash\Deprecated
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'is_group_leader' ) ) {
	/**
	 * Checks if a user is a group leader
	 *
	 * @since 2.1.0
	 *
	 * @deprecated 2.3.0 Use {@see 'learndash_is_group_leader_user'} instead.
	 *
	 * @param int|WP_User $user `WP_User` instance or user ID.
	 *
	 * @return boolean
	 */
	function is_group_leader( $user ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '2.3.0', 'learndash_is_group_leader_user()' );
		}

		return learndash_is_group_leader_user( $user );
	}
}
