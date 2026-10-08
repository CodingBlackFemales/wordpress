<?php
/**
 * Retroactive access tool provider class file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

namespace LearnDash\WooCommerce\Modules\Retroactive_Access_Tool;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Retroactive access tool class.
 *
 * @since 5.2.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->hooks();
	}

	/**
	 * Register hook callbacks.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	private function hooks(): void {
		add_filter( 'woocommerce_debug_tools', $this->container->callback( Handler::class, 'add_tool' ) );
		add_action( 'learndash_woocommerce_retroactive_access_tool', $this->container->callback( Handler::class, 'run_batch' ), 10, 2 );
	}
}
