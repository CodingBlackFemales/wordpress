<?php
/**
 * AJAX module provider class.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AJAX;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for AJAX modules.
 *
 * @since 4.8.0
 */
class Provider extends ServiceProvider {
	/**
	 * Global prefix for AJAX actions when logged in.
	 *
	 * @since 4.12.0
	 *
	 * @var string
	 */
	public static $global_prefix_logged_in = 'wp_ajax_learndash_';

	/**
	 * Global prefix for AJAX actions when logged out.
	 *
	 * @since 4.12.0
	 *
	 * @var string
	 */
	public static $global_prefix_logged_out = 'wp_ajax_nopriv_learndash_';

	/**
	 * Register service providers.
	 *
	 * @since 4.8.0
	 *
	 * @throws ContainerException If the container cannot resolve a service.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton( Search_Posts::class );
		$this->container->singleton( Notices\Dismisser::class );

		$this->hooks();
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 4.8.0
	 *
	 * @throws ContainerException If the container cannot resolve a service.
	 *
	 * @return void
	 */
	public function hooks(): void {
		// Search posts.
		add_action( 'wp_ajax_' . Search_Posts::$action, $this->container->callback( Search_Posts::class, 'handle_request' ) );

		// Notices.
		add_action(
			self::$global_prefix_logged_in . Notices\Dismisser::$action,
			$this->container->callback( Notices\Dismisser::class, 'handle_dismiss_request' )
		);
	}
}
