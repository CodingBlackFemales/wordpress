<?php
/**
 * LearnDash PayPal Payment Gateway Webhook Client.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal;

use LearnDash\Core\App;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Traits\Request;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use LearnDash\Core\Utilities\Cast;
use WP_Error;

/**
 * PayPal payment gateway webhook client class.
 *
 * @since 4.25.0
 */
class Webhook_Client {
	use Request;

	/**
	 * The webhook data option name.
	 *
	 * @var string
	 */
	private string $webhook_data_option_name = 'learndash_paypal_checkout_webhook_data';

	/**
	 * The payment gateway instance.
	 *
	 * @var Payment_Gateway|null
	 */
	private ?Payment_Gateway $gateway = null;

	/**
	 * Constructor.
	 *
	 * @since 4.25.0
	 */
	public function __construct() {
		$paypal_gateway = App::get( Payment_Gateway::class );

		if ( $paypal_gateway instanceof Payment_Gateway ) {
			$this->gateway = $paypal_gateway;
		}
	}

	/**
	 * Returns the webhook events.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string,string>
	 */
	public function get_webhook_events(): array {
		return [
			'CHECKOUT.ORDER.COMPLETED'    => sprintf(
				// translators: %s: order label.
				__( 'Checkout %s completed', 'learndash' ),
				learndash_get_custom_label_lower( 'order' )
			),
			'CHECKOUT.ORDER.APPROVED'     => sprintf(
				// translators: %s: order label.
				__( 'Checkout %s approved', 'learndash' ),
				learndash_get_custom_label_lower( 'order' )
			),
			'PAYMENT.ORDER.CANCELLED'     => sprintf(
				// translators: %s: order label.
				__( '%s payment canceled', 'learndash' ),
				learndash_get_custom_label( 'order' )
			),
			'PAYMENT.CAPTURE.COMPLETED'   => __( 'Payment capture completed', 'learndash' ),
			'PAYMENT.CAPTURE.DENIED'      => __( 'Payment capture denied', 'learndash' ),
			'PAYMENT.CAPTURE.REFUNDED'    => __( 'Payment capture refunded', 'learndash' ),
			'PAYMENT.CAPTURE.REVERSED'    => __( 'Payment capture reversed', 'learndash' ),
			'VAULT.PAYMENT-TOKEN.CREATED' => __( 'Vault payment token created', 'learndash' ),
			'VAULT.PAYMENT-TOKEN.DELETED' => __( 'Vault payment token deleted', 'learndash' ),
		];
	}

	/**
	 * Returns whether the event is processed by the payment gateway.
	 *
	 * @since 4.25.0
	 *
	 * @param string $event_name The event name to check.
	 *
	 * @return bool
	 */
	public function is_event_processable( string $event_name ): bool {
		return in_array( $event_name, array_keys( $this->get_webhook_events() ), true );
	}

