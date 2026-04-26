<?php
/**
 * Experiments module provider.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Experiments;

use LearnDash_Settings_Page_Experiments;
use LearnDash_Settings_Section_Experiments_List;
use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Experiments module provider.
 *
 * @since 4.13.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers service providers.
	 *
	 * @since 4.13.0
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton( Experiments::class );

		$this->hooks();
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 4.13.0
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		// The main initialization.
		add_action(
			'learndash_init',
			$this->container->callback( Experiments::class, 'init' )
		);

		// Enqueue admin scripts.
		add_action(
			'admin_enqueue_scripts',
			$this->container->callback( Experiments::class, 'enqueue_admin_scripts' )
		);

		// Settings.
		// Hooks can't be called via the container because of some architecture issues.

		// Page.
		add_action(
			'learndash_settings_pages_init',
			[ LearnDash_Settings_Page_Experiments::class, 'add_page_instance' ]
		);

		// Page section.
		add_action(
			'learndash_settings_sections_init',
			[ LearnDash_Settings_Section_Experiments_List::class, 'add_section_instance' ]
		);
	}
}
