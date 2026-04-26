<?php
/**
 * Customizer Themes provider class file.
 *
 * @since 4.15.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Customizer\Themes;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Customizer Themes service provider class.
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
		add_filter(
			'learndash_customizer_themes',
			$this->container->callback( self::class, 'register_themes' )
		);
	}

	/**
	 * Registers the Customizer Themes.
	 *
	 * @since 4.15.0
	 *
	 * @param Theme[] $themes Theme instances.
	 *
	 * @return Theme[]
	 */
	public function register_themes( array $themes = [] ): array {
		$themes[] = new LD30();

		return $themes;
	}
}
