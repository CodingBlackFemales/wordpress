<?php
/**
 * Admin Bar assets loader.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Admin_Bar;

use StellarWP\Learndash\StellarWP\Assets\Asset;
use StellarWP\Learndash\StellarWP\Assets\Assets as Base_Assets;

/**
 * Admin Bar assets loader.
 *
 * @since 4.18.0
 */
class Assets {
	/**
	 * Asset Group to register our Assets to and enqueue from.
	 *
	 * @since 4.18.0
	 *
	 * @var string
	 */
	public static string $group = 'learndash-admin-bar';

	/**
	 * Registers scripts to the asset group.
	 *
	 * @since 4.18.0
	 *
	 * @return void
	 */
	public function register_scripts(): void {
		Asset::add( 'learndash-admin-bar', 'styles.css' )
			->add_dependency( 'dashicons' )
			->add_style_data( 'rtl', true )
			->add_to_group( self::$group )
			->set_path( 'src/assets/dist/css/admin-bar', false )
			->register();
	}

	/**
	 * Enqueues scripts registered to the asset group.
	 *
	 * @since 4.18.0
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		Base_Assets::instance()->enqueue_group( self::$group );
	}
}
