<?php
/**
 * Updates the plugin details stored in transients used for auto-updates.
 *
 * @package Automattic\WooUpdateManager
 */

namespace Automattic\WooUpdateManager;

defined( 'ABSPATH' ) || exit;

/**
 * Class Woo_Subscription_Data_Updater
 */
class Woo_Subscription_Data_Updater {

	/**
	 * Load the hooks for replacing the package url in update_plugins and update_themes transients.
	 *
	 * @return void
	 */

	public static function load() {
		add_filter( 'update_woo_com_subscription_details', [ __CLASS__, 'update_plugin_package_url' ], 10, 3 );
	}

	/**
	 * Update the plugin/theme package url with the subscription data from Woo.
	 *
	 * @param array $item The plugin/theme update details.
	 * @param array $woo_subscription_data The subscription data from Woo.
	 * @param int   $product_id The Woo.com product ID.
	 * @return array The updated plugin/theme update details.
	 */
	public static function update_plugin_package_url( $item, $woo_subscription_data, $product_id ) {
		// We don't want to deliver a valid upgrade package when their subscription has expired.
		if ( ! self::has_active_subscription( $product_id ) ) {
			$item['package'] = 'woocommerce-com-expired-' . $product_id;
			return $item;
		}

		$item['package'] = $woo_subscription_data['package'];

		return $item;
	}

	/**
	 * Check for an active subscription.
	 *
	 * Checks a given product id against all subscriptions on
	 * the current site. Returns true if at least one active
	 * subscription is found.
	 *
	 * @param int $product_id The product id to look for.
	 *
	 * @return bool True if active subscription found.
	 */
	public static function has_active_subscription( $product_id ) {
		$auth          = \WC_Helper_Options::get( 'auth' );
		$subscriptions = \WC_Helper::get_subscriptions();

		if ( empty( $auth['site_id'] ) || empty( $subscriptions ) ) {
			return false;
		}

		// Check for an active subscription.
		foreach ( $subscriptions as $subscription ) {
			if ( $subscription['product_id'] != $product_id ) {
				continue;
			}

			if ( in_array( absint( $auth['site_id'] ), $subscription['connections'] ) ) {
				return true;
			}
		}

		return false;
	}
}

Woo_Subscription_Data_Updater::load();
