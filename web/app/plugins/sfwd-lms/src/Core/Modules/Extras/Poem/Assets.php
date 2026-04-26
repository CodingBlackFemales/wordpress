<?php
/**
 * Poem assets loader.
 *
 * @since 4.23.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Extras\Poem;

use LearnDash\Core\Utilities\Location;
use StellarWP\Learndash\StellarWP\Assets\Asset;
use StellarWP\Learndash\StellarWP\Assets\Assets as Base_Assets;

/**
 * Poem assets loader.
 *
 * @since 4.23.2
 */
class Assets {
	/**
	 * Asset Group to register our Assets to and enqueue from.
	 *
	 * @since 4.23.2
	 *
	 * @var string
	 */
	public static string $group = 'learndash-poem';

	/**
	 * Registers assets to the asset group.
	 *
	 * @since 4.23.2
	 *
	 * @return void
	 */
	public function register_assets(): void {
		Asset::add( 'learndash-poem-script', 'poem.js' )
			->add_to_group( self::$group )
			->set_path( 'src/assets/dist/js/admin/modules/extras', false )
			->set_dependencies( 'wp-i18n', 'thickbox' )
			->set_condition( fn() => $this->should_load_assets() )
			->enqueue_on( 'admin_enqueue_scripts', 10 ) // Must match the where it is hooked in the provider.
			->register();
	}

	/**
	 * Enqueues the assets.
	 *
	 * @since 4.23.2
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		Base_Assets::instance()->enqueue_group( self::$group );

		if ( $this->should_load_assets() ) {
			/*
			 * This loads both CSS and JS and will load extra logic for multi-sites, so we must handle it this way
			 * instead of simply using dependencies.
			 */
			add_thickbox();
		}
	}

	/**
	 * Determines if the assets should be loaded.
	 *
	 * @since 4.23.2
	 *
	 * @return bool
	 */
	private function should_load_assets(): bool {
		return Location::is_learndash_admin_page();
	}
}
