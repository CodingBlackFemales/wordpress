<?php
/**
 * LearnDash Admin Bar Provider class.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Admin_Bar;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for Admin Bar additions.
 *
 * @since 4.18.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.18.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton( Assets::class );
		$this->container->register( Payments\Provider::class );

		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.18.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_action(
			'init',
			$this->container->callback(
				Assets::class,
				'register_scripts'
			)
		);

		add_action(
			'wp_enqueue_scripts',
			$this->container->callback(
				Assets::class,
				'enqueue_scripts'
			)
		);

		add_action(
			'admin_enqueue_scripts',
			$this->container->callback(
				Assets::class,
				'enqueue_scripts'
			)
		);
	}
}
