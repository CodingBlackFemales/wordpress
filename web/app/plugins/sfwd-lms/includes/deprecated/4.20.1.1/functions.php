<?php
/**
 * Deprecated functions from LD 4.20.1.1.
 * The functions will be removed in a later version.
 *
 * @since 4.20.1.1
 *
 * @package LearnDash\Deprecated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'learndash_prepare_quiz_resume_data_to_js' ) ) {
	/**
	 * Utility function to prepare Quiz Resume PHP array to JSON.
	 *
	 * @since 3.5.1.2
	 * @deprecated 4.20.1.1
	 * @uses `esc_js()`
	 *
	 * @param array $quiz_resume_data Quiz Resume array.
	 */
	function learndash_prepare_quiz_resume_data_to_js( $quiz_resume_data = array() ) {
		_deprecated_function( __FUNCTION__, '4.20.1.1' );

		if ( ! empty( $quiz_resume_data ) ) {
			foreach ( $quiz_resume_data as $key => &$set ) {
				if ( 'formData' === substr( $key, 0, strlen( 'formData' ) ) ) { // Handle the form fields.
					if ( ( isset( $set['type'] ) ) && ( in_array( $set['type'], array( WpProQuiz_Model_Form::FORM_TYPE_TEXT, WpProQuiz_Model_Form::FORM_TYPE_TEXTAREA ) ) ) ) {
						if ( ( isset( $set['value'] ) ) && ( is_string( $set['value'] ) ) && ( ! empty( $set['value'] ) ) ) {
							$set['value'] = esc_js( $set['value'] );
						}
					}
				} elseif ( isset( $set['type'] ) ) { // Handle the question fields.
					if ( ( isset( $set['value'] ) ) && ( ! empty( $set['value'] ) ) ) {
						if ( in_array( $set['type'], array( 'free_answer', 'essay', 'cloze_answer' ), true ) ) {
							if ( is_string( $set['value'] ) ) {
								$set['value'] = esc_js( $set['value'] );
							} elseif ( is_array( $set['value'] ) ) {
								foreach ( $set['value'] as $set_value_idx => &$set_value_value ) {
									if ( ( is_string( $set_value_value ) ) && ( ! empty( $set_value_value ) ) ) {
										$set_value_value = esc_js( $set_value_value );
									}
								}
							}
						}
					}
				} elseif ( 'checked' === substr( $key, 0, strlen( 'checked' ) ) ) {
					if ( ( isset( $set['e']['AnswerMessage'] ) ) && ( ! empty( $set['e']['AnswerMessage'] ) ) ) {
						if ( is_string( $set['e']['AnswerMessage'] ) ) {
							$set['e']['AnswerMessage'] = esc_js( $set['e']['AnswerMessage'] );
						}
					}
				}
			}
		}

		return $quiz_resume_data;
	}
}
