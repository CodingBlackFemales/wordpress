<?php
/**
 * Reports Legacy module provider.
 *
 * @since 4.17.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports\Legacy;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Reports Legacy module provider.
 *
 * @since 4.17.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers service providers.
	 *
	 * @since 4.17.0
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( Settings\Provider::class );
	}
}
