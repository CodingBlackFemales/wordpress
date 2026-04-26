<?php
/**
 * LearnDash PayPal Setup Token Data Builder.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal;

use Learndash_Pricing_DTO;
use LearnDash\Core\Models\Product;
use LearnDash\Core\App;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Token;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Traits\Data_Builder;
use WP_User;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;

/**
 * PayPal Setup Token Data Builder for Recurring Payments with Free Trials.
 *
 * This class is specifically designed for creating setup tokens that will be used
 * for recurring payments with free trial periods. Unlike Order_Data which handles
 * immediate payments, setup tokens are used to save payment methods for future
 * recurring charges after the free trial period ends.
 *
 * @since 4.25.0
 */
class Setup_Token_Data {
	use Data_Builder;

	/**
	 * Payment Gateway instance.
	 *
	 * @since 4.25.0
	 *
	 * @var Payment_Gateway
	 */
	protected Payment_Gateway $gateway;

	/**
	 * Constructor.
	 *
	 * @since 4.25.0
	 */
	public function __construct() {
		$gateway = App::get( Payment_Gateway::class );

		if ( ! $gateway instanceof Payment_Gateway ) {
			return;
		}

		$this->gateway = $gateway;
	}
	/**
	 * Creates setup token data for PayPal recurring payments with free trials.
	 *
	 * @since 4.25.0
	 *
	 * @param int     $product_id      Product ID.
	 * @param WP_User $user            User instance.
	 * @param bool    $use_card_fields Whether to use card fields.
	 *
	 * @return array{
	 *     payment_source?: array<string,mixed>,
	 *     customer_id?: string,
	 * } Returns setup token data array or empty array if no free trial is found.
	 */
	public function build( int $product_id, WP_User $user, bool $use_card_fields = false ): array {
		$settings     = Payment_Gateway::get_settings();
		$return_url   = Cast::to_string( Arr::get( $settings, 'return_url', '' ) );
		$product_name = __( 'Save card', 'learndash' );

		// If product_id is 0, it means we are saving a card for later use in the Profile block.
		if ( $product_id > 0 ) {
			$product = Product::find( $product_id );
			if (
				! $product
				|| ! $product->is_price_type_subscribe()
			) {
				return [];
			}

			$pricing = $product->get_pricing( $user );

			// Setup tokens are specifically for recurring payments with free trials.
			// If no free trial is found, return empty array as setup tokens are intended for recurring payments with free trials.
			if (
				empty( $pricing->trial_duration_value )
				|| empty( $pricing->trial_duration_length )
			) {
				return [];
			}

			$product_name = $product->get_title();
			$products     = [ $product ];
		} else {
			$products = [];
		}

		// Build payment source based on card fields usage.
		if ( $use_card_fields ) {
			$payment_source = [
				'card' => [
					'verification_method' => 'SCA_WHEN_REQUIRED',
					'usage_type'          => 'MERCHANT',
					'experience_context'  => [
						'return_url' => Payment_Gateway::get_url_success( $products, $return_url ),
						'cancel_url' => Payment_Gateway::get_url_fail( $products, $return_url ),
					],
				],
			];
		} else {
			$payment_source = [
				'paypal' => [
					'description'        => $this->trim_text( $product_name ),
					'usage_type'         => 'MERCHANT',
					'experience_context' => [
						'return_url' => Payment_Gateway::get_url_success( $products, $return_url ),
						'cancel_url' => Payment_Gateway::get_url_fail( $products, $return_url ),
					],
				],
			];

			if ( $product_id > 0 ) {
				$payment_source['paypal']['usage_pattern'] = 'SUBSCRIPTION_PREPAID';
				$payment_source['paypal']['billing_plan']  = $this->create_billing_plan( $pricing, $product );
			}
		}

		$setup_token_data = [
			'payment_source' => $payment_source,
		];

		// Add customer ID if available.
		$customer_id = $this->get_user_customer_id( $user->ID );
		if ( ! empty( $customer_id ) ) {
			$setup_token_data['customer_id'] = $customer_id;
		}

		return $setup_token_data;
	}

