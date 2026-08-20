<?php

namespace WPMailSMTP\Pro\Providers\Outlook\OneClick\Auth;

use Exception;
use WPMailSMTP\Helpers\Helpers;

/**
 * Client for work with One-Click Setup API.
 *
 * @since 4.3.0
 */
class Client {

	/**
	 * The API base URL.
	 *
	 * @since 4.3.0
	 */
	const API_BASE_URL = 'https://api.wpmailsmtp.com/microsoft/v1/oauth/';

	/**
	 * Site URL.
	 *
	 * @since 4.3.0
	 *
	 * @var string
	 */
	private $site_url;

	/**
	 * Constructor method.
	 *
	 * @since 4.3.0
	 *
	 * @param string $site_url Site URL.
	 */
	public function __construct( $site_url = false ) {

		$this->site_url = $site_url ? $site_url : site_url();
	}

	/**
	 * Get API base URL.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public static function get_api_base_url() {

		return defined( 'WPMS_OUTLOOK_ONE_CLICK_SETUP_AUTH_URL' ) ? WPMS_OUTLOOK_ONE_CLICK_SETUP_AUTH_URL : self::API_BASE_URL;
	}

	/**
	 * Get authorization URL.
	 *
	 * @since 4.3.0
	 *
	 * @param array $args List of arguments.
	 *
	 * @return string
	 */
	public function get_auth_url( $args ) {

		$url = self::get_api_base_url() . 'authorize/';

		$args = wp_parse_args(
			$args,
			[
				'version' => WPMS_PLUGIN_VER,
				'siteurl' => $this->site_url,
				'license' => wp_mail_smtp()->get_license_key(),
			]
		);

		if ( ! empty( $args['return'] ) ) {
			$args['return'] = rawurlencode( $args['return'] );
		}

		return add_query_arg( $args, $url );
	}

	/**
	 * Get access and refresh tokens.
	 *
	 * @since 4.3.0
	 *
	 * @param string $authorization_code Authorization code.
	 *
	 * @throws Exception On request error.
	 *
	 * @return array
	 */
	public function get_tokens( $authorization_code ) {

		$response = $this->request(
			'token/',
			[
				'authorization_code' => $authorization_code,
			],
			'POST'
		);

		if ( $response->has_errors() ) {
			throw new Exception( $response->get_errors()->get_error_message(), $response->get_status_code() );
		}

		return $response->get_body();
	}

	/**
	 * Refresh access token.
	 *
	 * @since 4.3.0
	 *
	 * @param string $refresh_token Refresh token.
	 *
	 * @throws Exception On request error.
	 *
	 * @return array
	 */
	public function refresh_access_token( $refresh_token ) {

		$response = $this->request(
			'refresh-token/',
			[
				'refresh_token' => $refresh_token,
			],
			'POST'
		);

		if ( $response->has_errors() ) {
			throw new Exception( $response->get_errors()->get_error_message(), $response->get_status_code() );
		}

		return $response->get_body();
	}

	/**
	 * Disconnect.
	 *
	 * @since 4.3.0
	 *
	 * @return void
	 */
	public function disconnect() {

		$this->request(
			'disconnect/',
			[
				'siteurl' => $this->site_url,
			],
			'POST'
		);
	}

	/**
	 * Make a request.
	 *
	 * @since 4.3.0
	 *
	 * @param string $route  Endpoint name.
	 * @param array  $args   List of arguments.
	 * @param string $method Request method.
	 *
	 * @return Response
	 */
	private function request( $route, $args, $method = 'GET' ) {

		$body = wp_parse_args(
			$args,
			[
				'version' => WPMS_PLUGIN_VER,
				'siteurl' => $this->site_url,
				'license' => wp_mail_smtp()->get_license_key(),
			]
		);

		/**
		 * Allow modifying request arguments.
		 *
		 * @since 4.3.0
		 *
		 * @param array $args List of args.
		 */
		$request_args = apply_filters(
			'wp_mail_smtp_pro_providers_outlook_one_click_auth_client_request_send_args',
			[
				'headers'    => [
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'Cache-Control' => 'no-store, max-age=0',
				],
				'method'     => $method,
				'body'       => wp_json_encode( $body ),
				'timeout'    => 30,
				'user-agent' => Helpers::get_default_user_agent(),
			]
		);

		$response = wp_remote_request( self::get_api_base_url() . $route, $request_args );

		return new Response( $response );
	}
}
