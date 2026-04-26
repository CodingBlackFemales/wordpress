<?php
/**
 * Reports module provider.
 *
 * @since 4.17.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Modules\Reports\Settings as Reports_Settings;

/**
 * Reports module provider.
 *
 * @since 4.17.0
 */
class Provider extends ServiceProvider {
	/**
	 * Constant that can be used to prevent loading the Reports module if set to true.
	 *
	 * @since 4.17.0
	 *
	 * @var string
	 */
	private const PREVENT_LOAD_CONSTANT = 'LEARNDASH_MODULE_REPORTS_DISABLED';

	/**
	 * Registers service providers.
	 *
	 * @since 4.17.0
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton( ProPanel2::class );
		$this->container->singleton( Capabilities::class );

		$this->container->setVar(
			'learndash_settings_reports_page_id',
			'learndash-lms-reports'
		);

		/**
		 * This Provider is used for anything that should not be dependent on the below Constant/Filter check.
		 *
		 * This is functionality that existed before v4.17.0 but was moved to the Service Provider structure.
		 */
		$this->container->register( Legacy\Provider::class );

		if ( ! self::should_load() ) {
			// Register the disabled message provider when reports are disabled.
			$this->container->register( Disabled\Provider::class );
			return;
		}

		$this->container->register( Settings\Provider::class );
		$this->container->register( Migration\Provider::class );

		$this->hooks();
	}

	/**
	 * Controls whether the Reports functionality should be loaded.
	 *
	 * @since 4.17.0
	 *
	 * @return bool
	 */
	public static function should_load(): bool {
		// Check if Core Reports is disabled via the settings.
		$reports_settings = Reports_Settings::get();
		$reports_disabled = ! $reports_settings['display_reports'];

		return ! (
			(
				defined( self::PREVENT_LOAD_CONSTANT )
				&& Cast::to_bool( constant( self::PREVENT_LOAD_CONSTANT ) )
			)
			/**
			 * Filter to prevent loading Reports functionality.
			 * This filter cannot be added to your child theme's functions.php file as that will be loaded too late.
			 * This needs to be filtered from within a plugin before the plugins_loaded hook at priority 0.
			 * Adding this to a plugin outside of an action callback will result in it being before plugins_loaded.
			 *
			 * Example hooked to plugins_loaded at priority -1:
			 *
			 * add_action(
			 *    'plugins_loaded',
			 *    function () {
			 *        add_filter( 'learndash_module_reports_disabled', '__return_true' );
			 *    },
			 *    -1
			 * );
			 *
			 * Example without an action callback:
			 *
			 * add_filter( 'learndash_module_reports_disabled', '__return_true' );
			 *
			 * @since 4.17.0
			 *
			 * @param bool $prevent_loading Defaults to false.
			 *
			 * @return bool $prevent_loading
			 */
			|| apply_filters(
				'learndash_module_reports_disabled',
				false
			)
			|| $reports_disabled
		);
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 4.17.0
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_action(
			'plugins_loaded',
			$this->container->callback( ProPanel2::class, 'deactivate' ),
			1
		);

		add_action(
			'plugins_loaded',
			$this->container->callback( ProPanel2::class, 'load' ),
			2
		);

		add_action(
			'init',
			$this->container->callback( Capabilities::class, 'add' ),
			20 // After initial capabilities are assigned at priority 10.
		);
	}
}
