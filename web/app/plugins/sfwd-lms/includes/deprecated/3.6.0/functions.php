<?php
/**
 * Deprecated functions from LD 3.6.0
 * The functions will be removed in a later version.
 *
 * @package LearnDash\Deprecated
 * @since 3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'get_ld_quiz_id' ) ) {
	/**
	 * Returns the Quiz ID when submitting the Pro Quiz ID
	 *
	 * @since 2.1.0
	 * @deprecated 3.6.0
	 *
	 * @param int $pro_quizid WPProQuiz ID.
	 */
	function get_ld_quiz_id( $pro_quizid ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.6.0' );
		}
	}
}

