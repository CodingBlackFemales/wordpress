<?php

namespace WPMailSMTP\Pro\ProductApi;

use WPMailSMTP\Options;

/**
 * Credentials repository.
 *
 * @since 4.4.0
 */
class CredentialsRepository {

	/**
	 * Get credentials from storage.
	 *
	 * @since 4.4.0
	 *
	 *  @param string $environment Environment for which to get credentials.
	 *
	 * @return Credentials
	 */
	public function get( $environment = 'production' ) {

		$credentials = (array) Options::init()->get( 'product_api', 'credentials' );

		return Credentials::from_array( $credentials[ $environment ] ?? [] );
	}

	/**
	 * Save credentials to storage.
	 *
	 * @since 4.4.0
	 *
	 * @param Credentials $credentials Product API authentication credentials.
	 * @param string      $environment Environment for which to save credentials.
	 *
	 * @return void
	 */
	public function save( Credentials $credentials, $environment = 'production' ) {

		$options = Options::init();
		$all_opt = $options->get_all_raw();

		$all_opt['product_api']['credentials'][ $environment ] = $credentials->to_array();

		$options->set( $all_opt, false, true );
	}
}
