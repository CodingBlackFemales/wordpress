<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.ValidVariableName,WordPress.NamingConventions.ValidFunctionName,WordPress.NamingConventions.ValidHookName,PSR2.Classes.PropertyDeclaration.Underscore
class WpProQuiz_Helper_Form {

	/**
	 *
	 * @param WpProQuiz_Model_Form $form
	 * @param mixed $data
	 *
	 * @return bool
	 */
	public static function valid( $form, $data ) {
		if ( is_string( $data ) ) {
			$data = trim( $data );
		}

		if ( $form->isRequired() && ( trim( $data ) == '' ) ) {
			return false;
		}

		switch ( $form->getType() ) {
			case WpProQuiz_Model_Form::FORM_TYPE_TEXT:
			case WpProQuiz_Model_Form::FORM_TYPE_TEXTAREA:
				return true;
			case WpProQuiz_Model_Form::FORM_TYPE_CHECKBOX:
				return empty( $data ) ? true : '1' == $data;
			case WpProQuiz_Model_Form::FORM_TYPE_EMAIL:
				return empty( $data ) ? true : filter_var( $data, FILTER_VALIDATE_EMAIL ) !== false;
			case WpProQuiz_Model_Form::FORM_TYPE_NUMBER:
				return empty( $data ) ? true : is_numeric( $data );
			case WpProQuiz_Model_Form::FORM_TYPE_RADIO:
			case WpProQuiz_Model_Form::FORM_TYPE_SELECT:
				return empty( $data ) ? true : in_array( $data, $form->getData() );
			case WpProQuiz_Model_Form::FORM_TYPE_YES_NO:
				return ( 0 !== $data && 1 !== $data && '0' !== $data && '1' !== $data ) ? true : ( 0 == $data || 1 == $data );
			case WpProQuiz_Model_Form::FORM_TYPE_DATE:
				return true;

		}
	}

	/**
	 *
	 * @param WpProQuiz_Model_Form $form
	 * @param array $data
	 */
	public static function validData( $form, $data ) {
		if ( $form->isRequired() && empty( $data ) ) {
			return null;
		}

		$check  = 0;
		$format = $data['day'] . '-' . $data['month'] . '-' . $data['year'];

		if ( $data['day'] > 0 && $data['day'] <= 31 ) {
			$check++;
		}

		if ( $data['month'] > 0 && $data['month'] <= 12 ) {
			$check++;
		}

		/** This filter is documented in includes/lib/wp-pro-quiz/lib/helper/WpProQuiz_Helper_Until.php */
		$date_year_min = (int) apply_filters( 'learndash_quiz_custom_field_year_min', 1900 );

		/** This filter is documented in includes/lib/wp-pro-quiz/lib/helper/WpProQuiz_Helper_Until.php */
		$date_year_max = (int) apply_filters( 'learndash_quiz_custom_field_year_max', date( 'Y' ) + 20 );

		if ( $data['year'] >= $date_year_min && $data['year'] <= $date_year_max ) {
			$check++;
		}

		if ( $form->isRequired() ) {
			if ( 3 == $check ) {
				return $format;
			}

			return null;
		}

		if ( 0 == $check ) {
			return '';
		}

		if ( 3 == $check ) {
			return $format;
		}

		return null;
	}
}
