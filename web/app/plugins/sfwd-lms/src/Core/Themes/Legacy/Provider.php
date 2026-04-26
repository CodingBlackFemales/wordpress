<?php
/**
 * Provider for the Legacy Theme.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\Legacy;

use LearnDash_Theme_Register;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Class Provider for initializing theme implementations and hooks.
 *
 * @since 4.21.4
 */
class Provider extends ServiceProvider {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function register(): void {
		/**
		 * This is registered before checking if the theme should load because the notices need to be able to
		 * check if the theme is being changed to Legacy.
		 */
		$this->container->register( Notices\Provider::class );

		if ( ! $this->should_load() ) {
			return;
		}

		$this->container->register( Quiz\Provider::class );
	}

	/**
	 * Controls whether Legacy-specific Providers should be loaded.
	 *
	 * @since 4.21.4
	 *
	 * @return bool
	 */
	private function should_load(): bool {
		return LearnDash_Theme_Register::get_active_theme_key() === 'legacy';
	}
}
