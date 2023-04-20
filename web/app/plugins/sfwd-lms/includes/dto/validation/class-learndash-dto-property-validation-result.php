<?php
/**
 * The object to return the result of a DTO validation.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_DTO_Property_Validation_Result' ) ) {
	/**
	 * DTO property validation result.
	 *
	 * @since 4.5.0
	 */
	class Learndash_DTO_Property_Validation_Result {
		/**
		 * True if validation passed, false otherwise.
		 *
		 * @since 4.5.0
		 *
		 * @var bool
		 */
		private $is_valid;

		/**
		 * Error message if validation is not passed, an empty string otherwise.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		private $message;

		/**
		 * Constructor. Overriding the constructor in child classes and direct instantiating is disallowed.
		 *
		 * @since 4.5.0
		 *
		 * @param bool   $is_valid Is validation passed.
		 * @param string $message Error message if validation is not passed.
		 *
		 * @return void
		 */
		final protected function __construct( bool $is_valid, string $message = '' ) {
			$this->is_valid = $is_valid;
			$this->message  = $message;
		}

		/**
		 * Returns true if validation passed, false otherwise.
		 *
		 * @since 4.5.0
		 *
		 * @return bool
		 */
		public function is_valid(): bool {
			return $this->is_valid;
		}

		/**
		 * Returns a message.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		public function get_message(): string {
			return $this->message;
		}

		/**
		 * Creates a valid result.
		 *
		 * @since 4.5.0
		 *
		 * @return self
		 */
		public static function valid(): self {
			return new self( true );
		}

		/**
		 * Creates an invalid result.
		 *
		 * @since 4.5.0
		 *
		 * @param string $message Error message.
		 *
		 * @return self
		 */
		public static function invalid( string $message ): self {
			return new self( false, $message );
		}
	}
}
