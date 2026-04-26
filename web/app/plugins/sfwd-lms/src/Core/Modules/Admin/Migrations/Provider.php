<?php
/**
 * LearnDash Admin Migrations Provider class.
 *
 * @since 4.23.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Admin\Migrations;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;
use LearnDash\Core\Modules\Admin\Migrations\Reports;

/**
 * Service provider class for Admin Migrations module.
 *
 * @since 4.23.1
 */
class Provider extends ServiceProvider {
	/**
	 * Register the service provider.
	 *
	 * @since 4.23.1
	 *
	 * @return void
	 */
	public function register(): void {
		$this->hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 4.23.1
	 *
	 * @return void
	 */
	private function hooks(): void {
		// Migrate Core Reports default value.
		add_action(
			'learndash_version_upgraded',
			$this->container->callback( Reports::class, 'migrate_reports_default_value' )
		);

		// Set Core Reports default value on new install.
		add_action(
			'learndash_initialization_new_install',
			$this->container->callback( Reports::class, 'migrate_reports_default_value' )
		);
	}
}
