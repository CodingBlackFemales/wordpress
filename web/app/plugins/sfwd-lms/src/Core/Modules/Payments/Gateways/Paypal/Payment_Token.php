<?php
/**
 * PayPal Payment Token Handler.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal;

use LearnDash\Core\App;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;
use StellarWP\Learndash\StellarWP\DB\DB;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash\Core\Models\Product;
use LearnDash\Core\Repositories\Subscription as Subscription_Repository;

/**
 * PayPal Payment Token Handler.
 *
 * @since 4.25.0
 */
class Payment_Token {
	/**
	 * Base user meta key for storing payment tokens.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	private const PAYMENT_TOKENS_META_KEY = 'ld_paypal_payment_tokens';

	/**
	 * Base user meta key for storing PayPal customer ID.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	private const CUSTOMER_ID_META_KEY = 'ld_paypal_customer_id';

	/**
	 * Base user meta key for storing reference ID for checkout with saved payment method.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	private const REFERENCE_ID_META_KEY = 'ld_paypal_checkout_vault_reference_id';

	/**
	 * Current environment setting.
	 *
	 * @since 4.25.0
	 *
	 * @var bool
	 */
	private bool $is_sandbox = false;

	/**
	 * Sets the environment to use sandbox for API calls.
	 *
	 * @since 4.25.0
	 *
	 * @return self
	 */
	public function use_sandbox(): self {
		return $this->set_environment( true );
	}

	/**
	 * Sets the environment to use production for API calls.
	 *
	 * @since 4.25.0
	 *
	 * @return self
	 */
	public function use_production(): self {
		return $this->set_environment( false );
	}

	/**
	 * Returns the current environment.
	 *
	 * @since 4.25.0
	 *
	 * @return bool
	 */
	public function is_sandbox(): bool {
		return $this->is_sandbox;
	}

	/**
	 * Gets the payment token from the order.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,mixed> $order Order data from PayPal.
	 *
	 * @return array{
	 *     gateway?: string,
	 *     token?: string,
	 *     customer_id?: string,
	 *     type?: string,
	 *     status?: string,
	 *     card?: array{
	 *         holder_name: string,
	 *         brand: string,
	 *         last_4_digits: string,
	 *         expiry_date: string,
	 *     },
	 * }
	 */
	public static function get_payment_token_from_order( array $order ): array {
		$payment_sources = Arr::wrap( Arr::get( $order, 'payment_source', [] ) );

		// Get the first array key.
		$payment_source_key = array_key_first( $payment_sources );

		if ( ! in_array( $payment_source_key, [ 'card', 'paypal' ], true ) ) {
			return [];
		}

		$payment_source = $payment_sources[ $payment_source_key ];

		// Extract vault data from the payment source.
		$vault_data = Arr::get( $payment_source, 'attributes.vault', [] );

		if ( empty( $vault_data ) ) {
			return [];
		}

		$payment_token = [
			'gateway'     => 'paypal_checkout',
			'token'       => Cast::to_string( Arr::get( $vault_data, 'id', '' ) ),
			'customer_id' => Cast::to_string( Arr::get( $vault_data, 'customer.id', '' ) ),
			'type'        => Cast::to_string( $payment_source_key ),
			'status'      => Cast::to_string( Arr::get( $vault_data, 'status', '' ) ),
		];

		if ( 'card' === $payment_source_key ) {
			$payment_token['card'] = [
				'holder_name'   => Cast::to_string( Arr::get( $payment_source, 'name', '' ) ),
				'brand'         => Cast::to_string( Arr::get( $payment_source, 'brand', '' ) ),
				'last_4_digits' => Cast::to_string( Arr::get( $payment_source, 'last_digits', '' ) ),
				'expiry_date'   => Cast::to_string( Arr::get( $payment_source, 'expiry', '' ) ),
			];
		}

		return $payment_token;
	}

