<?php

namespace WPForms\Pro\Integrations\Gutenberg;

use WP_REST_Request; // phpcs:ignore WPForms.PHP.UseStatement.UnusedUseStatement
use WP_REST_Response; // phpcs:ignore WPForms.PHP.UseStatement.UnusedUseStatement
use WPForms\Integrations\Gutenberg\RestApi as RestApiBase;

/**
 * Rest API for Gutenberg block for Pro.
 *
 * @since 1.8.8
 */
class RestApi extends RestApiBase {

	/**
	 * Stock photos class instance.
	 *
	 * @since 1.8.8
	 *
	 * @var StockPhotos
	 */
	private $stock_photos_obj;

	/**
	 * Initialize class.
	 *
	 * @since 1.8.8
	 *
	 * @param FormSelector|mixed $form_selector_obj FormSelector object.
	 * @param ThemesData|mixed   $themes_data_obj   ThemesData object.
	 * @param StockPhotos|mixed  $stock_photos_obj  StockPhotos object.
	 */
	public function __construct( $form_selector_obj, $themes_data_obj, $stock_photos_obj ) {

		if ( ! $form_selector_obj || ! $themes_data_obj || ! $stock_photos_obj || ! wpforms_is_rest() ) {
			return;
		}

		$this->stock_photos_obj = $stock_photos_obj;

		parent::__construct( $form_selector_obj, $themes_data_obj );
	}

	/**
	 * Register API routes for Gutenberg block.
	 *
	 * @since 1.8.8
	 */
	public function register_api_routes() {

		parent::register_api_routes();

		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/stock-photos/install/',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'install_stock_photos' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);
	}

	/**
	 * Save custom themes.
	 *
	 * @since 1.8.8
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function install_stock_photos( WP_REST_Request $request ): WP_REST_Response {

		$force = (bool) ( $request->get_param( 'force' ) ?? false );

		// Install stock photos and return REST response.
		$result = $this->stock_photos_obj->install( $force );

		if ( ! empty( $result['error'] ) ) {
			return rest_ensure_response(
				[
					'result' => false,
					'error'  => $result['error'],
				]
			);
		}

		return rest_ensure_response(
			[
				'result'   => true,
				'pictures' => $result['pictures'] ?? [],
			]
		);
	}
}
