<?php
/**
 * Is_String validation rule.
 *
 * @since 4.21.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Validations\Rules;

use Closure;
use StellarWP\Learndash\StellarWP\Validation\Contracts\ValidationRule;

/**
 * Validation rule to ensure a value is a string.
 *
 * @since 4.21.1
 */
class Is_String implements ValidationRule {
	/**
	 * Returns the unique identifier for the validation rule.
	 *
	 * @since 4.21.1
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'string';
	}

	/**
	 * Creates a new instance of the validation rule from a string.
	 *
	 * @since 4.21.1
	 *
	 * @param string|null $options Options for the validation rule.
	 *
	 * @return ValidationRule
	 */
	public static function fromString( string $options = null ): ValidationRule {
		return new self();
	}

	/**
	 * Validates that the value is a string.
	 *
	 * @since 4.21.1
	 *
	 * @param mixed                $value  The value to validate.
	 * @param Closure              $fail   The callback to invoke on failure.
	 * @param string               $key    The field key being validated.
	 * @param array<string, mixed> $values All field values being validated.
	 *
	 * @return void
	 */
	public function __invoke( $value, Closure $fail, string $key, array $values ) {
		if ( ! is_string( $value ) ) {
			// translators: %s: The field name being validated.
			$fail( sprintf( __( '%s must be a valid string', 'learndash' ), '{field}' ) );
		}
	}

	/**
	 * Returns a serializable representation of the validation rule options.
	 *
	 * @since 4.21.1
	 *
	 * @return bool
	 */
	public function serializeOption(): bool {
		return true;
	}
}