	/**
	 * Saves a payment token to a reference ID.
	 *
	 * @since 4.25.0
	 *
	 * @param int                 $user_id      User ID.
	 * @param array<string,mixed> $order_data   Order data sent to PayPal.
	 * @param array<string,mixed> $order        Order data from PayPal.
	 *
	 * @return void
	 */
	public static function maybe_save_payment_token_to_reference_id( int $user_id, array $order_data, array $order ): void {
		$reference_id = Cast::to_string( Arr::get( $order_data, 'reference_id', '' ) );

		if ( empty( $reference_id ) ) {
			return;
		}

		$payment_sources = Arr::wrap( Arr::get( $order, 'payment_source', [] ) );

		if ( empty( $payment_sources ) ) {
			// No payment sources found.
			return;
		}

		$payment_source_key = array_key_first( $payment_sources );

		$payment_source = $payment_sources[ $payment_source_key ];

		// If the vault ID is set in the order data, use it. Otherwise, extract it from the payment source.
		if ( ! empty( $order_data['vault_id'] ) ) {
			$vault_id    = Cast::to_string( Arr::get( $order_data, 'vault_id', '' ) );
			$customer_id = Cast::to_string( Arr::get( $order_data, 'customer_id', '' ) );
		} else {
			// Extract vault data from the payment source.
			$customer_id = Cast::to_string( Arr::get( $payment_source, 'attributes.customer.id', '' ) );
			$vault_id    = Cast::to_string( Arr::get( $payment_source, 'vault_id', '' ) );
		}

		if ( empty( $customer_id ) || empty( $vault_id ) ) {
			return;
		}

		// Save the reference ID to the user meta.
		update_user_meta(
			$user_id,
			self::REFERENCE_ID_META_KEY . '_' . $reference_id,
			[
				'gateway'     => 'paypal_checkout',
				'token'       => $vault_id,
				'customer_id' => $customer_id,
				'type'        => Cast::to_string( $payment_source_key ),
			]
		);
	}

	/**
	 * Gets the payment token from a reference ID.
	 *
	 * @since 4.25.0
	 *
	 * @param int    $user_id User ID.
	 * @param string $reference_id Reference ID.
	 *
	 * @return array{
	 *     gateway?: string,
	 *     token?: string,
	 *     customer_id?: string,
	 *     type?: string,
	 * }
	 */
	public static function get_payment_token_from_reference_id( int $user_id, string $reference_id ): array {
		$data = array_filter( Arr::wrap( get_user_meta( $user_id, self::REFERENCE_ID_META_KEY . '_' . $reference_id, true ) ) );

		if ( empty( $data ) ) {
			return [];
		}

		return $data;
	}

	/**
	 * Saves a payment token for a user from order data.
	 *
	 * @since 4.25.0
	 *
	 * @param int                 $user_id    User ID.
	 * @param array<string,mixed> $order_data Order data from PayPal.
	 *
	 * @return bool True if saved successfully, false otherwise.
	 */
	public function save_user_payment_token_from_order( int $user_id, array $order_data ): bool {
		$payment_token = self::get_payment_token_from_order( $order_data );

		if ( empty( $payment_token ) ) {
			return false;
		}

		// Save the customer ID as separate user meta.
		if ( ! empty( $payment_token['customer_id'] ) ) {
			$this->save_user_customer_id( $user_id, $payment_token['customer_id'] );
		}

		// Check if the vault status is VAULTED.
		if ( 'VAULTED' !== $payment_token['status'] ) {
			return false;
		}

		$payment_token_data = [
			'id'       => $payment_token['token'],
			'customer' => [
				'id' => $payment_token['customer_id'],
			],
		];

		if ( ! empty( $payment_token['card'] ) ) {
			$payment_token_data['card'] = $payment_token['card'];
		}

		// Save the payment token.
		return $this->save_user_payment_token( $user_id, $payment_token_data );
	}

