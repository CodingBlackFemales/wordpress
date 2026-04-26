<?php
/**
 * Connected notice for PayPal checkout integration.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Admin;

use LearnDash\Core\Template\Template;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;
use StellarWP\Learndash\StellarWP\Arrays\Arr;

/**
 * Connected notice class.
 *
 * @since 4.25.0
 */
class Connected {
	/**
	 * Registers the connected message.
	 *
	 * @since 4.25.0
	 *
	 * @param string $settings_section_key Settings section key.
	 * @param string $settings_screen_id   Settings screen ID.
	 *
	 * @return void
	 */
	public function render_connected_message( string $settings_section_key, string $settings_screen_id ): void {
		if (
			'settings_paypal_checkout' !== $settings_section_key
			|| 'admin_page_learndash_lms_payments' !== $settings_screen_id
		) {
			return;
		}

		if ( ! Cast::to_bool( SuperGlobals::get_get_var( 'connected', false ) ) ) {
			return;
		}

		$settings = Payment_Gateway::get_settings();

		Template::show_admin_template(
			'modules/payments/gateways/paypal/connected-message',
			[
				'url'       => admin_url( 'admin.php?page=learndash_lms_payments&section-payment=settings_paypal_checkout' ),
				'test_mode' => Cast::to_bool( Arr::get( $settings, 'test_mode', false ) ),
			]
		);
	}
}
