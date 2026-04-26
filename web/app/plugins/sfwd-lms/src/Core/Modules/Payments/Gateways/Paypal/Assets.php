<?php
/**
 * LearnDash PayPal Payment Gateway Assets.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal;

use LearnDash\Core\App;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Client;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Token;
use LearnDash\Core\Themes\LD30\Shortcodes\Assets as LD30_Shortcodes_Assets;
use StellarWP\Learndash\StellarWP\Assets\Asset;
use StellarWP\Learndash\StellarWP\Assets\Assets as Base_Assets;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Countries;
use WP_Screen;

/**
 * PayPal payment gateway assets class.
 *
 * @since 4.25.0
 */
class Assets {
	/**
	 * Asset group to register our Assets to and enqueue from.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static string $group = 'learndash-paypal-checkout';

	/**
	 * Registers admin assets to the asset group.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_Screen $current_screen The current screen object.
	 *
	 * @return void
	 */
	public function register_admin_assets( WP_Screen $current_screen ): void {
		$settings = Payment_Gateway::get_settings();
		$that     = $this;

		Asset::add(
			'ld-paypal-checkout-partner',
			$this->get_partner_js_url(),
			LEARNDASH_SCRIPT_VERSION_TOKEN
		)
			->add_to_group( self::$group )
			->enqueue_on( 'admin_enqueue_scripts', 10 )
			->set_condition(
				static function () use ( $settings, $current_screen, $that ) {
					return ! Cast::to_bool( Arr::get( $settings, 'account_id', 0 ) )
						&& $that->is_settings_page( $current_screen );
				}
			)
			->register();

		Asset::add(
			'ld-paypal-checkout-admin',
			'admin/modules/payments/gateways/paypal/admin.js',
			LEARNDASH_SCRIPT_VERSION_TOKEN
		)
			->add_to_group( self::$group )
			->set_dependencies( 'ld-paypal-checkout-partner', 'jquery', 'wp-i18n' )
			->enqueue_on( 'admin_enqueue_scripts', 10 )
			->set_condition( fn() => $this->is_settings_page( $current_screen ) )
			->add_localize_script(
				'learndash.paypal_checkout.admin',
				[
					'endpoints'       => [
						'access_token' => rest_url( 'learndash/v1/commerce/paypal/onboarding/access_token' ),
						'disconnect'   => rest_url( 'learndash/v1/commerce/paypal/onboarding/disconnect' ),
						'reconnect'    => rest_url( 'learndash/v1/commerce/paypal/onboarding/reconnect' ),
						'signup_url'   => rest_url( 'learndash/v1/commerce/paypal/onboarding/signup_url' ),
					],
					'account_country' => Cast::to_string( Arr::get( $settings, 'account_country', 'US' ) ),
					'is_sandbox'      => Cast::to_bool( Arr::get( $settings, 'test_mode', 0 ) ),
					'is_setup_wizard' => Cast::to_bool( SuperGlobals::get_get_var( 'setup-wizard', 0 ) ),
					'settings_url'    => admin_url( 'admin.php?page=learndash_lms_payments&section-payment=settings_paypal_checkout' ),
				]
			)
			->register();
	}

