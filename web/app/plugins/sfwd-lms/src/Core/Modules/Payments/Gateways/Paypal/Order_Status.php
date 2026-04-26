<?php
/**
 * PayPal Order Status Helper.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal;

use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;

/**
 * PayPal Order Status Helper class.
 *
 * @since 4.25.0
 */
class Order_Status {
	/**
	 * Status constant for created orders.
	 *
	 * @since 4.25.0
	 */
	private const STATUS_CREATED = 'CREATED';

	/**
	 * Status constant for approved orders.
	 *
	 * @since 4.25.0
	 */
	private const STATUS_APPROVED = 'APPROVED';

	/**
	 * Status constant for completed orders.
	 *
	 * @since 4.25.0
	 */
	private const STATUS_COMPLETED = 'COMPLETED';

	/**
	 * Status constant for failed orders.
	 *
	 * @since 4.25.0
	 */
	private const STATUS_FAILED = 'FAILED';

	/**
	 * Status constant for declined orders.
	 *
	 * @since 4.25.0
	 */
	private const STATUS_DECLINED = 'DECLINED';

	/**
	 * Status constant for voided orders.
	 *
	 * @since 4.25.0
	 */
	private const STATUS_VOIDED = 'VOIDED';

	/**
	 * Returns the latest payment status from a PayPal order.
	 *
	 * @since 4.25.0
	 *
	 * @see https://developer.paypal.com/docs/api/orders/v2/#definition-order
	 *
	 * @param array<string,mixed> $order The order data. Check the link above for the expected structure.
	 *
	 * @return string The latest payment status. Empty string if no captures are found.
	 */
	public function get_latest_payment_status( array $order ): string {
		$captures       = [];
		$purchase_units = [];
		$status         = Cast::to_string( Arr::get( $order, 'status', '' ) );

		// If the order is not the status we expect, we don't need to check the captures.
		if ( self::STATUS_CREATED !== $status ) {
			return $status;
		}

		if (
			! isset( $order['purchase_units'] )
			|| ! is_array( $order['purchase_units'] )
			|| empty( $order['purchase_units'] )
		) {
			return $status;
		}

		$purchase_units = $order['purchase_units'];

		foreach ( $purchase_units as $unit ) {
			$capture = (array) Arr::get( $unit, 'payments.captures', [] );

			if ( ! empty( $capture ) ) {
				$captures[] = $capture;
			}
		}

		if ( empty( $captures ) ) {
			return $status;
		}

		// Get the latest captures from the purchase units (PayPal API returns the captures in descending order: latest to oldest).
		$captures = array_shift( $captures );

		// Sort the captures by update time (descending order: latest to oldest).
		usort(
			$captures,
			function ( $a, $b ) {
				/**
				 * Use the spaceship operator to compare the update times. It works exactly like the `strcmp()` function
				 * (returns 0 if equal, -1 if less, 1 if greater), but it works with any data type.
				 */
				return strtotime( Cast::to_string( Arr::get( $b, 'update_time', '' ) ) ) <=> strtotime( Cast::to_string( Arr::get( $a, 'update_time', '' ) ) );
			}
		);

		foreach ( $captures as $capture ) {
			$status = Cast::to_string( Arr::get( $capture, 'status', '' ) );

			// If the capture is final (the payment is completed), we don't need to check the other captures.
			if ( Cast::to_bool( Arr::get( $capture, 'final_capture', false ) ) ) {
				break;
			}
		}

		return $status;
	}

	/**
	 * Checks if a payment status indicates a successful payment.
	 *
	 * @since 4.25.0
	 *
	 * @param string $status The payment status to check.
	 *
	 * @return bool True if the status indicates a successful payment, false otherwise.
	 */
	public function is_successful_payment( string $status ): bool {
		return in_array( $status, [ self::STATUS_COMPLETED, self::STATUS_APPROVED ], true );
	}

	/**
	 * Checks if a payment status indicates a failed payment.
	 *
	 * @since 4.25.0
	 *
	 * @param string $status The payment status to check.
	 *
	 * @return bool True if the status indicates a failed payment, false otherwise.
	 */
	public function is_failed_payment( string $status ): bool {
		return in_array( $status, [ self::STATUS_FAILED, self::STATUS_DECLINED, self::STATUS_VOIDED ], true );
	}

	/**
	 * Checks if a payment status indicates a pending payment.
	 *
	 * @since 4.25.0
	 *
	 * @param string $status The payment status to check.
	 *
	 * @return bool True if the status indicates a pending payment, false otherwise.
	 */
	public function is_pending_payment( string $status ): bool {
		return self::STATUS_CREATED === $status;
	}

	/**
	 * Checks if a payment status indicates a completed payment.
	 *
	 * @since 4.25.0
	 *
	 * @param string $status The payment status to check.
	 *
	 * @return bool True if the status indicates a completed payment, false otherwise.
	 */
	public function is_payment_completed( string $status ): bool {
		return self::STATUS_COMPLETED === $status;
	}
}
