<?php
/**
 * Deprecated functions from LD 3.1.7
 * The functions will be removed in a later version.
 *
 * @package LearnDash\Deprecated
 * @since 3.1.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'learndash_user_can_bypass_course_limits' ) ) {
	/**
	 * LearnDash user can bypass course limits
	 *
	 * @deprecated 3.1.7 Use {@see 'learndash_can_user_bypass'} instead.
	 *
	 * @param int $user_id User ID.
	 */
	function learndash_user_can_bypass_course_limits( $user_id = null ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.0', 'learndash_can_user_bypass' );
		}

		return learndash_can_user_bypass( $user_id );
	}
}

if ( ! function_exists( 'is_course_prerequities_completed' ) ) {
	/**
	 * Is course prerequities completed
	 *
	 * @deprecated 3.1.7 Use {@see 'learndash_is_course_prerequities_completed'} instead.
	 *
	 * @param int $course_id Course ID.
	 */
	function is_course_prerequities_completed( $course_id = null ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.2.0', 'learndash_is_course_prerequities_completed' );
		}

		return learndash_is_course_prerequities_completed( $course_id );
	}
}
