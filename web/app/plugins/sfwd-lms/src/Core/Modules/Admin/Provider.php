<?php
/**
 * LearnDash Admin Provider class.
 *
 * @since 4.23.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Admin;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use LearnDash\Core\Modules\Admin\Migrations\Provider as MigrationsProvider;
use LearnDash\Core\Modules\Reports\Settings\Reports_Section;
use LearnDash\Core\Modules\Admin\Header\Provider as HeaderProvider;
use LearnDash\Core\Modules\Admin\Banner\Provider as BannerProvider;

/**
 * Service provider class for Admin module.
 *
 * @since 4.23.1
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.23.1
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( MigrationsProvider::class );
		$this->container->register( HeaderProvider::class );
		$this->container->register( BannerProvider::class );
		$this->hooks();
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 4.23.1
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_action(
			'learndash_settings_sections_init',
			[ Reports_Section::class, 'add_section_instance' ]
		);
	}
}
