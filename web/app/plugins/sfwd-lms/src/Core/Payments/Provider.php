<?php
/**
 * LearnDash Payments Provider class.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Payments;

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
		$this->container->register( Stripe\Provider::class );
	}
}
