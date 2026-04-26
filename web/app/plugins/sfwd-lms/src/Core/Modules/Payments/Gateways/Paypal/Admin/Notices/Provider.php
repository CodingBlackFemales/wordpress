<?php
/**
 * LearnDash PayPal Admin Notices Provider class.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Admin\Notices;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for PayPal Admin Notices.
 *
 * @since 4.25.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.25.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.25.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action(
			'admin_init',
			$this->container->callback( Ssl_Requirement::class, 'register_admin_notice' )
		);

		add_action(
			'admin_init',
			$this->container->callback( Connected::class, 'register_admin_notice' )
		);

		add_action(
			'admin_init',
			$this->container->callback( Disconnected::class, 'register_admin_notice' )
		);

		add_action(
			'admin_init',
			$this->container->callback( Reconnected::class, 'register_admin_notice' )
		);

		add_action(
			'admin_init',
			$this->container->callback( Signup_Error::class, 'register_admin_notice' )
		);

		add_action(
			'admin_init',
			$this->container->callback( Account_Verification::class, 'register_admin_notice' )
		);
	}
}