	/**
	 * Gets the customer ID for a user.
	 *
	 * @since 4.25.0
	 *
	 * @param int $user_id User ID.
	 *
	 * @return string Customer ID or empty string if not found.
	 */
	private function get_user_customer_id( int $user_id ): string {
		$payment_token = App::get( Payment_Token::class );

		if ( ! $payment_token instanceof Payment_Token ) {
			return '';
		}

		return Cast::to_string( $payment_token->get_user_customer_id( $user_id ) );
	}

	/**
	 * Creates billing plan data for setup tokens with free trial subscriptions.
	 *
	 * @since 4.25.0
	 *
	 * @param Learndash_Pricing_DTO $pricing Pricing DTO.
	 * @param Product               $product Product instance.
	 *
	 * @return array{
	 *     billing_cycles: array<int,array{
	 *         tenure_type: string,
	 *         sequence: int,
	 *         total_cycles: int,
	 *         frequency: array{
	 *             interval_unit: string,
	 *             interval_count: int,
	 *         },
	 *         pricing_scheme?: array{
	 *             pricing_model: string,
	 *             price: array{
	 *                 currency_code: string,
	 *                 value: string,
	 *             },
	 *         },
	 *         start_date?: string,
	 *     }>,
	 *     name: string,
	 *     one_time_charges: array{
	 *         total_amount: array{
	 *             currency_code: string,
	 *             value: string,
	 *         },
	 *     },
	 * } Billing plan data for setup tokens.
	 */
	private function create_billing_plan( Learndash_Pricing_DTO $pricing, Product $product ): array {
		$billing_cycles = [];

		// For setup tokens, we only handle free trials (not paid trials).
		// Setup tokens are specifically for recurring payments with free trials.
		$has_free_trial = ! empty( $pricing->trial_duration_value )
			&& ! empty( $pricing->trial_duration_length )
			&& $pricing->trial_price <= 0;

		// Add free trial billing cycle if trial exists.
		if ( $has_free_trial ) {
			// Free trial as first cycle.
			$billing_cycles[] = [
				'tenure_type'  => 'TRIAL',
				'sequence'     => 1,
				'total_cycles' => 1,
				'frequency'    => [
					'interval_unit'  => $this->get_frequency_interval_unit( $pricing->trial_duration_length ),
					'interval_count' => $pricing->trial_duration_value,
				],
			];
		}

		// Add regular billing cycle.
		$regular_cycle = [
			'tenure_type'    => 'REGULAR',
			'sequence'       => $has_free_trial ? 2 : 1,
			'total_cycles'   => $pricing->recurring_times,
			'frequency'      => [
				'interval_unit'  => $this->get_frequency_interval_unit( $pricing->duration_length ),
				'interval_count' => $pricing->duration_value,
			],
			'pricing_scheme' => [
				'pricing_model' => 'FIXED',
				'price'         => [
					'currency_code' => $pricing->currency,
					'value'         => $this->format_price( $pricing->price ),
				],
			],
		];

		// Set start date for free trials.
		if ( $has_free_trial ) {
			$start_date = $this->calculate_start_date( $pricing->trial_duration_value, $pricing->trial_duration_length );

			$regular_cycle['start_date'] = $start_date->format( 'Y-m-d' );
		}

		$billing_cycles[] = $regular_cycle;

		$billing_plan = [
			'billing_cycles'   => $billing_cycles,
			'name'             => $this->trim_text(
				// Remove accents from the name since PayPal doesn't support them.
				remove_accents(
					$product->get_title()
				)
			),
			'one_time_charges' => [
				'total_amount' => [
					'currency_code' => $pricing->currency,
					'value'         => $this->format_price( $pricing->trial_price ),
				],
			],
		];

		return $billing_plan;
	}
}