	/**
	 * Creates or updates the existing webhooks.
	 *
	 * @since 4.25.0
	 *
	 * @param bool $force_update Whether to force an update of the webhooks.
	 *
	 * @return bool|WP_Error
	 */
	public function create_or_update_existing_webhooks( bool $force_update = false ) {
		$existing_id = Cast::to_string(
			$this->get_webhook_data( 'id' )
		);

		$this->log( 'Creating or updating existing webhooks' );
		$this->log( 'Existing ID: ' . $existing_id );

		// If we don't have any existing data, we create the webhooks.
		if ( ! $existing_id ) {
			$this->log( 'No existing webhooks found, creating new ones.' );

			$webhook = $this->create_webhooks();

			// Update the settings if we have a webhook.
			if ( ! is_wp_error( $webhook ) ) {
				return $this->update_webhook_data( $webhook );
			}

			if ( 'learndash-paypal-checkout-webhook-url-already-exists' === $webhook->get_error_code() ) {
				$this->log( 'Webhook URL already exists, checking for existing webhooks.' );

				$error_message     = $webhook->get_error_message();
				$webhook_url       = $this->get_webhook_url();
				$existing_webhooks = $this->list_webhooks();

				$this->log( 'List of existing webhooks: ' . wp_json_encode( $existing_webhooks ) );

				if ( is_wp_error( $existing_webhooks ) ) {
					return $existing_webhooks;
				}

				// If we have multiple webhooks, we need to find the one that matches the URL.
				$existing_webhooks = array_filter(
					array_map(
						static function ( $webhook ) use ( $webhook_url ) {
							$webhook = Arr::wrap( $webhook );

							if ( $webhook_url !== Cast::to_string( Arr::get( $webhook, 'url', '' ) ) ) {
								return null;
							}

							return $webhook;
						},
						$existing_webhooks
					)
				);

				if ( empty( $existing_webhooks ) ) {
					$this->log( 'The webhook URL already exists but no webhooks matched the URL, returning an error.' );

					return new WP_Error( 'learndash-paypal-checkout-webhook-unexpected-update-create', $error_message );
				}

				$existing_webhook = current( $existing_webhooks );

				if ( ! $this->webhooks_needs_update( $existing_webhook ) ) {
					$this->log( 'The existing webhook is up-to-date. Nothing to do.' );

					// We found a existing webhook that matched the URL but we just save it to the DB since it was up-to-date.
					return $this->update_webhook_data( $existing_webhook );
				}
			}

			// Returns the failed webhook creation or update.
			return $webhook;
		}

		$this->log( 'Checking if webhooks need update.' );

		if (
			! $this->webhooks_needs_update()
			&& ! $force_update
		) {
			$this->log( 'No webhooks update needed.' );

			return true;
		}

		$this->log( 'Webhooks need update.' );

		$webhook = $this->update_webhooks( $existing_id );

		// Update the settings if the webhook was updated.
		if ( ! is_wp_error( $webhook ) ) {
			return $this->update_webhook_data( $webhook );
		}

		// If we are forcing an update, we try to get the webhook and update the data.
		if ( $force_update ) {
			$updated_webhook = $this->get_webhook( $existing_id );

			$this->log( 'Fetched latest webhook data: ' . wp_json_encode( $updated_webhook ) );

			if ( ! is_wp_error( $updated_webhook ) ) {
				return $this->update_webhook_data( $updated_webhook );
			}
		}

		$this->log( 'Error updating webhooks, creating new ones.' );

		$webhook = $this->create_webhooks();

		// Update the settings if a new webhook was created.
		if ( ! is_wp_error( $webhook ) ) {
			return $this->update_webhook_data( $webhook );
		}

		// Returns the failed webhook creation or update.
		return $webhook;
	}

	/**
	 * Returns a list of available webhooks.
	 *
	 * @since 4.25.0
	 *
	 * @return string[]
	 */
	public function get_available_webhooks(): array {
		$events = array_filter( Arr::wrap( $this->get_webhook_data( 'event_types' ) ) );

		if ( empty( $events ) ) {
			return [];
		}

		$webhooks    = [];
		$event_names = $this->get_webhook_events();

		foreach ( $events as $event ) {
			$name = Cast::to_string( Arr::get( $event, 'name', '' ) );

			if ( ! array_key_exists( $name, $event_names ) ) {
				// If the event name is not in the list of available events, we skip it.
				continue;
			}

			$webhooks[] = Cast::to_string( Arr::get( $event_names, $name, '' ) );
		}

		return $webhooks;
	}

	/**
	 * Returns the webhook data from the database.
	 *
	 * @since 4.25.0
	 *
	 * @param string $index The index to fetch from the webhook data.
	 *
	 * @return mixed
	 */
	public function get_webhook_data( string $index = '' ) {
		$webhook_data = get_option( $this->webhook_data_option_name, [] );

		if ( empty( $index ) ) {
			return $webhook_data;
		}

		$webhook_data = Arr::wrap( $webhook_data );

		return Arr::get( $webhook_data, $index, '' );
	}

	/**
	 * Deletes the webhook data from the database.
	 *
	 * @since 4.25.0
	 *
	 * @return bool
	 */
	public function delete_webhook_data(): bool {
		return delete_option( $this->webhook_data_option_name );
	}