	/**
	 * Saves a payment token for a user.
	 *
	 * @since 4.25.0
	 *
	 * @phpstan-param array{
	 *     id: string,
	 *     customer: array{
	 *         id: string,
	 *     },
	 *     card?: array{
	 *         holder_name: string,
	 *         brand: string,
	 *         last_4_digits: string,
	 *         expiry_date: string,
	 *     },
	 * } $payment_token_data
	 *
	 * @param int                 $user_id    User ID.
	 * @param array<string,mixed> $payment_token_data Payment token data from PayPal.
	 *
	 * @return bool True if saved successfully, false otherwise.
	 */
	public function save_user_payment_token( int $user_id, array $payment_token_data ): bool {
		$tokens   = $this->get_user_payment_tokens( $user_id );
		$token_id = Cast::to_string( Arr::get( $payment_token_data, 'id', '' ) );

		if ( empty( $token_id ) ) {
			return false;
		}

		$tokens[ $token_id ] = $payment_token_data;

		update_user_meta( $user_id, $this->get_payment_tokens_meta_key(), wp_json_encode( $tokens ) );

		return true;
	}

	/**
	 * Gets all payment tokens for a user.
	 *
	 * @since 4.25.0
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array<string,array{
	 *     id: string,
	 *     customer: array{
	 *         id: string,
	 *     },
	 *     card?: array{
	 *         holder_name: string,
	 *         brand: string,
	 *         last_4_digits: string,
	 *         expiry_date: string,
	 *     },
	 * }> Payment tokens.
	 */
	public function get_user_payment_tokens( int $user_id ): array {
		$tokens_json = get_user_meta( $user_id, $this->get_payment_tokens_meta_key(), true );

		if ( empty( $tokens_json ) ) {
			return [];
		}

		$tokens = json_decode( Cast::to_string( $tokens_json ), true );

		return is_array( $tokens ) ? $tokens : [];
	}

	/**
	 * Deletes a payment token for a user.
	 *
	 * @since 4.25.0
	 *
	 * @param int    $user_id            User ID.
	 * @param string $token_id           Payment token ID.
	 * @param bool   $delete_from_paypal Whether to delete the token from PayPal.
	 *
	 * @return bool True if deleted successfully, false otherwise.
	 */
	public function delete_user_payment_token( int $user_id, string $token_id, bool $delete_from_paypal = false ): bool {
		$tokens = $this->get_user_payment_tokens( $user_id );

		if ( ! isset( $tokens[ $token_id ] ) ) {
			return false;
		}

		if ( $delete_from_paypal ) {
			$client = App::get( Client::class );

			if ( ! $client instanceof Client ) {
				return false;
			}

			if ( $this->is_sandbox() ) {
				$client->use_sandbox();
			} else {
				$client->use_production();
			}

			$result = $client->delete_payment_token( $token_id );

			if ( is_wp_error( $result ) ) {
				return false;
			}
		}

		// Remove the token from the subscriptions.

		Subscription_Repository::remove_payment_token_by_user( $user_id, $token_id );

		unset( $tokens[ $token_id ] );

		return Cast::to_bool( update_user_meta( $user_id, $this->get_payment_tokens_meta_key(), wp_json_encode( $tokens ) ) );
	}

	/**
	 * Saves a customer ID for a user.
	 *
	 * @since 4.25.0
	 *
	 * @param int    $user_id User ID.
	 * @param string $customer_id PayPal customer ID.
	 *
	 * @return void
	 */
	public function save_user_customer_id( int $user_id, string $customer_id ): void {
		update_user_meta( $user_id, $this->get_customer_id_meta_key(), $customer_id );
	}

	/**
	 * Gets the customer ID for a user.
	 *
	 * @since 4.25.0
	 *
	 * @param int $user_id User ID.
	 *
	 * @return string|null Customer ID or null if not found.
	 */
	public function get_user_customer_id( int $user_id ): ?string {
		$customer_id = Cast::to_string( get_user_meta( $user_id, $this->get_customer_id_meta_key(), true ) );

		if ( empty( $customer_id ) ) {
			return null;
		}

		return $customer_id;
	}

