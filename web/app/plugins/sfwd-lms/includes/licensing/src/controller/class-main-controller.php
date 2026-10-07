<?php
/**
 * Licensing Main Controller.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Hub\Controller;

use Hub\Traits\Time;
use LearnDash\Hub\Framework\Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Main Controller, this will register a root page into wp-admin.
 */
class Main_Controller extends Controller {
	use Time;

	/**
	 * Projects constructor.
	 */
	public function __construct() {
		parent::__construct();

		// Defer the Harbor check to admin_menu so Harbor is fully initialized by then.
		// register_page() cannot be used here because it would add a nested admin_menu hook that never fires.
		add_action(
			is_multisite() ? 'network_admin_menu' : 'admin_menu',
			static function (): void {
				if ( lw_harbor_is_product_license_active( 'learndash' ) ) {
					return;
				}
				$cap = is_multisite() ? 'manage_network_options' : 'manage_options';
				add_submenu_page(
					'learndash-lms',
					__( 'Add-ons', 'learndash' ),
					__( 'Add-ons', 'learndash' ),
					$cap,
					'learndash-hub',
					[ new Projects_Controller(), 'display' ]
				);
			}
		);

		add_action( 'wp_loaded', [ $this, 'maybe_update' ] );
	}

	/**
	 * Check if we need to take action for new update.
	 *
	 * @since 4.18.0
	 *
	 * @return void
	 */
	public function maybe_update(): void {
		$version_option_name = 'learndash_hub_version';
		$version             = get_option( $version_option_name, '' );

		if ( empty( $version ) || version_compare( $version, '1.3.0', '<' ) ) {
			// updated to 1.3, try to flush the cache.
			delete_option( 'learndash-hub-projects-api' );
		}

		update_option( $version_option_name, HUB_VERSION );
	}

	/**
	 * All the scripts should be registered here, later we can use it when render the view.
	 *
	 * @since 4.18.0
	 * @deprecated 4.18.0
	 *
	 * @return void
	 */
	public function register_scripts() {
		_deprecated_function( __METHOD__, '4.18.0', 'LearnDash\Core\Modules\Licensing\Assets::register_assets' );

		$scripts = array(
			'licensing',
			'projects',
			'settings',
		);
		foreach ( $scripts as $script ) {
			wp_register_script(
				'learndash-hub-' . $script,
				hub_asset_url( '/assets/scripts/' . $script . '.js' ),
				array(
					'react',
					'react-dom',
					'wp-i18n',
				),
				HUB_VERSION,
				true
			);
		}
		wp_register_style(
			'learndash-hub-fontawesome',
			hub_asset_url( '/assets/css/fontawesome.min.css' ),
			array(),
			HUB_VERSION
		);
		wp_register_style(
			'learndash-hub',
			hub_asset_url( '/assets/css/app.css' ),
			array( 'learndash-hub-fontawesome' ),
			HUB_VERSION
		);
	}
}
