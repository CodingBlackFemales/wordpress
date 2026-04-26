<?php
/**
 * Provider for Legacy Quizzes.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\Legacy\Quiz;

use LearnDash\Core\Themes\LD30\Quiz\Assets;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Class Provider for initializing theme implementations and hooks.
 *
 * @since 4.21.4
 */
class Provider extends ServiceProvider {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function register(): void {
		$this->hooks();
	}

	/**
	 * Register hooks for the provider.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	private function hooks(): void {
		/**
		 * We are intentionally loading the LD30 Quiz Assets rather than Legacy-specific ones.
		 *
		 * This is because the Quiz Templates used by LD30 are technically Legacy ones.
		 */
		add_action(
			'init',
			$this->container->callback(
				Assets::class,
				'register_scripts'
			)
		);
	}
}
