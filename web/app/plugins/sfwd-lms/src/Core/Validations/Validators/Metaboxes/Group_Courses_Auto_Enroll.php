<?php
/**
 * Validator for the Group Courses Auto Enroll Metabox.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Validations\Validators\Metaboxes;

use LearnDash\Core\Validations\Rules\Restrict_Courses_In_Multiple_Groups_With_Start_Or_End_Date;
use LearnDash\Core\Validations\Traits\Groups_With_Start_Or_End_Date;
use LearnDash\Core\Validations\Validators\DTO\Action;
use LearnDash\Core\Validations\Validators\Validator;
use StellarWP\Learndash\StellarWP\Validation\Contracts\ValidationRule;
use StellarWP\Learndash\StellarWP\Validation\ValidationRuleSet;

/**
 * Group Courses Auto Enroll Metabox Validator class.
 *
 * @since 4.8.0
 */
class Group_Courses_Auto_Enroll extends Validator {
	use Groups_With_Start_Or_End_Date;

	/**
	 * The group id.
	 *
	 * @since 4.8.0
	 *
	 * @var int
	 */
	private $group_id;

	/**
	 * The constructor.
	 *
	 * @since 4.8.0
	 *
	 * @param int $group_id The group id.
	 */
	public function __construct( int $group_id ) {
		$this->group_id = $group_id;
	}

	/**
	 * The field courses auto enroll.
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public static $field_courses_auto_enroll = 'learndash_courses_auto_enroll';

	/**
	 * Returns the rule sets for the validator.
	 *
	 * @since 4.8.0
	 *
	 * @return array<string,array<int,ValidationRule|string>|ValidationRuleSet> Rule sets for the validator.
	 */
	protected function get_rule_sets(): array {
		return [
			self::$field_courses_auto_enroll => [
				new Restrict_Courses_In_Multiple_Groups_With_Start_Or_End_Date( $this->group_id ),
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
			self::$field_courses_auto_enroll => sprintf(
				// translators: placeholder: Group, Courses.
				esc_html_x( '%1$s %2$s Auto-enroll', 'placeholder: Group, Courses', 'learndash' ),
				learndash_get_custom_label( 'group' ),
				learndash_get_custom_label( 'courses' )
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
		if ( $field !== self::$field_courses_auto_enroll ) {
			return [];
		}

		return $this->get_actions_for_start_or_end_date_validation_fields();
	}
}
