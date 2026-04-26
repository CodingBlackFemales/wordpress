<?php
/**
 * Subscriptions Provider.
 *
 * Handles the initialization and management of subscription recurring payments functionality.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Subscriptions;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Provider class for subscriptions.
 *
 * @since 4.25.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->when( Processor::class )
			->needs( '$logger' )
			->give(
				function () {
					return new Logger();
				}
			);

		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function hooks(): void {
		// Initialize the scheduler.
		add_action(
			'init',
			$this->container->callback(
				Scheduler::class,
				'register_daily_check'
			)
		);

		// Register the daily subscription payment checker hook.
		add_action(
			Scheduler::$daily_check_hook,
			$this->container->callback(
				Scheduler::class,
				'check_due_payments'
			)
		);

		// Register the subscription payment processor hook.
		add_action(
			Scheduler::$payment_process_hook,
			$this->container->callback(
				Processor::class,
				'process_payment'
			),
			10,
			2
		);

		// Register the logger for the processor.
		add_filter(
			'learndash_loggers',
			$this->container->callback(
				Processor::class,
				'register_logger'
			)
		);

		// Handle subscription cancellation.

		add_action(
			'init',
			$this->container->callback(
				Handler::class,
				'handle_cancellation'
			)
		);

		// Register admin notices.

		add_action(
			'admin_init',
			$this->container->callback(
				Admin\Notice::class,
				'register_admin_notice'
			)
		);

		// Register the subscription payment retry hook.
		add_action(
			'learndash_subscription_payment_retry',
			$this->container->callback(
				Processor::class,
				'process_payment_retry'
			)
		);
	}
}
