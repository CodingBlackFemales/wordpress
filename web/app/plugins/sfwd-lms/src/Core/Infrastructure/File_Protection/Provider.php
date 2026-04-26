<?php
/**
 * File protection provider class.
 *
 * @since 4.10.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Infrastructure\File_Protection;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for file protection.
 *
 * @since 4.10.3
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.10.3
	 *
	 * @throws ContainerException If the container is not set.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.10.3
	 *
	 * @throws ContainerException If the container is not set.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'init', $this->container->callback( Path_Protection_Handler::class, 'init' ) );

		add_action(
			'init',
			$this->container->callback(
				File_Download_Handler::class,
				'download'
			),
			20 // Runs after Path_Protection_Handler::init().
		);
	}
}
