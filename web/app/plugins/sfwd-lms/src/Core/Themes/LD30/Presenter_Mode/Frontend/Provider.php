<?php
/**
 * LearnDash Presenter Mode Frontend Provider class.
 *
 * @since 4.23.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Presenter_Mode\Frontend;

use LearnDash\Core\Themes\LD30\Presenter_Mode\Settings;
use LearnDash_Theme_Register;
use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for Presenter Mode Frontend.
 *
 * @since 4.23.0
 */
class Provider extends ServiceProvider {
	/**
	 * Register service providers.
	 *
	 * @since 4.23.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->should_load() ) {
			return;
		}

		$this->hooks();
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.23.0
	 *
	 * @throws ContainerException If there's an issue while trying to bind the implementation.
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_action(
			'init',
			$this->container->callback(
				Assets::class,
				'register_assets'
			)
		);

		add_action(
			'wp_enqueue_scripts',
			$this->container->callback(
				Assets::class,
				'enqueue_assets'
			)
		);

		add_action(
			'learndash-focus-masthead-after',
			$this->container->callback(
				View::class,
				'inject_toggle_button'
			)
		);

		add_filter(
			'body_class',
			$this->container->callback(
				View::class,
				'update_body_class'
			)
		);
	}

	/**
	 * Controls whether Presenter Mode should be loaded.
	 *
	 * @since 4.23.0
	 *
	 * @return bool
	 */
	private function should_load(): bool {
		return LearnDash_Theme_Register::get_active_theme_key() === 'ld30'
			&& (bool) Settings::get()['focus_mode_enabled']
			&& (bool) Settings::get()['presenter_mode_enabled'];
	}
}
