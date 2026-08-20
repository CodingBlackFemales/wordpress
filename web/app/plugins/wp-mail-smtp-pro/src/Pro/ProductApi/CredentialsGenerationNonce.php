<?php

namespace WPMailSMTP\Pro\ProductApi;

/**
 * Nonce for credentials generation.
 *
 * @since 4.4.0
 */
class CredentialsGenerationNonce {

	/**
	 * Nonce option name.
	 *
	 * @since 4.4.0
	 */
	const NONCE_OPTION = 'wp_mail_smtp_product_api_credentials_generation_nonce';

	/**
	 * Create a new nonce.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function create() {

		$auth_salt = defined( 'AUTH_SALT' ) ? AUTH_SALT : '';

		$nonce = hash(
			'sha512',
			wp_generate_password(
				128,
				true,
				true
			) . $auth_salt . uniqid( '', true )
		);

		set_transient( self::NONCE_OPTION, $nonce, MINUTE_IN_SECONDS * 5 );

		return $nonce;
	}

	/**
	 * Get the nonce value.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get() {

		return get_transient( self::NONCE_OPTION );
	}

	/**
	 * Verify the nonce value.
	 *
	 * @since 4.4.0
	 *
	 * @param string $passed A passed nonce value.
	 *
	 * @return bool
	 */
	public function verify( $passed ) {

		return hash_equals( $this->get(), $passed );
	}

	/**
	 * Delete the nonce.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	public function delete() {

		delete_option( self::NONCE_OPTION );
	}
}
