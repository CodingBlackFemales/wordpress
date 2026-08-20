<?php

namespace WPMailSMTP\Pro\ProductApi;

use WP_Error;

/**
 * Authentication credentials generator.
 *
 * @since 4.4.0
 */
class CredentialsGenerator {

	/**
	 * Max number of attempts to generate credentials.
	 *
	 * @since 4.4.0
	 *
	 * @var int
	 */
	const MAX_ATTEMPTS = 10;

	/**
	 * Credentials generation attempt counter option name.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	const ATTEMPT_COUNTER_OPTION = 'wp_mail_smtp_product_api_credentials_generation_attempt_counter';

	/**
	 * Credentials generation lock option name.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	const LOCK_OPTION = 'wp_mail_smtp_product_api_credentials_generation_lock';

	/**
	 * Product API client.
	 *
	 * @since 4.4.0
	 *
	 * @var Client
	 */
	private $client;

	/**
	 * Constructor.
	 *
	 * @since 4.4.0
	 *
	 * @param Client $client Product API client.
	 */
	public function __construct( Client $client ) {

		$this->client = $client;
	}

	/**
	 * Generate authentication credentials.
	 *
	 * @since 4.4.0
	 *
	 * @param string $license_type License type. Either `pro` or `lite`.
	 *
	 * @return Credentials|WP_Error
	 */
	public function generate( $license_type ) {

		if ( $this->is_max_credentials_generation_attempts_reached() ) {
			return new WP_Error( 'credentials_generation_max_attempts_reached', esc_html__( 'Max number of attempts to generate credentials has been reached.', 'wp-mail-smtp-pro' ) );
		}

		if ( get_transient( self::LOCK_OPTION ) ) {
			return new WP_Error( 'credentials_generation_in_progress', esc_html__( 'Credentials generation already started by another request.', 'wp-mail-smtp-pro' ) );
		}

		set_transient( self::LOCK_OPTION, true, MINUTE_IN_SECONDS );

		$nonce = new CredentialsGenerationNonce();

		$response = $this->client->request(
			'POST',
			'auth/v1/' . $license_type . '/keys',
			[
				'body' => array_merge(
					$this->get_site_info(),
					[
						'nonce' => $nonce->create(),
					]
				),
			],
			false
		);

		$nonce->delete();
		$this->update_credentials_generation_attempts_count( $response->is_successful() );

		delete_transient( self::LOCK_OPTION );

		if ( ! $response->is_successful() ) {
			return $response->get_errors();
		}

		$body = $response->get_body();

		if ( ! isset( $body['site_id'], $body['public_key'], $body['token'] ) ) {
			return new WP_Error( 'credentials_generation_invalid_response', esc_html__( 'Invalid response for credentials generation.', 'wp-mail-smtp-pro' ) );
		}

		return Credentials::from_array( $body );
	}

	/**
	 * Check that we have not reached the max number of attempts to generate credentials.
	 *
	 * @since 4.4.0
	 *
	 * @return bool
	 */
	private function is_max_credentials_generation_attempts_reached() {

		$attempts_count = get_option( self::ATTEMPT_COUNTER_OPTION, 0 );

		return $attempts_count >= self::MAX_ATTEMPTS;
	}

	/**
	 * Update count of the attempts to generate credentials.
	 * It allows us to prevent sending requests to the API server infinitely.
	 *
	 * @since 4.4.0
	 *
	 * @param bool $reset Reset the attempts count.
	 */
	private function update_credentials_generation_attempts_count( $reset = false ) {

		if ( $reset ) {
			delete_option( self::ATTEMPT_COUNTER_OPTION );

			return;
		}

		update_option(
			self::ATTEMPT_COUNTER_OPTION,
			get_option( self::ATTEMPT_COUNTER_OPTION, 0 ) + 1
		);
	}

	/**
	 * Get site info data that required for credentials generation.
	 *
	 * @since 4.4.0
	 *
	 * @return array
	 */
	private function get_site_info() {

		$site_title    = get_bloginfo( 'name' );
		$site_locale   = get_locale();
		$site_timezone = wp_timezone_string();
		$admin_email   = get_option( 'admin_email' );

		$info = [
			'site_title'    => $site_title,
			'site_locale'   => $site_locale,
			'site_timezone' => $site_timezone,
			'admin_email'   => $admin_email,
		];

		if ( empty( $admin_email ) ) {
			return $info;
		}

		$user = get_user_by( 'email', $admin_email );

		if ( $user === false ) {
			return $info;
		}

		if ( ! empty( $user->first_name ) ) {
			$info['admin_first_name'] = $user->first_name;
		}

		if ( ! empty( $user->last_name ) ) {
			$info['admin_last_name'] = $user->last_name;
		}

		return $info;
	}
}
