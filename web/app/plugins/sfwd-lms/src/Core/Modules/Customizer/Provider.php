<?php
/**
 * Customizer provider class file.
 *
 * @since 4.15.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Customizer;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use LearnDash\Core\Modules\Customizer\Themes\Theme;

/**
 * Customizer service provider class.
 *
 * @since 4.15.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.15.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( Themes\Provider::class );
		$this->container->singleton( Themes_Loader::class, Themes_Loader::class );

		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.15.0
	 *
	 * @return void
	 */
	public function hooks() {
		add_action(
			'learndash_loaded',
			$this->container->callback( Themes_Loader::class, 'init' )
		);
	}
}
