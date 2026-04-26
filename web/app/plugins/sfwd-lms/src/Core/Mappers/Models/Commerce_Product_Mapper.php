<?php
/**
 * Commerce_Product_Mapper class for mapping core products to the correct Commerce Product model.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Mappers\Models;

use LearnDash\Core\Models\Commerce\One_Time_Payment;
use LearnDash\Core\Models\Commerce\Product as Commerce_Product;
use LearnDash\Core\Models\Commerce\Subscription;
use LearnDash\Core\Models\Product;

/**
 * Commerce_Product_Mapper class for mapping core products to the correct Commerce Product model.
 *
 * @since 4.25.0
 */
class Commerce_Product_Mapper {
	/**
	 * Creates the correct Commerce Product model for a given core product and transaction ID.
	 *
	 * @since 4.25.0
	 *
	 * @param Product $product        The core product.
	 * @param int     $transaction_id The transaction ID.
	 *
	 * @return Commerce_Product|null The Commerce Product or null if not found.
	 */
	public static function create( Product $product, int $transaction_id ): ?Commerce_Product {
		if ( $product->is_price_type_subscribe() ) {
			return Subscription::find( $transaction_id );
		}

		return One_Time_Payment::find( $transaction_id );
	}
}
