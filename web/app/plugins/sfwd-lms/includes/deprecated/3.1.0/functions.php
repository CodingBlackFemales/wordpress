<?php
/**
 * Deprecated functions from LD 3.1.0
 * The functions will be removed in a later version.
 *
 * @package LearnDash\Deprecated
 * @since 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'learndash_get_valid_transient' ) ) {
	/**
	 * Gets the valid transient.
	 *
	 * @deprecated 3.1.0 Use {@see 'LDLMS_Transients::get'} instead.
	 *
	 * @param string $transient_key Optional. Transient key. Default empty.
	 *
	 * @return mixed
	 */
	function learndash_get_valid_transient( $transient_key = '' ) {
		// if ( function_exists( '_deprecated_function' ) ) {
		// _deprecated_function( __FUNCTION__, '3.1', 'LDLMS_Transients::get' );
		// }

		return LDLMS_Transients::get( $transient_key );
	}
}

if ( ! function_exists( 'learndash_set_transient' ) ) {

	/**
	 * Sets the transient data.
	 *
	 * @deprecated 3.1.0 Use {@see 'LDLMS_Transients::set'} instead.
	 *
	 * @param string $transient_key    Optional. Transient key. Default empty.
	 * @param string $transient_data   Optional. Transient data. Default empty.
	 * @param int    $transient_expire Optional. Transient expiry time in seconds. Default 60.
	 *
	 * @return boolean
	 */
	function learndash_set_transient( $transient_key = '', $transient_data = '', $transient_expire = MINUTE_IN_SECONDS ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.1', 'LDLMS_Transients::set()' );
		}

		return LDLMS_Transients::set( $transient_key, $transient_data, $transient_expire );
	}
}

if ( ! function_exists( 'learndash_purge_transients' ) ) {
	/**
	 * Purges all the transients.
	 *
	 * @deprecated 3.1.0 Use {@see 'LDLMS_Transients::purge_all'} instead.
	 */
	function learndash_purge_transients() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.1', 'LDLMS_Transients::purge_all()' );
		}

		return LDLMS_Transients::purge_all();
	}
}
