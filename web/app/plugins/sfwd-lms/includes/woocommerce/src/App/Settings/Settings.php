<?php
/**
 * Settings class file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

namespace LearnDash\WooCommerce\Settings;

use LearnDash\WooCommerce\Admin\Pages\Sections\Settings_Enrollment_Status;

/**
 * Settings class.
 *
 * The class requires to be used in a function hooked to the `learndash_loaded` action
 * * at the earliest as it uses the LearnDash settings and sections API.
 *
 * @since 5.2.0
 */
class Settings {
	/**
	 * Get order or subscription statuses by access statuses specified in the settings.
	 *
	 * @since 5.2.0
	 *
	 * @param string $type    'order' or 'subscription'.
	 * @param bool   $granted Whether to get statuses for access granted or denied.
	 *
	 * @return array<string, string>
	 */
	public static function get_statuses_by_access_status( string $type, bool $granted ): array {
		if ( $type === 'order' ) {
			$checked_statuses = wc_get_order_statuses();
			$prefix           = Settings_Enrollment_Status::$order_setting_prefix;
		} elseif (
			$type === 'subscription'
			&& function_exists( 'wcs_get_subscription_statuses' )
		) {
			$checked_statuses = wcs_get_subscription_statuses();
			$prefix           = Settings_Enrollment_Status::$subscription_setting_prefix;
		} else {
			return [];
		}

		$statuses         = [];
		$expected_setting = $granted ? 'on' : '';

		foreach ( $checked_statuses as $key => $label ) {
			$setting = Settings_Enrollment_Status::get_setting(
				$prefix . $key
			);

			if ( $setting !== $expected_setting ) {
				continue;
			}

			// Remove 'wc-' prefix from the status key.
			$key = str_replace( 'wc-', '', $key );

			$statuses[ $key ] = $label;
		}

		return $statuses;
	}
}
