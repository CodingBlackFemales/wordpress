<?php
/**
 * Provider for initializing shortcode functionality.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

namespace LearnDash\Core\Shortcodes;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Class Provider for initializing shortcode functionality.
 *
 * @since 4.6.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function register() {
		$this->container->singleton( Overrides\Quiz::class, Overrides\Quiz::class );

		$this->hooks();
	}

	/**
	 * Register hooks for the provider.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 *
	 * @ignore
	 */
	public function hooks() {
		add_filter( 'learndash_quiz_shortcode_override_output', '__return_true' );
		add_filter( 'learndash_quiz_shortcode_output', $this->container->callback( Overrides\Quiz::class, 'override_output' ), 10, 2 );
		remove_filter( 'learndash_content', 'lesson_visible_after', 1 );
	}
}
