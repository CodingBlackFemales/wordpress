<?php
/**
 * Provider for initializing the Breezy Theme.
 *
 * @since 4.21.0
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

namespace LearnDash\Core\Themes\Breezy;

use LearnDash\Core\App;
use LearnDash\Core\Template\Steps;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Class Provider for initializing theme implementations and hooks.
 *
 * @since 4.21.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.21.0
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->should_load() ) {
			return;
		}

		// Bindings.

		$this->container->singleton( Theme::class, Theme::class );
		$this->container->singleton( Steps\Loader::class, Steps\Loader::class );

		// Initializations.

		$this->initialize_themes();
		$this->hooks();
	}

	/**
	 * Register hooks for the provider.
	 *
	 * @since 4.21.0
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
	 * @since 4.21.0
	 *
	 * @return void
	 */
	private function initialize_themes(): void {
		$initialize = static function (): void {
			/**
			 * Breezy theme.
			 *
			 * @var Theme $breezy_theme Breezy theme.
			 */
			$breezy_theme = App::container()->get( Theme::class );
			$breezy_theme::add_theme_instance( 'breezy' );
		};

		/**
		 * If code has already ran that would run LearnDash_Theme_Register::init(),
		 * such as LearnDash_Theme_Register::get_active_theme(), then hooking to learndash_themes_init won't work.
		 *
		 * In that case, we'll directly add the Theme Instance outside of a hook.
		 */
		if ( ! did_action( 'learndash_themes_init' ) ) {
			add_action(
				'learndash_themes_init',
				$initialize
			);
		} else {
			$initialize();
		}
	}

	/**
	 * Determines whether the Breezy theme should be loaded.
	 *
	 * @since 4.21.0
	 *
	 * @return bool
	 */
	private function should_load(): bool {
		// bail early if in-progress features are not enabled.
		if (
			! defined( 'LEARNDASH_ENABLE_IN_PROGRESS_FEATURES' )
			|| ! Cast::to_bool( constant( 'LEARNDASH_ENABLE_IN_PROGRESS_FEATURES' ) )
		) {
			return false;
		}

		// Breezy template.
		if (
			! defined( 'LEARNDASH_ENABLE_FEATURE_BREEZY_TEMPLATE' )
			|| ! Cast::to_bool( constant( 'LEARNDASH_ENABLE_FEATURE_BREEZY_TEMPLATE' ) )
		) {
			return false;
		}

		return true;
	}
}
