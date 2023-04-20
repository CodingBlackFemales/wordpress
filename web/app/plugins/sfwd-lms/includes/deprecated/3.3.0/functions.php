<?php
/**
 * Deprecated functions from LD 3.3.0
 * The functions will be removed in a later version.
 *
 * @package LearnDash\Deprecated
 * @since 3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'is_data_upgrade_quiz_questions_updated' ) ) {
	/**
	 * Utility function to check if the data upgrade for Quiz Questions has been run.
	 *
	 * @deprecated 3.3.0 Use {@see 'learndash_is_data_upgrade_quiz_questions_updated'} instead.
	 */
	function is_data_upgrade_quiz_questions_updated() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.3.0', 'learndash_is_data_upgrade_quiz_questions_updated' );
		}

		return learndash_is_data_upgrade_quiz_questions_updated();
	}
}


if ( ! function_exists( 'leandash_get_question_pro_fields' ) ) {
	/**
	 * Gets the `WPProQuiz` Question row column fields.
	 *
	 * @deprecated 3.3.0 Use {@see 'learndash_get_question_pro_fields'} instead.
	 *
	 * @param int          $question_pro_id Optional. The `WPProQuiz` Question ID. Default 0.
	 * @param string|array $fields          Optional. An array or comma delimited string of fields to return. Default null.
	 */
	function leandash_get_question_pro_fields( $question_pro_id = 0, $fields = null ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.3.0', 'learndash_get_question_pro_fields' );
		}

		return learndash_get_question_pro_fields( $question_pro_id, $fields );
	}
}

if ( ! function_exists( 'leandash_get_quiz_pro_fields' ) ) {
	/**
	 * Gets the `WPProQuiz` Quiz row column fields.
	 *
	 * @deprecated 3.3.0 Use {@see 'learndash_get_question_pro_fields'} instead.
	 *
	 * @param int          $quiz_pro_id Optional. The `WPProQuiz` Question ID. Default 0.
	 * @param string|array $fields       Optional. An array or comma delimited string of fields to return. Default null.
	 */
	function leandash_get_quiz_pro_fields( $quiz_pro_id = 0, $fields = null ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.3.0', 'learndash_get_quiz_pro_fields' );
		}

		return learndash_get_quiz_pro_fields( $quiz_pro_id, $fields );
	}
}


if ( ! function_exists( 'leardash_min_asset' ) ) {
	/**
	 * Utility function to load minified version of CSS/JS assets.
	 *
	 * @deprecated 3.3.0 Use {@see 'learndash_min_asset'} instead.
	 */
	function leardash_min_asset() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.3.0', 'learndash_min_asset' );
		}

		return learndash_min_asset();
	}
}

if ( ! function_exists( 'leardash_min_builder_asset' ) ) {
	/**
	 * Utility function to load minified version of CSS/JS builder assets.
	 *
	 * @deprecated 3.3.0 Use {@see 'learndash_min_builder_asset'} instead.
	 */
	function leardash_min_builder_asset() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.3.0', 'learndash_min_builder_asset' );
		}

		return learndash_min_builder_asset();
	}
}
