<?php
/**
 * LearnDash Payments Emails Provider class.
 *
 * @since 4.25.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Payments\Emails;

use LearnDash\Core\Modules\Payments\Emails\Settings\Final_Attempt_Coming_Up;
use LearnDash\Core\Modules\Payments\Emails\Settings\Initial_Payment_Failed;
use LearnDash\Core\Modules\Payments\Emails\Settings\Payment_Failed_Access_Revoked;
use LearnDash\Core\Modules\Payments\Emails\Settings\Second_Attempt_Failed;
use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use StellarWP\Learndash\StellarWP\Arrays\Arr;

/**
 * Service provider class for Emails.
 *
 * @since 4.25.3
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.25.3
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->should_load() ) {
			return;
		}

		// Ensure that the instances are only created once, using Core's methods to create and return the instance.
		$this->create_singleton_instances();

		$this->hooks();
	}

	/**
	 * Adds the payments emails sections.
	 *
	 * @since 4.25.3
	 *
	 * @return void
	 */
	public function add_payments_emails_sections(): void {
		$this->container->get( Initial_Payment_Failed::class );
		$this->container->get( Second_Attempt_Failed::class );
		$this->container->get( Final_Attempt_Coming_Up::class );
		$this->container->get( Payment_Failed_Access_Revoked::class );
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 4.25.3
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_action(
			'learndash_settings_sections_init',
			$this->container->callback(
				self::class,
				'add_payments_emails_sections'
			)
		);
	}

	/**
	 * Checks if the provider should be loaded.
	 *
	 * @since 4.25.3
	 *
	 * @return bool True if the provider should be loaded, false otherwise.
	 */
	private function should_load(): bool {
		// We can't use the LearnDash settings class because it's not loaded yet.
		$paypal_checkout_settings = get_option( 'learndash_settings_paypal_checkout', [] );

		// Only if PayPal checkout is enabled.
		return Arr::get( $paypal_checkout_settings, 'enabled', '' ) === 'yes';
	}

	/**
	 * Creates singleton instances of the email settings sections.
	 *
	 * @since 4.25.3
	 *
	 * @return void
	 */
	private function create_singleton_instances(): void {
		$this->container->singleton(
			Initial_Payment_Failed::class,
			function () {
				Initial_Payment_Failed::add_section_instance();

				return Initial_Payment_Failed::get_section_instance();
			}
		);

		$this->container->singleton(
			Second_Attempt_Failed::class,
			function () {
				Second_Attempt_Failed::add_section_instance();

				return Second_Attempt_Failed::get_section_instance();
			}
		);

		$this->container->singleton(
			Final_Attempt_Coming_Up::class,
			function () {
				Final_Attempt_Coming_Up::add_section_instance();

				return Final_Attempt_Coming_Up::get_section_instance();
			}
		);

		$this->container->singleton(
			Payment_Failed_Access_Revoked::class,
			function () {
				Payment_Failed_Access_Revoked::add_section_instance();

				return Payment_Failed_Access_Revoked::get_section_instance();
			}
		);
	}
}
