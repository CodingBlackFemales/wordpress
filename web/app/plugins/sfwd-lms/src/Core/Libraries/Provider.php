<?php
/**
 * LearnDash Libraries Provider class.
 *
 * @since 4.16.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Libraries;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for initializing libraries.
 *
 * @since 4.16.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.16.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( Assets\Provider::class );
	}
}
