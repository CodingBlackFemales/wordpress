<?php
/**
 * WC course product type class file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

namespace LearnDash\WooCommerce\Models\Products;

use LDLMS_Post_Types;
use WC_Product;

/**
 * Course product type class.
 *
 * @since 5.2.0
 */
class Course extends WC_Product {
	/**
	 * Product type.
	 *
	 * @since 5.2.0
	 *
	 * @var string
	 */
	public static $product_type = LDLMS_Post_Types::COURSE;

	/**
	 * Initializes course product.
	 *
	 * @since 5.2.0
	 *
	 * @param int|object $product WC Product data.
	 */
	public function __construct( $product ) {
		parent::__construct( $product );

		$this->supports = [
			'ajax_add_to_cart',
		];

		$this->set_virtual( true );
		$this->set_sold_individually( true );
	}

	/**
	 * Gets custom post type.
	 *
	 * @since 5.2.0
	 *
	 * @return string
	 */
	public function get_type() {
		return self::$product_type;
	}

	/**
	 * Gets the add to cart button text.
	 *
	 * @since 5.2.0
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		$text = $this->is_purchasable()
			? __( 'Add to cart', 'learndash' )
			: __( 'Read More', 'learndash' );

		// Kept for backward compatibility.
		$text = apply_filters_deprecated(
			'woocommerce_product_add_to_cart_text',
			[ $text, $this ],
			'2.0.0',
			'learndash_woocommerce_product_add_to_cart_text'
		);

		/**
		 * Filters the add to cart button text.
		 *
		 * @since 5.2.0
		 *
		 * @param string $text    The add to cart button text.
		 * @param self   $product The product object.
		 *
		 * @return string The add to cart button text.
		 */
		return apply_filters( 'learndash_woocommerce_product_add_to_cart_text', $text, $this );
	}

	/**
	 * Gets the add to cart button URL used on the shop page.
	 *
	 * @since 5.2.0
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		$url = $this->is_purchasable() && $this->is_in_stock()
			? remove_query_arg(
				'added-to-cart',
				add_query_arg(
					'add-to-cart',
					$this->get_id()
				)
			)
			: get_permalink( $this->get_id() );

		// Kept for backward compatibility.
		$url = apply_filters_deprecated(
			'woocommerce_product_add_to_cart_url',
			[ $url, $this ],
			'2.0.0',
			'learndash_woocommerce_product_add_to_cart_url'
		);

		/**
		 * Filters the add to cart button URL.
		 *
		 * @since 5.2.0
		 *
		 * @param string $url     The add to cart button URL.
		 * @param self   $product The product object.
		 *
		 * @return string The add to cart button URL.
		 */
		return apply_filters( 'learndash_woocommerce_product_add_to_cart_url', $url, $this );
	}
}
