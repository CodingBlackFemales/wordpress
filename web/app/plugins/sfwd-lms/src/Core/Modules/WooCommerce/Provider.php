<?php
/**
 * WooCommerce module provider.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\WooCommerce;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Config;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Sub_Plugin as Absorbed_Plugin;

/**
 * Service provider class for the WooCommerce module.
 *
 * @since 5.2.0
 */
class Provider extends ServiceProvider {
	/**
	 * Boots the bundled WooCommerce Integration add-on.
	 *
	 * Runs during `learndash_files_included` (`plugins_loaded` priority 0), ahead of the library
	 * boot at priority 1. `boot()` registers with the Absorber and arranges autoloading; the
	 * registration is buffered and does not need the library booted.
	 *
	 * @since 5.2.0
	 *
	 * @throws ContainerException If the binding cannot be resolved.
	 * @throws Config_Exception   If the sub-plugin configuration is unusable.
	 *
	 * @return void
	 */
	public function register(): void {
		/*
		 * A singleton so that `$this->container->callback()` memoizes what it returns, which is what lets the lifecycle
		 * hooks be found and removed by the same callable they were added with.
		 */
		$this->container->singleton( Sub_Plugin::class );

		$sub_plugin = $this->container->get( Sub_Plugin::class );

		if ( ! $sub_plugin instanceof Sub_Plugin ) {
			return;
		}

		$sub_plugin->boot();

		$this->hooks();
	}

	/**
	 * Listens for the Absorber announcing that the bundled add-on has been loaded.
	 *
	 * @since 5.2.0
	 *
	 * @throws Config_Exception If no hook prefix has been set.
	 *
	 * @return void
	 */
	public function hook_lifecycle(): void {
		add_action( Config::get_hook_name( 'loaded' ), [ $this, 'on_bundled_copy_loaded' ] );
	}

	/**
	 * Ties the add-on's deactivation to LearnDash's own, once the bundled copy is the copy that runs.
	 *
	 * The add-on registers no lifecycle hooks of its own: `register_deactivation_hook()` names the hook after the plugin
	 * basename of the file that calls it, which resolves inside LearnDash now that the add-on is bundled.
	 *
	 * Reached from the Absorber's announcement rather than outright, so this runs only for a copy that actually loaded.
	 * A copy that is disabled, has unmet dependencies, was vetoed, or stood down for an active standalone never gets
	 * here, which is what lets the callback reach straight for the add-on's own cron class and leaves a standalone copy
	 * answering for itself.
	 *
	 * Activation is not wired. `Learndash_WooCommerce_Cron::register_cron()` already reschedules on `admin_init`.
	 *
	 * @since 5.2.0
	 *
	 * @param Absorbed_Plugin $announced The sub-plugin the Absorber has loaded.
	 *
	 * @throws ContainerException If the binding cannot be resolved.
	 *
	 * @return void
	 */
	public function on_bundled_copy_loaded( Absorbed_Plugin $announced ): void {
		$sub_plugin = $this->container->get( Sub_Plugin::class );

		if ( ! $sub_plugin instanceof Sub_Plugin ) {
			return;
		}

		// Every bundled add-on listens on the one hook, so each has to answer only for itself.
		if ( $announced->get_slug() !== $sub_plugin->get_slug() ) {
			return;
		}

		add_action(
			'learndash_deactivated',
			$this->container->callback( Sub_Plugin::class, 'deactivate' )
		);
	}

	/**
	 * Listens for the Absorber settling on the bundled copy.
	 *
	 * Not the announcement itself: the library has no hook prefix to build that hook's name from until it is configured
	 * at `plugins_loaded` priority 1, and it requires no bundled file until 6.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_action( 'plugins_loaded', [ $this, 'hook_lifecycle' ], 2 );
	}
}
