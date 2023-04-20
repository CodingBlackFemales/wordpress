<?php
/**
 * This class provides the easy way to operate data.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Pricing_DTO' ) && class_exists( 'Learndash_DTO' ) ) {
	/**
	 * Pricing DTO class. Used for the product and transaction pricing.
	 *
	 * @since 4.5.0
	 */
	class Learndash_Pricing_DTO extends Learndash_DTO {
		private const VALID_DURATION_VALUES = array( 'Y', 'M', 'W', 'D' );

		/**
		 * Properties are being cast to the specified type on construction.
		 *
		 * @since 4.5.0
		 *
		 * @var array<string, string>
		 */
		protected $cast = array(
			'currency'              => 'string',
			'price'                 => 'float',
			'discount'              => 'float',
			'discounted_price'      => 'float',
			'recurring_times'       => 'int',
			'duration_value'        => 'int',
			'duration_length'       => 'string',
			'trial_price'           => 'float',
			'trial_duration_value'  => 'int',
			'trial_duration_length' => 'string',
		);

		/**
		 * Currency.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		public $currency = '';

		/**
		 * Price.
		 *
		 * @since 4.5.0
		 *
		 * @var float
		 */
		public $price = 0.0;

		/**
		 * Discount.
		 *
		 * @since 4.5.0
		 *
		 * @var float
		 */
		public $discount = 0.0;

		/**
		 * Discounted price.
		 *
		 * @since 4.5.0
		 *
		 * @var float
		 */
		public $discounted_price = 0.0;

		/**
		 * Recurring times (for subscriptions).
		 *
		 * @since 4.5.0
		 *
		 * @var int
		 */
		public $recurring_times = 0;

		/**
		 * Duration value (for subscriptions).
		 *
		 * @since 4.5.0
		 *
		 * @var int
		 */
		public $duration_value = 0;

		/**
		 * Duration length (for subscriptions). Valid values: Y, M, W, D.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		public $duration_length = '';

		/**
		 * Trial price.
		 *
		 * @since 4.5.0
		 *
		 * @var float
		 */
		public $trial_price = 0.0;

		/**
		 * Trial duration value (for subscriptions).
		 *
		 * @since 4.5.0
		 *
		 * @var int
		 */
		public $trial_duration_value = 0;

		/**
		 * Trial duration length (for subscriptions). Valid values: Y, M, W, D.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		public $trial_duration_length = '';

		/**
		 * Validates properties on construction based on validators.
		 * Key is a property name, value is an array of validator objects.
		 *
		 * @since 4.5.0
		 *
		 * @return array<string,mixed>
		 */
		protected function get_validators(): array {
			$validators = parent::get_validators();

			if ( ! empty( $this->currency ) ) {
				$validators['currency'] = array(
					Learndash_DTO_Property_Validator_String_Case::uppercase(),
				);
			}

			if ( ! empty( $this->duration_length ) ) {
				$validators['duration_length'] = array(
					new Learndash_DTO_Property_Validator_Possible_Values( self::VALID_DURATION_VALUES ),
				);
			}

			if ( ! empty( $this->trial_duration_length ) ) {
				$validators['trial_duration_length'] = array(
					new Learndash_DTO_Property_Validator_Possible_Values( self::VALID_DURATION_VALUES ),
				);
			}

			return $validators;
		}
	}
}
