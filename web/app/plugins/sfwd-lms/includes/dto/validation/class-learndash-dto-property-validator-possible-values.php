<?php
/**
 * This class provides the easy way to validate DTO properties.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_DTO_Property_Validator' ) ) {
	/**
	 * DTO property validator class.
	 *
	 * @since 4.5.0
	 */
	class Learndash_DTO_Property_Validator_Possible_Values implements Learndash_DTO_Property_Validator {
		/**
		 * Possible values.
		 *
		 * @since 4.5.0
		 *
		 * @var array<mixed>
		 */
		private $valid_values;

		/**
		 * Constructor.
		 *
		 * @since 4.5.0
		 *
		 * @param array<mixed> $valid_values Possible valid values.
		 *
		 * @return void
		 */
		public function __construct( array $valid_values ) {
			$this->valid_values = $valid_values;
		}

		/**
		 * Validates if a property value is one of the possible values.
		 *
		 * @since 4.5.0
		 *
		 * @param mixed $value Value.
		 *
		 * @return Learndash_DTO_Property_Validation_Result
		 */
		public function validate( $value ): Learndash_DTO_Property_Validation_Result {
			if ( ! in_array( $value, $this->valid_values, true ) ) {
				return Learndash_DTO_Property_Validation_Result::invalid(
					sprintf(
						'Value %s must be equal to one of the following: "%s".',
						wp_json_encode( $value ),
						implode( ', ', $this->valid_values )
					)
				);
			}

			return Learndash_DTO_Property_Validation_Result::valid();
		}
	}
}
