<?php
/**
 * BuddyBoss SSO OAuth2 class.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO
 */

use BBSSO\Persistent\BB_SSO_Persistent;

require_once 'class-bb-sso-auth.php';

/**
 * Abstract class BB_SSO_Oauth2.
 *
 * Provides OAuth2 functionality for single sign-on (SSO) integrations.
 *
 * @since 2.6.30
 */
abstract class BB_SSO_Oauth2 extends BB_SSO_Auth {

	const CSRF_LENGTH = 32;

	protected $state = false;

	protected $client_id;
	protected $client_secret;
	protected $redirect_uri;

	protected $endpoint_authorization;
	protected $endpoint_access_token;
	protected $endpoint_rest_api;

	protected $default_rest_params = array();

	protected $scopes = array();

	/**
	 * Abstract class BB_SSO_Oauth2.
	 *
	 * Provides OAuth2 functionality for single sign-on (SSO) integrations.
	 *
	 * @since 2.6.30
	 */
	public function check_error() {
		if ( isset( $_REQUEST['error'] ) && isset( $_REQUEST['error_description'] ) ) {
			if ( $this->validate_state() ) {
				throw new Exception( esc_html( $_REQUEST['error'] ) . ': ' . esc_html( $_REQUEST['error_description'] ) );
			}
		}
	}

	/**
	 * Validates the state by comparing the stored and received state values.
	 *
	 * @since 2.6.30
	 *
	 * @return bool True if the state is valid, false otherwise.
	 */
	protected function validate_state() {
		$this->state = BB_SSO_Persistent::get( $this->provider_id . '_state' );
		if ( false === $this->state ) {
			return false;
		}

		if ( empty( $_GET['state'] ) ) {
			return false;
		}

		if ( $_GET['state'] === $this->state ) {
			return true;
		}

		return false;
	}

	/**
	 * Sends a GET request to the specified API endpoint.
	 *
	 * @since 2.6.30
	 *
	 * @param string       $path     The API path to append to the endpoint URL.
	 * @param array        $data     (Optional) The data to include in the request. Defaults to an empty array.
	 * @param string|false $endpoint (Optional) The full API endpoint URL. Defaults to the class property
	 *                               $endpoint_rest_api.
	 *
	 * @throws Exception If the request fails or the response is unexpected.
	 * @return array The response body decoded as an associative array.
	 */
	public function get( $path, $data = array(), $endpoint = false ) {

		$http_args = array(
			'timeout'    => 15,
			'user-agent' => 'WordPress',
			'body'       => array_merge( $this->default_rest_params, $data ),
		);
		if ( ! $endpoint ) {
			$endpoint = $this->endpoint_rest_api;
		}
		$request = wp_remote_get( $endpoint . $path, $this->extend_http_args( $this->extend_all_http_args( $http_args ) ) );

		if ( is_wp_error( $request ) ) {

			throw new Exception( esc_html( $request->get_error_message() ) );
		} elseif ( wp_remote_retrieve_response_code( $request ) !== 200 ) {

			$this->error_from_response( json_decode( wp_remote_retrieve_body( $request ), true ) );
		}

		$result = json_decode( wp_remote_retrieve_body( $request ), true );

		if ( ! is_array( $result ) ) {
			throw new Exception(
				sprintf(
				/* translators: %s: The response body. */
					esc_html__( 'Unexpected response: %s', 'buddyboss-pro' ),
					wp_remote_retrieve_body( $request )
				)
			);
		}

		return $result;
	}

	/**
	 * Adds an authorization header to the HTTP request if the access token is available.
	 *
	 * @since 2.6.30
	 *
	 * @param array $http_args The arguments for the HTTP request.
	 *
	 * @return array The modified HTTP request arguments.
	 */
	protected function extend_http_args( $http_args ) {
		if ( isset( $this->access_token_data['access_token'] ) ) {
			$http_args['headers'] = array(
				'Authorization' => 'Bearer ' . $this->access_token_data['access_token'],
			);
		}

		return $http_args;
	}

	/**
	 * Extends the HTTP request arguments with additional data if needed.
	 * Can be overridden for custom HTTP request modifications.
	 *
	 * @since 2.6.30
	 *
	 * @param array $http_args The arguments for the HTTP request.
	 *
	 * @return array The modified HTTP request arguments.
	 */
	protected function extend_all_http_args( $http_args ) {

		return $http_args;
	}

	/**
	 * Throws an exception based on an OAuth error response.
	 *
	 * @since 2.6.30
	 *
	 * @param array $response The error response from OAuth provider.
	 *
	 * @throws Exception If an error is found in the response.
	 */
	protected function error_from_response( $response ) {
		if ( isset( $response['error'] ) ) {
			$error_description = isset( $response['error_description'] ) ? esc_html( $response['error_description'] ) : __( 'No additional details available', 'buddyboss-pro' );
			throw new Exception( esc_html( $response['error'] ) . ': ' . $error_description );
		}
	}

