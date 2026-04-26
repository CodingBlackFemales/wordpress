<?php
/**
 * Stripe Webhook Auto Configuring Handler.
 *
 * @since 4.20.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Stripe;

use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Utilities\Str;
use Learndash_Payment_Gateway;
use Learndash_Stripe_Gateway;

/**
 * Stripe Webhook Auto Configuring Handler.
 *
 * @since 4.20.1
 *
 * @phpstan-type WebhooksConfiguration array{
 *  id: string,
 *  secret: string,
 * }
 */
class Webhook_Auto_Configuring {
	/**
	 * Stripe mode live.
	 *
	 * @since 4.20.1
	 *
	 * @var string
	 */
	private const STRIPE_MODE_LIVE = 'live';

	/**
	 * Stripe mode sandbox.
	 *
	 * @since 4.20.1
	 *
	 * @var string
	 */
	private const STRIPE_MODE_SANDBOX = 'sandbox';

	/**
	 * The base name of the option that stores the auto-configuring webhooks data in the database.
	 *
	 * This key will be concatenated with the mode (live or sandbox). Example: learndash_stripe_webhooks_live.
	 *
	 * @since 4.20.1
	 *
	 * @var string
	 */
	private const STRIPE_WEBHOOKS_OPTION_BASE_NAME = 'learndash_stripe_webhooks_';

	/**
	 * Stores the error message, if any. Default is an empty string.
	 *
	 * @var string
	 */
	private $error_message = '';

	/**
	 * Returns the Whodat server URL.
	 *
	 * @since 4.20.1
	 *
	 * @return string
	 */
	private static function get_whodat_server_url(): string {
		if ( defined( 'LEARNDASH_WHODAT_SERVER_URL' ) ) {
			return LEARNDASH_WHODAT_SERVER_URL;
		}

		return 'https://whodat.stellarwp.com/';
	}

	/**
	 * Returns the default error message.
	 *
	 * @since 4.20.1
	 *
	 * @param bool $is_live_mode Whether is live mode.
	 *
	 * @return string
	 */
	private static function get_default_error_message( bool $is_live_mode ): string {
		$action_nonce = wp_create_nonce( Connection_Handler::$ajax_action_post_connect );

		return sprintf(
			// translators: %1$s: Opening anchor tag, %2$s: Closing anchor tag.
			__( 'Your webhooks are not properly configured. %1$sConfigure webhooks%2$s', 'learndash' ),
			'<a href="#" id="learndash-stripe-configure-webhooks" data-nonce="' . $action_nonce . '" data-is-live-mode="' . ( $is_live_mode ? 'true' : 'false' ) . '">',
			'</a>'
		);
	}

	/**
	 * Enables a webhook endpoint for the Stripe account ID provided.
	 *
	 * @since 4.20.1
	 *
	 * @param string $account_id   The Stripe account ID.
	 * @param string $webhook_url  The webhook URL.
	 * @param bool   $is_live_mode Whether is live mode.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function enable( string $account_id, string $webhook_url, bool $is_live_mode ): bool {
		// Validate the input to prevent unnecessary requests.

		if (
			empty( $account_id )
			|| empty( $webhook_url )
		) {
			$this->error_message = self::get_default_error_message( $is_live_mode );
			$this->add_log_message( 'Empty account ID or webhook URL.' );

			return false;
		}

		$mode = $is_live_mode ? self::STRIPE_MODE_LIVE : self::STRIPE_MODE_SANDBOX;

		$response_data = $this->send_whodat_request(
			'ld/commerce/v1/stripe/webhook/enable',
			[
				'stripe_user_id' => $account_id,
				'webhook_url'    => $webhook_url,
				'mode'           => $mode,
				'known_webhooks' => [ $this->get_webhooks_configuration( $is_live_mode )['id'] ],
			]
		);

		if ( ! is_array( $response_data ) ) {
			// Check if we reach the Stripe webhooks limit. It's ugly, but it's the only way to check it for now.

			$this->error_message = is_string( $response_data ) && Str::contains( $response_data, 'have reached the maximum of 16 test webhook endpoints' )
				? __( 'You have reached the limit of 16 webhooks in Stripe. Please disconnect them in your Stripe Dashboard or connect a different account.', 'learndash' )
				: self::get_default_error_message( $is_live_mode );

			$this->add_log_message( 'Invalid response data: ' . print_r( $response_data, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Intentional.

			return false;
		}

		// Case of invalid domain.

		if (
			! is_array( $response_data['webhook'] )
			&& ! Cast::to_bool( $response_data['webhook'] )
		) {
			$this->error_message = sprintf(
				// translators: %s: URL to the Stripe Webhooks documentation.
				__( 'Your Webhooks were not properly configured. Try <a target="_blank" href="%1$s">adding the webhook manually</a> or reach out to our support team for assistance.', 'learndash' ),
				'https://learndash.com/support/kb/core/settings/stripe/#h-add-a-stripe-webhook'
			);

			$this->add_log_message( 'Invalid webhook data: ' . print_r( $response_data, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Intentional.

			return false;
		}

		$this->add_webhook_configuration(
			$is_live_mode,
			Cast::to_string( $response_data['webhook']['id'] ),
			Cast::to_string( $response_data['webhook']['secret'] )
		);

		return true;
	}

	/**
	 * Disables a webhook endpoint for the Stripe account ID provided.
	 *
	 * @since 4.20.1
	 *
	 * @param string $account_id   The Stripe account ID.
	 * @param string $webhook_url  The webhook URL.
	 * @param bool   $is_live_mode Whether is live mode.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function disable( string $account_id, string $webhook_url, bool $is_live_mode ): bool {
		// Validate the input to prevent unnecessary requests.

		if (
			empty( $account_id )
			|| empty( $webhook_url )
		) {
			$this->add_log_message( 'Empty account ID or webhook URL.' );

			return false;
		}

		$mode = $is_live_mode ? self::STRIPE_MODE_LIVE : self::STRIPE_MODE_SANDBOX;

		$known_webhooks = $this->get_webhooks_configuration( $is_live_mode )['id'];

		// If there are no known webhooks, we don't need to disable anything.

		if ( empty( $known_webhooks ) ) {
			return true;
		}

		$response_data = $this->send_whodat_request(
			'ld/commerce/v1/stripe/webhook/disable',
			[
				'stripe_user_id' => $account_id,
				'webhook_url'    => $webhook_url,
				'mode'           => $mode,
				'known_webhooks' => [ $known_webhooks ],
			]
		);

		// We should not update the webhook configuration because we need to maintain the webhook data in case a user wants to enable it again.

		// In case of failure, the response data will contain the boolean false in the 'webhook' key.
		return is_array( $response_data ) && is_array( $response_data['webhook'] );
	}

	/**
	 * Returns the error message from the last operation.
	 *
	 * @since 4.20.1
	 *
	 * @return string Empty string if there is no error.
	 */
	public function get_last_error_message(): string {
		return $this->error_message;
	}

