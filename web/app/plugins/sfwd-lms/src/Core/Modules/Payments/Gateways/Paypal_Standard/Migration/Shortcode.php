<?php
/**
 * PayPal Standard Migration Shortcode.
 *
 * @since 4.25.3
 * @version 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Migration;

use LearnDash\Core\Template\Template;
use LearnDash\Core\App;
use LearnDash\Core\Utilities\Countries;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Models\Product;

/**
 * PayPal Standard Migration Shortcode class.
 *
 * @since 4.25.3
 */
class Shortcode {
	/**
	 * Outputs the migration shortcode.
	 *
	 * @since 4.25.3
	 *
	 * @return void
	 */
	public function output(): void {
		$settings = Payment_Gateway::get_settings();

		// If the migration was not successful, show the migration template.
		$migration_data = User_Data::get_migration_data(
			get_current_user_id(),
			1 === Cast::to_int( Arr::get( $settings, 'test_mode', 0 ) )
		);

		// If the migration was successful, show the success template.
		if (
			SuperGlobals::get_var( 'migration_successful', false )
			|| Arr::has( $migration_data, 'status' )
		) {
			Template::show_template(
				'components/payments/paypal-standard/shortcodes/migration/success'
			);

			return;
		}

		// If the migration was not successful, show the migration template.
		$subscriptions = App::get( Subscriptions::class );

		if ( ! $subscriptions instanceof Subscriptions ) {
			return;
		}

		$product_ids = $subscriptions->get_user_subscribed_product_ids( get_current_user_id() );

		if ( empty( $product_ids ) ) {
			return;
		}

		$products = Product::find_many( $product_ids );

		$countries = [
			'' => sprintf(
				'&ndash; %s &ndash;',
				esc_html__( 'Select a country', 'learndash' )
			),
		] + Countries::get_all();

		Template::show_template(
			'components/payments/paypal-standard/shortcodes/migration',
			[
				'countries' => $countries,
				'products'  => $products,
			]
		);

		/**
		 * Fires after the PayPal Standard migration shortcode.
		 *
		 * @since 4.25.3
		 *
		 * @param int[] $product_ids The product IDs.
		 */
		do_action( 'learndash_paypal_standard_migration_shortcode_after', $product_ids );
	}
}
