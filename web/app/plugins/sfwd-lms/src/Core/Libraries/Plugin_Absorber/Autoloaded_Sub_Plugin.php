<?php
/**
 * Bundled sub-plugin that ships classes needing autoloading.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Libraries\Plugin_Absorber;

use LearnDash\Core\Autoloader;
use LearnDash\Core\Libraries\Plugin_Absorber\Contracts\Sub_Plugin;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Config;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Exceptions\Config_Exception;
use StellarWP\Learndash\Nexcess\PluginAbsorber\Sub_Plugin as Absorbed_Plugin;

/**
 * A bundled add-on whose own classes have to be loadable without a `composer.json` entry.
 *
 * The namespace is registered when the Plugin Absorber announces that it is about to require the bundled file, so the
 * mapping exists only for a copy that is actually loading. A `composer.json` entry cannot be conditional, and would
 * leave the namespace pointing at code the Absorber declined to load -- so an active standalone copy of the add-on
 * could be served the bundled classes instead of its own. This is also how the other bundled modules work: Course Grid
 * registers its own autoloader from its main file, and Licensing ships a module-local Composer classmap.
 *
 * {@see Autoloader} appends itself to the SPL chain while Composer prepends, so a standalone copy's own Composer
 * loader always answers ahead of this one. The wrong copy cannot be resolved rather than being resolved and undone.
 *
 * @since 5.2.0
 */
abstract class Autoloaded_Sub_Plugin implements Sub_Plugin {
	/**
	 * Slug the Plugin Absorber knows the bundled add-on by.
	 *
	 * The same slug the add-on passes to `Absorber::register()`. It is what tells this add-on's announcement from
	 * another's.
	 *
	 * @since 5.2.0
	 *
	 * @return string
	 */
	abstract public function get_slug(): string;

	/**
	 * PSR-4 prefixes the bundled copy ships, keyed by prefix with the absolute directory as the value.
	 *
	 * @since 5.2.0
	 *
	 * @return array<string,string>
	 */
	abstract protected function get_psr4_prefixes(): array;

	/**
	 * Registers the bundled add-on with the Plugin Absorber.
	 *
	 * Reached through `boot()`, so an add-on writes only its own registration and never has to remember to arrange
	 * the autoloading around it.
	 *
	 * Pass {@see self::should_load()} as the registration's `enabled` callable, so the add-on can be turned off the
	 * way the other bundled modules can.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	abstract protected function register(): void;

	/**
	 * Whether the bundled add-on should be loaded at all.
	 *
	 * The Plugin Absorber has a `should_load` filter of its own, but reaching for it means knowing the library is
	 * there and matching on a slug, which is no way to ask a site owner to turn an add-on off. This is the pair every
	 * other bundled module carries, named after the add-on rather than after the library.
	 *
	 * Given to the Absorber as `enabled` rather than applied to its own filter, so an add-on turned off here is
	 * invisible to the conflict pass too: `Conflict\Detector::is_in_conflict()` answers false for a sub-plugin that is
	 * not enabled, and a standalone copy is never deactivated in favour of a bundled copy that was never going to
	 * load.
	 *
	 * @since 5.2.0
	 *
	 * @return bool
	 */
	protected function should_load(): bool {
		$module_name = $this->get_module_name();

		$prevent_load_constant = 'LEARNDASH_MODULE_' . strtoupper( $module_name ) . '_DISABLED';

		return ! (
			(
				defined( $prevent_load_constant )
				&& Cast::to_bool( constant( $prevent_load_constant ) )
			)
			/**
			 * Filter to prevent loading a bundled add-on.
			 * This filter cannot be added to your child theme's functions.php file as that will be loaded too late.
			 * This needs to be filtered from within a plugin before the plugins_loaded hook at priority 6.
			 *
			 * Example hooked to plugins_loaded at priority -1:
			 *
			 * add_action(
			 *    'plugins_loaded',
			 *    function () {
			 *        add_filter( 'learndash_module_notifications_disabled', '__return_true' );
			 *    },
			 *    -1
			 * );
			 *
			 * Example without an action callback:
			 *
			 * add_filter( 'learndash_module_notifications_disabled', '__return_true' );
			 *
			 * @since 5.2.0
			 *
			 * @param bool $prevent_loading Defaults to false.
			 *
			 * @return bool
			 */
			|| apply_filters(
				"learndash_module_{$module_name}_disabled", // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- The name is built from the add-on's slug, behind this plugin's own prefix.
				false
			)
		);
	}

	/**
	 * The add-on's name as the constant and the filter spell it.
	 *
	 * Taken from the slug, so the two cannot drift: `learndash-notifications` gives `notifications`, and with it
	 * `LEARNDASH_MODULE_NOTIFICATIONS_DISABLED` and `learndash_module_notifications_disabled`. An add-on whose slug
	 * does not read well that way can say so by overriding this.
	 *
	 * @since 5.2.0
	 *
	 * @return string
	 */
	protected function get_module_name(): string {
		$slug = $this->get_slug();

		if ( strpos( $slug, 'learndash-' ) === 0 ) {
			$slug = substr( $slug, strlen( 'learndash-' ) );
		}

		return str_replace( '-', '_', $slug );
	}

	/**
	 * Registers the add-on, and arranges for its namespace to be mapped if the Absorber loads the bundled copy.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function boot(): void {
		// Its order against the hook below does not matter: `Absorber::register()` only buffers the configuration, so
		// it needs neither the library booted nor a container set, and the registry is not read until priority 5.
		$this->register();

		// Not the announcement itself: the library has no hook prefix to build that hook's name from until it is
		// configured at `plugins_loaded` priority 1. It requires no bundled file until 6.
		add_action( 'plugins_loaded', [ $this, 'hook_autoloading' ], 2 );
	}

	/**
	 * Listens for the Absorber announcing that the bundled file is about to be required.
	 *
	 * @since 5.2.0
	 *
	 * @throws Config_Exception If no hook prefix has been set.
	 *
	 * @return void
	 */
	public function hook_autoloading(): void {
		add_action( Config::get_hook_name( 'loading' ), [ $this, 'register_autoloading' ] );
	}

	/**
	 * Registers this add-on's prefixes on this plugin's autoloader.
	 *
	 * The announcement is the last thing to happen before the require, so the prefixes are in place by the time the
	 * bundled file references its own classes at file scope. Every gate has passed by then: a copy that is disabled,
	 * has unmet dependencies, was vetoed by `should_load`, or stood down for a standalone copy never reaches it, and
	 * so never has its namespace mapped.
	 *
	 * Safe to run more than once. `Autoloader::register_prefix()` keeps its directories unique, and
	 * `spl_autoload_register()` ignores a callable it already holds.
	 *
	 * @since 5.2.0
	 *
	 * @param Absorbed_Plugin $sub_plugin The sub-plugin the Absorber is about to require.
	 *
	 * @return void
	 */
	public function register_autoloading( Absorbed_Plugin $sub_plugin ): void {
		// Every bundled add-on listens on the one hook, so each has to answer only for itself.
		if ( $sub_plugin->get_slug() !== $this->get_slug() ) {
			return;
		}

		$autoloader = Autoloader::instance();

		foreach ( $this->get_psr4_prefixes() as $prefix => $directory ) {
			$autoloader->register_prefix( $prefix, $directory );
		}

		$autoloader->register_autoloader();
	}
}
