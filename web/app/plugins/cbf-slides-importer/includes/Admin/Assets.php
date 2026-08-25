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
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'maybe_load_scripts' ) );
		add_action( 'admin_print_scripts', array( AssetsMain::class, 'localize_printed_scripts' ), 5 );
		add_action( 'admin_print_footer_scripts', array( AssetsMain::class, 'localize_printed_scripts' ), 5 );
	}


	/**
	 * Only enqueue plugin assets on the importer page.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public static function maybe_load_scripts( string $hook ): void {
		// The importer page slug produces a hook like "learndash-lms_page_cbf-slides-importer".
		if ( strpos( $hook, ImporterPage::PAGE_SLUG ) === false ) {
			return;
		}

		// Register Google API client library so the Picker can be loaded on demand.
		wp_register_script(
			'google-api-client',
			'https://apis.google.com/js/api.js',
			array(),
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			false // must load in <head> for gapi to be available before picker init
		);

		AssetsMain::load_scripts();
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
			'deps' => array( 'google-api-client' ),
			'data' => array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'rest_url'    => rest_url( 'cbf-si/v1/' ),
				'wp_rest_url' => rest_url( '' ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
			),
		);
		return $scripts;
	}
}
