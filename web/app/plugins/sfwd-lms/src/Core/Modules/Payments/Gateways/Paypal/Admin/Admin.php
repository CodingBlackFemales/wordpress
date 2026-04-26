<?php
/**
 * Admin class for PayPal Checkout.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Admin;

use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;

/**
 * Class Admin.
 *
 * @since 4.25.0
 */
class Admin {
	/**
	 * Checks if the current page is the PayPal Checkout settings page and stop
	 * showing the Stripe Connect banner.
	 *
	 * @since 4.25.0
	 *
	 * @param bool $is_on_payments_setting_page Whether the current page is the payments settings page.
	 *
	 * @return bool
	 */
	public function hide_stripe_connect_banner( bool $is_on_payments_setting_page ): bool {
		return $is_on_payments_setting_page
			&& SuperGlobals::get_get_var( 'section-payment' ) !== 'settings_paypal_checkout';
	}

	/**
	 * Hides the telemetry modal on PayPal onboarding via setup wizard.
	 *
	 * @since 4.25.3
	 *
	 * @param bool $should_show Whether to show the telemetry modal.
	 *
	 * @return bool Whether to show the telemetry modal.
	 */
	public function hide_telemetry_modal_on_paypal_onboarding_via_setup_wizard( bool $should_show ): bool {
		// Prevent telemetry modal from showing on PayPal onboarding via setup wizard.

		if (
			Cast::to_string( SuperGlobals::get_get_var( 'page' ) ) === 'learndash_lms_payments'
			&& Cast::to_string( SuperGlobals::get_get_var( 'section-payment' ) ) === 'settings_paypal_checkout'
			&& Cast::to_string( SuperGlobals::get_get_var( 'setup-wizard' ) ) === '1'
		) {
			return false;
		}

		return $should_show;
	}
}
