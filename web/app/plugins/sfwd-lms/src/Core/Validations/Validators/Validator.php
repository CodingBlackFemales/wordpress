<?php
/**
 * Abstract class for LearnDash Validators.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Validations\Validators;

use LearnDash\Core\Validations\Validators\DTO\Action;
use StellarWP\Learndash\StellarWP\Validation\Contracts\ValidationRule;
use StellarWP\Learndash\StellarWP\Validation\ValidationRuleSet;
use StellarWP\Learndash\StellarWP\Validation\Validator as StellarValidator;

/**
 * Base validator class.
 *
 * @since 4.8.0
 */
abstract class Validator {
	/**
	 * Returns the rule sets for the validator.
	 *
	 * @since 4.8.0
	 *
	 * @return array<string,array<int,ValidationRule|string>|ValidationRuleSet> Rule sets for the validator.
	 */
	abstract protected function get_rule_sets(): array;

	/**
	 * Returns the labels for the validator.
	 *
	 * @since 4.8.0
	 *
	 * @return array<string,string> Labels for the validator.
	 */
	abstract protected function get_labels(): array;

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
		return [];
	}

	/**
	 * Validates the given values.
	 *
	 * @since 4.8.0
	 *
	 * @param array<string,mixed> $values Values to validate. [field_id => value].
	 *
	 * @return StellarValidator
	 */
	public function validate( array $values ): StellarValidator {
		return new StellarValidator(
			$this->get_rule_sets(),
			$values,
			$this->get_labels()
		);
	}
}
