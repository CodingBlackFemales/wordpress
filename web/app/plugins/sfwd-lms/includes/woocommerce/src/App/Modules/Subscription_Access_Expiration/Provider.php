<?php
/**
 * Subscription access expiration provider class file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

namespace LearnDash\WooCommerce\Modules\Subscription_Access_Expiration;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Subscription access expiration provider class.
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
		add_filter( 'ld_course_access_expires_on', $this->container->callback( Handler::class, 'expire_course_access' ), 10, 3 );
		add_action( 'learndash_user_course_access_expired', $this->container->callback( Handler::class, 'cancel_on_expiration' ), 10, 2 );
		add_action( 'learndash_woocommerce_cancel_lapsed_subscription', $this->container->callback( Handler::class, 'cancel_lapsed' ) );
	}
}
