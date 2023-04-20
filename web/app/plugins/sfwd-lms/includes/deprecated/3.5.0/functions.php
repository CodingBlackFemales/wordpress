<?php
/**
 * Deprecated functions from LD 3.5.0
 * The functions will be removed in a later version.
 *
 * @package LearnDash\Deprecated
 * @since 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Other deprecated class functions.
 */
/**
 * In includes/lib/wp-pro-quiz/lib/view/WpProQuiz_View_FrontQuiz.php
 * WpProQuiz_View_FrontQuiz::fetchCloze();
 * WpProQuiz_View_FrontQuiz::clozeCallback();
 * WpProQuiz_View_FrontQuiz::fetchAssessment();
 * WpProQuiz_View_FrontQuiz::assessmentCallback();
 */

if ( ! function_exists( 'fetchQuestionCloze' ) ) {
	/**
	 * Formats the quiz cloze type answers into an array to be used when comparing responses.
	 *
	 * The function is copied from `WpProQuiz_View_FrontQuiz` class.
	 *
	 * @since 2.5.0
	 * @deprecated 3.5.0 Use {@see 'learndash_question_cloze_fetch_data'} instead.
	 *
	 * @param string  $answer_text      Answer text.
	 * @param boolean $convert_to_lower Optional. Whether to convert anwser to lowercase. Default true.
	 *
	 * @return array An array of cloze question data.
	 */
	function fetchQuestionCloze( $answer_text, $convert_to_lower = true ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.5.0', 'learndash_question_cloze_fetch_data' );
		}

		return learndash_question_cloze_fetch_data( $answer_text, $convert_to_lower );
	}
}
