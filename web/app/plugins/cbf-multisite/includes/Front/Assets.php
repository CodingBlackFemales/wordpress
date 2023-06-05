<?php
/**
 * Register frontend assets.
 *
 * @class       FrontAssets
 * @version     1.0.0
 * @package     CodingBlackFemales/Multisite/Classes/
 */

namespace CodingBlackFemales\Multisite\Front;

use CodingBlackFemales\Multisite\Assets as AssetsMain;
use CodingBlackFemales\Multisite\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend assets class
 */
final class Assets {

	/**
	 * Hook in methods.
	 */
	public static function hooks() {
		add_filter( '_enqueue_styles', array( __CLASS__, 'add_styles' ), 9 );
		add_filter( '_enqueue_scripts', array( __CLASS__, 'add_scripts' ), 9 );
		add_action( 'wp_enqueue_scripts', array( AssetsMain::class, 'load_scripts' ) );
		add_action( 'wp_print_scripts', array( AssetsMain::class, 'localize_printed_scripts' ), 5 );
		add_action( 'wp_print_footer_scripts', array( AssetsMain::class, 'localize_printed_scripts' ), 5 );
	}


	/**
	 * Add styles for the admin.
	 *
	 * @param array $styles Admin styles.
	 * @return array<string,array>
	 */
	public static function add_styles( $styles ) {

		$styles['cbf-multisite-general'] = array(
			'src' => AssetsMain::localize_asset( 'css/frontend/cbf-multisite.css' ),
		);

		return $styles;
	}


	/**
	 * Add scripts for the admin.
	 *
	 * @param  array $scripts Admin scripts.
	 * @return array<string,array>
	 */
	public static function add_scripts( $scripts ) {

		$scripts['cbf-multisite-general'] = array(
			'src'  => AssetsMain::localize_asset( 'js/frontend/cbf-multisite.js' ),
			'data' => array(
				'ajax_url' => Utils::ajax_url(),
			),
		);

		return $scripts;
	}
}
