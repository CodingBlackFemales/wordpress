<?php
/**
 * Admin pages provider class file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

namespace LearnDash\WooCommerce\Admin\Pages;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use StellarWP\Learndash\lucatume\DI52\ContainerException;

/**
 * Admin pages service provider class.
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
		// We can't use the container's callback method here because of LearnDash core infrastructure issue.

		add_action(
			'learndash_settings_pages_init',
			function () {
				Settings::add_page_instance();
			}
		);

		add_action(
			'learndash_settings_sections_init',
			function () {
				Sections\Settings_Enrollment_Status::add_section_instance();
			}
		);

		add_action( 'admin_enqueue_scripts', $this->container->callback( Sections\Settings_Enrollment_Status::class, 'enqueue_admin_scripts' ) );
	}
}
