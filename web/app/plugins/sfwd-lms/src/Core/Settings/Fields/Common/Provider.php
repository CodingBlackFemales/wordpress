<?php
/**
 * Common Settings Fields provider class file.
 *
 * @since 4.15.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Settings\Fields\Common;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Common Settings Fields service provider class.
 *
 * @since 4.15.2
 */
class Provider extends ServiceProvider {
	/**
	 * Registers service providers.
	 *
	 * @since 4.15.2
	 *
	 * @return void
	 */
	public function register(): void {
		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.15.2
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter(
			'learndash_settings_field_html_after',
			$this->container->callback( Description_After::class, 'add' ),
			9, // Lower than default priority to make this easier for 3rd parties to run their own callbacks after ours.
			2
		);
	}
}
