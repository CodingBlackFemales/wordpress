<?php
/**
 * PayPal Standard Migration Admin Pagination class.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Migration\Admin;

use LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Migration\Subscriptions;
use LearnDash\Core\App;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Template\Template;
use LearnDash_Settings_Section;
use StellarWP\Learndash\StellarWP\Arrays\Arr;

/**
 * PayPal Standard Migration Admin Pagination class.
 *
 * @since 4.25.3
 */
class Pagination {
	/**
	 * Handles the PayPal Standard migration table pagination AJAX request.
	 *
	 * @since 4.25.3
	 *
	 * @return void
	 */
	public function ajax_table_pagination(): void {
		check_ajax_referer( 'paypal_standard_migration', 'nonce' );

		$subscriptions = App::get( Subscriptions::class );

		if ( ! $subscriptions instanceof Subscriptions ) {
			wp_send_json_error( __( 'Error fetching subscriptions.', 'learndash' ) );
		}

		$include_migrated = 'on' === Cast::to_string( SuperGlobals::get_post_var( 'include_migrated', 'off' ) );
		$page             = Cast::to_int( SuperGlobals::get_post_var( 'page', 1 ) );
		$per_page         = 10; // Default items per page.

		$paypal_settings = array_filter(
			Arr::wrap(
				LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal' )
			)
		);

		$is_sandbox = 'yes' === Cast::to_string( Arr::get( $paypal_settings, 'paypal_sandbox', 'no' ) );

		$paypal_account_link = $is_sandbox
			? 'https://www.sandbox.paypal.com/billing/subscriptions/'
			: 'https://www.paypal.com/billing/subscriptions/';

		Template::show_admin_template(
			'modules/payments/gateways/paypal-standard/current-subscriptions',
			[
				'subscriptions'       => $subscriptions->get_current_subscriptions( $page, $per_page, $include_migrated ),
				'current_page'        => $page,
				'total_items'         => $subscriptions->get_total_subscriptions( $include_migrated ),
				'per_page'            => $per_page,
				'paypal_account_link' => $paypal_account_link,
			]
		);
		exit;
	}
}
