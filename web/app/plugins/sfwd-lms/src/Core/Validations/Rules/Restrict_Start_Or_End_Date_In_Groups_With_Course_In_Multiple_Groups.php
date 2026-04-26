<?php
/**
 * LearnDash validation class for Start or End Date in Groups that have at least one course that belongs to multiple groups.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Validations\Rules;

use Closure;
use LearnDash\Core\Models\Group;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Validations\Traits\Groups_With_Start_Or_End_Date;
use StellarWP\Learndash\StellarWP\Validation\Config;
use StellarWP\Learndash\StellarWP\Validation\Contracts\ValidationRule;

/**
 * Validates the Group Start or End Date when the group has at least one course that belongs to multiple groups.
 *
 * @since 4.8.0
 */
class Restrict_Start_Or_End_Date_In_Groups_With_Course_In_Multiple_Groups implements ValidationRule {
	use Groups_With_Start_Or_End_Date;

	/**
	 *
	 * The group model.
	 *
	 * @since 4.8.0
	 *
	 * @var Group
	 */
	private $group;

	/**
	 * The constructor.
	 *
	 * @since 4.8.0
	 *
	 * @param int $group_id The group id.
	 */
	public function __construct( int $group_id ) {
		$group = Group::find( $group_id );

		if ( ! $group ) {
			Config::throwInvalidArgumentException( 'Restrict Start Or End Date In Groups With Course In Multiple Groups rule requires a Group ID value' );

			return;
		}

		$this->group = $group;
	}

	/**
	 * Returns the unique id of the validation rule.
	 *
	 * @since 4.8.0
	 *
	 * @return string
	 */
	public static function id(): string {
		return 'restrictStartOrEndDateInGroupsWithCourseInMultipleGroups';
	}

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
		if ( ! is_numeric( $options ) ) {
			Config::throwInvalidArgumentException( 'Restrict Start Or End Date In Groups With Course In Multiple Groups rule requires a numeric value' );
		}

		return new self( Cast::to_int( $options ) );
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
		// If the group does not have a start or end date, then there is nothing to validate.

		if ( empty( $value ) ) {
			return;
		}

		// If the group contains at least one course that belongs to multiple groups, then the validation fails.

		if ( $this->contains_course_that_belongs_to_multiple_groups( $this->group->get_id() ) ) {
			$fail(
				sprintf(
					// translators: placeholder: field name, group, course, groups.
					__(
						'Sorry! You can not set a %1$s in this %2$s because it contains at least one %3$s that belongs to other %4$s.',
						'learndash'
					),
					'{field}',
					learndash_get_custom_label( 'group' ),
					learndash_get_custom_label( 'course' ),
					learndash_get_custom_label( 'groups' )
				)
			);

			return;
		}
	}
}
