<?php
/**
 * Payments gateways provider.
 *
 * @since 4.20.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for payment gateways.
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
		$this->container->register( Stripe\Provider::class );
		$this->container->register( Paypal\Provider::class );
		$this->container->register( Paypal_Standard\Provider::class );
	}
}
