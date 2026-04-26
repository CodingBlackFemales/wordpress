<?php
/**
 * LearnDash Stripe Provider class.
 *
 * @since 4.20.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Stripe;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for Stripe.
 *
 * @since 4.20.1
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.20.1
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton( Webhook_Auto_Configuring::class );

		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.20.1
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function hooks() {
		// Stripe connection handler.

		add_action(
			'wp_ajax_' . Connection_Handler::$ajax_action_pre_disconnect,
			$this->container->callback( Connection_Handler::class, 'handle_ajax_pre_disconnect_request' )
		);

		add_action(
			'wp_ajax_' . Connection_Handler::$ajax_action_post_connect,
			$this->container->callback( Connection_Handler::class, 'handle_ajax_post_connect_request' )
		);
	}
}
