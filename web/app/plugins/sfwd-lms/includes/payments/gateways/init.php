<?php
/**
 * LearnDash payment gateways.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LEARNDASH_GATEWAYS_PATH = LEARNDASH_LMS_PLUGIN_DIR . 'includes/payments/gateways/';
require_once LEARNDASH_GATEWAYS_PATH . 'class-learndash-payment-gateway.php';

// Requires all gateways. Please don't forget to create an instance of the gateways below.
require_once LEARNDASH_GATEWAYS_PATH . 'class-learndash-unknown-gateway.php';
require_once LEARNDASH_GATEWAYS_PATH . 'class-learndash-paypal-ipn-gateway.php';
require_once LEARNDASH_GATEWAYS_PATH . 'class-learndash-stripe-gateway.php';
require_once LEARNDASH_GATEWAYS_PATH . 'class-learndash-razorpay-gateway.php';

add_action(
	'init',
	function () {
		/**
		 * Filters the list of payment gateways.
		 *
		 * @since 4.5.0
		 *
		 * @param Learndash_Payment_Gateway[] $gateways List of payment gateway instances.
		 *
		 * @return Learndash_Payment_Gateway[] List of payment gateway instances.
		 */
		$gateways = apply_filters(
			'learndash_payment_gateways',
			array(
				// gateways instances initialization.
				new Learndash_Unknown_Gateway(),
				new Learndash_Paypal_IPN_Gateway(),
				new Learndash_Stripe_Gateway(),
				new Learndash_Razorpay_Gateway(),
			)
		);

		foreach ( $gateways as $gateway ) {
			if ( ! $gateway instanceof Learndash_Payment_Gateway ) {
				continue;
			}

			$gateway->init();
		}
	}
);
