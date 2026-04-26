<?php
/**
 * LearnDash PayPal Payment Gateway Client.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal;

use LearnDash\Core\Modules\Payments\Gateways\Paypal\Traits\Request;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash\Core\Utilities\Cast;
use WP_Error;

/**
 * PayPal payment gateway client class.
 *
 * @since 4.25.0
 */
class Client {
	use Request;

	/**
	 * PayPal client token key.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $client_token_key = 'learndash_paypal_checkout_client_token';

	/**
	 * Returns the PayPal homepage URL.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_paypal_home_page_url(): string {
		return sprintf(
			'https://%1$spaypal.com/',
			$this->is_sandbox()
				? 'sandbox.'
				: ''
		);
	}

	/**
	 * Deletes the access token data.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function delete_access_token_data(): void {
		delete_option( $this->access_token_key );
		delete_option( $this->access_token_data_key );
	}

	/**
	 * Deletes the client token.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function delete_client_token(): void {
		delete_option( $this->client_token_key );
	}

	/**
	 * Returns the client data.
	 *
	 * @since 4.25.0
	 *
	 * @return array{
	 *     client_id: string,
	 *     client_secret: string,
	 *     merchant_id: string,
	 * }
	 */
	public function get_client_data(): array {
		$settings = Payment_Gateway::get_settings();

		return [
			'client_id'     => Cast::to_string( Arr::get( $settings, 'client_id', '' ) ),
			'client_secret' => Cast::to_string( Arr::get( $settings, 'client_secret', '' ) ),
			'merchant_id'   => Cast::to_string( Arr::get( $settings, 'account_id', '' ) ),
		];
	}

