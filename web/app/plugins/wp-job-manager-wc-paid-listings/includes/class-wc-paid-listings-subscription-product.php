<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPJM Subscription Product
 */
class WP_Job_Manager_WCPL_Subscription_Product extends WC_Product_Subscription {
	/**
	 * Compatibility function for `get_id()` method
	 *
	 * @return int
	 */
	public function get_id() {
		if ( WC_Paid_Listings::is_woocommerce_pre( '3.0.0' ) ) {
			return $this->id;
		}
		return parent::get_id();
	}

	/**
	 * Get product id
	 *
	 * @return int
	 */
	public function get_product_id() {
		return $this->get_id();
	}

	/**
	 * Compatibility function to retrieve product meta.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get_product_meta( $key ) {
		if ( WC_Paid_Listings::is_woocommerce_pre( '3.0.0' ) ) {
			return $this->{$key};
		}
		return $this->get_meta( '_' . $key );
	}

	/**
	 * We want to sell jobs one at a time
	 *
	 * @return boolean
	 */
	public function is_sold_individually() {
		return true;
	}

	/**
	 * Jobs are always virtual
	 *
	 * @return boolean
	 */
	public function is_virtual() {
		return true;
	}

	/**
	 * Job products are downloadable so orders don't require manual processing
	 *
	 * @return boolean
	 */
	public function is_downloadable() {
		return true;
	}

}
