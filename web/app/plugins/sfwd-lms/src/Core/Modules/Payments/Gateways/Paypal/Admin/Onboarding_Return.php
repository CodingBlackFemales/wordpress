<?php
/**
 * LearnDash PayPal Admin Onboarding Return class.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Admin;

use LearnDash\Core\App;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Whodat_Client;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Client;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Webhook_Client;
use LearnDash_Settings_Section;

/**
 * Class for handling the onboarding return.
 *
 * @since 4.25.0
 */
class Onboarding_Return {
	/**
	 * Handles the onboarding return.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function handler(): void {
		if ( ! is_admin() ) {
			return;
		}

		$data = Arr::wrap( SuperGlobals::get_sanitized_superglobal( 'REQUEST' ) );

		if ( ! $this->validate_request_data( $data ) ) {
			return;
		}

		$whodat_client = App::get( Whodat_Client::class );

		if ( ! $whodat_client instanceof Whodat_Client ) {
			return;
		}

		$client = App::get( Client::class );

		if ( ! $client instanceof Client ) {
			return;
		}

		$webhook_client = App::get( Webhook_Client::class );

		if ( ! $webhook_client instanceof Webhook_Client ) {
			return;
		}

		$this->handle_onboarding_return( $data, $whodat_client, $client, $webhook_client );
	}

	/**
	 * Validates the request data.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,string> $data The request data.
	 *
	 * @return bool
	 */
	protected function validate_request_data( array $data ): bool {
		// Check if it's the gateway settings page and the user is connected to PayPal.
		return 'learndash_lms_payments' === Arr::get( $data, 'page' )
			&& 'settings_paypal_checkout' === Arr::get( $data, 'section-payment' )
			&& '1' === Arr::get( $data, 'signup_return' )
			&& 'addipmt' === Arr::get( $data, 'productIntentId' )
			&& Arr::has( $data, 'merchantId' );
	}

	/**
	 * Handles the onboarding return.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,string> $data           The request data.
	 * @param Whodat_Client        $whodat_client  The Whodat client.
	 * @param Client               $client         The client.
	 * @param Webhook_Client       $webhook_client The webhook client.
	 *
	 * @return void
	 */
	protected function handle_onboarding_return(
		array $data,
		Whodat_Client $whodat_client,
		Client $client,
		Webhook_Client $webhook_client
	): void {
		$is_sandbox = $this->is_sandbox();

		if ( $is_sandbox ) {
			$client->use_sandbox();
			$webhook_client->use_sandbox();
		} else {
			$client->use_production();
			$webhook_client->use_production();
		}

		$seller_data = $whodat_client->get_seller_referral_data(
			$whodat_client->get_referral_data_link(),
			$is_sandbox
		);

		if ( is_wp_error( $seller_data ) ) {
			$this->error_redirect( $seller_data->get_error_message() );

			return;
		}

		$referral_data       = Arr::wrap( Arr::get( $seller_data, 'referral_data', [] ) );
		$integration_details = Arr::wrap(
			Arr::get(
				$referral_data,
				'operations.0.api_integration_preference.rest_api_integration.first_party_details',
				[]
			)
		);

		$hash = Cast::to_string( Arr::get( $integration_details, 'seller_nonce', '' ) );

		// Validate the hash.
		if ( $hash !== Whodat_Client::get_transient_hash() ) {
			$this->error_redirect( esc_html__( 'Invalid hash.', 'learndash' ) );

			return;
		}

		$settings = [
			'signup_hash'              => $hash,
			'merchant_id'              => Cast::to_string( Arr::get( $data, 'merchantId', '' ) ), // It's the merchant ID in the LD app.
			'merchant_id_in_paypal'    => Cast::to_string( Arr::get( $data, 'merchantIdInPayPal', '' ) ), // It's the merchant ID in PayPal.
			'api_granted_scopes'       => implode( ',', Arr::wrap( Arr::get( $integration_details, 'features', [] ) ) ),
			'supports_custom_payments' => in_array(
				'PPCP',
				Arr::wrap( Arr::get( $referral_data, 'products', [] ) ),
				true
			),
		];

		// PayPal partner JS will call the ajax_fetch_access_token() method to generate the access token.
		// This happens after the user completes the onboarding process.
		$access_token = $client->get_access_token();
		$credentials  = $whodat_client->get_seller_credentials(
			$access_token,
			$is_sandbox
		);

		if ( is_wp_error( $credentials ) ) {
			$this->error_redirect( $credentials->get_error_message() );

			return;
		}

		if (
			! Arr::has( $credentials, 'client_id' )
			|| ! Arr::has( $credentials, 'client_secret' )
		) {
			// Save the settings before redirecting to the error page.
			$this->save_settings( $settings );

			$this->error_redirect( Cast::to_string( Arr::get( $credentials, 'message', '' ) ) );

			return;
		}

		// Save the credentials.
		$client_id     = Cast::to_string( Arr::get( $credentials, 'client_id', '' ) );
		$client_secret = Cast::to_string( Arr::get( $credentials, 'client_secret', '' ) );

		$settings['client_id']     = $client_id;
		$settings['client_secret'] = $client_secret;
		$settings['account_id']    = Cast::to_string( Arr::get( $credentials, 'payer_id', '' ) );

		$this->save_settings( $settings );

		// Refresh access token data.
		$token_data = $client->get_access_token_from_client_credentials(
			$client_id,
			$client_secret,
			$settings['account_id']
		);

		if ( is_wp_error( $token_data ) ) {
			$this->error_redirect( $token_data->get_error_message() );

			return;
		}

		$client->save_access_token_data( $token_data );

		// Create or update webhooks.
		$webhook_client->create_or_update_existing_webhooks();

		$this->success_redirect();
	}

