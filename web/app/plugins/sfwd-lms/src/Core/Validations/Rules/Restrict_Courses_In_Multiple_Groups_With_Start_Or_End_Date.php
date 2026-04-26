<?php
/**
 * LearnDash validation class for courses in multiple groups with start or end date.
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
 * Validates the courses of a group to restrict a course in multiple groups with a start or end date.
 *
 * @since 4.8.0
 */
class Restrict_Courses_In_Multiple_Groups_With_Start_Or_End_Date implements ValidationRule {
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
			Config::throwInvalidArgumentException( 'Restrict Courses In Multiple Groups With Start Or End Date rule requires a Group ID value' );

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
		return 'restrictCoursesInMultipleGroupsWithStartOrEndDate';
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
			Config::throwInvalidArgumentException( 'Restrict Courses In Multiple Groups With Start Or End Date rule requires a numeric value' );
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
		if ( empty( $value ) ) {
			return;
		}

		if ( ! is_array( $value ) ) {
			$fail(
				// translators: placeholder: validation field name.
				sprintf( __( '%s must be an array of courses', 'learndash' ), '{field}' )
			);

			return;
		}

		foreach ( $value as $course_id ) {
			$group_ids = learndash_get_course_groups( $course_id );

			// adding the current group to the list of groups.
			if ( ! in_array( $this->group->get_id(), $group_ids, true ) ) {
				array_push( $group_ids, $this->group->get_id() );
			}

			$conflicting_group = $this->get_first_conflicting_group_with_start_or_end_date( $group_ids );

			if ( ! empty( $conflicting_group ) ) {
				$fail(
					sprintf(
						// translators: placeholder: course, group, group, groups, group title.
						__(
							'Sorry! The %1$s can not belong to this %2$s because it already belongs to other %3$s and the %4$s "%5$s" has a start or end date.',
							'learndash'
						),
						learndash_get_custom_label( 'course' ),
						learndash_get_custom_label( 'group' ),
						learndash_get_custom_label( 'groups' ),
						learndash_get_custom_label( 'group' ),
						$conflicting_group->get_title()
					)
				);

				return;
			}
		}
	}
}
