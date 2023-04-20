<?php
/**
 * Deprecated functions from LD 2.5.0
 * The functions will be removed in a later version.
 *
 * @package LearnDash\Deprecated
 * @since 2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'leandash_redirect_post_location' ) ) {
	/**
	 * Used when editing Lesson, Topic, Quiz or Question post items. This filter is needed to add
	 * the 'course_id' parameter back to the edit URL after the post is submitted (saved).
	 *
	 * @since 2.5.0
	 * @deprecated 2.5.0 Use {@see 'learndash_redirect_post_location'} instead.
	 *
	 * @param string $location Optional. Location.  Default empty.
	 */
	function leandash_redirect_post_location( $location = '' ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '2.5.0', 'learndash_redirect_post_location()' );
		}

		return learndash_redirect_post_location( $location );
	}
}
