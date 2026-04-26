<?php
/**
 * Licensing Notices service provider class file.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Licensing\Notices;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use StellarWP\Learndash\lucatume\DI52\ContainerException;

/**
 * Licensing Notices service provider class.
 *
 * @since 4.18.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers service provider.
	 *
	 * @since 4.18.0
	 *
	 * @throws ContainerException If the service provider is not registered.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->hooks();
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 4.18.0
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_action(
			'admin_init',
			$this->container->callback( Invalid_License::class, 'display' ),
		);
	}
}
