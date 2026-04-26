<?php
/**
 * Subscription Processor.
 *
 * Handles processing of individual subscriptions.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Subscriptions;

use LearnDash\Core\Models\Commerce\Subscription;
use LearnDash\Core\Modules\Payments\Subscriptions\Retry_Scheduler;
use WP_User;

/**
 * Processor class for subscriptions.
 *
 * @since 4.25.0
 */
class Processor {
	/**
	 * Logger instance.
	 *
	 * @since 4.25.0
	 *
	 * @var Logger|null
	 */
	private ?Logger $logger = null;

	/**
	 * Constructor.
	 *
	 * @since 4.25.0
	 *
	 * @param Logger $logger The logger.
	 */
	public function __construct( Logger $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Registers the logger.
	 *
	 * @since 4.25.0
	 *
	 * @param array<Logger> $loggers The loggers.
	 *
	 * @return array<Logger> The loggers.
	 */
	public function register_logger( array $loggers ): array {
		if ( ! $this->logger instanceof Logger ) {
			return $loggers;
		}

		$loggers[] = $this->logger;

		return $loggers;
	}

	/**
	 * Process a payment for a specific subscription.
	 *
	 * @since 4.25.0
	 *
	 * @param int $subscription_id The subscription ID.
	 * @param int $user_id         The user ID.
	 *
	 * @return void
	 */
	public function process_payment( int $subscription_id, int $user_id ): void {
		$subscription = Subscription::find( $subscription_id );

		if ( ! $subscription ) {
			$this->log_error( 'Subscription not found: ' . $subscription_id );
			return;
		}

		$user = get_user_by( 'ID', $user_id );

		if (
			! $user
			|| ! $user->exists()
		) {
			$this->log_error( 'User not found: ' . $user_id );
			return;
		}

		// Check if subscription is still active or in trial.
		if ( ! in_array( $subscription->get_status(), $this->get_active_statuses(), true ) ) {
			$this->log_info( 'Subscription is not active or in trial: ' . $subscription_id );
			return;
		}

		$product = $subscription->get_product();

		if ( ! $product ) {
			$this->log_error( 'Product not found for subscription: ' . $subscription_id );
			return;
		}

		// Process the payment through the gateway.
		$this->process_payment_through_gateway(
			$subscription,
			$user,
			$subscription->get_gateway_name()
		);
	}

	/**
	 * Process a payment retry for a specific subscription.
	 *
	 * @since 4.25.3
	 *
	 * @param int $subscription_id The subscription ID.
	 *
	 * @return void
	 */
	public function process_payment_retry( int $subscription_id ): void {
		$subscription = Subscription::find( $subscription_id );

		if ( ! $subscription ) {
			$this->log_error( 'Subscription not found for retry: ' . $subscription_id );
			return;
		}

		$this->log_info( 'Processing scheduled payment retry for subscription ID[' . $subscription_id . ']' );

		// Process the payment through the gateway.
		$this->process_payment_through_gateway(
			$subscription,
			$subscription->get_user(),
			$subscription->get_gateway_name()
		);
	}

	/**
	 * Process the subscription through the payment gateway.
	 *
	 * @since 4.25.0
	 *
	 * @param Subscription $subscription The subscription.
	 * @param WP_User      $user         The user.
	 * @param string       $gateway_name The payment gateway name.
	 *
	 * @return void
	 */
	private function process_payment_through_gateway(
		Subscription $subscription,
		WP_User $user,
		string $gateway_name
	): void {
		/**
		 * Fires before processing a subscription payment.
		 *
		 * @since 4.25.0
		 *
		 * @param Subscription              $subscription The subscription.
		 * @param WP_User                   $user         The user.
		 */
		do_action( 'learndash_payment_subscription_before_process_' . $gateway_name, $subscription, $user );

		// Get the payment token for this subscription.
		$payment_token = $subscription->get_payment_token();

		if ( empty( $payment_token ) ) {
			$this->log_error( 'No payment token found for subscription: ' . $subscription->get_id() );
		}

		// Maybe expire the subscription if the recurring times is reached.

		$subscription->maybe_expire();

		// Bail if the subscription is expired.

		if ( $subscription->is_expired() ) {
			$this->log_info( 'Subscription has been expired: ' . $subscription->get_id() );
			return;
		}

		/**
		 * Fires to process a subscription payment with a specific gateway.
		 *
		 * @since 4.25.0
		 *
		 * @param bool                  $result        The result of the payment processing.
		 * @param Subscription          $subscription  The subscription.
		 * @param WP_User               $user          The user.
		 * @param array<string, string> $payment_token The payment token.
		 *
		 * @return bool True if payment was successful, false otherwise.
		 */
		$result = apply_filters(
			'learndash_payment_subscription_process_with_gateway_' . $gateway_name,
			false,
			$subscription,
			$user,
			$payment_token
		);

		if ( $result ) {
			$this->log_info( 'Payment processing successful for subscription: ' . $subscription->get_id() );

			// Reset retry count and cancel any scheduled retries on successful payment.
			$subscription->reset_retry_count();
			Retry_Scheduler::cancel_retry( $subscription );

			// Calculate the next payment date.
			$subscription->calculate_next_payment_date();

			/**
			 * Fires after successfully processing a subscription payment.
			 *
			 * @since 4.25.0
			 *
			 * @param Subscription $subscription The subscription.
			 * @param WP_User      $user         The user.
			 */
			do_action( 'learndash_payment_subscription_after_success_' . $gateway_name, $subscription, $user );
		} else {
			$this->log_error( 'Payment processing failed for subscription: ' . $subscription->get_id() );

			/**
			 * Fires after failing to process a subscription payment.
			 *
			 * @since 4.25.0
			 *
			 * @param Subscription $subscription The subscription.
			 * @param WP_User      $user         The user.
			 */
			do_action( 'learndash_payment_subscription_after_failure_' . $gateway_name, $subscription, $user );
		}
	}

	/**
	 * Get the active statuses.
	 *
	 * @since 4.25.0
	 *
	 * @return string[] The active statuses.
	 */
	private function get_active_statuses(): array {
		return [
			Subscription::$status_active,
			Subscription::$status_trial,
		];
	}

	/**
	 * Log an info message.
	 *
	 * @since 4.25.0
	 *
	 * @param string $message The message to log.
	 *
	 * @return void
	 */
	private function log_info( string $message ): void {
		if ( ! $this->logger ) {
			return;
		}

		$this->logger->info( $message );
	}

	/**
	 * Log an error message.
	 *
	 * @since 4.25.0
	 *
	 * @param string $message The message to log.
	 *
	 * @return void
	 */
	private function log_error( string $message ): void {
		if ( ! $this->logger ) {
			return;
		}

		$this->logger->error( $message );
	}
}
