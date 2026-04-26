<?php
/**
 * Provider for modules.
 *
 * @since 4.14.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Provider class for modules.
 *
 * @since 4.14.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.14.0
	 *
	 * @throws ContainerException If the container is not set.
	 *
	 * @return void
	 */
	public function register() {
		$this->container->register( REST\Provider::class );
		$this->container->register( AI\Provider::class );
		$this->container->register( AJAX\Provider::class );
		$this->container->register( Admin\Provider::class );
		$this->container->register( Support\Provider::class );
		$this->container->register( Customizer\Provider::class );
		$this->container->register( Experiments\Provider::class );
		$this->container->register( Reports\Provider::class );
		$this->container->register( Registration\Provider::class );
		$this->container->register( Payments\Provider::class );
		$this->container->register( Admin_Bar\Provider::class );
		$this->container->register( Licensing\Provider::class );
		$this->container->register( Course_Grid\Provider::class );
		$this->container->register( Quiz\Provider::class );
		$this->container->register( Extras\Provider::class );
		$this->container->register( Admin\Provider::class );
		$this->container->register( Course_Reviews\Provider::class );
	}
}
