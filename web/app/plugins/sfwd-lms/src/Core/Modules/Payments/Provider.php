<?php
/**
 * Payments module provider.
 *
 * @since 4.19.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use StellarWP\Learndash\StellarWP\Assets\Assets;

/**
 * Service provider class for Stripe.
 *
 * @since 4.19.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.19.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( Orders\Provider::class );
		$this->container->register( Gateways\Provider::class );
		$this->container->register( Subscriptions\Provider::class );
		$this->container->register( Emails\Provider::class );

		add_action(
			'admin_enqueue_scripts',
			$this->container->callback( $this, 'enqueue_scripts' )
		);
	}

	/**
	 * Enqueues scripts.
	 *
	 * @since 4.19.0
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		Assets::instance()->enqueue_group( 'learndash-module-payments' );
	}
}