	/**
	 * Retrieves the test URL (usually the access token endpoint).
	 *
	 * @since 2.6.30
	 *
	 * @return string The access token endpoint URL.
	 */
	public function get_test_url() {
		return $this->endpoint_access_token;
	}

	/**
	 * Checks if authentication data is available (i.e., OAuth code).
	 *
	 * @since 2.6.30
	 *
	 * @return bool True if OAuth code is present, false otherwise.
	 */
	public function has_authenticate_data() {
		return isset( $_REQUEST['code'] );
	}

	/**
	 * Sets the client ID.
	 *
	 * @since 2.6.30
	 *
	 * @param string $client_id OAuth2 client ID.
	 */
	public function set_client_id( $client_id ) {
		$this->client_id = $client_id;
	}

	/**
	 * Sets the client secret.
	 *
	 * @since 2.6.30
	 *
	 * @param string $client_secret OAuth2 client secret.
	 */
	public function set_client_secret( $client_secret ) {
		$this->client_secret = $client_secret;
	}

	/**
	 * Sets the redirect URI.
	 *
	 * @since 2.6.30
	 *
	 * @param string $redirect_uri OAuth2 redirect URI.
	 */
	public function set_redirect_uri( $redirect_uri ) {
		$this->redirect_uri = $redirect_uri;
	}

	/**
	 * Creates the authorization URL with required query parameters.
	 *
	 * Adds response_type, client_id, redirect_uri and state as query parameter in the Authorization Url.
	 * client_id can be found in the App when you create one
	 * redirect_uri is the url you wish to be redirected after you entered you login credentials
	 * state is a randomly generated string
	 *
	 * @since 2.6.30
	 *
	 * @return string The full authorization URL with query parameters.
	 */
	public function create_auth_url() {

		$args = array(
			'response_type' => 'code',
			'client_id'     => rawurlencode( $this->client_id ),
			'redirect_uri'  => rawurlencode( $this->redirect_uri ),
			'state'         => rawurlencode( $this->get_state() ),
		);

		$scopes = apply_filters( 'bb_sso_' . $this->provider_id . '_scopes', $this->scopes );
		if ( count( $scopes ) ) {
			$args['scope'] = rawurlencode( $this->format_scopes( $scopes ) );
		}

		$args = apply_filters( 'bb_sso_' . $this->provider_id . '_auth_url_args', $args );

		return add_query_arg( $args, $this->get_endpoint_authorization() );
	}

	/**
	 * Retrieves the stored state for the current provider.
	 *
	 * @since 2.6.30
	 *
	 * @return string The stored or newly generated state.
	 */
	protected function get_state() {
		$this->state = BB_SSO_Persistent::get( $this->provider_id . '_state' );
		if ( null === $this->state ) {
			$this->state = $this->generate_random_state();

			BB_SSO_Persistent::set( $this->provider_id . '_state', $this->state );
		}

		return $this->state;
	}

	/**
	 * Generates a random CSRF state string.
	 *
	 * @since 2.6.30
	 *
	 * @return string The random state string.
	 */
	protected function generate_random_state() {

		if ( function_exists( 'random_bytes' ) ) {
			return $this->bytes_to_string( random_bytes( self::CSRF_LENGTH ) );
		}

		if ( function_exists( 'mcrypt_create_iv' ) ) {
			/** @noinspection PhpDeprecationInspection */
			$binary_string = mcrypt_create_iv( self::CSRF_LENGTH, MCRYPT_DEV_URANDOM );

			if ( false !== (bool) $binary_string ) {
				return $this->bytes_to_string( $binary_string );
			}
		}

		if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			$was_cryptographically_strong = false;

			$binary_string = openssl_random_pseudo_bytes( self::CSRF_LENGTH, $was_cryptographically_strong );

			if ( false !== (bool) $binary_string && true === (bool) $was_cryptographically_strong ) {
				return $this->bytes_to_string( $binary_string );
			}
		}

