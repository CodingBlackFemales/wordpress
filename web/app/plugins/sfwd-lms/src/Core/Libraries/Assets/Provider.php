<?php
/**
 * LearnDash Assets Provider class.
 *
 * @since 4.16.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Libraries\Assets;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use StellarWP\Learndash\StellarWP\Assets\Config as Assets_Config;

/**
 * Service provider class for initializing libraries.
 *
 * @since 4.16.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.16.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->register_actions();
	}

	/**
	 * Register actions.
	 *
	 * @since 4.16.0
	 *
	 * @return void
	 */
	public function register_actions(): void {
		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
	}

	/**
	 * Fired on plugins_loaded.
	 *
	 * @since 4.16.0
	 *
	 * @return void
	 */
	public function plugins_loaded(): void {
		Assets_Config::set_hook_prefix( 'learndash' );
		Assets_Config::set_path( LEARNDASH_LMS_PLUGIN_DIR );
		Assets_Config::set_version( LEARNDASH_VERSION );
		Assets_Config::set_relative_asset_path( 'src/assets/dist' );
	}
}
