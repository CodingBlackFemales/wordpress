<?php
/**
 * This interface for a DTO property validator.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( 'Learndash_DTO_Property_Validator' ) ) {
	/**
	 * DTO property validator interface.
	 *
	 * @since 4.5.0
	 */
	interface Learndash_DTO_Property_Validator {
		/**
		 * Validates a property and returns a validation result.
		 *
		 * @since 4.5.0
		 *
		 * @param mixed $value Value to validate.
		 *
		 * @return Learndash_DTO_Property_Validation_Result
		 */
		public function validate( $value ): Learndash_DTO_Property_Validation_Result;
	}
}
