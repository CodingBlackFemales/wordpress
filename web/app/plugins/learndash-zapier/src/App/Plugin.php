<?php
/**
 * Plugin service provider class file.
 *
 * @since 2.3.2
 *
 * @package LearnDash\Zapier
 */

namespace LearnDash\Zapier;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use StellarWP\Learndash\lucatume\DI52\ContainerException;

/**
 * Plugin service provider class.
 *
 * @since 2.3.2
 */
class Plugin extends ServiceProvider {
	/**
	 * Register service provider.
	 *
	 * @since 2.3.2
	 *
	 * @throws ContainerException If the service provider is not registered.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( Admin\Provider::class );
	}
}