	/**
	 * Enqueues admin assets registered to the asset group.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function enqueue_admin_assets(): void {
		Base_Assets::instance()->enqueue_group( self::$group );
	}

	/**
	 * Registers frontend assets.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function register_frontend_assets(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

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

		// Checkout scripts.
		Asset::add(
			'ld-paypal-checkout-sdk',
			$this->get_sdk_url(),
			'none'
		)
			->add_to_group( 'learndash-registration' ) // Use the same group as the modern registration assets.
			->set_type( 'js' )
			->register();

		Asset::add(
			'ld-paypal-checkout-fraudnet',
			'https://c.paypal.com/da/r/fb.js',
		)
			->add_to_group( 'learndash-registration' ) // Use the same group as the modern registration assets.
			->set_type( 'js' )
			->register();

		Asset::add(
			'ld-paypal-checkout-public',
			'modules/payments/gateways/paypal/checkout.js',
			LEARNDASH_SCRIPT_VERSION_TOKEN
		)
			->add_to_group( 'learndash-registration' ) // Use the same group as the modern registration assets.
			->set_dependencies( 'ld-paypal-checkout-sdk', 'wp-i18n' )
			->add_localize_script(
				'learndash.paypal_checkout',
				[
					'endpoints'                  => [
						'create_order'  => rest_url( 'learndash/v1/commerce/paypal/payments/order' ),
						'capture_order' => rest_url( 'learndash/v1/commerce/paypal/payments/capture' ),
						'confirm_order' => rest_url( 'learndash/v1/commerce/paypal/payments/confirm' ),
						'cancel_order'  => rest_url( 'learndash/v1/commerce/paypal/payments/cancel' ),
						'cards'         => rest_url( 'learndash/v1/commerce/paypal/payments/cards' ),
						'setup_token'   => rest_url( 'learndash/v1/commerce/paypal/payments/setup-token' ),
						'payment_token' => rest_url( 'learndash/v1/commerce/paypal/payments/payment-token' ),
						'start_trial'   => rest_url( 'learndash/v1/commerce/paypal/payments/start-trial' ),
					],
					'is_sandbox'                 => $this->is_sandbox(),
					'user_id'                    => get_current_user_id(),
					'requires_vault_setup_token' => $payment_token->requires_vault_setup_token(
						Cast::to_int( SuperGlobals::get_get_var( 'ld_register_id', 0 ) ),
						get_current_user_id()
					),
					'is_subscription_product'    => $payment_token->is_subscription_product(
						Cast::to_int( SuperGlobals::get_get_var( 'ld_register_id', 0 ) )
					),
					'customer_id'                => Cast::to_string( $payment_token->get_user_customer_id( get_current_user_id() ) ),
					'products'                   => [ Cast::to_int( SuperGlobals::get_get_var( 'ld_register_id', 0 ) ) ],
					'nonce'                      => wp_create_nonce( 'wp_rest' ),
				]
			)
			->register();

		// Success payment script.
		Asset::add(
			'ld-paypal-checkout-success-payment',
			'modules/payments/gateways/paypal/success-payment.js',
			LEARNDASH_SCRIPT_VERSION_TOKEN
		)
			->set_dependencies( 'wp-i18n' )
			->add_to_group( 'learndash-paypal-checkout' )
			->set_condition(
				static function () {
					return Cast::to_bool( SuperGlobals::get_get_var( 'ld_paypal_checkout_success', 0 ) );
				}
			)
			->enqueue_on( 'wp_footer', 10 )
			->add_localize_script(
				'learndash.paypal_checkout.success_payment',
				$this->get_success_payment_data()
			)
			->register();
	}

	/**
	 * Registers profile assets.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function register_profile_assets(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

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

		// Profile block scripts.
		Asset::add(
			'ld-paypal-card-manager',
			'modules/payments/gateways/paypal/card-manager.js',
			LEARNDASH_SCRIPT_VERSION_TOKEN
		)
			->add_to_group( LD30_Shortcodes_Assets::$group )
			->set_dependencies(
				'ld-paypal-checkout-sdk-card-manager',
				'learndash-ld30-shortcodes-script',
				'wp-i18n',
				'wp-hooks'
			)
			->set_condition(
				fn() => $this->can_accept_card_payments()
			)
			->enqueue_on( 'wp_footer', 10 )
			->add_localize_script(
				'learndash.paypal_card_manager',
				[
					'endpoints'   => [
						'setup_token'   => rest_url( 'learndash/v1/commerce/paypal/payments/setup-token' ),
						'payment_token' => rest_url( 'learndash/v1/commerce/paypal/payments/payment-token' ),
					],
					'is_sandbox'  => $this->is_sandbox(),
					'user_id'     => get_current_user_id(),
					'customer_id' => Cast::to_string( $payment_token->get_user_customer_id( get_current_user_id() ) ),
					'nonce'       => wp_create_nonce( 'wp_rest' ),
				]
			)
			->register();

		// Register the PayPal SDK for the card manager modal.
		Asset::add(
			'ld-paypal-checkout-sdk-card-manager',
			$this->get_sdk_url( true ),
			'none'
		)
			->add_to_group( LD30_Shortcodes_Assets::$group )
			->set_type( 'js' )
			->set_condition(
				fn() => $this->can_accept_card_payments()
			)
			->enqueue_on( 'wp_footer', 10 )
			->register();
	}

	/**
	 * Displays the FraudNet JSON.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function display_fraudnet_json(): void {
		$settings = Payment_Gateway::get_settings();

		/*
		 * Generate a random 32 character UUID.
		 *
		 * This code is inspired by the Stripe PHP library. It's an alternative
		 * to wp_generate_uuid4() which may lead to collisions since it uses
		 * mt_rand() instead of openssl_random_pseudo_bytes().
		 *
		 * @link https://developer.wordpress.org/reference/functions/wp_generate_uuid4/#comment-6070
		 */
		$random_bytes    = array_values(
			(array) unpack(
				'N1a/n4b/N1c',
				Cast::to_string( openssl_random_pseudo_bytes( 16 ) )
			)
		);
		$random_bytes[2] = ( $random_bytes[2] & 0x0FFF ) | 0x4000;
		$random_bytes[3] = ( $random_bytes[3] & 0x3FFF ) | 0x8000;

