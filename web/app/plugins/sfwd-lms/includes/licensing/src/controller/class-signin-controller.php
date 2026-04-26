<?php
/**
 * Licensing Sign-in Controller.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Hub\Controller;

use LearnDash\Hub\Framework\Controller;
use LearnDash\Hub\Traits\License;
use LearnDash\Hub\Traits\Permission;

defined( 'ABSPATH' ) || exit;

/**
 * This controller only appear if user not signed in.
 */
class Signin_Controller extends Controller {
	use Permission;
	use License;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'wp_ajax_ld_hub_verify_and_save_license', array( $this, 'verify_license' ) );
	}

	/**
	 * Ajax endpoint to verifying the license.
	 */
	public function verify_license() {
		if ( ! $this->check_permission() ) {
			return;
		}

		if ( ! $this->verify_nonce( 'ld_hub_verify_license' ) ) {
			return;
		}

		//phpcs:ignore
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		//phpcs:ignore
		$key   = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';

		if ( empty( $email ) || empty( $key ) ) {
			wp_send_json_error( __( 'Please provide a valid email and license key.', 'learndash' ) );
		}

		$ret = $this->get_api()->verify_license( $email, $key, true );

		if ( is_wp_error( $ret ) ) {
			wp_send_json_error( $ret->get_error_message() );
		}

		$this->allow_user(
			get_current_user_id(),
			array(),
			true
		);

		wp_send_json_success();
	}

	/**
	 * Register the scripts
	 *
	 * @since 4.18.0
	 * @deprecated 4.18.0
	 *
	 * @return void
	 */
	public function register_scripts() {
		_deprecated_function( __METHOD__, '4.18.0', 'LearnDash\Core\Modules\Licensing\Assets::register_assets' );

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

	/**
	 * Display the login.
	 */
	public function display() {
		$this->render( 'signin' );
	}
}
