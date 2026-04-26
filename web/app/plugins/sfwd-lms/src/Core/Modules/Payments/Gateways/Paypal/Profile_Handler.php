<?php
/**
 * PayPal Profile Handler.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal;

use LearnDash\Core\App;
use LearnDash\Core\Models\User;
use LearnDash\Core\Modules\Payments\DTO\Card as Card_DTO;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use WP_Error;

/**
 * PayPal Profile Handler.
 *
 * @since 4.25.0
 */
class Profile_Handler {
	/**
	 * Shows the saved cards.
	 *
	 * @since 4.25.3
	 *
	 * @param bool $show_saved_cards Whether to show the saved cards.
	 *
	 * @return bool Whether to show the saved cards.
	 */
	public function show_saved_cards( bool $show_saved_cards ): bool {
		$settings = Payment_Gateway::get_settings();

		if (
			empty( $settings )
			|| Cast::to_string( Arr::get( $settings, 'enabled', '' ) ) !== 'yes' // Stop if the PayPal Checkout gateway is not enabled.
			|| ! Payment_Gateway::account_is_connected() // Stop if the PayPal account is not connected.
			|| ! Cast::to_bool( Arr::get( $settings, 'merchant_account_verified', false ) ) // Stop if the PayPal account is not verified.
			|| ! Cast::to_bool( Arr::get( $settings, 'merchant_account_is_ready', false ) ) // Stop if the PayPal account is not ready for payments.
			|| ! Payment_Gateway::is_payment_method_card_active() // Stop if the payment method 'Credit Card' is not active.
		) {
			return $show_saved_cards;
		}

		return true;
	}

	/**
	 * Gets the payment method information for PayPal payment tokens.
	 *
	 * @since 4.25.0
	 *
	 * @phpstan-param array{
	 *    description: string,
	 *    icon: string,
	 * } $information
	 *
	 * @param array<string,string> $information The payment method information.
	 * @param array<string,string> $payment_token The payment token data.
	 *
	 * @return array{description: string, icon: string} The payment method information.
	 */
	public function get_payment_method_information( array $information, array $payment_token ): array {
		// If another filter has already provided information, return it.
		if ( ! empty( $information['description'] ) ) {
			return $information;
		}

		if ( 'paypal_checkout' !== Arr::get( $payment_token, 'gateway', '' ) ) {
			return $information;
		}

		$token_id = Cast::to_string( Arr::get( $payment_token, 'token', '' ) );

		if ( empty( $token_id ) ) {
			return $information;
		}

		// Get the PayPal client.
		$client = App::get( Client::class );

		if ( ! $client instanceof Client ) {
			return $information;
		}

		// Set the environment based on the gateway settings.
		$gateway_settings = Payment_Gateway::get_settings();

		if ( ! empty( $gateway_settings ) && Cast::to_bool( Arr::get( $gateway_settings, 'test_mode', false ) ) ) {
			$client->use_sandbox();
		} else {
			$client->use_production();
		}

		// Fetch the payment token details from PayPal.
		$token_data = $client->get_payment_token( $token_id );

		if ( is_wp_error( $token_data ) || empty( $token_data ) ) {
			return $information;
		}

		$payment_source = Arr::wrap( Arr::get( $token_data, 'payment_source', [] ) );

		if ( empty( $payment_source ) ) {
			return $information;
		}

		// Get the first payment source type.
		$payment_source_key = array_key_first( $payment_source );

		if ( ! in_array( $payment_source_key, [ 'card', 'paypal' ], true ) ) {
			return $information;
		}

		$source_data = $payment_source[ $payment_source_key ];

		// Card payment source.
		if ( 'card' === $payment_source_key ) {
			$brand       = Cast::to_string( Arr::get( $source_data, 'brand', '' ) );
			$last_digits = Cast::to_string( Arr::get( $source_data, 'last_digits', '' ) );

			$description = ! empty( $last_digits )
				? sprintf( '**** **** **** %s', $last_digits )
				: __( 'Credit/Debit Card', 'learndash' );

			$brand = mb_strtolower( $brand );

			// Maestro is deprecated since July 2023. Display as Mastercard.
			return [
				'description' => $description,
				'icon'        => 'maestro' === $brand
					? 'mastercard'
					: $brand,
			];
		}

		// PayPal payment source.
		$email = Cast::to_string( Arr::get( $source_data, 'email_address', '' ) );

		$description = ! empty( $email )
			? $this->redact_email( $email )
			: __( 'PayPal', 'learndash' );

		return [
			'description' => $description,
			'icon'        => 'paypal',
		];
	}

