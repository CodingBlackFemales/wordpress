<?php
/**
 * LearnDash PayPal Admin Provider class.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Gateways\Paypal\Admin;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for PayPal Admin.
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
		$this->container->register( Notices\Provider::class );

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
	protected function hooks(): void {
		// Register onboarding return.
		add_action(
			'wp_loaded',
			$this->container->callback( Onboarding_Return::class, 'handler' )
		);

		// Register connected message.
		add_action(
			'learndash_section_after',
			$this->container->callback( Connected::class, 'render_connected_message' ),
			10,
			2
		);

		// Hide the Stripe Connect banner if the current page is the PayPal Checkout settings page.
		add_filter(
			'learndash_stripe_is_on_payments_setting_page',
			$this->container->callback(
				Admin::class,
				'hide_stripe_connect_banner'
			)
		);

		// Hide the telemetry modal on PayPal onboarding via setup wizard.
		add_filter(
			'learndash_show_telemetry_modal',
			$this->container->callback(
				Admin::class,
				'hide_telemetry_modal_on_paypal_onboarding_via_setup_wizard'
			)
		);
	}
}
