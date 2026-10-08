<?php
/**
 * Controllers service provider class file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

namespace LearnDash\WooCommerce\Controllers;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Controllers service provider class.
 *
 * @since 5.2.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers service provider.
	 *
	 * @since 5.2.0
	 *
	 * @throws ContainerException If the service provider is not registered.
	 *
	 * @return void
	 */
	public function register() {
		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 5.2.0
	 *
	 * @throws ContainerException If the service provider is not registered.
	 *
	 * @return void
	 */
	public function hooks() {
		// Product hooks.

		add_filter( 'woocommerce_product_class', $this->container->callback( Product::class, 'filter_product_class' ), 10, 2 );
		// Use WC simple product add to cart template for course product.
		add_action( 'woocommerce_course_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
	}
}