	/**
	 * Saves the settings.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,mixed> $data The settings data.
	 *
	 * @return void
	 */
	protected function save_settings( array $data ): void {
		LearnDash_Settings_Section::set_section_settings_all(
			'LearnDash_Settings_Section_PayPal_Checkout',
			$data
		);
	}

	/**
	 * Checks if the sandbox mode is enabled.
	 *
	 * @since 4.25.0
	 *
	 * @return bool
	 */
	protected function is_sandbox(): bool {
		$settings = Arr::wrap( LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal_Checkout' ) );

		return Cast::to_bool( Arr::get( $settings, 'test_mode', 0 ) );
	}

	/**
	 * Gets the return URL.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,string> $data The request data.
	 *
	 * @return string
	 */
	protected function get_return_url( array $data ): string {
		$data = array_merge(
			[
				'page'            => 'learndash_lms_payments',
				'section-payment' => 'settings_paypal_checkout',
			],
			$data
		);

		return add_query_arg( $data, admin_url( 'admin.php' ) );
	}

	/**
	 * Redirects to the error page.
	 *
	 * @since 4.25.0
	 *
	 * @param string $message The error message.
	 *
	 * @return void
	 */
	protected function error_redirect( string $message ): void {
		// Clear all transients.
		Whodat_Client::delete_all_transients();

		wp_safe_redirect(
			esc_url_raw(
				$this->get_return_url(
					[
						'signup_error'         => '1',
						'signup_error_message' => $message,
					]
				)
			)
		);

		exit;
	}

	/**
	 * Redirects to the success page.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	protected function success_redirect(): void {
		if ( Cast::to_bool( SuperGlobals::get_get_var( 'is_setup_wizard', false ) ) ) {
			wp_safe_redirect(
				esc_url_raw(
					admin_url( 'admin.php?page=learndash-setup-wizard' )
				)
			);

			exit;
		}

		wp_safe_redirect(
			esc_url_raw(
				$this->get_return_url(
					[
						'connected' => '1',
					]
				)
			)
		);

		exit;
	}
}
