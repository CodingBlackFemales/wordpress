<?php
/**
 * Legacy service provider class file.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Licensing\Legacy;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use StellarWP\Learndash\lucatume\DI52\ContainerException;

/**
 * Legacy service provider class.
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
			'plugins_loaded',
			$this->container->callback( Loader::class, 'deactivate' ),
			1
		);

		add_action(
			'admin_notices',
			$this->container->callback( Loader::class, 'show_deactivated_notice' )
		);

		add_action(
			'plugins_loaded',
			$this->container->callback( Loader::class, 'load' ),
			2
		);

		add_action(
			'current_screen',
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

		add_filter(
			'learndash_admin_tab_sets',
			$this->container->callback(
				Admin::class,
				'maybe_hide_license_tab'
			),
			20
		);
	}
}
