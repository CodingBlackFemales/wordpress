<?php
/**
 * Infrastructure provider class.
 *
 * @since 4.10.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Infrastructure;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for infrastructure.
 *
 * @since 4.10.3
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.10.3
	 *
	 * @throws ContainerException If the container is not set.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( File_Protection\Provider::class );
	}
}
