<?php
/**
 * LearnDash Shortcodes Provider class.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Shortcodes;

use StellarWP\Learndash\lucatume\DI52\ContainerException;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Service provider class for Shortcodes.
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
			'wp_ajax_learndash_ld30_shortcodes_load_card_manager_form',
			$this->container->callback(
				Card_Management::class,
				'handle_load_card_manager_form'
			)
		);
	}
}
