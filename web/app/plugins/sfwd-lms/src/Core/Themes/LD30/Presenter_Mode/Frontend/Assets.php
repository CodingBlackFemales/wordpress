<?php
/**
 * Presenter Mode Frontend assets loader.
 *
 * @since 4.23.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Presenter_Mode\Frontend;

use LDLMS_Post_Types;
use StellarWP\Learndash\StellarWP\Assets\Asset;
use StellarWP\Learndash\StellarWP\Assets\Assets as Base_Assets;

/**
 * Presenter Mode Frontend assets loader.
 *
 * @since 4.23.0
 */
class Assets {
	/**
	 * Asset Group to register our Assets to and enqueue from.
	 *
	 * @since 4.23.0
	 *
	 * @var string
	 */
	public static string $group = 'learndash-presenter-mode-frontend';

	/**
	 * Registers assets to the asset group.
	 *
	 * @since 4.23.0
	 *
	 * @return void
	 */
	public function register_assets(): void {
		$post_types = LDLMS_Post_Types::get_post_types( 'course_steps' );

		Asset::add( 'learndash-presenter-mode-style', 'presenter-mode.css' )
			->add_style_data( 'rtl', true )
			->add_to_group( self::$group )
			->set_path( 'src/assets/dist/css/themes/ld30/components', false )
			->set_condition(
				static function () use ( $post_types ) {
					return is_singular( $post_types );
				}
			)
			->enqueue_on( 'wp_enqueue_scripts', 10 ) // Must match the where it is hooked in the provider.
			->register();

		Asset::add( 'learndash-presenter-mode-script', 'presenter-mode.js' )
			->add_to_group( self::$group )
			->set_path( 'src/assets/dist/js/themes/ld30/components', false )
			->set_condition(
				static function () use ( $post_types ) {
					return is_singular( $post_types );
				}
			)
			->enqueue_on( 'wp_enqueue_scripts', 10 ) // Must match the where it is hooked in the provider.
			->register();
	}

	/**
	 * Enqueues the assets.
	 *
	 * @since 4.23.0
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		Base_Assets::instance()->enqueue_group( self::$group );
	}
}
