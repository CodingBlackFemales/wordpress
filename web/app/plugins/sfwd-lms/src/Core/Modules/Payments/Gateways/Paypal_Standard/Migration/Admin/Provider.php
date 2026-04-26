<?php
/**
 * PayPal Standard Migration Admin Provider.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal_Standard\Migration\Admin;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * PayPal Standard Migration Admin Provider.
 *
 * This class is used to register the migration related classes to help
 * migrate subscriptions from PayPal Standard to PayPal Checkout in admin.
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
		// Register AJAX actions.
		add_action(
			'wp_ajax_learndash_paypal_migration_table_pagination',
			$this->container->callback(
				Pagination::class,
				'ajax_table_pagination'
			)
		);
	}
}
