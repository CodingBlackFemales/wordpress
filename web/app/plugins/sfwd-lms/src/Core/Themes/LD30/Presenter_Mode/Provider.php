<?php
/**
 * LearnDash Presenter Mode Provider class.
 *
 * @since 4.23.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Presenter_Mode;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for Presenter Mode.
 *
 * @since 4.23.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.23.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( Frontend\Provider::class );

		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.23.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_filter(
			'learndash_settings_fields',
			$this->container->callback(
				Settings::class,
				'add_settings_fields'
			),
			10,
			2
		);
	}
}
