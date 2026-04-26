<?php
/**
 * LearnDash LD30 Modern Theme Assets class.
 *
 * @since 4.21.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Modern;

use StellarWP\Learndash\StellarWP\Assets\Asset;
use StellarWP\Learndash\StellarWP\Assets\Assets as Base_Assets;

/**
 * LearnDash LD30 Modern Theme Assets class.
 *
 * @since 4.21.0
 */
class Assets {
	/**
	 * Asset Group name.
	 *
	 * @since 4.21.0
	 *
	 * @var string
	 */
	private const GROUP = 'learndash-modern';

	/**
	 * Registers scripts that can be enqueued.
	 *
	 * @since 4.21.0
	 *
	 * @return void
	 */
	public function register_scripts(): void {
		$breakpoint_asset = Base_Assets::instance()->get( 'learndash-breakpoints' );

		if ( $breakpoint_asset instanceof Asset ) {
			$breakpoint_asset->add_to_group( self::GROUP );
		}

		Asset::add( 'learndash-ld30-modern', 'css/modern.css' )
			->add_to_group( self::GROUP )
			->set_path( 'themes/ld30/assets' )
			->add_dependency( 'dashicons' )
			->register();

		Asset::add( 'learndash-ld30-modern-script', 'js/themes/ld30/modern/main.js' )
			->add_to_group( self::GROUP )
			->add_dependency( 'learndash-main' )
			->set_path( 'src/assets/dist' )
			->register();
	}

	/**
	 * Enqueues the scripts for the Modern theme.
	 *
	 * @since 4.21.0
	 *
	 * @return void
	 */
	public static function enqueue_scripts(): void {
		Base_Assets::instance()->enqueue_group( self::GROUP );
	}
}
