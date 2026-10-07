<?php
/**
 * Sets up CSS/JS for the Gutenberg blocks.
 *
 * @since 4.17.0
 *
 * @package LearnDash
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues block editor styles and scripts.
 *
 * Fires on `enqueue_block_assets` hook.
 */
function learndash_propanel_editor_scripts() {
	if ( ! is_admin() ) {
		return;
	}

	if (
		! learndash_is_admin_user()
		&& ! learndash_is_group_leader_user()
		&& ! current_user_can( 'propanel_widgets' )
	) {
		return;
	}

	// Make paths variables so we don't write em twice ;).
	$learndash_block_path        = '../assets/js/index.js';
	$learndash_editor_style_path = '../assets/js/index.css';
	$asset_path                  = dirname( __DIR__ ) . '/assets/js/index.asset.php';
	$learndash_block_dependencies = file_exists( $asset_path )
		? include $asset_path
		: array(
			'dependencies' => array(),
			'version'      => LD_PP_VERSION,
		);

	wp_enqueue_style( 'ld-propanel-style', LD_PP_PLUGIN_URL . 'dist/css/ld-propanel.css', null, LD_PP_VERSION );

	wp_register_script( 'ld-propanel-chart-script', LD_PP_PLUGIN_URL . 'dist/vendor/Chart.js', array( 'jquery' ), LD_PP_VERSION, false );

	$learndash_block_dependencies['dependencies'] = array_merge(
		$learndash_block_dependencies['dependencies'],
		array(
			'ld-propanel-chart-script',
			'ldlms-blocks-js',
		)
	);

	// Enqueue the bundled block JS file.
	wp_enqueue_script(
		'ld-propanel-blocks-js',
		plugins_url( $learndash_block_path, __FILE__ ),
		$learndash_block_dependencies['dependencies'],
		LEARNDASH_SCRIPT_VERSION_TOKEN
	);

	$ldlms_settings = array(
		'settings' => array(
			'per_page' => LearnDash_Settings_Section_General_Per_Page::get_section_settings_all(),
		),
		'nonce'    => wp_create_nonce( 'ld-propanel' ),
	);

	// Load the MO file translations into wp.i18n script hook.
	// learndash_load_inline_script_locale_data();

	wp_localize_script( 'ld-propanel-blocks-js', 'ld_propanel_settings', $ldlms_settings );

	// Enqueue optional editor only styles.
	wp_enqueue_style(
		'ld-propanel-blocks-editor-css',
		plugins_url( $learndash_editor_style_path, __FILE__ ),
		array(),
		LEARNDASH_SCRIPT_VERSION_TOKEN
	);
	wp_style_add_data( 'ld-propanel-blocks-editor-css', 'rtl', 'replace' );
}
// Hook scripts function into block editor hook.
add_action( 'enqueue_block_assets', 'learndash_propanel_editor_scripts', 11 );

add_filter(
	'block_categories_all',
	function ( $block_categories ) {
		$ld_block_cat_found = false;

		foreach ( $block_categories as $block_cat ) {
			if ( ( isset( $block_cat['slug'] ) ) && ( 'ld-propanel-blocks' === $block_cat['slug'] ) ) {
				$ld_block_cat_found = true;
			}
		}

		if ( false === $ld_block_cat_found ) {
			$block_categories[] = array(
				'slug'  => 'ld-propanel-blocks',
				'title' => esc_html__( 'LearnDash LMS Reporting Blocks', 'learndash' ),
				'icon'  => false,
			);
		}

		// Always return $default_block_categories.
		return $block_categories;
	},
	30,
	1
);
