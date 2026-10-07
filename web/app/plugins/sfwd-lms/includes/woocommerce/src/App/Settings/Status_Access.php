<?php
/**
 * Status access settings class file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

namespace LearnDash\WooCommerce\Settings;

/**
 * Status access settings class.
 *
 * The class requires to be used in a function hooked to the `learndash_loaded` action
 * at the earliest as it uses the LearnDash settings and sections API.
 *
 * @since 5.2.0
 */
class Status_Access {
	/**
	 * Get access denied order statuses specified in the settings.
	 *
	 * This method returns an array of order statuses in key label pairs.
	 * E.g. [
	 *   'cancelled' => 'Cancelled',
	 *   'failed' => 'Failed',
	 * ]
	 *
	 * @since 5.2.0
	 *
	 * @return array<string, string>
	 */
	public static function get_access_denied_order_statuses(): array {
		return Settings::get_statuses_by_access_status( 'order', false );
	}

	/**
	 * Get access granted subscription statuses specified in the settings.
	 *
	 * This method returns an array of subscription statuses in key label pairs.
	 * E.g. [
	 *   'active' => 'Active',
	 *   'on-hold' => 'On Hold',
	 * ]
	 *
	 * @since 5.2.0
	 *
	 * @return array<string, string>
	 */
	public static function get_access_granted_subscription_statuses(): array {
		return Settings::get_statuses_by_access_status( 'subscription', true );
	}

	/**
	 * Get access denied subscription statuses specified in the settings.
	 *
	 * This method returns an array of subscription statuses in key label pairs.
	 * E.g. [
	 *   'cancelled' => 'Cancelled',
	 *   'expired' => 'Expired',
	 * ]
	 *
	 * @since 5.2.0
	 *
	 * @return array<string, string>
	 */
	public static function get_access_denied_subscription_statuses(): array {
		return Settings::get_statuses_by_access_status( 'subscription', false );
	}

	/**
	 * Get access granted order statuses specified in the settings.
	 *
	 * This method returns an array of order statuses in key label pairs.
	 * E.g. [
	 *   'completed' => 'Completed',
	 *   'processing' => 'Processing',
	 * ]
	 *
	 * @since 5.2.0
	 *
	 * @return array<string, string>
	 */
	public static function get_access_granted_order_statuses(): array {
		return Settings::get_statuses_by_access_status( 'order', true );
	}

	/**
	 * Whether course access is kept when a subscription expires.
	 *
	 * Expiring is the one access denying status a store can opt out of, so it is read on its own
	 * rather than through the denied status list.
	 *
	 * @since 5.2.0
	 *
	 * @return bool True when access is kept on expiration, false when it is withdrawn.
	 */
	public static function is_access_removal_disabled_on_expiration(): bool {
		return 'yes' === get_option( 'learndash_woocommerce_disable_access_removal_on_expiration', 'no' );
	}
}
