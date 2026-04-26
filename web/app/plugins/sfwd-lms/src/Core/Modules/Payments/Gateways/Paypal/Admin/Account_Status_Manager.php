<?php
/**
 * Class for managing PayPal account status in the database.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Admin;

use StellarWP\Learndash\StellarWP\Arrays\Arr;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;
use LearnDash_Settings_Section;

/**
 * Account status manager class.
 *
 * @since 4.25.0
 */
class Account_Status_Manager {
	/**
	 * The account verifier instance.
	 *
	 * @since 4.25.0
	 *
	 * @var Account_Verifier
	 */
	private Account_Verifier $account_verifier;

	/**
	 * Constructor.
	 *
	 * @since 4.25.0
	 */
	public function __construct() {
		$this->account_verifier = new Account_Verifier();
	}

	/**
	 * Verifies the account status and updates the account status in the database.
	 *
	 * This method is responsible for:
	 * 1. Checking if we're on the correct settings page.
	 * 2. Getting the current settings.
	 * 3. Verifying the account status.
	 * 4. Updating the database with verification results.
	 * 5. Returning a boolean indicating if the account is ready for payments.
	 *
	 * @since 4.25.0
	 *
	 * @return bool
	 */
	public function verify_account_status(): bool {
		// Stop if we're not on the PayPal checkout settings page.
		if ( 'settings_paypal_checkout' !== SuperGlobals::get_get_var( 'section-payment', '' ) ) {
			return false;
		}

		$settings = Arr::wrap( LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal_Checkout' ) );

		$is_account_ready = $this->account_verifier->verify_account( $settings );

		// If there's an error, return it without updating the database.
		if ( ! $is_account_ready ) {
			return false;
		}

		// Mark the account as ready and verified.
		$this->set_account_ready_and_verified();

		return true;
	}

	/**
	 * Returns the error messages.
	 *
	 * @since 4.25.0
	 *
	 * @return string[] The error messages.
	 */
	public function get_error_messages(): array {
		return $this->account_verifier->get_error_messages();
	}

	/**
	 * Sets the account status to ready for payments and verified.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	protected function set_account_ready_and_verified(): void {
		LearnDash_Settings_Section::set_section_settings_all(
			'LearnDash_Settings_Section_PayPal_Checkout',
			[
				'merchant_account_is_ready' => '1',
				'merchant_account_verified' => '1',
			]
		);
	}
}
