<?php
/**
 * Abstract Product model class for Commerce.
 *
 * It's a base class for all products in the Commerce namespace (level 2 of the transaction hierarchy: Subscriptions and One Time Payments).
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models\Commerce;

use LearnDash\Core\Models\Product as Core_Product;
use LearnDash\Core\Models\Transaction;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Mappers\Models\Commerce_Product_Mapper;
use LearnDash\Core\Enums\Commerce\Cancellation_Reason;

/**
 * Abstract Product model class for Commerce.
 *
 * @since 4.25.0
 */
abstract class Product extends Transaction {
	/**
	 * Meta key for the product status.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static $meta_key_product_status = 'product_status';

	/**
	 * Meta key for the cancellation date.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static $meta_key_cancellation_date = 'cancellation_date';

	/**
	 * Meta key for the cancellation reason.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static $meta_key_cancellation_reason = 'cancellation_reason';

	/**
	 * Meta key for the ID of the user who canceled the product.
	 *
	 * @since 4.25.3
	 *
	 * @var string
	 */
	public static $meta_key_cancellation_user_id = 'cancellation_user_id';

	/**
	 * Meta key for the status history.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static $meta_key_status_history = 'status_history';

	/**
	 * Creates a Product model from a Transaction.
	 *
	 * @since 4.25.0
	 *
	 * @param Transaction $transaction The transaction to cast.
	 *
	 * @return Product|null The Product model or null if the transaction cannot be cast.
	 */
	public static function create_from_transaction( Transaction $transaction ): ?Product {
		$product = $transaction->get_product();

		if ( ! $product ) {
			return null;
		}

		$commerce_product = Commerce_Product_Mapper::create( $product, $transaction->get_id() );

		if ( ! $commerce_product instanceof static ) {
			return null;
		}

		return $commerce_product;
	}

	/**
	 * Returns the status based on the Core Product.
	 *
	 * @since 4.25.0
	 *
	 * @param Core_Product $product The product.
	 *
	 * @return string
	 */
	abstract public function get_status_based_on_product( Core_Product $product ): string;

	/**
	 * Returns the status label.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	abstract public function get_status_label(): string;

	/**
	 * Returns the product price.
	 *
	 * @since 4.25.0
	 *
	 * @return float
	 */
	abstract public function get_price(): float;

	/**
	 * Cancels the product.
	 *
	 * @since 4.25.0
	 *
	 * @param string $reason             The reason for the cancellation.
	 * @param bool   $force_cancellation Whether to force the cancellation. Default false.
	 *
	 * @return bool True if the product was canceled. False otherwise.
	 */
	public function cancel( string $reason, bool $force_cancellation = false ): bool {
		$this->set_meta( self::$meta_key_cancellation_date, time() );
		$this->set_meta( self::$meta_key_cancellation_reason, $reason );
		$this->set_meta( self::$meta_key_cancellation_user_id, get_current_user_id() );

		return true;
	}

	/**
	 * Returns the timestamp when the product was canceled, or null if the product is not canceled.
	 *
	 * @since 4.25.0
	 *
	 * @return int|null
	 */
	public function get_cancellation_date(): ?int {
		$cancellation_date = $this->getAttribute( self::$meta_key_cancellation_date );

		return is_null( $cancellation_date )
			? null
			: Cast::to_int( $cancellation_date );
	}

	/**
	 * Returns the ID of the user who canceled the product.
	 * Returns null if the product is not canceled or if the cancellation was done by the system (via webhooks or payment processing).
	 *
	 * @since 4.25.3
	 *
	 * @return int|null
	 */
	public function get_cancellation_user_id(): ?int {
		$user_id = Cast::to_int( $this->getAttribute( self::$meta_key_cancellation_user_id, 0 ) );

		return $user_id <= 0
			? null
			: Cast::to_int( $user_id );
	}

	/**
	 * Returns the cancellation reason enum, or null if the product is not canceled.
	 *
	 * @since 4.25.3
	 *
	 * @return ?Cancellation_Reason
	 */
	public function get_cancellation_reason(): ?Cancellation_Reason {
		$cancellation_reason = Cast::to_string( $this->getAttribute( self::$meta_key_cancellation_reason ) );

		if ( empty( $cancellation_reason ) ) {
			return null;
		}

		try {
			return Cancellation_Reason::from( $cancellation_reason );
		} catch ( \UnexpectedValueException $e ) {
			return null;
		}
	}

	/**
	 * Returns the product status.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_status(): string {
		return Cast::to_string( $this->getAttribute( self::$meta_key_product_status ) );
	}

	/**
	 * Sets the product status.
	 *
	 * @since 4.25.0
	 *
	 * @param string $status The status.
	 *
	 * @return void
	 */
	public function set_status( string $status ): void {
		$this->set_meta( self::$meta_key_product_status, $status );

		$this->add_status_history( $status );
	}

	/**
	 * Adds a status history entry.
	 *
	 * @since 4.25.0
	 *
	 * @param string $status The new status.
	 *
	 * @return void
	 */
	protected function add_status_history( string $status ): void {
		$status_history = $this->getAttribute( self::$meta_key_status_history );

		if ( ! is_array( $status_history ) ) {
			$status_history = [];
		}

		$status_history[] = [
			'status' => $status,
			'date'   => time(),
		];

		$this->set_meta( self::$meta_key_status_history, $status_history );
	}
}
