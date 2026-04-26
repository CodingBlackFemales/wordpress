<?php
/**
 * LearnDash PayPal Order Data Builder.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal;

use Learndash_Pricing_DTO;
use LearnDash\Core\Models\Product;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Traits\Data_Builder;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;
use WP_User;

/**
 * PayPal Order Data Builder.
 *
 * @since 4.25.0
 */
class Order_Data {
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
	 *
	 * @param Payment_Gateway $gateway Payment gateway instance.
	 *
	 * @return void
	 */
	public function __construct( Payment_Gateway $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Creates order data for PayPal.
	 *
	 * @since 4.25.0
	 *
	 * @param array<int> $product_ids           Product IDs.
	 * @param WP_User    $user                  User instance.
	 * @param bool       $use_card_fields       Whether to use card fields.
	 * @param bool       $describe_billing_plan Whether to describe the billing plan.
	 *
	 * @return array{
	 *     reference_id: string,
	 *     custom_id: string,
	 *     description: string,
	 *     currency_code: string,
	 *     amount: string,
	 *     merchant_id: string,
	 *     return_url: string,
	 *     cancel_url: string,
	 *     items: array<int,array{
	 *         name: string,
	 *         quantity: int,
	 *         unit_amount: array{
	 *             currency_code: string,
	 *             value: string,
	 *         },
	 *         billing_plan?: array{
	 *             billing_cycles: array<int,array{
	 *                 tenure_type: string,
	 *                 sequence: int,
	 *                 total_cycles: int,
	 *                 frequency: array{
	 *                     interval_unit: string,
	 *                     interval_count: int,
	 *                 },
	 *                 pricing_scheme: array{
	 *                     pricing_model: string,
	 *                     price: array{
	 *                         currency_code: string,
	 *                         value: string,
	 *                     },
	 *                 },
	 *                 start_date?: string,
	 *             }>,
	 *             name: string,
	 *         },
	 *     }>,
	 *     first_name: string,
	 *     last_name: string,
	 *     email: string,
	 * }
	 */
	public function build(
		array $product_ids,
		WP_User $user,
		bool $use_card_fields = false,
		bool $describe_billing_plan = true
	): array {
		$amount     = 0;
		$items      = [];
		$names      = [];
		$products   = [];
		$settings   = Payment_Gateway::get_settings();
		$return_url = Cast::to_string( Arr::get( $settings, 'return_url', '' ) );

		foreach ( $product_ids as $product_id ) {
			$product = Product::find( $product_id );

			if ( ! $product ) {
				continue;
			}

			$pricing         = $product->get_pricing( $user );
			$price           = $product->get_final_price( $user );
			$has_trial       = $product->is_price_type_subscribe()
				&& ! empty( $pricing->trial_duration_value )
				&& ! empty( $pricing->trial_duration_length );
			$has_trial_price = $pricing->trial_price > 0;

			// Skip free trials - they will use a different API.
			if (
				$has_trial
				&& ! $has_trial_price
				&& $describe_billing_plan
			) {
				continue;
			}

			$unit_amount = $price;

			if (
				$has_trial_price
				&& $describe_billing_plan
			) {
				// Paid trial - use trial price as unit amount.
				$unit_amount = $pricing->trial_price;
			}

			$amount += $unit_amount;

			$item = [
				'name'        => $this->trim_text( $product->get_title() ),
				'quantity'    => 1,
				'unit_amount' => [
					'currency_code' => $pricing->currency,
					'value'         => $this->format_price( $unit_amount ),
				],
			];

			// Add billing plan for subscription products.
			if (
				$product->is_price_type_subscribe()
				&& ! $use_card_fields
				&& $describe_billing_plan
			) {
				$item['billing_plan'] = $this->create_billing_plan( $pricing, $product );
			}

			$items[]    = $item;
			$names[]    = $product->get_title();
			$products[] = $product;
		}

		$reference_id = $this->gateway->create_order_reference_id( $user->ID, $product_ids );

		// Store the reference ID in the user meta.
		$this->gateway->update_user_reference_id_data(
			$user->ID,
			$reference_id,
			[
				'product_ids' => $product_ids,
			]
		);

		return [
			'reference_id'  => $reference_id,
			'custom_id'     => Cast::to_string(
				wp_json_encode(
					[
						'user_id'     => $user->ID,
						'product_ids' => $product_ids,
						'ld_version'  => LEARNDASH_VERSION,
					]
				)
			),
			'description'   => $this->trim_text( implode( ', ', $names ) ),
			'currency_code' => mb_strtoupper( learndash_get_currency_code() ),
			'amount'        => $this->format_price( $amount ),
			'merchant_id'   => Cast::to_string( Arr::get( $settings, 'account_id', '' ) ),
			'return_url'    => Payment_Gateway::get_url_success( $products, $return_url ),
			'cancel_url'    => Payment_Gateway::get_url_fail( $products, $return_url ),
			'items'         => $items,
			'first_name'    => $user->first_name,
			'last_name'     => $user->last_name,
			'email'         => $user->user_email,
		];
	}

	/**
	 * Creates billing plan data for subscription products.
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
	 *         pricing_scheme: array{
	 *             pricing_model: string,
	 *             price: array{
	 *                 currency_code: string,
	 *                 value: string,
	 *             },
	 *         },
	 *         start_date?: string,
	 *     }>,
	 *     name: string,
	 * } Billing plan data.
	 */
	protected function create_billing_plan( Learndash_Pricing_DTO $pricing, Product $product ): array {
		$billing_cycles = [];

		$has_paid_trial = ! empty( $pricing->trial_duration_value )
			&& ! empty( $pricing->trial_duration_length )
			&& $pricing->trial_price > 0;

		// Add trial billing cycle if trial exists (paid trials only).
		if ( $has_paid_trial ) {
			// Paid trial as first cycle.
			$billing_cycles[] = [
				'tenure_type'    => 'REGULAR',
				'sequence'       => 1,
				'total_cycles'   => 1,
				'frequency'      => [
					'interval_unit'  => $this->get_frequency_interval_unit( $pricing->trial_duration_length ),
					'interval_count' => $pricing->trial_duration_value,
				],
				'pricing_scheme' => [
					'pricing_model' => 'FIXED',
					'price'         => [
						'currency_code' => $pricing->currency,
						'value'         => $this->format_price( $pricing->trial_price ),
					],
				],
			];
		}

		// Add regular billing cycle.
		$regular_cycle = [
			'tenure_type'    => 'REGULAR',
			'sequence'       => $has_paid_trial ? 2 : 1,
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

		// Set start date for paid trials.
		if ( $has_paid_trial ) {
			$start_date = $this->calculate_start_date( $pricing->trial_duration_value, $pricing->trial_duration_length );

			$regular_cycle['start_date'] = $start_date->format( 'Y-m-d' );
		}

		$billing_cycles[] = $regular_cycle;

		$billing_plan = [
			'billing_cycles' => $billing_cycles,
			'name'           => $this->trim_text(
				// Remove accents from the name since PayPal doesn't support them.
				remove_accents(
					$product->get_title()
				)
			),
		];

		return $billing_plan;
	}
}
