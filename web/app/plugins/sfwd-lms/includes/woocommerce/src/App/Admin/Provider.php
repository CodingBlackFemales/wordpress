<?php
/**
 * Admin provider class file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

namespace LearnDash\WooCommerce\Admin;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use StellarWP\Learndash\lucatume\DI52\ContainerException;

/**
 * Admin service provider class.
 *
 * @since 5.2.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 5.2.0
	 *
	 * @throws ContainerException If the service provider is not registered.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( Pages\Provider::class );

		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 5.2.0
	 *
	 * @throws ContainerException If the service provider is not registered.
	 *
	 * @return void
	 */
	public function hooks() {
		add_filter( 'learndash_template_admin_template_paths', $this->container->callback( Admin::class, 'register_admin_template_paths' ), 10, 2 );
		add_filter( 'learndash_submenu', $this->container->callback( Admin::class, 'register_submenu' ) );
		add_action( 'admin_notices', $this->container->callback( Admin::class, 'show_guest_checkout_setting_enabled_notice' ) );
	}
}