	/**
	 * Gets a specific payment token for a user.
	 *
	 * @since 4.25.0
	 *
	 * @param int    $user_id User ID.
	 * @param string $token_id Payment token ID.
	 *
	 * @return array<string,mixed>|null Payment token data or null if not found.
	 */
	public function get_user_payment_token( int $user_id, string $token_id ): ?array {
		$tokens = $this->get_user_payment_tokens( $user_id );

		if ( $token_id !== 'paypal' ) {
			return $tokens[ $token_id ] ?? null;
		}

		// If the token ID is 'paypal', we need to get the token from the user meta.
		foreach ( $tokens as $token ) {
			// If the token has no card data, return it.
			if ( empty( $token['card'] ) ) {
				return $token;
			}
		}

		return null;
	}

	/**
	 * Processes a webhook event.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,mixed> $event_data The webhook event data.
	 *
	 * @return bool True if processed successfully, false otherwise.
	 */
	public function process_webhook_event( array $event_data ): bool {
		$event_name = Cast::to_string( Arr::get( $event_data, 'event_type', '' ) );

		if ( empty( $event_name ) ) {
			return false;
		}

		switch ( $event_name ) {
			case 'VAULT.PAYMENT-TOKEN.CREATED':
				return $this->process_payment_token_created( $event_data );
			case 'VAULT.PAYMENT-TOKEN.DELETED':
				return $this->process_payment_token_deleted( $event_data );
		}

		return false;
	}

	/**
	 * Checks if a product requires a vault setup token.
	 *
	 * This method determines if a product is recurring and includes a free trial,
	 * which means it requires a vault setup token from PayPal to charge later.
	 *
	 * @since 4.25.0
	 *
	 * @param int $product_id Product ID.
	 * @param int $user_id    User ID.
	 *
	 * @return bool True if the product requires a vault setup token, false otherwise.
	 */
	public function requires_vault_setup_token( int $product_id, int $user_id ): bool {
		$product = Product::find( $product_id );

		if (
			! $product
			|| ! $product->is_price_type_subscribe()
			|| ! $product->has_trial()
		) {
			return false;
		}

		// Check if the trial is free (trial_price is 0).
		$pricing = $product->get_pricing( $user_id );

		return $pricing->trial_price <= 0;
	}

	/**
	 * Checks if a product is a subscription product.
	 *
	 * @since 4.25.0
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return bool True if the product is a subscription product, false otherwise.
	 */
	public function is_subscription_product( int $product_id ): bool {
		$product = Product::find( $product_id );

		return $product instanceof Product && $product->is_price_type_subscribe();
	}

	/**
	 * Processes a payment token created event.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,mixed> $event_data The webhook event data.
	 *
	 * @return bool True if processed successfully, false otherwise.
	 */
	private function process_payment_token_created( array $event_data ): bool {
		$customer_id = Cast::to_string( Arr::get( $event_data, 'resource.customer.id', '' ) );

		if ( empty( $customer_id ) ) {
			return false;
		}

		// Find user by customer ID.
		$user_id = $this->find_user_by_customer_id( $customer_id );

		if ( 0 === $user_id ) {
			return false;
		}

		// Extract payment token data from the event resource.
		$resource = array_filter(
			Arr::wrap(
				Arr::get( $event_data, 'resource', [] )
			)
		);

		if ( empty( $resource ) ) {
			return false;
		}

		$payment_token_data = [
			'id'       => Cast::to_string( Arr::get( $resource, 'id', '' ) ),
			'customer' => [
				'id' => Cast::to_string( Arr::get( $resource, 'customer.id', '' ) ),
			],
		];

		if ( Arr::has( $resource, 'payment_source.card' ) ) {
			$payment_token_data['card'] = [
				'holder_name'   => Cast::to_string( Arr::get( $resource, 'payment_source.card.name', '' ) ),
				'brand'         => Cast::to_string( Arr::get( $resource, 'payment_source.card.brand', '' ) ),
				'last_4_digits' => Cast::to_string( Arr::get( $resource, 'payment_source.card.last_digits', '' ) ),
				'expiry_date'   => Cast::to_string( Arr::get( $resource, 'payment_source.card.expiry', '' ) ),
			];
		}

		// Save the payment token.
		return $this->save_user_payment_token( $user_id, $payment_token_data );
	}