	/**
	 * Verifies a webhook signature.
	 *
	 * @see https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature_post
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,mixed> $validation_fields The validation fields to use.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function verify_webhook_signature(
		array $validation_fields
	) {
		return $this->client_post(
			'v1/notifications/verify-webhook-signature',
			[],
			[
				'headers' => [
					'PayPal-Partner-Attribution-Id' => Payment_Gateway::get_partner_attribution_id(),
				],
				'body'    => $validation_fields,
			]
		);
	}

	/**
	 * Updates the webhook data in the database.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,mixed> $webhook_data The webhook data to update.
	 *
	 * @return bool
	 */
	protected function update_webhook_data( array $webhook_data ): bool {
		return update_option( $this->webhook_data_option_name, $webhook_data );
	}

	/**
	 * Returns the webhook URL.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	protected function get_webhook_url(): string {
		return rest_url( 'learndash/v1/commerce/paypal/webhook' );
	}

	/**
	 * Checks if the webhooks need to be updated.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,mixed> $webhook The webhook data or empty array to use data from the database.
	 *
	 * @return bool
	 */
	protected function webhooks_needs_update( array $webhook = [] ): bool {
		if ( empty( $webhook ) ) {
			$webhook_id = Cast::to_string( $this->get_webhook_data( 'id' ) );

			if ( empty( $webhook_id ) ) {
				return true;
			}

			$webhook = $this->get_webhook( $webhook_id );

			if ( is_wp_error( $webhook ) ) {
				return true;
			}
		}

		$webhook = Arr::wrap( $webhook );

		// If these are not valid indexes, we just say we need an update.
		if ( ! isset( $webhook['url'], $webhook['event_types'] ) ) {
			return true;
		}

		$url = Cast::to_string( Arr::get( $webhook, 'url', '' ) );

		if ( $url !== $this->get_webhook_url() ) {
			return true;
		}

		$has_diff_events = array_diff(
			array_keys( $this->get_webhook_events() ),
			wp_list_pluck(
				Arr::wrap( Arr::get( $webhook, 'event_types', [] ) ),
				'name'
			)
		);

		if ( ! empty( $has_diff_events ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Creates all the webhooks needed for the PayPal API.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	protected function create_webhooks() {
		$response = $this->client_post(
			'v1/notifications/webhooks',
			[],
			[
				'headers' => [
					'PayPal-Partner-Attribution-Id' => Payment_Gateway::get_partner_attribution_id(),
				],
				'body'    => [
					'url'         => $this->get_webhook_url(),
					'event_types' => array_map(
						static function ( $event_type ) {
							return [
								'name' => $event_type,
							];
						},
						array_keys( $this->get_webhook_events() )
					),
				],
			]
		);

		$this->log( 'Create webhooks response: ' . wp_json_encode( $response ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// If the webhook was created successfully, return the response.
		if ( '' !== Cast::to_string( Arr::get( $response, 'id', '' ) ) ) {
			return $response;
		}

		// If the webhook was not created successfully, return an error.
		$error = $this->json_decode(
			Cast::to_string( Arr::get( $response, 'body', '' ) )
		);

		$error_name = Cast::to_string( Arr::get( $error, 'name', '' ) );

		if ( empty( $error_name ) ) {
			return new WP_Error(
				'learndash-paypal-checkout-webhook-unexpected',
				esc_html__( 'Unexpected PayPal response when creating webhook', 'learndash' ),
				$response
			);
		}

		if ( 'WEBHOOK_URL_ALREADY_EXISTS' === $error_name ) {
			return new WP_Error(
				'learndash-paypal-checkout-webhook-url-already-exists',
				Cast::to_string( Arr::get( $error, 'message', '' ) ),
				$response
			);
		}

		// Limit has been reached, we cannot just delete all webhooks without permission.
		if ( 'WEBHOOK_NUMBER_LIMIT_EXCEEDED' === $error_name ) {
			return new WP_Error(
				'learndash-paypal-checkout-webhook-limit-exceeded',
				esc_html__( 'PayPal webhook limit has been reached, you need to go into your developer.paypal.com account and remove webhooks from the associated account', 'learndash' ),
				$response
			);
		}

		return new WP_Error(
			'learndash-paypal-checkout-webhook-unexpected',
			esc_html__( 'Unexpected PayPal response when creating webhook', 'learndash' ),
			$response
		);
	}

	/**
	 * Updates a list of webhooks with the given ID.
	 *
	 * @since 4.25.0
	 *
	 * @param string $webhook_id The webhook list ID to update.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	protected function update_webhooks( string $webhook_id ) {
		$response = $this->client_patch(
			'v1/notifications/webhooks/' . rawurlencode( $webhook_id ),
			[],
			[
				'headers' => [
					'PayPal-Partner-Attribution-Id' => Payment_Gateway::get_partner_attribution_id(),
				],
				'body'    => [
					[
						'op'    => 'replace',
						'path'  => '/url',
						'value' => $this->get_webhook_url(),
					],
					[
						'op'    => 'replace',
						'path'  => '/event_types',
						'value' => array_map(
							static function ( $event_type ) {
								return [
									'name' => $event_type,
								];
							},
							array_keys( $this->get_webhook_events() )
						),
					],
				],
			]
		);

		$this->log( 'Update webhooks response: ' . wp_json_encode( $response ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// If the webhook was updated successfully, return the response.
		if ( '' !== Cast::to_string( Arr::get( $response, 'id', '' ) ) ) {
			return $response;
		}

		$error = $this->json_decode(
			Cast::to_string(
				Arr::get( $response, 'body', '' )
			)
		);

		$error_name = Cast::to_string(
			Arr::get( $error, 'name', '' )
		);

		if ( empty( $error_name ) ) {
			return new WP_Error(
				'learndash-paypal-checkout-webhook-update-unexpected',
				esc_html__( 'Unexpected PayPal response when updating webhook', 'learndash' ),
				$response
			);
		}

		if ( 'INVALID_RESOURCE_ID' === $error_name ) {
			return new WP_Error(
				'learndash-paypal-checkout-webhook-update-invalid-id',
				Cast::to_string(
					Arr::get( $error, 'message', '' )
				)
			);
		}

		return new WP_Error(
			'learndash-paypal-checkout-webhook-update-unexpected',
			esc_html__( 'Unexpected PayPal response when updating webhook', 'learndash' ),
			$response
		);
	}

	/**
	 * Fetches a list of webhooks.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	protected function list_webhooks() {
		$response = $this->client_get(
			'v1/notifications/webhooks',
			[],
			[
				'headers' => [
					'PayPal-Partner-Attribution-Id' => Payment_Gateway::get_partner_attribution_id(),
					'Prefer'                        => 'return=representation',
				],
				'body'    => [],
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = Arr::wrap( $response );

		if ( empty( $response['webhooks'] ) ) {
			return new WP_Error(
				'learndash-paypal-checkout-webhook-list-empty',
				esc_html__( 'No webhooks found', 'learndash' ),
				$response
			);
		}

		return $response['webhooks'];
	}

	/**
	 * Returns a webhook by ID.
	 *
	 * @since 4.25.0
	 *
	 * @param string $webhook_id The webhook ID to fetch.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	protected function get_webhook( string $webhook_id ) {
		$response = $this->client_get(
			'v1/notifications/webhooks/' . rawurlencode( $webhook_id ),
			[],
			[
				'headers' => [
					'PayPal-Partner-Attribution-Id' => Payment_Gateway::get_partner_attribution_id(),
				],
				'body'    => [],
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// If the webhook was not found, return an error.
		if ( ! Arr::has( $response, 'id' ) ) {
			return new WP_Error(
				'learndash-paypal-checkout-webhook-not-found',
				esc_html__( 'Webhook not found', 'learndash' ),
				$response
			);
		}

		return $response;
	}

	/**
	 * Logs a message.
	 *
	 * @since 4.25.0
	 *
	 * @param string $message  The message to log.
	 *
	 * @return void
	 */
	private function log( string $message ): void {
		if ( ! $this->gateway instanceof Payment_Gateway ) {
			return;
		}

		$this->gateway->log_info( $message );
	}
}