	/**
	 * Creates an order in the PayPal API.
	 *
	 * @see https://developer.paypal.com/docs/api/orders/v2/#orders_create
	 *
	 * @since 4.25.0
	 *
	 * @phpstan-param array{
	 *     reference_id: string,
	 *     custom_id: string,
	 *     description: string,
	 *     invoice_id?: string,
	 *     currency_code: string,
	 *     amount: string,
	 *     merchant_id: string,
	 *     return_url: string,
	 *     cancel_url: string,
	 *     use_card_fields?: bool,
	 *     save_payment_method?: bool,
	 *     customer_id?: string,
	 *     vault_id?: string,
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
	 * } $data
	 *
	 * @param array<string,mixed> $data The data to use.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_order( array $data ) {
		// Default body for the order with PayPal button.
		$body = [
			'intent'         => 'CAPTURE',
			'purchase_units' => [],
		];

		// Set the payment source based on the use_card_fields parameter.
		if ( ! empty( $data['use_card_fields'] ) ) {
			$card_config = [
				'attributes'         => [
					'verification' => [
						/**
						 * Use 3D Secure when required.
						 *
						 * @see https://developer.paypal.com/docs/api/orders/v2/#orders_create!ct=application/json&path=payment_source/card/attributes/verification/method&t=request
						 */
						'method' => 'SCA_WHEN_REQUIRED',
					],
				],
				'experience_context' => [
					'shipping_preference' => 'NO_SHIPPING',
					'return_url'          => $data['return_url'],
					'cancel_url'          => $data['cancel_url'],
				],
			];

			// Vault the payment method only if not using a existing vaulted payment method.
			if ( ! empty( $data['save_payment_method'] ) && empty( $data['vault_id'] ) ) {
				$card_config['attributes']['vault'] = [
					'store_in_vault' => 'ON_SUCCESS',
					'usage_type'     => 'MERCHANT',
				];
			}

			if ( ! empty( $data['customer_id'] ) ) {
				$card_config['attributes']['customer']['id'] = $data['customer_id'];
			}

			if ( ! empty( $data['vault_id'] ) ) {
				$card_config['vault_id'] = $data['vault_id'];
			}

			$body['payment_source'] = [
				'card' => $card_config,
			];
		} else {
			$paypal_config = [
				'experience_context' => [
					'shipping_preference'       => 'NO_SHIPPING',
					'user_action'               => 'PAY_NOW',
					'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
					'return_url'                => $data['return_url'],
					'cancel_url'                => $data['cancel_url'],
				],
				'attributes'         => [],
			];

			// Vault the payment method only if not using a existing vaulted payment method.
			if ( empty( $data['vault_id'] ) ) {
				$paypal_config['attributes']['vault'] = [
					'store_in_vault' => 'ON_SUCCESS',
					'usage_type'     => 'MERCHANT',
				];

				if ( Arr::has( $data, 'items.0.billing_plan' ) ) {
					$paypal_config['attributes']['vault']['usage_pattern'] = 'SUBSCRIPTION_PREPAID';
				}
			}

			if ( ! empty( $data['customer_id'] ) ) {
				$paypal_config['attributes']['customer']['id'] = $data['customer_id'];
			}

			if ( ! empty( $data['vault_id'] ) ) {
				$paypal_config['vault_id'] = $data['vault_id'];
			}

			$body['payment_source'] = [
				'paypal' => $paypal_config,
			];
		}

		$purchase_units = [
			'reference_id'        => $data['reference_id'],
			'custom_id'           => $data['custom_id'],
			'description'         => $data['description'],
			'amount'              => [
				'currency_code' => $data['currency_code'],
				'value'         => $data['amount'],
				'breakdown'     => [
					'item_total' => [
						'currency_code' => $data['currency_code'],
						'value'         => $data['amount'],
					],
				],
			],
			'payee'               => [
				'merchant_id' => $data['merchant_id'],
			],
			'payer'               => [
				'name'          => [
					'given_name' => $data['first_name'],
					'surname'    => $data['last_name'],
				],
				'email_address' => $data['email'],
			],
			'payment_instruction' => [
				'disbursement_mode' => 'INSTANT',
			],
			'items'               => $data['items'],
		];

		if ( ! empty( $data['invoice_id'] ) ) {
			$purchase_units['invoice_id'] = $data['invoice_id'];
		}

		$body['purchase_units'][] = $purchase_units;

		$headers = $this->get_merchant_request_headers( Cast::to_string( $data['reference_id'] ) );

		// Add support to FraudNet.
		$headers['PayPal-Client-Metadata-Id'] = 'f';

		return $this->client_post(
			'v2/checkout/orders',
			[],
			[
				'headers' => $headers,
				'body'    => $body,
			]
		);
	}

	/**
	 * Fetches an order from the PayPal API.
	 *
	 * @since 4.25.0
	 *
	 * @param string $order_id The order ID to fetch.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_order( string $order_id ) {
		return $this->client_get(
			sprintf(
				'v2/checkout/orders/%s',
				rawurlencode( $order_id )
			),
			[],
			[
				'headers' => $this->get_request_headers( $order_id ),
				'body'    => [],
			]
		);
	}

	/**
	 * Captures an order in the PayPal API.
	 *
	 * @since 4.25.0
	 *
	 * @param string $order_id The PayPal order ID.
	 * @param string $payer_id The PayPal payer ID. Defaults to an empty string.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function capture_order(
		string $order_id,
		string $payer_id = ''
	) {
		$body = [];

		if ( ! empty( $payer_id ) ) {
			$body['payer_id'] = $payer_id;
		}

		return $this->client_post(
			sprintf(
				'v2/checkout/orders/%s/capture',
				rawurlencode( $order_id )
			),
			[],
			[
				'headers' => $this->get_merchant_request_headers( $order_id . $payer_id ),
				'body'    => $body,
			]
		);
	}

	/**
	 * Creates a payment token in the PayPal API.
	 *
	 * @since 4.25.0
	 *
	 * @param string $token_id The token ID to use.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_payment_token( string $token_id ) {
		return $this->client_post(
			'v3/vault/payment-tokens',
			[],
			[
				'headers' => $this->get_merchant_request_headers( $token_id ),
				'body'    => [
					'payment_source' => [
						'token' => [
							'id'   => $token_id,
							'type' => 'SETUP_TOKEN',
						],
					],
				],
			]
		);
	}

	/**
	 * Lists payment tokens for a customer from the PayPal API.
	 *
	 * @see https://developer.paypal.com/docs/api/payment-tokens/v3/#customer_payment-tokens_get
	 *
	 * @since 4.25.0
	 *
	 * @param string $customer_id The customer ID to list payment tokens for.
	 * @param string $page_token  The page token for pagination. Defaults to empty string.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function list_payment_tokens(
		string $customer_id,
		string $page_token = ''
	) {
		$query_args = [
			'customer_id'    => $customer_id,
			'total_required' => true,
		];

		if ( ! empty( $page_token ) ) {
			$query_args['page_token'] = $page_token;
		}

		return $this->client_get(
			'v3/vault/payment-tokens',
			$query_args,
			[
				'headers' => $this->get_request_headers( $customer_id ),
				'body'    => [],
			]
		);
	}

	/**
	 * Gets a specific payment token from the PayPal API.
	 *
	 * @see https://developer.paypal.com/docs/api/payment-tokens/v3/#payment-tokens_get
	 *
	 * @since 4.25.0
	 *
	 * @param string $token_id The payment token ID to retrieve.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_payment_token( string $token_id ) {
		return $this->client_get(
			'v3/vault/payment-tokens/' . $token_id,
			[],
			[
				'headers' => $this->get_request_headers( $token_id ),
				'body'    => [],
			]
		);
	}

	/**
	 * Deletes a payment token from the PayPal API.
	 *
	 * @see https://developer.paypal.com/docs/api/payment-tokens/v3/#payment-tokens_delete
	 *
	 * @since 4.25.0
	 *
	 * @param string $token_id The payment token ID to delete.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function delete_payment_token( string $token_id ) {
		return $this->client_delete(
			'v3/vault/payment-tokens/' . $token_id,
			[],
			[
				'headers' => $this->get_request_headers( $token_id ),
				'body'    => [],
			]
		);
	}

	/**
	 * Creates a setup token in the PayPal API.
	 *
	 * @see https://developer.paypal.com/docs/api/payment-tokens/v3/#setup-tokens_create
	 *
	 * @since 4.25.0
	 *
	 * @phpstan-param array{
	 *     payment_source: array<string,mixed>,
	 *     customer_id?: string,
	 * } $data
	 *
	 * @param array<string,mixed> $data The data to use.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_setup_token( array $data ) {
		$body = [
			'payment_source' => $data['payment_source'],
		];

		// Add customer if provided.
		if ( ! empty( $data['customer_id'] ) ) {
			$body['customer'] = [
				'id' => $data['customer_id'],
			];
		}

		return $this->client_post(
			'v3/vault/setup-tokens',
			[],
			[
				'headers' => $this->get_merchant_request_headers(
					Cast::to_string( wp_json_encode( $data ) ) . time()
				),
				'body'    => $body,
			]
		);
	}

	/**
	 * Fetches an access token from the PayPal API using the authorization code.
	 *
	 * @since 4.25.0
	 *
	 * @param string $shared_id The shared ID to use.
	 * @param string $auth_code The authorization code to use.
	 * @param string $hash      The hash saved in the transient to use as the verifier code.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_access_token_from_authorization_code(
		string $shared_id,
		string $auth_code,
		string $hash
	) {
		$auth = base64_encode( $shared_id ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- We need to encode the shared ID.

		$args = [
			'headers' => [
				'Authorization' => sprintf( 'Basic %s', $auth ),
				'Content-Type'  => 'application/x-www-form-urlencoded',
			],
			'body'    => [
				'grant_type'    => 'authorization_code',
				'code'          => $auth_code,
				'code_verifier' => $hash,
			],
		];

		$response = $this->client_post( 'v1/oauth2/token', [], $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( Arr::has( $response, 'error_description' ) ) {
			return new WP_Error(
				'ld-paypal-checkout-api-client-error',
				Cast::to_string( Arr::get( $response, 'error_description', '' ) ),
				$response
			);
		}

		// Update the access token data for later use.
		$this->save_access_token_data( $response );

		return $response;
	}

	/**
	 * Fetches the seller status from the PayPal API.
	 *
	 * @see https://developer.paypal.com/docs/api/partner-referrals/v1/#merchant-integration_status
	 *
	 * @since 4.25.0
	 *
	 * @param string $seller_merchant_id The seller merchant ID.
	 * @param string $partner_id         The partner ID.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_seller_status( string $seller_merchant_id, string $partner_id ) {
		return $this->client_get(
			sprintf(
				'v1/customer/partners/%1$s/merchant-integrations/%2$s',
				rawurlencode( $partner_id ),
				rawurlencode( $seller_merchant_id )
			),
			[],
			[
				'headers' => $this->get_request_headers( $seller_merchant_id . $partner_id ),
				'body'    => [],
			]
		);
	}

	/**
	 * Returns a client token if it's still valid, otherwise generates a new one.
	 *
	 * @since 4.25.0
	 *
	 * @return array{
	 *     client_token?: string,
	 *     expires_in?: int,
	 *     valid_until?: int,
	 * }
	 */
	public function get_client_token(): array {
		$stored_token = Arr::wrap( get_option( $this->client_token_key, [] ) );
		$valid_until  = Cast::to_int( Arr::get( $stored_token, 'valid_until', 0 ) );

		// If the token is still valid, return it.
		if (
			! empty( $stored_token )
			&& $valid_until > time()
		) {
			return $stored_token;
		}

		/**
		 * Access token.
		 *
		 * @var array{
		 *     client_token?: string,
		 *     expires_in?: int,
		 * }|WP_Error $token The token data.
		 */
		$token = $this->client_post( 'v1/identity/generate-token' );

		if ( is_wp_error( $token ) ) {
			return [];
		}

		$expires_in = Cast::to_int(
			Arr::get( $token, 'expires_in', 0 )
		);

		/*
		 * The token is valid for 1 hour, but we store it until 5 minutes before
		 * it expires to avoid any issues.
		 *
		 * The checkout form is refreshed in case the token is expired, but we
		 * want to avoid any issues with the token being invalid during the
		 * checkout process or if the user takes too long to complete the payment.
		 */
		$token['valid_until'] = ( time() + $expires_in ) - 300;

		update_option( $this->client_token_key, $token );

		return $token;
	}

	/**
	 * Fetches an ID token from the PayPal API using client credentials for first time payers.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_first_time_payer_id_token() {
		$client_data = $this->get_client_data();
		$auth        = base64_encode( $client_data['client_id'] . ':' . $client_data['client_secret'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- We need to encode the client credentials.

		return $this->client_post(
			'v1/oauth2/token',
			[],
			[
				'headers' => [
					'Authorization' => sprintf( 'Basic %s', $auth ),
					'Content-Type'  => 'application/x-www-form-urlencoded',
				],
				'body'    => [
					'grant_type'    => 'client_credentials',
					'response_type' => 'id_token',
				],
			]
		);
	}

	/**
	 * Fetches an ID token from the PayPal API using client credentials for existing payers.
	 *
	 * @since 4.25.0
	 *
	 * @param string $customer_id The customer ID to use.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_id_token( string $customer_id ) {
		$client_data = $this->get_client_data();
		$auth        = base64_encode( $client_data['client_id'] . ':' . $client_data['client_secret'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- We need to encode the client credentials.

		return $this->client_post(
			'v1/oauth2/token',
			[],
			[
				'headers' => [
					'Authorization' => sprintf( 'Basic %s', $auth ),
					'Content-Type'  => 'application/x-www-form-urlencoded',
				],
				'body'    => [
					'grant_type'         => 'client_credentials',
					'response_type'      => 'id_token',
					'target_customer_id' => $customer_id,
				],
			]
		);
	}

	/**
	 * Returns the headers for a PayPal request.
	 *
	 * @since 4.25.0
	 *
	 * @param string $request_id The request ID to use.
	 *
	 * @return array<string,string>
	 */
	protected function get_request_headers( string $request_id ): array {
		return [
			'PayPal-Partner-Attribution-Id' => Payment_Gateway::get_partner_attribution_id(),
			'PayPal-Request-Id'             => md5( $request_id . Payment_Gateway::get_partner_attribution_id() ),
			'Prefer'                        => 'return=representation',
		];
	}

	/**
	 * Returns the headers for a PayPal merchant request.
	 *
	 * @since 4.25.0
	 *
	 * @param string $request_id The request ID to use.
	 *
	 * @return array<string,string>
	 */
	protected function get_merchant_request_headers( string $request_id ): array {
		$client_data = $this->get_client_data();

		return array_merge(
			$this->get_request_headers( $request_id ),
			[
				'PayPal-Auth-Assertion' => $this->generate_auth_assertion(
					Cast::to_string( Arr::get( $client_data, 'client_id', '' ) ),
					Cast::to_string( Arr::get( $client_data, 'merchant_id', '' ) )
				),
			]
		);
	}
}
