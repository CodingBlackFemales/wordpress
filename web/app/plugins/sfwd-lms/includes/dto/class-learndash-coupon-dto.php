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

if ( ! class_exists( 'Learndash_Coupon_DTO' ) && class_exists( 'Learndash_DTO' ) ) {
	/**
	 * Coupon DTO class. Used to map the coupon data.
	 *
	 * @since 4.5.0
	 */
	class Learndash_Coupon_DTO extends Learndash_DTO {
		const VALID_TYPES = array( LEARNDASH_COUPON_TYPE_FLAT, LEARNDASH_COUPON_TYPE_PERCENTAGE );

		/**
		 * Properties are being cast to the specified type on construction.
		 *
		 * @since 4.5.0
		 *
		 * @var array<string, string>
		 */
		protected $cast = array(
			'currency'         => 'string',
			'price'            => 'float',
			'discount'         => 'float',
			'discounted_price' => 'float',
			'coupon_id'        => 'int',
			'code'             => 'string',
			'type'             => 'string',
			'amount'           => 'float',
		);

		/**
		 * Coupon ID.
		 *
		 * @since 4.5.0
		 *
		 * @var int
		 */
		public $coupon_id = 0;

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
		 * Code.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		public $code = '';

		/**
		 * Type. Valid values are LEARNDASH_COUPON_TYPE_FLAT and LEARNDASH_COUPON_TYPE_PERCENTAGE.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		public $type = '';

		/**
		 * Amount.
		 *
		 * @since 4.5.0
		 *
		 * @var float
		 */
		public $amount = 0.0;

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

			if ( ! empty( $this->type ) ) {
				$validators['type'] = array(
					new Learndash_DTO_Property_Validator_Possible_Values( self::VALID_TYPES ),
				);
			}

			return $validators;
		}
	}
}
