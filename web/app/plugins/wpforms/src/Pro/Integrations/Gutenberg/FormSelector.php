<?php

namespace WPForms\Pro\Integrations\Gutenberg;

use WPForms\Integrations\Gutenberg\FormSelector as FormSelectorBase;
use WPForms\Pro\Admin\Education\StringsTrait as ProStringsTrait;

/**
 * Gutenberg block for Pro.
 *
 * @since 1.7.0
 */
class FormSelector extends FormSelectorBase {

	use ProStringsTrait;

	/**
	 * Stock photos class instance.
	 *
	 * @since 1.8.8
	 *
	 * @var StockPhotos
	 */
	private $stock_photos_obj;

	/**
	 * Load an integration.
	 *
	 * @since 1.8.8
	 */
	public function load() {

		$this->stock_photos_obj = new StockPhotos();
		$this->themes_data_obj  = new ThemesData( $this->stock_photos_obj );

		parent::load();
	}

	/**
	 * Integration hooks.
	 *
	 * @since 1.8.8
	 */
	protected function hooks() {

		add_action( 'rest_api_init', [ $this, 'init_rest' ] );

		parent::hooks();
	}

	/**
	 * Initialize rest API.
	 *
	 * @since 1.8.8
	 */
	public function init_rest() {

		if ( ! $this->rest_api_obj ) {
			$this->rest_api_obj = new RestApi( $this, $this->themes_data_obj, $this->stock_photos_obj );
		}
	}

	/**
	 * Register WPForms Gutenberg block styles.
	 *
	 * @since 1.7.4.2
	 */
	protected function register_styles() {

		parent::register_styles();

		if ( ! is_admin() ) {
			return;
		}

		$min                 = wpforms_get_min_suffix();
		$disable_css_setting = (int) wpforms_setting( 'disable-css', '1' );
		$deps                = [ 'wpforms-integrations' ];

		if ( $disable_css_setting !== 3 && $this->render_engine !== 'classic' ) {
			$css_file = $disable_css_setting === 2 ? 'base' : 'full';
			$deps     = [ 'wpforms-pro-gutenberg-form-selector' ];

			wp_register_style(
				'wpforms-pro-gutenberg-form-selector',
				WPFORMS_PLUGIN_URL . "assets/pro/css/frontend/{$this->render_engine}/wpforms-{$css_file}{$min}.css",
				[ 'wpforms-gutenberg-form-selector' ],
				WPFORMS_VERSION
			);
		}

		wp_register_style(
			'wpforms-pro-integrations',
			WPFORMS_PLUGIN_URL . "assets/pro/css/admin-integrations{$min}.css",
			$deps,
			WPFORMS_VERSION
		);
	}

	/**
	 * Load WPForms Gutenberg block scripts.
	 *
	 * @since 1.7.0
	 */
	public function enqueue_block_editor_assets() {

		parent::enqueue_block_editor_assets();

		$min = wpforms_get_min_suffix();

		if ( ! $this->is_legacy_block() ) {
			wp_enqueue_script(
				'wpforms-pro-admin-education-core',
				WPFORMS_PLUGIN_URL . "assets/pro/js/admin/education/core{$min}.js",
				[ 'wpforms-admin-education-core' ],
				WPFORMS_VERSION,
				true
			);

			wp_enqueue_script(
				'wpforms-generic-utils',
				WPFORMS_PLUGIN_URL . "assets/js/share/utils{$min}.js",
				[ 'jquery' ],
				WPFORMS_VERSION,
				true
			);

			wp_enqueue_script(
				'wpforms-gutenberg-form-selector',
				WPFORMS_PLUGIN_URL . "assets/pro/js/integrations/gutenberg/formselector.es5{$min}.js",
				[ 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery', 'wpforms-pro-admin-education-core', 'wpforms-generic-utils' ],
				WPFORMS_VERSION,
				true
			);
		}

		wp_localize_script(
			'wpforms-gutenberg-form-selector',
			'wpforms_gutenberg_form_selector',
			$this->get_localize_data()
		);

		wp_enqueue_style( 'wpforms-pro-integrations' );
	}

	/**
	 * Get localize data.
	 *
	 * @since 1.8.8
	 *
	 * @return array
	 */
	public function get_localize_data(): array {

		$data = parent::get_localize_data();

		$strings = [
			'stockInstallTheme' => esc_html__( 'The theme you’ve selected has a background image.', 'wpforms' ),
			'stockInstallBg'    => esc_html__( 'In order to use Stock Photos, an image library must be downloaded and installed.', 'wpforms' ),
			'stockInstall'      => esc_html__( 'It\'s quick and easy, and you\'ll only have to do this once.', 'wpforms' ),
			'continue'          => esc_html__( 'Continue', 'wpforms' ),
			'cancel'            => esc_html__( 'Cancel', 'wpforms' ),
			'installing'        => esc_html__( 'Installing', 'wpforms' ),
			'uhoh'              => esc_html__( 'Uh oh!', 'wpforms' ),
			'close'             => esc_html__( 'Close', 'wpforms' ),
			'commonError'       => esc_html__( 'Something went wrong while performing an AJAX request.', 'wpforms' ),
			'picturesTitle'     => esc_html__( 'Choose a Stock Photo', 'wpforms' ),
			'picturesSubTitle'  => esc_html__( 'Browse for the perfect image for your form background.', 'wpforms' ),
		];

		$data['strings'] = array_merge( $data['strings'], $strings );

		$data['stockPhotos'] = [
			'urlPath'  => $this->stock_photos_obj->get_url_path(),
			'pictures' => $this->stock_photos_obj->get_pictures(),
		];

		$data['isLicenseActive'] = wpforms()->is_pro() && wpforms_get_license_key() && wpforms()->obj( 'license' )->is_active();

		return $data;
	}
}
