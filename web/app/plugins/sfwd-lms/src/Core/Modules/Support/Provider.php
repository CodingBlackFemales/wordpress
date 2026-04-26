<?php
/**
 * Provider for support modules.
 *
 * @since 4.14.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Support;

use LearnDash\Core\Modules\Support\Requirements\WordPress;
use LearnDash\Core\Modules\Support\TrustedLogin\TrustedLogin;
use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Provider class for support modules.
 *
 * @since 4.14.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.14.0
	 *
	 * @throws ContainerException If the container is not set.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.14.0
	 *
	 * @throws ContainerException If the container is not set.
	 *
	 * @return void
	 */
	public function hooks(): void {
		// TrustedLogin support module hooks.

		add_action( 'learndash_init', $this->container->callback( TrustedLogin::class, 'register' ) );
		add_action( 'admin_head', $this->container->callback( TrustedLogin::class, 'remove_submenu_item' ) );
		add_action( 'admin_enqueue_scripts', $this->container->callback( TrustedLogin::class, 'add_scripts' ) );

		// Support policy module hooks.

		add_filter( 'upgrader_pre_download', $this->container->callback( WordPress::class, 'check_required_wp_version' ), 10, 4 );
	}
}
