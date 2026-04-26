<?php
/**
 * PayPal Standard Migration Provider.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Migration;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * PayPal Standard Migration Provider.
 *
 * This class is used to register the migration related classes to help
 * migrate subscriptions from PayPal Standard to PayPal Checkout.
 *
 * @since 4.25.3
 */
class Provider extends ServiceProvider {
	/**
	 * Register the service provider.
	 *
	 * @since 4.25.3
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( Admin\Provider::class );

		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.25.3
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	private function hooks(): void {
		// Register migration shortcode.
		add_shortcode(
			'ld_migrate_paypal_subscription',
			$this->container->callback(
				Shortcode::class,
				'output'
			)
		);

		// Register Action Scheduler hook for migration.
		add_action(
			Scheduler::$schedule_migration_hook,
			$this->container->callback(
				Processor::class,
				'run_migration'
			),
			10,
			3
		);
	}
}
