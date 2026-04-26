<?php
/**
 * Provider for REST API.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Provider class for REST API.
 *
 * @since 4.25.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.25.0
	 *
	 * @throws ContainerException If the container is not set.
	 *
	 * @return void
	 */
	public function register() {
		$this->container->register( Documentation_Migration\Provider::class );
		$this->container->register( V1\Provider::class );
	}
}
