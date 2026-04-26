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

if ( ! class_exists( 'Learndash_Transaction_Gateway_Transaction_DTO' ) && class_exists( 'Learndash_DTO' ) ) {
	/**
	 * Coupon transaction gateway event DTO class.
	 *
	 * @since 4.5.0
	 */
	class Learndash_Transaction_Gateway_Transaction_DTO extends Learndash_DTO {
		/**
		 * Properties are being cast to the specified type on construction.
		 *
		 * @since 4.5.0
		 * @since 4.19.0 Added `customer_id` and `event`.
		 *
		 * @var array<string, string>
		 */
		protected $cast = [
			'id'          => 'string',
			'customer_id' => 'string',
			'event'       => 'array',
		];

		/**
		 * ID.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		public $id = '';

		/**
		 * Customer ID.
		 *
		 * @since 4.19.0
		 *
		 * @var string
		 */
		public $customer_id = '';

		/**
		 * Event.
		 *
		 * @since 4.5.0
		 * @since 4.19.0 Changed the default value from an empty string to an empty array.
		 *
		 * TODO: We should have separate Array Shapes for each Gateway rather than trying to combine them into one.
		 * @var array{
		 *     customer?: string,
		 *     contains?: string[],
		 *     payload?: array{
		 *         payment?: array{
		 *             entity: array{
		 *                 customer_id: string,
		 *             },
		 *         },
		 *         subscription?: array{
		 *             entity: array{
		 *                 customer_id?: string,
		 *             }
		 *         }
		 *     },
		 *     payer_id?: string,
		 * }
		 */
		public $event = [];
	}
}
