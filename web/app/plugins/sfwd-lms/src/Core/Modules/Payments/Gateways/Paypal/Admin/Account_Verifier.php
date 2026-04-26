<?php
/**
 * Class for verifying PayPal account status.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Admin;

use LearnDash\Core\App;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Client;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;

/**
 * Account verifier class.
 *
 * @since 4.25.0
 */
class Account_Verifier {
	/**
	 * The error message.
	 *
	 * @since 4.25.0
	 *
	 * @var string[]
	 */
	private array $error_messages = [];

	/**
	 * Returns the error messages.
	 *
	 * @since 4.25.0
	 *
	 * @return string[]
	 */
	public function get_error_messages(): array {
		return $this->error_messages;
	}

	/**
	 * Verifies the account status and returns an error message if necessary.
	 *
	 * This method is responsible for:
	 * 1. Checking if the account is ready for payments.
	 * 2. Checking if the account supports all features.
	 * 3. Registering errors if any checks fail.
	 * 4. Returning a boolean indicating if the account is ready for payments.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,mixed> $settings The PayPal settings.
	 *
	 * @return bool
	 */
	public function verify_account( array $settings ): bool {
		if (
			empty( Arr::get( $settings, 'merchant_id_in_paypal' ) )
			|| ! empty( Arr::get( $settings, 'merchant_account_verified' ) )
		) {
			return false;
		}

		$seller_status = $this->get_seller_status(
			Cast::to_string( Arr::get( $settings, 'merchant_id_in_paypal' ) ),
			Payment_Gateway::get_partner_id(),
			Cast::to_bool( Arr::get( $settings, 'test_mode', false ) )
		);

		if ( empty( $seller_status ) ) {
			$this->error_messages[] = esc_html__( 'Failed to verify account status. Try reconnecting the account.', 'learndash' );

			return false;
		}

		if ( ! Cast::to_bool( Arr::get( $settings, 'merchant_account_is_ready' ) ) ) {
			$error = $this->check_if_account_ready( $seller_status );

			// Stop here if the account is not ready for payments.
			if ( ! $error ) {
				return false;
			}
		}

		// Return here if the account is ready for payments, but does not support custom payments.
		if ( ! Cast::to_bool( Arr::get( $settings, 'supports_custom_payments' ) ) ) {
			return true;
		}

		return $this->check_if_account_support_all_features( $seller_status );
	}

	/**
	 * Retrieves the seller status from PayPal.
	 *
	 * @since 4.25.0
	 *
	 * @param string $merchant_id The merchant ID.
	 * @param string $partner_id  The partner ID.
	 * @param bool   $test_mode   Whether the test mode is enabled.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_seller_status( string $merchant_id, string $partner_id, bool $test_mode ): array {
		$client = App::get( Client::class );

		if ( ! $client instanceof Client ) {
			return [];
		}

		if ( $test_mode ) {
			$client->use_sandbox();
		} else {
			$client->use_production();
		}

		$seller_status = $client->get_seller_status(
			$merchant_id,
			$partner_id
		);

		if ( is_wp_error( $seller_status ) ) {
			return [];
		}

		return $seller_status;
	}

	/**
	 * Checks if the account is ready for payments.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,mixed> $seller_status The seller status.
	 *
	 * @return bool
	 */
	protected function check_if_account_ready( array $seller_status ): bool {
		if (
			! Arr::exists( $seller_status, 'payments_receivable' )
			|| ! Arr::exists( $seller_status, 'primary_email_confirmed' )
		) {
			// Return here since the rest of the validations will definitely fail.
			$this->error_messages[] = esc_html__( 'There was a problem with the status check for your PayPal account. Please try disconnecting and connecting again. If the problem persists, please contact support.', 'learndash' );

			return false;
		}

		// Stop if the account is not ready for payments.
		if ( ! Cast::to_bool( Arr::get( $seller_status, 'payments_receivable' ) ) ) {
			$this->error_messages[] = esc_html__( 'Your account has been limited by PayPal - please check your PayPal account email inbox for details and next steps.', 'learndash' );
		}

		// Stop if the account email is not confirmed.
		if ( ! Cast::to_bool( Arr::get( $seller_status, 'primary_email_confirmed' ) ) ) {
			$this->error_messages[] = wp_kses(
				sprintf(
					// translators: %s: PayPal email confirmation docs.
					__( 'Your PayPal account email is unconfirmed - please <a href="%s" target="_blank">confirm</a> your PayPal account email to start accepting payments.', 'learndash' ),
					'https://www.paypal.com/us/cshelp/article/how-do-i-confirm-my-email-address-help138'
				),
				[
					'a' => [
						'href'   => [],
						'target' => [],
					],
				]
			);
		}

		if ( ! empty( $this->error_messages ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if the account supports all features.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,mixed> $seller_status The seller status.
	 *
	 * @return bool
	 */
	protected function check_if_account_support_all_features( array $seller_status ): bool {
		if ( array_diff( [ 'products', 'capabilities' ], array_keys( $seller_status ) ) ) {
			$this->error_messages[] = esc_html__( 'Your account was expected to be able to accept custom payments, but is not. Please make sure your account country matches the country setting. If the problem persists, please contact PayPal.', 'learndash' );

			// Return here since the rest of the validations will definitely fail.
			return false;
		}

		// Grab the PPCP_CUSTOM product from the status data.
		$custom_product = current(
			array_filter(
				Arr::wrap(
					Arr::get( $seller_status, 'products', [] )
				),
				static function ( $product ) {
					return 'PPCP_CUSTOM' === Arr::get( $product, 'name' );
				}
			)
		);

		if (
			! is_array( $custom_product )
			|| 'SUBSCRIBED' !== Arr::get( $custom_product, 'vetting_status' )
		) {
			$this->error_messages[] = esc_html__( 'Advanced Credit and Debit is not enabled for your account, please login into your PayPal account to provide more information.', 'learndash' );
		}

		// Loop through the capabilities and see if any are not active.
		$invalid_capabilities = [];
		$capabilities         = Arr::wrap(
			Arr::get( $seller_status, 'capabilities', [] )
		);

		foreach ( $capabilities as $capability ) {
			if ( $capability['status'] !== 'ACTIVE' ) {
				$invalid_capabilities[] = $capability['name'];
			}
		}

		if ( ! empty( $invalid_capabilities ) ) {
			$this->error_messages[] = esc_html__( 'Reach out to PayPal to resolve the following capabilities:', 'learndash' ) . ' ' . implode( ', ', $invalid_capabilities );
		}

		if ( ! empty( $this->error_messages ) ) {
			return false;
		}

		return true;
	}
}
