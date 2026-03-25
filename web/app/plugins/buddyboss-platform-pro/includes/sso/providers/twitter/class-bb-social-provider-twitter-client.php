<?php
/**
 * Class BB_Social_Provider_Twitter_Client
 *
 * This class handles the authentication and interaction with the Twitter API using OAuth 1.0a.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO/Providers/Twitter
 */

use BBSSO\Persistent\BB_SSO_Persistent;

require_once bb_sso_path() . 'lib/PKCE/class-bb-sso-pkce.php';

/**
 * Class BB_Social_Provider_Twitter_Client
 *
 * @since 2.6.30
 */
class BB_Social_Provider_Twitter_Client extends BB_SSO_Oauth2 {

	/**
	 * Access token data for Twitter.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $access_token_data = array(
		'access_token' => '',
		'expires_in'   => -1,
		'created'      => -1,
	);

	/**
	 * The Twitter authorization endpoint.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $endpoint_authorization = 'https://twitter.com/i/oauth2/authorize';

	/**
	 * The Twitter access token endpoint.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $endpoint_access_token = 'https://api.twitter.com/2/oauth2/token';

	/**
	 * The Twitter REST API endpoint.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $endpoint_rest_api = 'https://api.twitter.com/2/';

	/**
	 * The Twitter scopes required for authentication.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $scopes = array(
		'users.read',
		'tweet.read',
	);

	/**
	 * Creates the authorization URL for Twitter's OAuth2.
	 *
	 * This method generates a URL for the user to authenticate and authorize
	 * the application with their Twitter account. It uses PKCE for enhanced security.
	 *
	 * @since 2.6.30
	 *
	 * @return string The full authorization URL with query parameters.
	 *
	 * @throws BB_SSO_Exception If there is an issue generating the URL.
	 */
	public function create_auth_url() {
		try {

			$code_verifier = \BBSSO\PKCE\BB_SSO_PKCE::generate_code_verifier( 128 );

			$args = array(
				'response_type'         => 'code',
				'client_id'             => rawurlencode( $this->client_id ),
				'redirect_uri'          => rawurlencode( $this->redirect_uri ),
				'state'                 => rawurlencode( $this->get_state() ),
				'code_challenge'        => \BBSSO\PKCE\BB_SSO_PKCE::generate_code_challenge( $code_verifier ),
				'code_challenge_method' => 'S256',
			);
			BB_SSO_Persistent::set( $this->provider_id . '_code_verifier', $code_verifier );

			$scopes = apply_filters( 'bb_sso_' . $this->provider_id . '_scopes', $this->scopes );
			if ( count( $scopes ) ) {
				$args['scope'] = rawurlencode( $this->format_scopes( $scopes ) );
			}

			$args = apply_filters( 'bb_sso_' . $this->provider_id . '_auth_url_args', $args );

			return add_query_arg( $args, $this->get_endpoint_authorization() );
		} catch ( Exception $e ) {
			throw new BB_SSO_Exception( esc_html( $e->getMessage() ) );
		}
	}

	/**
	 * Deletes persistent login data associated with the Twitter provider.
	 *
	 * This method clears any stored data related to the Twitter login session,
	 * including the code verifier used in the authentication process.
	 *
	 * @since 2.6.30
	 */
	public function delete_login_persistent_data() {
		parent::delete_login_persistent_data();
		BB_SSO_Persistent::delete( $this->provider_id . '_code_verifier' );
	}

	/**
	 * Extends HTTP arguments for the authentication request to Twitter.
	 *
	 * This method modifies the HTTP arguments to include the necessary headers and
	 * body parameters for the OAuth2 token request.
	 *
	 * @since 2.6.30
	 *
	 * @param array $http_args The original HTTP arguments to be extended.
	 *
	 * @return array The modified HTTP arguments including headers and body.
	 */
	protected function extend_authenticate_http_args( $http_args ) {
		$http_args['headers'] = array(
			'Content-Type'  => 'application/x-www-form-urlencoded',
			'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ), // phpcs:ignore
		);
		$http_args['body']    = array(
			'code'          => $_GET['code'], // phpcs:ignore
			'grant_type'    => 'authorization_code',
			'client_id '    => $this->client_id,
			'redirect_uri'  => $this->redirect_uri,
			'code_verifier' => BB_SSO_Persistent::get( $this->provider_id . '_code_verifier' ),
		);

		return $http_args;
	}

	/**
	 * Sends a GET request to the specified API endpoint.
	 *
	 * @since 2.6.90
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

		if ( 429 === wp_remote_retrieve_response_code( $request ) ) {
			\BBSSO\BB_SSO_Notices::add_error( esc_html__( 'Rate limit exceeded. Please try again in a few minutes.', 'buddyboss-pro' ), 'info' );
		}

		return $result;
	}
}
