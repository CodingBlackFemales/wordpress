<?php
/**
 * Product controller class file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

namespace LearnDash\WooCommerce\Controllers;

use LearnDash\WooCommerce\Models\Products;

/**
 * Product controller class.
 *
 * @since 5.2.0
 */
class Product {
	/**
	 * Filters product class.
	 *
	 * @since 5.2.0
	 *
	 * @param string $class        Product class.
	 * @param string $product_type Product type.
	 *
	 * @return string
	 */
	public function filter_product_class( $class, string $product_type ) {
		if ( $product_type === Products\Course::$product_type ) {
			$class = Products\Course::class;
		}

		return $class;
	}
}
