<?php
/**
 * Validator for the Group Access Settings Metabox.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Validations\Validators\Metaboxes;

use LearnDash\Core\Validations\Rules\Restrict_Start_Or_End_Date_In_Groups_With_Course_In_Multiple_Groups;
use LearnDash\Core\Validations\Traits\Groups_With_Start_Or_End_Date;
use LearnDash\Core\Validations\Validators\DTO\Action;
use LearnDash\Core\Validations\Validators\Validator;
use StellarWP\Learndash\StellarWP\Validation\Contracts\ValidationRule;
use StellarWP\Learndash\StellarWP\Validation\ValidationRuleSet;

/**
 * Group Access Settings Metabox Validator class.
 *
 * @since 4.8.0
 */
class Group_Access_Settings extends Validator {
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
	 * The field Start Date
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public static $field_start_date = 'group_start_date';

	/**
	 * The field End Date
	 *
	 * @since 4.8.0
	 *
	 * @var string
	 */
	public static $field_end_date = 'group_end_date';

	/**
	 * Returns the rule sets for the validator.
	 *
	 * @since 4.8.0
	 *
	 * @return array<string,array<int,ValidationRule|string>|ValidationRuleSet> Rule sets for the validator.
	 */
	protected function get_rule_sets(): array {
		return [
			self::$field_start_date => [
				new Restrict_Start_Or_End_Date_In_Groups_With_Course_In_Multiple_Groups( $this->group_id ),
			],
			self::$field_end_date   => [
				new Restrict_Start_Or_End_Date_In_Groups_With_Course_In_Multiple_Groups( $this->group_id ),
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
			self::$field_start_date => esc_html__( 'Start Date', 'learndash' ),
			self::$field_end_date   => esc_html__( 'End Date', 'learndash' ),
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
		switch ( $field ) {
			case self::$field_start_date:
			case self::$field_end_date:
				return $this->get_actions_for_start_or_end_date_validation_fields();

			default:
				return [];
		}
	}
}
