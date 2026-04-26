<?php
/**
 * Provider for LD30 Quiz.
 *
 * @since 4.21.3
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Quiz;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Class Provider for initializing theme implementations and hooks.
 *
 * @since 4.21.3
 */
class Provider extends ServiceProvider {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.21.3
	 *
	 * @return void
	 */
	public function register(): void {
		$this->hooks();
	}

	/**
	 * Register hooks for the provider.
	 *
	 * @since 4.21.3
	 *
	 * @return void
	 */
	private function hooks(): void {
		add_action(
			'init',
			$this->container->callback(
				Assets::class,
				'register_scripts'
			)
		);
	}
}
