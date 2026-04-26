<?php
/**
 * LearnDash validation class for course groups with start or end date.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Validations\Rules;

use Closure;
use LearnDash\Core\Validations\Traits\Groups_With_Start_Or_End_Date;
use StellarWP\Learndash\StellarWP\Validation\Contracts\ValidationRule;

/**
 * Validates the groups of a course when one of the groups have a start or end date.
 *
 * @since 4.8.0
 */
class Restrict_Multiple_Course_Groups_With_Start_Or_End_Date implements ValidationRule {
	use Groups_With_Start_Or_End_Date;

	/**
	 * Creates a new instance of the validation rule from a string form.
	 *
	 * @since 4.8.0
	 *
	 * @param string|null $options The options for the validation rule.
	 *
	 * @return ValidationRule
	 */
	public static function fromString( string $options = null ): ValidationRule {
		return new self();
	}

	/**
	 * Returns the unique id of the validation rule.
	 *
	 * @since 4.8.0
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'restrictMultipleCourseGroupsWithStartOrEndDate';
	}

	/**
	 * The invokable method used to validate the value.
	 *
	 * @since 4.8.0
	 *
	 * @param mixed        $value  The value to validate.
	 * @param Closure      $fail   The callback to invoke if the value is invalid.
	 * @param string       $key    The key of the value in the values array.
	 * @param array<mixed> $values The values array.
	 *
	 * @return void
	 */
	public function __invoke( $value, Closure $fail, string $key, array $values ) {
		if ( empty( $value ) ) {
			return;
		}

		if ( ! is_array( $value ) ) {
			$fail(
				// translators: placeholder: validation field name.
				sprintf( __( '%s must be an array of groups', 'learndash' ), '{field}' )
			);

			return;
		}

		$conflicting_group = $this->get_first_conflicting_group_with_start_or_end_date( $value );

		if ( ! empty( $conflicting_group ) ) {
			$fail(
				sprintf(
					// translators: placeholder: course, groups, group, group title.
					__(
						'Sorry! The %1$s can not belong to these %2$s because the %3$s "%4$s" has a start or end date.',
						'learndash'
					),
					learndash_get_custom_label( 'course' ),
					learndash_get_custom_label( 'groups' ),
					learndash_get_custom_label( 'group' ),
					$conflicting_group->get_title()
				)
			);

			return;
		}
	}
}