	/**
	 * Processes a payment token deleted event.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,mixed> $event_data The webhook event data.
	 *
	 * @return bool True if processed successfully, false otherwise.
	 */
	private function process_payment_token_deleted( array $event_data ): bool {
		$token_id = Cast::to_string( Arr::get( $event_data, 'resource.id', '' ) );

		if ( empty( $token_id ) ) {
			return false;
		}

		// Find the user who has this payment token.
		$user_id = $this->find_user_by_payment_token( $token_id );

		if ( 0 === $user_id ) {
			// Token not found in any user's stored tokens, but this is not an error.
			// The token might have been deleted already or never existed in our system.
			return true;
		}

		$deleted = $this->delete_user_payment_token( $user_id, $token_id );

		// Log the deletion for debugging purposes.
		if ( $deleted ) {
			$gateway = App::get( Payment_Gateway::class );
			if ( $gateway instanceof Payment_Gateway ) {
				$gateway->log_info(
					sprintf(
						'Payment token %s deleted for user ID %d.',
						$token_id,
						$user_id
					)
				);
			}
		}

		return true;
	}

	/**
	 * Finds the user who has a specific payment token.
	 *
	 * @since 4.25.0
	 *
	 * @param string $token_id The payment token ID to search for.
	 *
	 * @return int User ID who has this payment token, or 0 if not found.
	 */
	private function find_user_by_payment_token( string $token_id ): int {
		// Search for users who have this payment token in their meta.
		$results = (array) DB::table( 'usermeta' )
			->select( 'user_id', 'meta_value' )
			->where( 'meta_key', $this->get_payment_tokens_meta_key() )
			->whereLike( 'meta_value', $token_id )
			->getAll( ARRAY_A );

		if ( empty( $results[0] ) ) {
			return 0;
		}

		foreach ( $results as $result ) {
			$meta_value = Cast::to_string( Arr::get( $result, 'meta_value', '' ) );

			if ( empty( $meta_value ) ) {
				continue;
			}

			$tokens = json_decode( $meta_value, true );

			if (
				is_array( $tokens )
				&& isset( $tokens[ $token_id ] )
			) {
				return Cast::to_int( Arr::get( $result, 'user_id', 0 ) );
			}
		}

		return 0;
	}

	/**
	 * Finds the user by PayPal customer ID.
	 *
	 * @since 4.25.0
	 *
	 * @param string $customer_id The PayPal customer ID to search for.
	 *
	 * @return int User ID who has this customer ID, or 0 if not found.
	 */
	private function find_user_by_customer_id( string $customer_id ): int {
		// Search for users who have this customer ID in their meta.
		$results = (array) DB::table( 'usermeta' )
			->select( 'user_id' )
			->where( 'meta_key', $this->get_customer_id_meta_key() )
			->where( 'meta_value', $customer_id )
			->getAll( ARRAY_A );

		if ( empty( $results[0] ) ) {
			return 0;
		}

		return Cast::to_int( Arr::get( $results[0], 'user_id', 0 ) );
	}

	/**
	 * Gets the environment-aware payment tokens meta key.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	private function get_payment_tokens_meta_key(): string {
		return $this->is_sandbox()
			? self::PAYMENT_TOKENS_META_KEY . '_sandbox'
			: self::PAYMENT_TOKENS_META_KEY . '_production';
	}

	/**
	 * Gets the environment-aware customer ID meta key.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	private function get_customer_id_meta_key(): string {
		return $this->is_sandbox()
			? self::CUSTOMER_ID_META_KEY . '_sandbox'
			: self::CUSTOMER_ID_META_KEY . '_production';
	}

	/**
	 * Sets the environment for API calls.
	 *
	 * @since 4.25.0
	 *
	 * @param bool $is_sandbox Whether to use the sandbox environment. Defaults to false.
	 *
	 * @return self
	 */
	private function set_environment( bool $is_sandbox = false ): self {
		$this->is_sandbox = $is_sandbox;

		return $this;
	}
}
