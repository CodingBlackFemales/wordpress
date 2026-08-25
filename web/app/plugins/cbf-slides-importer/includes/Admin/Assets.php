<?php
/**
 * Register and enqueue admin assets.
 *
 * @class   Admin\Assets
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Admin;

use CodingBlackFemales\SlidesImporter\Assets as AssetsMain;
use CodingBlackFemales\SlidesImporter\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin assets class.
 *
 * Registers the plugin admin stylesheet and JS bundle, and passes the
 * REST API nonce and base URL to the JS via wp_localize_script.
 */
final class Assets extends AssetsMain {

	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_filter( 'cbf_si_enqueue_styles', array( __CLASS__, 'add_styles' ), 9 );
		add_filter( 'cbf_si_enqueue_scripts', array( __CLASS__, 'add_scripts' ), 9 );
		add_action( 'admin_enqueue_scripts', array( AssetsMain::class, 'load_scripts' ) );
		add_action( 'admin_print_scripts', array( AssetsMain::class, 'localize_printed_scripts' ), 5 );
		add_action( 'admin_print_footer_scripts', array( AssetsMain::class, 'localize_printed_scripts' ), 5 );
	}


	/**
	 * Declare admin styles.
	 *
	 * @param  array $styles Existing styles array.
	 * @return array<string,array>
	 */
	public static function add_styles( array $styles ): array {
		$styles['cbf-slides-importer-admin'] = array(
			'src' => AssetsMain::localize_asset( 'css/admin/cbf-slides-importer.css' ),
		);
		return $styles;
	}


	/**
	 * Declare admin scripts.
	 *
	 * The main JS bundle receives:
	 * - ajax_url  : for any legacy wp_ajax calls
	 * - rest_url  : base REST URL for the plugin's namespace
	 * - nonce     : wp_rest nonce for REST authentication
	 *
	 * @param  array $scripts Existing scripts array.
	 * @return array<string,array>
	 */
	public static function add_scripts( array $scripts ): array {
		$scripts['cbf-slides-importer-admin'] = array(
			'src'  => AssetsMain::localize_asset( 'js/admin/cbf-slides-importer.js' ),
			'deps' => array( 'jquery', 'wp-api-fetch' ),
			'data' => array(
				'ajax_url' => Utils::ajax_url(),
				'rest_url' => rest_url( 'cbf-si/v1/' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
			),
		);
		return $scripts;
	}
}
