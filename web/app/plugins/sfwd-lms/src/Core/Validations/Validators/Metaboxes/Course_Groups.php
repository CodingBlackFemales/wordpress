<?php
/**
 * Validator for the Course Groups Metabox.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Validations\Validators\Metaboxes;

use LearnDash\Core\Validations\Rules\Restrict_Multiple_Course_Groups_With_Start_Or_End_Date;
use LearnDash\Core\Validations\Traits\Groups_With_Start_Or_End_Date;
use LearnDash\Core\Validations\Validators\DTO\Action;
use LearnDash\Core\Validations\Validators\Validator;
use StellarWP\Learndash\StellarWP\Validation\Contracts\ValidationRule;
use StellarWP\Learndash\StellarWP\Validation\ValidationRuleSet;

/**
 * Course Groups Metabox Validator class.
 *
 * @since 4.8.0
 */
class Course_Groups extends Validator {
	use Groups_With_Start_Or_End_Date;

	/**
	 * The field groups.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public static $field_groups = 'learndash_groups';

	/**
	 * Returns the rule sets for the validator.
	 *
	 * @since 4.8.0
	 *
	 * @return array<string,array<int,ValidationRule|string>|ValidationRuleSet> Rule sets for the validator.
	 */
	protected function get_rule_sets(): array {
		return [
			self::$field_groups => [
				new Restrict_Multiple_Course_Groups_With_Start_Or_End_Date(),
			],
		];
	}

	/**
	 * Returns the labels for the validator.
	 *
	 * @since 4.8.0
	 *
	 * @return array<string> Labels for the validator.
	 */
	protected function get_labels(): array {
		return [
			self::$field_groups => sprintf(
				// translators: placeholder: Course, Groups.
				esc_html_x( '%1$s %2$s', 'placeholder: Course, Groups', 'learndash' ),
				learndash_get_custom_label( 'course' ),
				learndash_get_custom_label( 'groups' )
			),
		];
	}

	/**
	 * Returns the actions for a field to show in the WP frontend.
	 *
	 * @since 4.8.0
	 *
	 * @param string $field The field.
	 *
	 * @return array<Action> The actions for a field to show in the WP frontend.
	 */
	public function get_actions_for_field( string $field ): array {
		if ( $field !== self::$field_groups ) {
			return [];
		}

		return $this->get_actions_for_start_or_end_date_validation_fields();
	}
}
