<?php
/**
 * LearnDash PayPal Standard Gateway Assets.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard;

use StellarWP\Learndash\StellarWP\Assets\Asset;
use StellarWP\Learndash\StellarWP\Assets\Assets as Base_Assets;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash\Core\App;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Token;
use WP_Screen;

/**
 * PayPal Standard gateway assets class.
 *
 * @since 4.25.3
 */
class Assets {
	/**
	 * Asset group to register our Assets to and enqueue from.
	 *
	 * @since 4.25.3
	 *
	 * @var string
	 */
	public static string $group = 'learndash-paypal-standard';

	/**
	 * Registers admin assets to the asset group.
	 *
	 * @since 4.25.3
	 *
	 * @param WP_Screen $current_screen The current screen object.
	 *
	 * @return void
	 */
	public function register_admin_assets( WP_Screen $current_screen ): void {
		Asset::add(
			'ld-paypal-standard-migration',
			'admin/modules/payments/gateways/paypal-standard/migration.js',
			LEARNDASH_SCRIPT_VERSION_TOKEN
		)
			->add_to_group( self::$group )
			->set_dependencies( 'jquery', 'wp-i18n' )
			->enqueue_on( 'admin_enqueue_scripts', 10 )
			->set_condition( fn() => $this->is_settings_page( $current_screen ) )
			->add_localize_script(
				'learndash.paypal_standard_admin_migration',
				[
					'nonce' => wp_create_nonce( 'paypal_standard_migration' ),
				]
			)
			->register();
	}

	/**
	 * Enqueues admin assets registered to the asset group.
	 *
	 * @since 4.25.3
	 *
	 * @return void
	 */
	public function enqueue_admin_assets(): void {
		Base_Assets::instance()->enqueue_group( self::$group );
	}

	/**
	 * Registers public assets.
	 *
	 * @since 4.25.3
	 *
	 * @return void
	 */
	public function register_public_assets(): void {
		$payment_token = App::get( Payment_Token::class );

		if (
			! $payment_token instanceof Payment_Token
			|| ! is_user_logged_in()
		) {
			return;
		}

		if ( $this->is_sandbox() ) {
			$payment_token->use_sandbox();
		} else {
			$payment_token->use_production();
		}

		// Add PayPal Checkout SDK script for the migration.
		Asset::add(
			'ld-paypal-checkout-sdk-migration',
			$this->get_sdk_url(),
			'none'
		)
			->add_to_group( self::$group )
			->set_type( 'js' )
			->register();

		Asset::add(
			'ld-paypal-checkout-fraudnet',
			'https://c.paypal.com/da/r/fb.js',
		)
			->add_to_group( self::$group )
			->set_type( 'js' )
			->register();

		Asset::add(
			'ld-paypal-standard-migration-public',
			'modules/payments/gateways/paypal-standard/migration.js',
			LEARNDASH_SCRIPT_VERSION_TOKEN
		)
			->add_to_group( self::$group )
			->set_type( 'js' )
			->set_dependencies( 'ld-paypal-checkout-sdk-migration', 'wp-i18n' )
			->add_localize_script(
				'learndash.paypal_standard_migration',
				[
					'endpoints'   => [
						'setup_token'   => rest_url( 'learndash/v1/commerce/paypal-standard/migration/setup-token' ),
						'payment_token' => rest_url( 'learndash/v1/commerce/paypal-standard/migration/payment-token' ),
					],
					'is_sandbox'  => $this->is_sandbox(),
					'user_id'     => get_current_user_id(),
					'customer_id' => Cast::to_string( $payment_token->get_user_customer_id( get_current_user_id() ) ),
					'nonce'       => wp_create_nonce( 'wp_rest' ),
				]
			)
			->register();
	}

	/**
	 * Enqueues public assets registered to the asset group.
	 *
	 * @since 4.25.3
	 *
	 * @return void
	 */
	public function enqueue_public_assets(): void {
		Base_Assets::instance()->enqueue_group( self::$group );
	}

	/**
	 * Returns true if we're on the LearnDash LMS > Settings page > Payments > PayPal Standard section.
	 *
	 * @since 4.25.3
	 *
	 * @param WP_Screen $current_screen The current screen object.
	 *
	 * @return bool
	 */
	private function is_settings_page( WP_Screen $current_screen ): bool {
		return $current_screen->id === 'admin_page_learndash_lms_payments'
			&& Cast::to_string( SuperGlobals::get_var( 'section-payment' ) ) === 'settings_paypal';
	}

	/**
	 * Returns the PayPal Checkout SDK URL.
	 *
	 * @since 4.25.3
	 *
	 * @return string PayPal Checkout SDK URL.
	 */
	private function get_sdk_url(): string {
		$settings = Payment_Gateway::get_settings();

		/**
		 * PayPal Checkout SDK query parameters.
		 *
		 * @link https://developer.paypal.com/sdk/js/configuration/#query-parameters
		 */
		return add_query_arg(
			[
				'client-id'       => Cast::to_string( Arr::get( $settings, 'client_id', '' ) ),
				'merchant-id'     => Cast::to_string( Arr::get( $settings, 'account_id', '' ) ),
				'components'      => 'card-fields',
				'intent'          => 'capture',
				'disable-funding' => 'credit', // Not supported in all countries.
				'currency'        => strtoupper( learndash_get_currency_code() ),
			],
			'https://www.paypal.com/sdk/js'
		);
	}

	/**
	 * Returns true if the PayPal Standard is in sandbox mode.
	 *
	 * @since 4.25.3
	 *
	 * @return bool
	 */
	private function is_sandbox(): bool {
		$settings = Payment_Gateway::get_settings();

		return Cast::to_bool( Arr::get( $settings, 'test_mode', false ) );
	}
}
