<?php
/**
 * Stripe Webhook Validation Handler
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Payments\Stripe;

use Learndash_Payment_Gateway;
use Learndash_Stripe_Gateway;

/**
 * Stripe Webhook Validation Handler.
 *
 * @since 4.6.0
 */
class Webhook_Setup_Validator {
	private const TRANSIENT_KEY = 'learndash_stripe_webhook_validation';
	private const TRANSIENT_EXPIRATION = 60;

	private const STATUS_IN_PROGRESS = 'in_progress';
	private const STATUS_SUCCESS = 'success';

	/**
	 * Ajax action name.
	 *
	 * @since 4.6.0
	 *
	 * @var string
	 */
	public static $ajax_action = 'learndash_validate_stripe_webhook';

	/**
	 * Handles webhook validation ajax request.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function handle_ajax_request(): void {
		// Validate.

		if ( ! is_admin() ) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'Invalid request.', 'learndash' ),
				]
			);
		}

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, self::$ajax_action ) ) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'Invalid nonce.', 'learndash' ),
				]
			);
		}

		$call_index = isset( $_POST['call_index'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['call_index'] ) ) : 0;

		if ( $call_index < 1 ) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'Invalid call index.', 'learndash' ),
				]
			);
		}

		// Process.

		// If it's the first call, we need to create and delete a fake coupon to test the webhook.
		// A webhook processor will update the transient to mark the validation as successful.

		if ( 1 === $call_index ) {
			$stripe_gateway = Learndash_Payment_Gateway::get_active_payment_gateway_by_name( Learndash_Stripe_Gateway::get_name() );

			if ( ! $stripe_gateway instanceof Learndash_Stripe_Gateway ) {
				wp_send_json_error(
					[
						'message' => esc_html__( 'Stripe gateway is not active.', 'learndash' ),
					]
				);
			}

			$coupon_id = $stripe_gateway->create_fake_coupon_for_webhook_test();

			$this->mark_in_progress( $coupon_id );
		}

		$status = self::get_status();

		// Return response.

		wp_send_json_success(
			[
				'success'  => self::STATUS_SUCCESS === $status,
				'fail'     => '' === $status,
				'progress' => self::STATUS_IN_PROGRESS === $status,
			]
		);
	}

	/**
	 * Deletes transient.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function reset(): void {
		delete_transient( self::TRANSIENT_KEY );
	}

	/**
	 * Sets transient to mark webhook validation in progress.
	 *
	 * @since 4.6.0
	 *
	 * @param string $secret_key Secret key.
	 *
	 * @return void
	 */
	public function mark_in_progress( string $secret_key ): void {
		$data = [
			'status'     => self::STATUS_IN_PROGRESS,
			'secret_key' => $secret_key,
		];

		// If in a minute it will still be in progress, it will be deleted. And the status will be empty.
		set_transient( self::TRANSIENT_KEY, $data, self::TRANSIENT_EXPIRATION );
	}

	/**
	 * Sets transient to mark webhook validation in progress.
	 *
	 * @since 4.6.0
	 *
	 * @param string $secret_key Secret key.
	 *
	 * @return bool True if transient was set.
	 */
	public function mark_successful( string $secret_key ): bool {
		if ( $this->get_secret_key() !== $secret_key ) {
			return false;
		}

		$data = [
			'status'     => self::STATUS_SUCCESS,
			'secret_key' => $secret_key,
		];

		return set_transient( self::TRANSIENT_KEY, $data, self::TRANSIENT_EXPIRATION );
	}

	/**
	 * Returns status or empty string if no transient.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	protected function get_status(): string {
		return $this->get_data()['status'] ?? '';
	}

	/**
	 * Returns secret_key or empty string if no transient.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	protected function get_secret_key(): string {
		return $this->get_data()['secret_key'] ?? '';
	}

	/**
	 * Returns data from transient or empty array if no transient.
	 *
	 * @since 4.6.0
	 *
	 * @return array<string, string>
	 */
	protected function get_data(): array {
		$data = get_transient( self::TRANSIENT_KEY );

		if ( ! is_array( $data ) || empty( $data ) ) {
			return [];
		}

		return $data;
	}
}
