<?php
/**
 * Licensing provider class file.
 *
 * @since 4.17.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Licensing;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Licensing provider class.
 *
 * @since 4.17.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers service providers.
	 *
	 * @since 4.17.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton( Status_Checker::class );
	}
}
