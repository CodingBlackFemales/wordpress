<?php
/**
 * Provider for initializing theme implementations and hooks.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Class Provider for initializing theme implementations and hooks.
 *
 * @since 4.6.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( LD30\Provider::class );
		$this->container->register( Legacy\Provider::class );
		$this->container->register( Breezy\Provider::class );
	}
}
