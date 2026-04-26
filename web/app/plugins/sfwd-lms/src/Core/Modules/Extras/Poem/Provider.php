<?php
/**
 * Poem service provider class file.
 *
 * @since 4.23.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Extras\Poem;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use StellarWP\Learndash\lucatume\DI52\ContainerException;

/**
 * Poem service provider class.
 *
 * @since 4.23.2
 */
class Provider extends ServiceProvider {
	/**
	 * Registers service provider.
	 *
	 * @since 4.23.2
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
	 * @since 4.23.2
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_action(
			'admin_init',
			$this->container->callback(
				Assets::class,
				'register_assets'
			)
		);

		add_action(
			'admin_enqueue_scripts',
			$this->container->callback(
				Assets::class,
				'enqueue_assets'
			)
		);
	}
}
