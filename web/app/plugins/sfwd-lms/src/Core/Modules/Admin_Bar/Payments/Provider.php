<?php
/**
 * LearnDash Admin Bar Payments Provider class.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Admin_Bar\Payments;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for Payments Admin Bar additions.
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
		$this->container->singleton( Test_Mode::class );

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
			'admin_bar_menu',
			$this->container->callback(
				Test_Mode::class,
				'create'
			)
		);
	}
}