		return $this->random_str( self::CSRF_LENGTH );
	}

	/**
	 * Converts a binary string to a URL-safe string.
	 *
	 * @since 2.6.30
	 *
	 * @param string $binary_string The binary string to convert.
	 *
	 * @return string The converted string.
	 */
	private function bytes_to_string( $binary_string ) {
		return substr( bin2hex( $binary_string ), 0, self::CSRF_LENGTH );
	}

	private function random_str( $length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ) {
		$str = '';
		$max = strlen( $keyspace ) - 1;
		for ( $i = 0; $i < $length; ++$i ) {
			$str .= $keyspace[ random_int( 0, $max ) ];
		}

		return $str;
	}

	/**
	 * Formats and connects an array of scopes with whitespace.
	 *
	 * @since 2.6.30
	 *
	 * @param array $scopes The scopes to format.
	 *
	 * @return string The formatted scope string.
	 */
	protected function format_scopes( $scopes ) {
		return implode( ' ', array_unique( $scopes ) );
	}

	/**
	 * Retrieves the authorization endpoint URL.
	 *
	 * @since 2.6.30
	 *
	 * @return string The authorization endpoint URL.
	 */
	public function get_endpoint_authorization() {
		return $this->endpoint_authorization;
	}

	/**
	 * Authenticates using the OAuth2 code and exchanges it for an access token.
	 *
	 * @since 2.6.30
	 *
	 * @throws Exception If the state is invalid or the request fails.
	 * @return bool|string False if authentication fails, JSON-encoded access token data otherwise.
	 */
	public function authenticate() {

		if ( isset( $_GET['code'] ) ) {
			if ( ! $this->validate_state() ) {
				throw new Exception( 'Unable to validate CSRF state' );
			}

			$http_args = array(
				'timeout'    => 15,
				'user-agent' => 'WordPress',
				'body'       => array(
					'grant_type'    => 'authorization_code',
					'code'          => $_GET['code'],
					'redirect_uri'  => $this->redirect_uri,
					'client_id'     => $this->client_id,
					'client_secret' => $this->client_secret,
				),
			);

			$request = wp_remote_post( $this->endpoint_access_token, $this->extend_authenticate_http_args( $this->extend_all_http_args( $http_args ) ) );

			if ( is_wp_error( $request ) ) {

				throw new Exception( esc_html( $request->get_error_message() ) );
			} elseif ( wp_remote_retrieve_response_code( $request ) !== 200 ) {

				$this->error_from_response( json_decode( wp_remote_retrieve_body( $request ), true ) );
			}

			$access_token_data = json_decode( wp_remote_retrieve_body( $request ), true );

			if ( ! is_array( $access_token_data ) ) {
				throw new Exception(
					sprintf(
					/* translators: %s: The response body. */
						esc_html__( 'Unexpected response: %s', 'buddyboss-pro' ),
						wp_remote_retrieve_body( $request )
					)
				);
			}

			$access_token_data = $this->extend_access_token_data( $access_token_data );

			$access_token_data['created'] = time();

			$this->access_token_data = $access_token_data;

			return wp_json_encode( $access_token_data );
		}

		return false;
	}

	/**
	 * Allows adding additional authentication data to the request arguments.
	 * Used for customizing the authentication process.
	 *
	 * @since 2.6.30
	 *
	 * @param array $http_args The arguments for the authentication request.
	 *
	 * @return array The modified authentication request arguments.
	 */
	protected function extend_authenticate_http_args( $http_args ) {

		return $http_args;
	}

	/**
	 * Extends or modifies the access token data.
	 * Useful for adjusting the access token format if needed.
	 *
	 * @since 2.6.30
	 *
	 * @param array $access_token_data The access token data.
	 *
	 * @return array The modified access token data.
	 */
	protected function extend_access_token_data( $access_token_data ) {

		return $access_token_data;
	}

	/**
	 * Deletes the login persistent data.
	 *
	 * @since 2.6.30
	 */
	public function delete_login_persistent_data() {
		BB_SSO_Persistent::delete( $this->provider_id . '_state' );
	}

	/**
	 * Sends a POST request to the specified API endpoint.
	 *
	 * @since 2.6.30
	 *
	 * @param string       $path     The API path to append to the endpoint URL.
	 * @param array        $data     (Optional) The data to include in the request. Defaults to an empty array.
	 * @param string|false $endpoint (Optional) The full API endpoint URL. Defaults to the class property
	 *                               $endpoint_rest_api.
	 *
	 * @throws Exception If the request fails or the response is unexpected.
	 * @return array The response body decoded as an associative array.
	 */
	public function post( $path, $data = array(), $endpoint = false ) {

		$http_args = array(
			'timeout'    => 15,
			'user-agent' => 'WordPress',
			'body'       => array_merge( $this->default_rest_params, $data ),
		);
		if ( ! $endpoint ) {
			$endpoint = $this->endpoint_rest_api;
		}

		$request = wp_remote_post( $endpoint . $path, $this->extend_http_args( $this->extend_all_http_args( $http_args ) ) );

		if ( is_wp_error( $request ) ) {

			throw new Exception( esc_html( $request->get_error_message() ) );
		} elseif ( wp_remote_retrieve_response_code( $request ) !== 200 ) {
			$this->error_from_response( json_decode( wp_remote_retrieve_body( $request ), true ) );
		}

		$result = json_decode( wp_remote_retrieve_body( $request ), true );

		if ( ! is_array( $result ) ) {
			throw new Exception(
				sprintf(
				/* translators: %s: The response body. */
					esc_html__( 'Unexpected response: %s', 'buddyboss-pro' ),
					wp_remote_retrieve_body( $request )
				)
			);
		}

		return $result;
	}
}