		Template::show_template(
			'components/payments/paypal/checkout/fraudnet',
			[
				'data' => [
					'f'       => vsprintf( '%08x%04x%04x%04x%04x%08x', $random_bytes ),
					's'       => sprintf(
						'%s_%s',
						Cast::to_string( Arr::get( $settings, 'account_id', '' ) ),
						get_the_ID()
					),
					'sandbox' => Cast::to_bool( Arr::get( $settings, 'test_mode', false ) ),
				],
			]
		);
	}

	/**
	 * Updates the PayPal Checkout SDK script attributes.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,mixed> $attributes Script attributes.
	 *
	 * @return array<string,mixed> Updated script attributes.
	 */
	public function update_sdk_script_attributes( array $attributes ): array {
		if (
			'ld-paypal-checkout-sdk-js' !== Arr::get( $attributes, 'id', '' )
			&& 'ld-paypal-checkout-sdk-card-manager-js' !== Arr::get( $attributes, 'id', '' )
			&& 'ld-paypal-checkout-sdk-migration-js' !== Arr::get( $attributes, 'id', '' )
		) {
			return $attributes;
		}

		$payment_token = App::get( Payment_Token::class );

		if ( ! $payment_token instanceof Payment_Token ) {
			return $attributes;
		}

		$client = App::get( Client::class );

		if ( ! $client instanceof Client ) {
			return $attributes;
		}

		if ( $this->is_sandbox() ) {
			$payment_token->use_sandbox();
			$client->use_sandbox();
		} else {
			$payment_token->use_production();
			$client->use_production();
		}

		$client_token_data = $client->get_client_token();

		if ( empty( $client_token_data ) ) {
			return $attributes;
		}

		$customer_id = $payment_token->get_user_customer_id( get_current_user_id() );

		if ( empty( $customer_id ) ) {
			$id_token_data = $client->get_first_time_payer_id_token();

			if ( is_wp_error( $id_token_data ) ) {
				return $attributes;
			}
		} else {
			$id_token_data = $client->get_id_token( $customer_id );

			if ( is_wp_error( $id_token_data ) ) {
				return $attributes;
			}
		}

		// Remove the version query because it breaks the PayPal SDK.
		$attributes['src'] = remove_query_arg( 'ver', Cast::to_string( Arr::get( $attributes, 'src', '' ) ) );

		// Add the PayPal Checkout SDK attributes.
		$attributes['data-partner-attribution-id']  = Payment_Gateway::get_partner_attribution_id();
		$attributes['data-client-token']            = Cast::to_string( Arr::get( $client_token_data, 'client_token', '' ) );
		$attributes['data-client-token-expires-in'] = Cast::to_string( Arr::get( $client_token_data, 'valid_until', 0 ) );
		$attributes['data-user-id-token']           = Cast::to_string( Arr::get( $id_token_data, 'id_token', '' ) );

		// Checkout specific attributes.
		if ( 'ld-paypal-checkout-sdk-js' === Arr::get( $attributes, 'id', '' ) ) {
			$attributes['data-page-type'] = 'checkout';
		}

		// Add class to the script tag.
		$attributes['class'] = 'ld-paypal-checkout-sdk';

		return $attributes;
	}

	/**
	 * Returns the URL of the partner.js file.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	private function get_partner_js_url(): string {
		$client = App::get( Client::class );

		if ( ! $client instanceof Client ) {
			return '';
		}

		$client->use_production(); // Specifically don't use the sandbox URL here.

		return sprintf(
			'%1$swebapps/merchantboarding/js/lib/lightbox/partner.js',
			$client->get_paypal_home_page_url()
		);
	}

	/**
	 * Returns the PayPal Checkout SDK URL.
	 *
	 * @since 4.25.0
	 *
	 * @param bool $is_card_manager Whether the SDK is being used for the card manager modal.
	 *
	 * @return string PayPal Checkout SDK URL.
	 */
	private function get_sdk_url( bool $is_card_manager = false ): string {
		$settings   = Payment_Gateway::get_settings();
		$methods    = (array) Arr::get( $settings, 'payment_methods', [] );
		$components = [];

		if ( in_array( 'paypal', $methods, true ) ) {
			$components[] = 'buttons';
		}

		if ( in_array( 'card', $methods, true ) ) {
			$components[] = 'card-fields';
		}

		// Show only the card fields component if the SDK is being used for the card manager modal.
		if ( $is_card_manager ) {
			$components = [ 'card-fields' ];
		}

		/**
		 * PayPal Checkout SDK query parameters.
		 *
		 * @link https://developer.paypal.com/sdk/js/configuration/#query-parameters
		 */
		return add_query_arg(
			[
				'client-id'       => Cast::to_string( Arr::get( $settings, 'client_id', '' ) ),
				'merchant-id'     => Cast::to_string( Arr::get( $settings, 'account_id', '' ) ),
				'components'      => implode( ',', $components ),
				'intent'          => 'capture',
				'disable-funding' => 'credit', // Not supported in all countries.
				'currency'        => strtoupper( learndash_get_currency_code() ),
			],
			'https://www.paypal.com/sdk/js'
		);
	}

	/**
	 * Returns true if we're on the LearnDash LMS > Settings page > Payments > PayPal Checkout section.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_Screen $current_screen The current screen object.
	 *
	 * @return bool
	 */
	private function is_settings_page( WP_Screen $current_screen ): bool {
		return $current_screen->id === 'admin_page_learndash_lms_payments'
			&& Cast::to_string( SuperGlobals::get_var( 'section-payment' ) ) === 'settings_paypal_checkout';
	}

	/**
	 * Returns the success payment data.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string, string>
	 */
	private function get_success_payment_data(): array {
		if ( ! Cast::to_bool( SuperGlobals::get_get_var( 'ld_paypal_checkout_success', 0 ) ) ) {
			return [];
		}

		$order_id = sanitize_text_field( Cast::to_string( SuperGlobals::get_get_var( 'pp_order_id', '' ) ) );

		$data = get_user_meta( get_current_user_id(), 'ld_paypal_checkout_order_id_' . $order_id, true );

		if ( ! empty( $data ) ) {
			return Arr::wrap( $data );
		}

		$client = App::get( Client::class );

		if ( ! $client instanceof Client ) {
			return [];
		}

		$settings = Payment_Gateway::get_settings();

		if ( Cast::to_bool( Arr::get( $settings, 'test_mode', false ) ) ) {
			$client->use_sandbox();
		} else {
			$client->use_production();
		}

		$order = $client->get_order( $order_id );

		if ( is_wp_error( $order ) ) {
			return [];
		}

		if ( Arr::has( $order, 'payment_source.card' ) ) {
			$payment_type = sprintf(
				// translators: %s: payment type.
				__( 'Card (%s)', 'learndash' ),
				Cast::to_string( Arr::get( $order, 'payment_source.card.brand', '' ) )
			);
		} else {
			$payment_type = __( 'PayPal', 'learndash' );
		}

		$data = [
			'payment_type'   => $payment_type,
			'transaction_id' => Cast::to_string( Arr::get( $order, 'id', '' ) ),
		];

		// Store the data in user meta to avoid making too many API calls.
		update_user_meta( get_current_user_id(), 'ld_paypal_checkout_order_id_' . $order_id, $data );

		return $data;
	}

	/**
	 * Returns true if the PayPal Checkout is in sandbox mode.
	 *
	 * @since 4.25.0
	 *
	 * @return bool
	 */
	private function is_sandbox(): bool {
		$settings = Payment_Gateway::get_settings();

		return Cast::to_bool( Arr::get( $settings, 'test_mode', false ) );
	}

	/**
	 * Returns true if the PayPal Checkout can accept card payments.
	 *
	 * @since 4.25.0
	 *
	 * @return bool
	 */
	private function can_accept_card_payments(): bool {
		$settings = Payment_Gateway::get_settings();

		return in_array(
			'card',
			(array) Arr::get( $settings, 'payment_methods', [] ),
			true
		);
	}

	/**
	 * Returns true if the PayPal Checkout gateway is enabled.
	 *
	 * @since 5.0.2
	 *
	 * @return bool
	 */
	private function is_enabled(): bool {
		$settings = Payment_Gateway::get_settings();

		return 'yes' === Arr::get( $settings, 'enabled', 'no' );
	}
}
