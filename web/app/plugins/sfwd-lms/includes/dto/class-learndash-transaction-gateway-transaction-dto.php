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
		 *
		 * @var array<string, string>
		 */
		protected $cast = array(
			'id' => 'string',
		);

		/**
		 * ID.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		public $id = '';

		/**
		 * Event.
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		public $event = '';
	}
}
