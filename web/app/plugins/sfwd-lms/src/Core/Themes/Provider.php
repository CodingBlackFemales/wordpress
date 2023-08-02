<?php
/**
 * Provider for initializing theme implementations and hooks.
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

namespace LearnDash\Core\Themes;

use LearnDash\Core\Template\Steps;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Class Provider for initializing theme implementations and hooks.
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
	public function register(): void {
		// Bindings.

		$this->container->singleton( Breezy::class, Breezy::class );
		$this->container->singleton( Steps\Loader::class, Steps\Loader::class );

		// Initializations.

		$this->initialize_themes();
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
	private function hooks(): void {
		// Steps loader hooks.

		add_filter(
			'learndash_breezy_localize_script_data',
			$this->container->callback( Steps\Loader::class, 'add_scripts_data' )
		);
		add_action(
			'wp_ajax_' . Steps\Loader::$sub_steps_ajax_action_name,
			$this->container->callback( Steps\Loader::class, 'handle_sub_steps_ajax_request' )
		);
		add_action(
			'wp_ajax_nopriv_' . Steps\Loader::$sub_steps_ajax_action_name,
			$this->container->callback( Steps\Loader::class, 'handle_sub_steps_ajax_request' )
		);
	}

	/**
	 * Initializes the themes.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	private function initialize_themes(): void {
		add_action(
			'learndash_themes_init',
			function(): void {
				/**
				 * Breezy theme.
				 *
				 * @var Breezy $breezy_theme Breezy theme.
				 */
				$breezy_theme = $this->container->get( Breezy::class );
				$breezy_theme::add_theme_instance( 'breezy' );
			}
		);
	}
}
