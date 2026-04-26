<?php
/**
 * Reports Settings module provider.
 *
 * @since 4.17.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports\Settings;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Reports Base Settings module provider.
 *
 * @since 4.17.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers service providers.
	 *
	 * @since 4.17.0
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->hooks();
	}

	/**
	 * Registers WordPress hooks.
	 *
	 * @since 4.17.0
	 *
	 * @throws ContainerException If the container cannot be resolved.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_action(
			'learndash_settings_page_after_metaboxes',
			$this->container->callback( Page::class, 'create_dashboard' ),
			10,
			2
		);
	}
}
