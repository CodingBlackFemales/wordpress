<?php
/**
 * PayPal Standard Migration Processor.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Migration;

use LearnDash\Core\App;
use LearnDash\Core\Models\Product;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Token;
use LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Migration\Subscriptions;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash_Settings_Section;
use WP_Error;
use WP_User;

/**
 * PayPal Standard Migration Processor.
 *
 * This class handles the migration of PayPal Standard subscriptions to PayPal Checkout.
 *
 * @since 4.25.3
 */
class Processor {
	/**
	 * Runs the migration for a specific subscription.
	 *
	 * @since 4.25.3
	 *
	 * @param int    $product_id       The product ID.
	 * @param int    $user_id          The user ID.
	 * @param string $payment_token_id The payment token ID.
	 *
	 * @return bool|WP_Error True if migration successful, WP_Error on failure.
	 */
	public function run_migration( int $product_id, int $user_id, string $payment_token_id ) {
		// Get the user.
		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return new WP_Error(
				'user_not_found',
				__( 'User not found.', 'learndash' )
			);
		}

		// Get the product.
		$product = Product::find( $product_id );

		if ( ! $product ) {
			return new WP_Error(
				'product_not_found',
				__( 'Product not found.', 'learndash' )
			);
		}

		$subscriptions = App::get( Subscriptions::class );

		if ( ! $subscriptions instanceof Subscriptions ) {
			return new WP_Error(
				'subscriptions_not_found',
				__( 'Subscriptions not found.', 'learndash' )
			);
		}

		$paypal_subscription_id = $subscriptions->get_paypal_subscription_id( $user_id, $product_id );

		// Get the PayPal Standard subscription ID from user meta.
		if ( empty( $paypal_subscription_id ) ) {
			return new WP_Error(
				'subscription_not_found',
				__( 'PayPal subscription not found.', 'learndash' )
			);
		}

		// Cancel the PayPal Standard subscription.
		$cancel_result = $this->cancel_paypal_standard_subscription( $paypal_subscription_id );

		if ( is_wp_error( $cancel_result ) ) {
			return $cancel_result;
		}

		// Create the new PayPal Checkout subscription.
		$create_result = $this->create_paypal_checkout_subscription( $product, $user, $payment_token_id );

		if ( is_wp_error( $create_result ) ) {
			return $create_result;
		}

		// Update the user migration data.
		User_Data::update_migrated_product_data(
			$user_id,
			$product_id,
			$this->is_paypal_standard_test_mode()
		);

		return true;
	}

	/**
	 * Cancels a PayPal Standard subscription using PayPal NVP API.
	 *
	 * @since 4.25.3
	 *
	 * @param string $subscription_id The PayPal Standard subscription ID.
	 *
	 * @return bool|WP_Error True if cancelled successfully, WP_Error on failure.
	 */
	private function cancel_paypal_standard_subscription( string $subscription_id ) {
		$client = App::get( Client::class );

		if ( ! $client instanceof Client ) {
			return new WP_Error(
				'client_not_found',
				__( 'PayPal Standard client not found.', 'learndash' )
			);
		}

		// Set the environment for the client.
		if ( $this->is_paypal_standard_test_mode() ) {
			$client->use_sandbox();
		} else {
			$client->use_production();
		}

		$api_keys = $this->get_paypal_standard_migration_api_keys();

		$result = $client->cancel_subscription(
			$subscription_id,
			$api_keys['api_username'],
			$api_keys['api_password'],
			$api_keys['api_signature']
		);

		if ( ! $result ) {
			return new WP_Error(
				'cancellation_failed',
				__( 'Failed to cancel PayPal Standard subscription.', 'learndash' )
			);
		}

		return true;
	}

	/**
	 * Creates a new PayPal Checkout subscription.
	 *
	 * @since 4.25.3
	 *
	 * @param Product $product          The product.
	 * @param WP_User $user            The user.
	 * @param string  $payment_token_id The payment token ID.
	 *
	 * @return bool|WP_Error True if created successfully, WP_Error on failure.
	 */
	private function create_paypal_checkout_subscription( Product $product, WP_User $user, string $payment_token_id ) {
		// Get the PayPal Checkout gateway.
		$gateway = App::get( Payment_Gateway::class );

		if ( ! $gateway instanceof Payment_Gateway ) {
			return new WP_Error(
				'gateway_not_found',
				__( 'PayPal Checkout gateway not found.', 'learndash' )
			);
		}

		$token_handler = App::get( Payment_Token::class );

		if ( ! $token_handler instanceof Payment_Token ) {
			return new WP_Error(
				'token_handler_not_found',
				__( 'PayPal Checkout token handler not found.', 'learndash' )
			);
		}

		$settings = Payment_Gateway::get_settings();

		// Set the environment for the token handler.
		if ( '1' === Cast::to_string( Arr::get( $settings, 'test_mode', '0' ) ) ) {
			$token_handler->use_sandbox();
		} else {
			$token_handler->use_production();
		}

		// Get the payment token for the user.
		$payment_token = $token_handler->get_user_payment_token( $user->ID, $payment_token_id );

		if ( ! $payment_token ) {
			return new WP_Error(
				'payment_token_not_found',
				__( 'PayPal Checkout payment token not found.', 'learndash' )
			);
		}

		$payment_result = $gateway->process_ipn_subscription_migration(
			$product,
			$user,
			$payment_token
		);

		if ( ! $payment_result ) {
			return new WP_Error(
				'payment_processing_failed',
				__( 'Failed to process subscription payment.', 'learndash' )
			);
		}

		return true;
	}

	/**
	 * Returns the PayPal Standard settings.
	 *
	 * @since 4.25.3
	 *
	 * @return array<string,mixed> The PayPal Standard settings.
	 */
	private function get_paypal_standard_settings(): array {
		return array_filter(
			Arr::wrap(
				LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal' )
			)
		);
	}

	/**
	 * Returns true if the PayPal Standard is in test mode.
	 *
	 * @since 4.25.3
	 *
	 * @return bool
	 */
	private function is_paypal_standard_test_mode(): bool {
		$settings = $this->get_paypal_standard_settings();

		return 'yes' === Cast::to_string( Arr::get( $settings, 'paypal_sandbox', 'no' ) );
	}

	/**
	 * Returns the PayPal Standard migration API keys.
	 *
	 * @since 4.25.3
	 *
	 * @return array{
	 *     api_username: string,
	 *     api_password: string,
	 *     api_signature: string,
	 * } The PayPal Standard migration API keys.
	 */
	private function get_paypal_standard_migration_api_keys(): array {
		$settings = array_filter(
			Arr::wrap(
				LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal_Migration_How_To' )
			)
		);

		return [
			'api_username'  => Cast::to_string( Arr::get( $settings, 'api_username', '' ) ),
			'api_password'  => Cast::to_string( Arr::get( $settings, 'api_password', '' ) ),
			'api_signature' => Cast::to_string( Arr::get( $settings, 'api_signature', '' ) ),
		];
	}
}
