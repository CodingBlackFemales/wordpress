<?php
/**
 * Settings provider class file.
 *
 * @since 4.15.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Settings;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Settings service provider class.
 *
 * @since 4.15.2
 */
class Provider extends ServiceProvider {
	/**
	 * Registers service providers.
	 *
	 * @since 4.15.2
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( Fields\Provider::class );
		$this->container->singleton( Initialization::class );

		add_action( 'init', $this->container->callback( Initialization::class, 'run' ) );
		add_action( 'admin_head', $this->container->callback( Menu::class, 'update_main_menu_label' ) );
		add_filter( 'learndash_header_variant', $this->container->callback( Header\Variants\Modern::class, 'enable' ) );
	}
}
