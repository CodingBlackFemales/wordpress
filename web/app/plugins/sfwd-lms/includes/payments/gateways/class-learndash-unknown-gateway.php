<?php
/**
 * This class is made for null object pattern support.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

use LearnDash\Core\Models\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Unknown_Gateway' ) && class_exists( 'Learndash_Payment_Gateway' ) ) {
	/**
	 * Unknown gateway class.
	 *
	 * @since 4.5.0
	 */
	class Learndash_Unknown_Gateway extends Learndash_Payment_Gateway {
		private const GATEWAY_NAME = '';

		/**
		 * Returns a flag to easily identify if the gateway supports transactions management.
		 *
		 * @since 4.5.0
		 *
		 * @return bool True if a gateway supports managing subscriptions/other transactions. False otherwise.
		 */
		public function supports_transactions_management(): bool {
			return false;
		}

		/**
		 * Returns a flag to easily identify if the gateway supports logger.
		 *
		 * @since 4.5.0
		 *
		 * @return bool True if a gateway supports logger. False otherwise.
		 */
		public function supports_logger(): bool {
			return false;
		}

		/**
		 * Cancels a subscription.
		 *
		 * @since 4.5.0
		 *
		 * @param string $subscription_id Subscription ID.
		 *
		 * @return WP_Error
		 */
		public function cancel_subscription( string $subscription_id ): WP_Error {
			return new WP_Error(
				self::$wp_error_code,
				__( 'Subscription management is not possible because the payment gateway is not enabled or configured.', 'learndash' )
			);
		}

		/**
		 * Returns the gateway name.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		public static function get_name(): string {
			return self::GATEWAY_NAME;
		}

		/**
		 * Returns the gateway label.
		 *
		 * @since 4.5.0
		 *
		 * @return string
		 */
		public static function get_label(): string {
			return esc_html__( 'Unknown', 'learndash' );
		}

		/**
		 * Adds hooks.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function add_extra_hooks(): void {
			// No hooks.
		}

		/**
		 * Enqueues scripts.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function enqueue_scripts(): void {
			// No scripts.
		}

		/**
		 * Creates a session/order/subscription on backend if needed.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function setup_payment(): void {
			// No actions.
		}

		/**
		 * Returns true if everything is configured and payment gateway can be used, otherwise false.
		 *
		 * @since 4.5.0
		 *
		 * @return bool Always true.
		 */
		public function is_ready(): bool {
			return true;
		}

		/**
		 * Returns true it's a test mode, otherwise false.
		 *
		 * @since 4.5.0
		 *
		 * @return bool Always true.
		 */
		protected function is_test_mode(): bool {
			return true;
		}

		/**
		 * Configures settings.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		protected function configure(): void {
			// Do nothing.
		}

		/**
		 * Maps transaction meta.
		 *
		 * @since 4.5.0
		 *
		 * @param mixed   $data    Data.
		 * @param Product $product Product.
		 *
		 * @throws Learndash_DTO_Validation_Exception Exception.
		 *
		 * @return Learndash_Transaction_Meta_DTO
		 */
		protected function map_transaction_meta( $data, Product $product ): Learndash_Transaction_Meta_DTO {
			return Learndash_Transaction_Meta_DTO::create();
		}

		/**
		 * Handles the webhook.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function process_webhook(): void {
			// Do nothing.
		}

		/**
		 * Returns payment button HTML markup.
		 *
		 * @since 4.5.0
		 *
		 * @param array<mixed> $params Payment params.
		 * @param WP_Post      $post   Post being processing.
		 *
		 * @return string Empty string.
		 */
		public function map_payment_button_markup( array $params, WP_Post $post ): string {
			return '';
		}
	}
}
