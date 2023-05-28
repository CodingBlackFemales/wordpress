<?php

namespace LearnDash\Hub\Controller;

use LearnDash\Hub\Framework\Controller;
use LearnDash\Hub\Traits\License;
use LearnDash\Hub\Traits\Permission;

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
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
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
			wp_send_json_error( __( 'Please provide a valid email and license key.', 'learndash-hub' ) );
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
	 */
	public function register_scripts() {
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
		wp_enqueue_style( 'learndash-hub' );
		$this->render( 'signin' );
	}
}
