<?php
/**
 * Deprecated WC course product type class file.
 *
 * @since 5.2.0
 * @deprecated 5.2.0
 *
 * @package LearnDash\WooCommerce\Deprecated
 */

_deprecated_file(
	__FILE__,
	'2.0.0',
	esc_html(
		LEARNDASH_WOOCOMMERCE_PLUGIN_PATH . 'src/App/Models/Products/Course.php'
	)
);

/**
 * Deprecated course product type class.
 *
 * Kept for backward compatibility if in any case the class is referred directly.
 *
 * @since 5.2.0
 * @deprecated 5.2.0 Use LearnDash\WooCommerce\Models\Products\Course instead.
 */
class WC_Product_Course extends WC_Product { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound, Generic.Classes.OpeningBraceSameLine.ContentAfterBrace -- Deprecated class.
	/**
	 * Initializes course product.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 *
	 * @param mixed $product WC Product data.
	 */
	public function __construct( $product ) {
		_deprecated_class(
			__CLASS__,
			'2.0.0',
			'LearnDash\WooCommerce\Models\Products\Course'
		);

		_deprecated_constructor(
			__CLASS__,
			'2.0.0'
		);

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
	 * @deprecated 5.2.0
	 *
	 * @return string
	 */
	public function get_type() {
		_deprecated_function(
			__METHOD__,
			'2.0.0',
			'LearnDash\WooCommerce\Models\Products\Course::get_type'
		);

		return 'course';
	}

	/**
	 * Gets the add to cart button text.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		_deprecated_function(
			__METHOD__,
			'2.0.0',
			'LearnDash\WooCommerce\Models\Products\Course::add_to_cart_text'
		);

		$text = $this->is_purchasable()
			? __( 'Add to cart', 'learndash' )
			: __( 'Read More', 'learndash' );

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Deprecated hook.
		return apply_filters( 'woocommerce_product_add_to_cart_text', $text, $this );
	}

	/**
	 * Gets the add to cart button URL used on the shop page.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		_deprecated_function(
			__METHOD__,
			'2.0.0',
			'LearnDash\WooCommerce\Models\Products\Course::add_to_cart_url'
		);

		$url = $this->is_purchasable() && $this->is_in_stock()
			? remove_query_arg(
				'added-to-cart',
				add_query_arg( 'add-to-cart', $this->get_id() )
			)
			: get_permalink( $this->get_id() );

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Deprecated hook.
		return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
	}
}
