<?php
/**
 * Reports Legacy Settings module provider.
 *
 * @since 4.17.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports\Legacy\Settings;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use LearnDash_Settings_Page;

/**
 * Reports Base Settings module provider.
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
		// Ensure that the Instance is only created once, using Core's methods to create and return the instance.
		$this->container->singleton(
			Page::class,
			function () {
				Page::add_page_instance();

				return LearnDash_Settings_Page::get_page_instance( Page::class );
			}
		);

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
			'learndash_settings_pages_init',
			$this->container->callback( Page::class, 'add_page_instance' ),
			10
		);

		add_filter(
			'learndash_header_data',
			$this->container->callback( Page::class, 'set_header_data' )
		);
	}
}
