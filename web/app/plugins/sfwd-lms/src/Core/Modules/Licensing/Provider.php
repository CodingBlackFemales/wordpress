<?php
/**
 * Licensing module provider.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Licensing;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use LearnDash\Core\Utilities\Cast;

/**
 * Licensing module provider.
 *
 * @since 4.18.0
 */
class Provider extends ServiceProvider {
	/**
	 * Constant that can be used to prevent loading the Licensing module if set to true.
	 *
	 * @since 4.18.0
	 *
	 * @var string
	 */
	private const PREVENT_LOAD_CONSTANT = 'LEARNDASH_MODULE_LICENSING_DISABLED';

	/**
	 * Registers service providers.
	 *
	 * @since 4.18.0
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! self::should_load() ) {
			return;
		}

		$this->container->register( Legacy\Provider::class );
		$this->container->register( Notices\Provider::class );
	}

	/**
	 * Controls whether the Licensing functionality should be loaded.
	 *
	 * @since 4.18.0
	 *
	 * @return bool
	 */
	public static function should_load(): bool {
		return ! (
			(
				defined( self::PREVENT_LOAD_CONSTANT )
				&& Cast::to_bool( constant( self::PREVENT_LOAD_CONSTANT ) )
			)
			/**
			 * Filter to prevent loading licensing functionality.
			 * This filter cannot be added to your child theme's functions.php file as that will be loaded too late.
			 * This needs to be filtered from within a plugin before the plugins_loaded hook at priority 0.
			 * Adding this to a plugin outside of an action callback will result in it being before plugins_loaded.
			 *
			 * Example hooked to plugins_loaded at priority -1:
			 *
			 * add_action(
			 *    'plugins_loaded',
			 *    function () {
			 *        add_filter( 'learndash_module_licensing_disabled', '__return_true' );
			 *    },
			 *    -1
			 * );
			 *
			 * Example without an action callback:
			 *
			 * add_filter( 'learndash_module_licensing_disabled', '__return_true' );
			 *
			 * @since 4.18.0
			 *
			 * @param bool $prevent_loading Defaults to false.
			 *
			 * @return bool
			 */
			|| apply_filters(
				'learndash_module_licensing_disabled',
				false
			)
		);
	}
}
