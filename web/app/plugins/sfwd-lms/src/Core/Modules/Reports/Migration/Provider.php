<?php
/**
 * Reports migration provider.
 *
 * @since 4.17.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports\Migration;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Reports migration provider.
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
		$this->container->singleton( Notices\ProPanel30::class );

		$this->hooks();
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 4.17.0
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_action(
			'admin_notices',
			$this->container->callback( Notices\ProPanel30::class, 'displays_admin_notice' ),
		);
	}
}
