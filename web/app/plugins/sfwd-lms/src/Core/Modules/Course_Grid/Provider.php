<?php
/**
 * Course grid module provider.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Course_Grid;

use LearnDash\Core\Modules\Course_Grid\Legacy\Loader;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Course Grid module provider.
 *
 * @since 4.21.4
 */
class Provider extends ServiceProvider {
	/**
	 * Constant that can be used to prevent loading the Course Grid module if set to true.
	 *
	 * @since 4.21.4
	 *
	 * @var string
	 */
	private const PREVENT_LOAD_CONSTANT = 'LEARNDASH_MODULE_COURSE_GRID_DISABLED';

	/**
	 * Controls whether the Course Grid module should be loaded.
	 *
	 * @since 4.21.4
	 *
	 * @return bool
	 */
	public static function should_load(): bool {
		return ! (
			// Check the constant to prevent loading the Course Grid module.
			(
				defined( self::PREVENT_LOAD_CONSTANT )
				&& Cast::to_bool( constant( self::PREVENT_LOAD_CONSTANT ) )
			)
			/**
			 * Filter to prevent loading Course Grid module.
			 * This filter cannot be added to your child theme's functions.php file as that will be loaded too late.
			 * This needs to be filtered from within a plugin before the plugins_loaded hook at priority 0.
			 * Adding this to a plugin outside an action callback will result in it being before plugins_loaded.
			 *
			 * Example hooked to plugins_loaded at priority -1:
			 *
			 * add_action(
			 *    'plugins_loaded',
			 *    function () {
			 *        add_filter( 'learndash_module_course_grid_disabled', '__return_true' );
			 *    },
			 *    -1
			 * );
			 *
			 * Example without an action callback:
			 *
			 * add_filter( 'learndash_module_course_grid_disabled', '__return_true' );
			 *
			 * @since 4.21.4
			 *
			 * @param bool $prevent_loading Defaults to false.
			 *
			 * @return bool $prevent_loading
			 */
			|| apply_filters(
				'learndash_module_course_grid_disabled',
				false
			)
		);
	}

	/**
	 * Registers service providers.
	 *
	 * @since 4.21.4
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton( Loader::class );

		if ( ! self::should_load() ) {
			return;
		}

		$this->hooks();
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 4.21.4
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_action(
			'plugins_loaded',
			$this->container->callback( Loader::class, 'deactivate' ),
			1
		);

		add_action(
			'plugins_loaded',
			$this->container->callback( Loader::class, 'load' ),
			2
		);

		add_filter(
			'wp_admin_notice_markup',
			$this->container->callback( Loader::class, 'update_legacy_plugin_activation_notice' )
		);

		add_action(
			'admin_init',
			$this->container->callback( Notice::class, 'register_admin_notices' )
		);
	}
}
