<?php
/**
 * LearnDash Plugin Absorber Provider class.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Libraries\Plugin_Absorber;

use LearnDash\Core\App;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Absorber;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Config;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;

/**
 * Service provider class for initializing the Plugin Absorber library.
 *
 * @since 5.2.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers actions.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->register_actions();
	}

	/**
	 * Registers actions.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function register_actions(): void {
		// The library resolves conflicts at `plugins_loaded` priority 5 and loads the bundled copies at 6.
		add_action( 'plugins_loaded', [ $this, 'configure' ], 1 ); // Run after LD is loaded.
	}

	/**
	 * Configures and boots the Plugin Absorber library.
	 *
	 * Bundled add-ons call `Absorber::register()` from their own modules during
	 * `learndash_files_included` (`plugins_loaded` priority 0), ahead of this boot: `boot()` keeps
	 * the container its first call saw, and the registry is not read until priority 5.
	 *
	 * @since 5.2.0
	 *
	 * @throws Config_Exception If the hook prefix or the container is unusable.
	 *
	 * @return void
	 */
	public function configure(): void {
		Config::set_hook_prefix( 'learndash' );
		Config::set_container( App::container() );

		Absorber::boot();
	}
}
