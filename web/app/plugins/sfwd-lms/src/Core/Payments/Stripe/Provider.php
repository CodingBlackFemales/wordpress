<?php
/**
 * LearnDash Stripe Provider class.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Payments\Stripe;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for Stripe.
 *
 * @since 4.6.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.6.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton( Webhook_Setup_Validator::class );

		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.6.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action(
			'wp_ajax_' . Webhook_Setup_Validator::$ajax_action,
			$this->container->callback( Webhook_Setup_Validator::class, 'handle_ajax_request' )
		);
	}
}
