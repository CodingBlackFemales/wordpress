<?php
/**
 * Shortcodes assets loader.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Shortcodes;

use LDLMS_Post_Types;
use StellarWP\Learndash\StellarWP\Assets\Asset;
use StellarWP\Learndash\StellarWP\Assets\Assets as Base_Assets;

/**
 * Shortcodes assets loader.
 *
 * @since 4.25.0
 */
class Assets {
	/**
	 * Asset Group to register our Assets to and enqueue from.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static string $group = 'learndash-ld30-shortcodes';

	/**
	 * Registers assets to the asset group.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function register_assets(): void {
		Asset::add( 'learndash-ld30-shortcodes-style', 'shortcodes.css' )
			->add_style_data( 'rtl', true )
			->add_to_group( self::$group )
			->set_path( 'src/assets/dist/css/themes/ld30', false )
			->set_condition(
				static function () {
					return self::has_learndash_shortcodes();
				}
			)
			->set_dependencies( 'dashicons' )
			->enqueue_on( 'wp_enqueue_scripts', 10 ) // Must match the where it is hooked in the provider.
			->register();

		Asset::add( 'learndash-ld30-shortcodes-script', 'main.js' )
			->add_to_group( self::$group )
			->set_path( 'src/assets/dist/js/themes/ld30/shortcodes', false )
			->set_condition(
				static function () {
					return self::has_learndash_shortcodes();
				}
			)
			->set_dependencies( 'wp-i18n', 'wp-hooks' )
			->add_localize_script(
				'learndash.global',
				[
					'ajax_url'        => admin_url( 'admin-ajax.php' ),
					'nonce'           => wp_create_nonce( 'learndash_ld30_shortcodes' ),
					'rest_nonce'      => wp_create_nonce( 'wp_rest' ),
					'remove_card_url' => rest_url( 'learndash/v1/profile/remove-card' ),
					'labels'          => [
						'course' => learndash_get_custom_label_lower( 'course' ),
					],
				]
			)
			->enqueue_on( 'wp_enqueue_scripts', 10 ) // Must match the where it is hooked in the provider.
			->register();
	}

	/**
	 * Checks if the current page contains LearnDash shortcodes.
	 *
	 * @since 4.25.0
	 *
	 * @return bool True if shortcodes are present, false otherwise.
	 */
	private static function has_learndash_shortcodes(): bool {
		global $learndash_shortcode_used;

		return $learndash_shortcode_used;
	}

	/**
	 * Enqueues the assets.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		Base_Assets::instance()->enqueue_group( self::$group );
	}
}
