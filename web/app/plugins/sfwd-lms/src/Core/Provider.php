<?php
/**
 * Provider for initializing the LearnDash Core plugin.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core;

use LearnDash\Core\Template\Breakpoints;
use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use StellarWP\Learndash\StellarWP\Assets\Asset;
use StellarWP\Learndash\StellarWP\Assets\Assets;

/**
 * Class Provider for the LearnDash Core.
 *
 * @since 4.6.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.6.0
	 *
	 * @throws ContainerException If the registration fails.
	 *
	 * @return void
	 */
	public function register(): void {
		// Registering implementations.
		$this->container->register( Libraries\Provider::class );

		$this->register_actions();

		$this->container->register( Licensing\Provider::class );
		$this->container->register( Settings\Provider::class );
		$this->container->register( Modules\Provider::class );
		$this->container->register( Infrastructure\Provider::class );

		$this->container->register( Themes\Provider::class );

		$this->container->register( Mcp\Provider::class );

		// Initialize our version tracking.
		// Register this late, so our other providers have an opportunity to hook into these changes.
		Version_Tracker::sync_version( learndash_sanitize_version_string( LEARNDASH_VERSION ) );
	}

	/**
	 * Register actions.
	 *
	 * @since 4.16.0
	 *
	 * @return void
	 */
	public function register_actions(): void {
		add_action( 'init', [ $this, 'register_scripts' ], 1 );
	}

	/**
	 * Registers the core LearnDash scripts that can be enqueued.
	 *
	 * These are global-level, core scripts that are used throughout the plugin.
	 *
	 * @since 4.16.0
	 *
	 * @return void
	 */
	public function register_scripts(): void {
		Asset::add( 'learndash-main', 'js/main.js' )
			->add_to_group( 'learndash-core' )
			->add_localize_script(
				'learndash.global',
				[
					'ajaxurl'      => admin_url( 'admin-ajax.php' ),
					/**
					 * Filters an additional scroll offset used when programmatically scrolling to an element on the page.
					 *
					 * @since 5.0.1
					 *
					 * @param int $scroll_offset The scroll offset. Default 0.
					 *
					 * @return int The scroll offset. Default 0.
					 */
					'scrollOffset' => apply_filters( 'learndash_scroll_offset', 0 ),
				]
			)
			->register();

		Asset::add( 'learndash-breakpoints', 'js/breakpoints.js' )
			->add_to_group( 'learndash-core' )
			->set_dependencies( 'learndash-main' )
			->add_localize_script(
				'learndash.views.breakpoints',
				[
					'list' => Breakpoints::get(),
				]
			)
			->register();
	}
}