	/**
	 * Returns the signing key (webhook secret key) for the given mode.
	 *
	 * @since 4.20.1
	 *
	 * @param bool $is_live_mode Whether is live mode.
	 *
	 * @return string Empty string if the key is not found.
	 */
	public function get_signing_key( bool $is_live_mode ): string {
		return $this->get_webhooks_configuration( $is_live_mode )['secret'];
	}

	/**
	 * Adds a log message to the Stripe Connect Orders log.
	 *
	 * @since 4.20.1
	 *
	 * @param string $message The message.
	 *
	 * @return void
	 */
	private function add_log_message( string $message ): void {
		$stripe_gateway = Learndash_Payment_Gateway::get_active_payment_gateway_by_name( Learndash_Stripe_Gateway::get_name() );

		if ( ! $stripe_gateway instanceof Learndash_Stripe_Gateway ) {
			return;
		}

		$stripe_gateway->log_error(
			__( 'LearnDash Stripe Webhook Auto Configuring error:', 'learndash' ) . ' ' . $message
		);
	}

	/**
	 * Sends a request to the Whodat server.
	 *
	 * @since 4.20.1
	 *
	 * @param string              $endpoint The endpoint.
	 * @param array<string,mixed> $args     The arguments.
	 *
	 * @return mixed The answer data. False if the request failed.
	 */
	private function send_whodat_request( string $endpoint, array $args ) {
		$response = wp_remote_get( trailingslashit( self::get_whodat_server_url() ) . $endpoint . '?' . http_build_query( $args ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_body = wp_remote_retrieve_body( $response );

		if ( empty( $response_body ) ) {
			return false;
		}

		$json_value = json_decode( $response_body, true );

		// If the response is not a JSON, we return the response body.

		return is_array( $json_value ) ? $json_value : $response_body;
	}

	/**
	 * Returns the webhook configuration.
	 *
	 * @since 4.20.1
	 *
	 * @param bool $is_live_mode Whether is live mode.
	 *
	 * @return WebhooksConfiguration
	 */
	private function get_webhooks_configuration( bool $is_live_mode ): array {
		$default_configuration = [
			'id'     => '',
			'secret' => '',
		];

		$option = get_option(
			self::STRIPE_WEBHOOKS_OPTION_BASE_NAME . ( $is_live_mode ? self::STRIPE_MODE_LIVE : self::STRIPE_MODE_SANDBOX ),
			[]
		);

		// Validate the option to prevent unexpected behavior.

		$option = ! is_array( $option ) || empty( $option )
			? $default_configuration
			: wp_parse_args( $option, $default_configuration );

		/**
		 * Set the array shape.
		 *
		 * Unfortunately, PHPStan doesn't validate it well. We have tests to confirm that the array shape is correct.
		 *
		 * @var WebhooksConfiguration $option
		 */
		return $option;
	}

	/**
	 * Adds a webhook configuration.
	 *
	 * @since 4.20.1
	 *
	 * @param bool   $is_live_mode Whether is live mode.
	 * @param string $id           The Webhook ID.
	 * @param string $secret       The Webhook secret.
	 *
	 * @return void
	 */
	private function add_webhook_configuration( bool $is_live_mode, string $id, string $secret ): void {
		/**
		 * If the secret is empty or false and we have a valid ID, we don't need to update the webhook.
		 * It means that the webhook is the same.
		 */

		if (
			(
				empty( $secret )
				|| ! Cast::to_bool( $secret )
			) &&
				! empty( $id )
		) {
			return;
		}

		update_option(
			self::STRIPE_WEBHOOKS_OPTION_BASE_NAME . ( $is_live_mode ? self::STRIPE_MODE_LIVE : self::STRIPE_MODE_SANDBOX ),
			[
				'id'     => $id,
				'secret' => $secret,
			]
		);
	}
}
