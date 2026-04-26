<?php
/**
 * Reports Disabled Provider.
 *
 * @since 4.23.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports\Disabled;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for Reports Disabled functionality.
 *
 * @since 4.23.1
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.23.1
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton( Notice::class );

		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.23.1
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_action(
			'learndash_settings_page_after_metaboxes',
			$this->container->callback( Notice::class, 'show_notice' ),
			10,
			2
		);
	}
}
