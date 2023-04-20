<?php
/**
 * Deprecated functions from LD 4.1.0
 * The functions will be removed in a later version.
 *
 * @package LearnDash\Deprecated
 * @since 4.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'learndash_30_the_currency_symbol' ) ) {


	/**
	 * Outputs the currency symbol.
	 *
	 * @deprecated 4.1.0 Please use {@see 'learndash_the_currency_symbol'} instead.
	 *
	 * @since 3.0.0
	 */
	function learndash_30_the_currency_symbol() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '4.1.0', 'learndash_the_currency_symbol' );
		}

		echo wp_kses_post( learndash_30_get_currency_symbol() );
	}
}

if ( ! function_exists( 'learndash_30_get_currency_symbol' ) ) {
	/**
	 * Gets the currency symbol.
	 *
	 * @deprecated 4.1.0 Please use {@see 'learndash_get_currency_symbol'} instead.
	 *
	 * @since 3.0.0
	 *
	 * @return string|false Returns currency symbol.
	 */
	function learndash_30_get_currency_symbol() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '4.1.0', 'learndash_get_currency_symbol' );
		}

		$currency = '';

		$options         = get_option( 'sfwd_cpt_options' );
		$stripe_settings = get_option( 'learndash_stripe_settings' );

		if ( class_exists( 'LearnDash_Settings_Section' ) ) {
			$paypal_enabled          = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_PayPal', 'enabled' );
			$paypal_currency         = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_PayPal', 'paypal_currency' );
			$stripe_connect_enabled  = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Stripe_Connect', 'enabled' );
			$stripe_connect_currency = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Stripe_Connect', 'currency' );
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active( 'learndash-stripe/learndash-stripe.php' ) && ! empty( $stripe_settings ) && ! empty( $stripe_settings['currency'] ) ) {
			$currency = $stripe_settings['currency'];
		} elseif ( isset( $paypal_enabled ) && $paypal_enabled && ! empty( $paypal_currency ) ) {
			$currency = $paypal_currency;
		} elseif ( isset( $stripe_connect_enabled ) && $stripe_connect_enabled && ! empty( $stripe_connect_currency ) ) {
			$currency = $stripe_connect_currency;
		} elseif ( isset( $options['modules'] ) && isset( $options['modules']['sfwd-courses_options'] ) && isset( $options['modules']['sfwd-courses_options']['sfwd-courses_paypal_currency'] ) ) {
			$currency = $options['modules']['sfwd-courses_options']['sfwd-courses_paypal_currency'];
		}

		if ( class_exists( 'NumberFormatter' ) ) {
			$locale        = get_locale();
			$number_format = new NumberFormatter( $locale . '@currency=' . $currency, NumberFormatter::CURRENCY );
			$currency      = $number_format->getSymbol( NumberFormatter::CURRENCY_SYMBOL );
		}

		return $currency;

	}
}
