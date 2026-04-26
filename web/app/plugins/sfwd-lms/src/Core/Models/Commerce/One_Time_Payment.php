<?php
/**
 * One-Time Payment model class.
 *
 * Represents a one-time payment transaction (child of Order) in the LearnDash payment system.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models\Commerce;

use LearnDash\Core\Models\Product as Core_Product;

/**
 * One-Time Payment model class.
 *
 * @since 4.25.0
 */
class One_Time_Payment extends Product {
	/**
	 * One-time payment status 'Active'.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static $status_active = 'active';

	/**
	 * One-time payment status 'Canceled'.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static $status_canceled = 'canceled';

	/**
	 * Returns the one-time payment status based on the product.
	 *
	 * @since 4.25.0
	 *
	 * @param Core_Product $product The product.
	 *
	 * @return string The status. Empty string if the product is not a one-time payment.
	 */
	public function get_status_based_on_product( Core_Product $product ): string {
		if ( $product->is_price_type_subscribe() ) {
			return ''; // Not applicable.
		}

		return self::$status_active;
	}

	/**
	 * Returns the status label.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_status_label(): string {
		switch ( $this->get_status() ) {
			case self::$status_active:
				return __( 'Active', 'learndash' );
			case self::$status_canceled:
				return __( 'Canceled', 'learndash' );
			default:
				return __( 'Unknown', 'learndash' );
		}
	}

	/**
	 * Returns the product price.
	 *
	 * @since 4.25.0
	 *
	 * @return float
	 */
	public function get_price(): float {
		$product = $this->get_product();

		if ( ! $product ) {
			return 0.00;
		}

		return $product->get_final_price();
	}

	/**
	 * Cancels the one-time payment.
	 *
	 * @since 4.25.0
	 *
	 * @param string $reason             The reason for the cancellation.
	 * @param bool   $force_cancellation Whether to force the cancellation. Default false.
	 *
	 * @return bool True if the one-time payment was canceled. False otherwise.
	 */
	public function cancel( string $reason, bool $force_cancellation = false ): bool {
		$this->set_status( self::$status_canceled );

		return parent::cancel( $reason, $force_cancellation );
	}
}
