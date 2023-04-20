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
	class Learndash_DTO_Property_Validator_String_Case implements Learndash_DTO_Property_Validator {
		/**
		 * If true, the value must be in lowercase. Otherwise, it must be in uppercase.
		 *
		 * @since 4.5.0
		 *
		 * @var bool
		 */
		private $lowercase;

		/**
		 * Constructor. Prevents direct instantiation.
		 *
		 * @since 4.5.0
		 *
		 * @param bool $lowercase If true, the value must be in lowercase. Otherwise, it must be in uppercase.
		 *
		 * @return void
		 */
		protected function __construct( bool $lowercase ) {
			$this->lowercase = $lowercase;
		}

		/**
		 * Validates if a property value is in the required case.
		 *
		 * @since 4.5.0
		 *
		 * @param mixed $value Value.
		 *
		 * @return Learndash_DTO_Property_Validation_Result
		 */
		public function validate( $value ): Learndash_DTO_Property_Validation_Result {
			$casted_value = strval( $value );
			$is_valid     = $this->lowercase ? ctype_lower( $casted_value ) : ctype_upper( $casted_value );

			if ( ! $is_valid ) {
				return Learndash_DTO_Property_Validation_Result::invalid(
					sprintf(
						'Value %s must be in %s.',
						$casted_value,
						$this->lowercase ? 'lowercase' : 'uppercase'
					)
				);
			}

			return Learndash_DTO_Property_Validation_Result::valid();
		}

		/**
		 * Creates a lowercase validator.
		 *
		 * @since 4.5.0
		 *
		 * @return self
		 */
		public static function lowercase(): self {
			return new self( true );
		}

		/**
		 * Creates an uppercase validator.
		 *
		 * @since 4.5.0
		 *
		 * @return self
		 */
		public static function uppercase(): self {
			return new self( false );
		}
	}
}
