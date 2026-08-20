<?php

namespace WPMailSMTP\Pro\ProductApi;

/**
 * Product API Client.
 *
 * @since 4.4.0
 */
class Client {

	/**
	 * Product name.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	protected $product_name;

	/**
	 * Base URL.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	protected $base_url;

	/**
	 * Product API environment.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	protected $environment;

	/**
	 * Current site URL.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	protected $site_url;

	/**
	 * License key.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	protected $license_key;

	/**
	 * License type (either `lite` or `pro`).
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	protected $license_type;

	/**
	 * User agent.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	protected $user_agent;

	/**
	 * Credentials repository.
	 *
	 * @since 4.4.0
	 *
	 * @var CredentialsRepository
	 */
	protected $credentials_repository;

	/**
	 * Credentials.
	 *
	 * @since 4.4.0
	 *
	 * @var Credentials
	 */
	protected $credentials;

	/**
	 * Constructor.
	 *
	 * @since 4.4.0
	 *
	 * @param string $product_name Product name.
	 * @param string $base_url     Base URL.
	 * @param array  $args         Initialization arguments.
	 */
	public function __construct( $product_name, $base_url, $args ) {

		$this->product_name = $product_name;
		$this->base_url     = $base_url;
		$this->site_url     = ! empty( $args['site_url'] ) ? $args['site_url'] : get_site_url();
		$this->license_key  = ! empty( $args['license_key'] ) ? $args['license_key'] : '';
		$this->license_type = ! empty( $args['license_type'] ) ? $args['license_type'] : 'lite';
		$this->user_agent   = ! empty( $args['user_agent'] ) ? $args['user_agent'] : '';
		$this->environment  = ! empty( $args['environment'] ) ? sanitize_key( $args['environment'] ) : 'production';

		$this->credentials_repository = new CredentialsRepository();
		$this->credentials            = $this->credentials_repository->get( $this->environment );
	}

	/**
	 * Make a request.
	 *
	 * @since 4.4.0
	 *
	 * @param string $method        Request method.
	 * @param string $endpoint      Request endpoint.
	 * @param array  $args          Request arguments.
	 * @param bool   $authenticated Whether the request should be authenticated.
	 *
	 * @return Response|WP_Error
	 */
	public function request( $method, $endpoint, $args = [], bool $authenticated = true ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		$url          = $this->base_url . $endpoint;
		$request_args = [
			'method' => $method,
		];
		$headers      = [
			'X-Site-Url' => $this->site_url,
			'Accept'     => 'application/json',
		];

		if ( ! empty( $this->user_agent ) ) {
			$request_args['user-agent'] = $this->user_agent;
		}

		if ( isset( $args['json'] ) ) {
			$request_args['body']    = wp_json_encode( $args['json'] );
			$headers['Content-Type'] = 'application/json';
		} elseif ( isset( $args['query'] ) ) {
			$url = add_query_arg( $args['query'], $url );
		} elseif ( isset( $args['body'] ) ) {
			$request_args['body'] = $args['body'];
		}

		if ( isset( $args['timeout'] ) ) {
			$request_args['timeout'] = $args['timeout'];
		}

		if ( $this->license_type === 'pro' && ! empty( $this->license_key ) ) {
			$headers['X-License-Key'] = $this->license_key;
		}

		if ( $authenticated ) {
			if ( ! $this->credentials->is_valid() ) {
				$credentials = $this->generate_credentials();

				if ( is_wp_error( $credentials ) ) {
					return $credentials;
				}
			}

			$headers['X-Site-Id']    = $this->credentials->get_site_id();
			$headers['X-Public-Key'] = $this->credentials->get_public_key();
			$headers['X-Token']      = $this->credentials->get_token();
		}

		$request_args['headers'] = array_merge( $headers, $args['headers'] ?? [] );

		$response = wp_safe_remote_request( $url, $request_args );

		return new Response( $response );
	}

	/**
	 * Generate credentials.
	 *
	 * @since 4.4.0
	 *
	 * @return Credentials|WP_Error
	 */
	private function generate_credentials() {

		$credentials_generator = new CredentialsGenerator( $this );
		$credentials           = $credentials_generator->generate( $this->license_type );

		if ( is_wp_error( $credentials ) ) {
			return $credentials;
		}

		$this->credentials = $credentials;

		$this->credentials_repository->save( $this->credentials, $this->environment );

		return $this->credentials;
	}
}
