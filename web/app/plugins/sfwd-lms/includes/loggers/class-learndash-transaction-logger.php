<?php
/**
 * This class provides an easy way to log everything.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Transaction_Logger' ) && class_exists( 'Learndash_Logger' ) ) {
	/**
	 * Transaction logger class.
	 *
	 * @since 4.5.0
	 */
	class Learndash_Transaction_Logger extends Learndash_Logger {
		/**
		 * Gateway.
		 *
		 * @since 4.5.0
		 *
		 * @var Learndash_Payment_Gateway $gateway
		 */
		private $gateway;

		/**
		 * Logger constructor.
		 *
		 * @since 4.5.0
		 *
		 * @param Learndash_Payment_Gateway $gateway Gateway.
		 *
		 * @return void
		 */
		public function __construct( Learndash_Payment_Gateway $gateway ) {
			$this->gateway = $gateway;
		}

		/**
		 * Returns the label.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		public function get_label(): string {
			return $this->gateway->get_label() . ' ' . LearnDash_Custom_Label::get_label( 'transactions' );
		}

		/**
		 * Returns the name.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		public function get_name(): string {
			return $this->gateway->get_name() . '_transactions';
		}
	}
}
