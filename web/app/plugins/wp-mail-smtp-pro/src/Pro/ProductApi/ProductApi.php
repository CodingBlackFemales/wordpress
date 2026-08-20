<?php

namespace WPMailSMTP\Pro\ProductApi;

use WPMailSMTP\Helpers\Helpers;
use WPMailSMTP\WP;

/**
 * Product API integration.
 *
 * @since 4.4.0
 */
class ProductApi {

	/**
	 * Product name.
	 *
	 * @since 4.4.0
	 */
	const PRODUCT_NAME = 'wp-mail-smtp';

	/**
	 * Base URL.
	 *
	 * @since 4.4.0
	 */
	const BASE_URL = 'https://wpmailsmtpapi.com/';

	/**
	 * Product API client.
	 *
	 * @since 4.4.0
	 *
	 * @var Client
	 */
	protected $client = null;

	/**
	 * Register hooks.
	 *
	 * @since 4.4.0
	 */
	public function hooks() {

		add_action( 'init', [ $this, 'maybe_verify_nonce' ] );
		add_action( 'init', [ $this, 'maybe_unlock_credentials_generation' ] );
	}

	/**
	 * Get the Product API client.
	 *
	 * @since 4.4.0
	 *
	 * @return Client
	 */
	public function get_client() {

		if ( $this->client !== null ) {
			return $this->client;
		}

		$base_url     = defined( 'WPMS_PRODUCT_API_BASE_URL' ) ? WPMS_PRODUCT_API_BASE_URL : self::BASE_URL;
		$environment  = defined( 'WPMS_PRODUCT_API_ENV' ) ? WPMS_PRODUCT_API_ENV : 'production';
		$site_url     = WP::get_site_url();
		$license_key  = wp_mail_smtp()->get_license_key();
		$license_type = wp_mail_smtp()->is_pro() && ! empty( $license_key ) ? 'pro' : 'lite';
		$user_agent   = Helpers::get_default_user_agent();

		$this->client = new Client(
			self::PRODUCT_NAME,
			$base_url,
			[
				'site_url'     => $site_url,
				'license_key'  => $license_key,
				'license_type' => $license_type,
				'user_agent'   => $user_agent,
				'environment'  => $environment,
			]
		);

		return $this->client;
	}

	/**
	 * Verify nonce during the product API credentials generation.
	 *
	 * @since 4.4.0
	 */
	public function maybe_verify_nonce() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_REQUEST[ self::PRODUCT_NAME . '-product-api-nonce-verification' ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$request_none = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		$nonce        = new CredentialsGenerationNonce();

		if ( empty( $request_none ) || ! $nonce->verify( $request_none ) ) {
			wp_send_json( [ 'status' => 'failed' ], 403 );
		}

		wp_send_json( [ 'status' => 'success' ] );
	}

	/**
	 * Unlock the product API credentials generation.
	 *
	 * @since 4.4.0
	 */
	public function maybe_unlock_credentials_generation() {

		if (
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			! isset( $_GET[ self::PRODUCT_NAME . '-product-api-unlock-credentials-generation' ] ) ||
			! current_user_can( wp_mail_smtp()->get_capability_manage_options() ) ||
			! wp_mail_smtp()->get_admin()->is_admin_page()
		) {
			return;
		}

		delete_option( CredentialsGenerator::ATTEMPT_COUNTER_OPTION );
		delete_transient( CredentialsGenerator::LOCK_OPTION );
	}
}
