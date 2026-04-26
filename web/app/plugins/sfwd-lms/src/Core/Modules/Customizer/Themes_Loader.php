<?php
/**
 * Customizer Themes Loader.
 *
 * @since 4.15.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Customizer;

use LearnDash\Core\Modules\Customizer\Themes\Theme;

/**
 * Customizer Themes Loader.
 *
 * @since 4.15.0
 */
class Themes_Loader {
	/**
	 * Contains the list of loaded Customizer Theme instances.
	 *
	 * @since 4.15.0
	 *
	 * @var Theme[]
	 */
	protected array $themes = [];

	/**
	 * Initializes the module.
	 *
	 * @since 4.15.0
	 *
	 * @return void
	 */
	public function init(): void {
		$this->load_themes();

		/**
		 * Fires before the Customizer Themes are initialized.
		 *
		 * @since 4.15.0
		 *
		 * @param Theme[] $themes List of Customizer Theme instances.
		 */
		do_action( 'learndash_customizer_themes_init_before', $this->themes );

		foreach ( $this->themes as $theme ) {
			$theme->init();
		}

		/**
		 * Fires after the Customizer Themes are initialized.
		 *
		 * @since 4.15.0
		 *
		 * @param Theme[] $themes List of Customizer Theme instances.
		 */
		do_action( 'learndash_customizer_themes_init_after', $this->themes );
	}

	/**
	 * Loads the list of experiments.
	 *
	 * @since 4.15.0
	 *
	 * @return void
	 */
	protected function load_themes(): void {
		/**
		 * Filters the list of Customizer Themes.
		 *
		 * @since 4.15.0
		 *
		 * @param Theme[] $themes List of Customizer Theme instances.
		 *
		 * @return Theme[] List of Customizer Theme instances.
		 */
		$themes = apply_filters( 'learndash_customizer_themes', [] );

		$themes = array_filter(
			$themes,
			fn( $theme ): bool => $theme instanceof Theme
		);

		$this->themes = array_values( $themes );
	}
}
