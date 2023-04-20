<?php
/**
 * Deprecated functions from LD 2.6.4
 * The functions will be removed in a later version.
 *
 * @package LearnDash\Deprecated
 * @since 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ld_course_access_update' ) ) {
	/**
	 * Updates the course access time for a user.
	 *
	 * @since 2.6.0
	 * @deprecated 2.6.0 Use {@see 'ld_course_access_from_update'} instead.
	 *
	 * @param int     $course_id Course ID for update.
	 * @param int     $user_id User ID for update.
	 * @param mixed   $access Optional. Value can be a date string (YYYY-MM-DD hh:mm:ss or integer value. Default empty.
	 * @param boolean $is_gmt Optional. If $access value is GMT (true) or relative to site timezone (false). Default false.
	 *
	 * @return boolean Returns true if success.
	 */
	function ld_course_access_update( $course_id, $user_id, $access = '', $is_gmt = false ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '2.6.0', 'ld_course_access_from_update()' );
		}

		return ld_course_access_from_update( $course_id, $user_id, $access, $is_gmt );
	}
}

if ( ( ! class_exists( 'Learndash_Admin_Settings_Data_Upgrades' ) ) && ( class_exists( 'Learndash_Admin_Data_Upgrades' ) ) ) {
	/**
	 * Deprecated Class for admin settings data upgrades
	 */
	class Learndash_Admin_Settings_Data_Upgrades {
		// phpcs:ignore Squiz.Commenting.FunctionComment
		public static function get_instance( $instance_key = '' ) {
			if ( function_exists( '_deprecated_function' ) ) {
				_deprecated_function( 'Learndash_Admin_Settings_Data_Upgrades::get_instance()', '2.6.0', 'Learndash_Admin_Data_Upgrades::get_instance()' );
			}

			return Learndash_Admin_Data_Upgrades::get_instance();
		}
	}
}