	/**
	 * Gets the saved cards.
	 *
	 * @since 4.25.0
	 *
	 * @param Card_DTO[] $cards The saved cards.
	 * @param User       $user  The user model.
	 *
	 * @return Card_DTO[] The saved cards.
	 */
	public function get_saved_cards( array $cards, User $user ): array {
		$payment_token = App::get( Payment_Token::class );

		if ( ! $payment_token instanceof Payment_Token ) {
			return $cards;
		}

		$settings = Payment_Gateway::get_settings();

		if ( Cast::to_bool( Arr::get( $settings, 'test_mode', false ) ) ) {
			$payment_token->use_sandbox();
		} else {
			$payment_token->use_production();
		}

		$payment_tokens = $payment_token->get_user_payment_tokens( $user->get_id() );

		if ( empty( $payment_tokens ) ) {
			return $cards;
		}

		foreach ( $payment_tokens as $data ) {
			if ( ! Arr::has( $data, 'card' ) ) {
				continue;
			}

			$brand = mb_strtolower( Cast::to_string( Arr::get( $data, 'card.brand', '' ) ) );

			// Maestro is deprecated since July 2023. Display as Mastercard.
			$brand = $brand === 'maestro' ? 'mastercard' : $brand;

			$expiry_date = Cast::to_string( Arr::get( $data, 'card.expiry_date', '' ) );
			$date        = explode( '-', $expiry_date );

			// Convert 2025-08 to 08/25.
			if ( count( $date ) === 2 ) {
				$expiry_date = sprintf(
					'%s/%s',
					$date[1],
					substr( $date[0], 2, 2 )
				);
			}

			$cards[] = Card_DTO::create(
				[
					'gateway_id'    => 'paypal_checkout',
					'card_id'       => Cast::to_string( Arr::get( $data, 'id', '' ) ),
					'brand'         => $brand,
					'holder_name'   => Cast::to_string( Arr::get( $data, 'card.holder_name', '' ) ),
					'last_4_digits' => Cast::to_string( Arr::get( $data, 'card.last_4_digits', '' ) ),
					'expiry_date'   => $expiry_date,
				]
			);
		}

		return $cards;
	}

	/**
	 * Handles the card removal.
	 *
	 * @since 4.25.0
	 *
	 * @param bool|WP_Error $result The result of the card removal operation.
	 * @param string        $card_id The ID of the card to remove.
	 * @param string        $gateway_id The ID of the payment gateway.
	 * @param int           $user_id The ID of the user.
	 *
	 * @return bool|WP_Error The result of the card removal operation.
	 */
	public function handle_remove_card( $result, $card_id, $gateway_id, $user_id ) {
		if ( 'paypal_checkout' !== $gateway_id ) {
			return $result;
		}

		$payment_token = App::get( Payment_Token::class );

		if ( ! $payment_token instanceof Payment_Token ) {
			return $result;
		}

		$settings = Payment_Gateway::get_settings();

		if ( Cast::to_bool( Arr::get( $settings, 'test_mode', false ) ) ) {
			$payment_token->use_sandbox();
		} else {
			$payment_token->use_production();
		}

		$removed = $payment_token->delete_user_payment_token( $user_id, $card_id, true );

		if ( ! $removed ) {
			return $result;
		}

		return true;
	}

	/**
	 * Redacts an email address to protect privacy.
	 *
	 * Shows only the first character of the local part and the domain.
	 * Example: john.doe@example.com becomes j***@example.com.
	 *
	 * @since 4.25.0
	 *
	 * @param string $email The email address to redact.
	 *
	 * @return string The redacted email address.
	 */
	private function redact_email( string $email ): string {
		if ( empty( $email ) || ! is_email( $email ) ) {
			return $email;
		}

		$parts = explode( '@', $email );

		if ( count( $parts ) !== 2 ) {
			return $email;
		}

		$local_part = $parts[0];
		$domain     = $parts[1];

		if ( strlen( $local_part ) <= 1 ) {
			return $email;
		}

		$redacted_local = $local_part[0] . str_repeat( '*', strlen( $local_part ) - 1 );

		return $redacted_local . '@' . $domain;
	}
}
