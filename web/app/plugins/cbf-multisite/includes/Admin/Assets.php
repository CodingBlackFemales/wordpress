<?php
/**
 * Register admin assets.
 *
 * @class       AdminAssets
 * @version     1.0.0
 * @package     CodingBlackFemales/Multisite/Classes/
 */

namespace CodingBlackFemales\Multisite\Admin;

use CodingBlackFemales\Multisite\Assets as AssetsMain;
use CodingBlackFemales\Multisite\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin assets class
 */
final class Assets {

	/**
	 * Hook in methods.
	 */
	public static function hooks() {
		add_filter( '_enqueue_styles', array( __CLASS__, 'add_styles' ), 9 );
		add_filter( '_enqueue_scripts', array( __CLASS__, 'add_scripts' ), 9 );
		add_action( 'admin_enqueue_scripts', array( AssetsMain::class, 'load_scripts' ) );
		add_action( 'admin_print_scripts', array( AssetsMain::class, 'localize_printed_scripts' ), 5 );
		add_action( 'admin_print_footer_scripts', array( AssetsMain::class, 'localize_printed_scripts' ), 5 );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_block_editor_scripts' ) );
	}


	/**
	 * Add styles for the admin.
	 *
	 * @param array $styles Admin styles.
	 * @return array<string,array>
	 */
	public static function add_styles( $styles ) {

		$styles['cbf-multisite-admin'] = array(
			'src' => AssetsMain::localize_asset( 'css/admin/cbf-multisite.css' ),
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

		$scripts['cbf-multisite-admin'] = array(
			'src'  => AssetsMain::localize_asset( 'js/admin/cbf-multisite.js' ),
			'data' => array(
				'ajax_url' => Utils::ajax_url(),
			),
		);

		$scripts['cbf-multisite-group-figure'] = array(
			'src'  => AssetsMain::localize_asset( 'js/admin/group-block-figure.js' ),
			'deps' => array( 'wp-hooks', 'wp-blocks', 'wp-element', 'wp-compose', 'wp-block-editor', 'wp-components' ),
		);

		return $scripts;
	}


	/**
	 * Add inline styles for the block editor.
	 *
	 * @return void
	 */
	public static function enqueue_block_editor_scripts() {
		// Hide the Group block's original (hardcoded) HTML element control.
		// The wrapper carries a dedicated .block-editor-html-element-control class,
		// so we can target it precisely. The :has() guard ensures the rule only
		// fires when a Group block with our extended control is in the panel.
		wp_add_inline_style(
			'wp-edit-blocks',
			'.block-editor-block-inspector__advanced:has(.cbf-html-element-control) .block-editor-html-element-control { display: none !important; }'
		);
	}
}
