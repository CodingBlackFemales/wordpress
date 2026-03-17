<?php
/**
 * Class BB_Social_Provider_Facebook_Client
 *
 * Handles Facebook OAuth2 authentication and access token management for BuddyBoss Social Provider.
 * Extends BB_SSO_Oauth2 to manage the OAuth2 process and related endpoints.
 *
 * @since   2.6.30
 *
 * @package BuddyBossPro/SSO/Providers/Facebook
 */

/**
 * Class BB_Social_Provider_Facebook_Client
 */
class BB_Social_Provider_Facebook_Client extends BB_SSO_Oauth2 {

	/**
	 * Default Graph API version
	 *
	 * @since 2.6.30
	 *
	 * @var string The default version of the Facebook Graph API used in requests.
	 */
	const DEFAULT_GRAPH_VERSION = 'v19.0';
	/**
	 * Access Token Data.
	 *
	 * @since 2.6.30
	 *
	 * @var array Stores the access token data including token, expiration, and creation time.
	 */
	protected $access_token_data = array(
		'access_token' => '',
		'expires_in'   => -1,
		'created'      => -1,
	);
	/**
	 * Scopes.
	 *
	 * @since 2.6.30
	 *
	 * @var array Required OAuth scopes for Facebook API requests.
	 */
	protected $scopes = array(
		'public_profile',
		'email',
	);
	/**
	 * Is Test Mode.
	 *
	 * @since 2.6.30
	 *
	 * @var bool Indicates if the client is running in test mode.
	 */
	private $is_test = false;

	/**
	 * Constructor.
	 *
	 * @since 2.6.30
	 *
	 * @param string $provider_id The provider identifier.
	 * @param bool   $is_test     Determines if the client is in test mode.
	 */
	public function __construct( $provider_id, $is_test ) {
		$this->is_test = $is_test;
		parent::__construct( $provider_id );
		$this->endpoint_access_token = 'https://graph.facebook.com/' . self::DEFAULT_GRAPH_VERSION . '/oauth/access_token';
		$this->endpoint_rest_api     = 'https://graph.facebook.com/' . self::DEFAULT_GRAPH_VERSION . '/';
	}

	/**
	 * Gets the Facebook authorization endpoint URL, adjusting for mobile or desktop views.
	 *
	 * @since 2.6.30
	 *
	 * @return string The endpoint URL for initiating OAuth authorization.
	 */
	public function get_endpoint_authorization() {

		$endpoint_authorization = 'https://www.facebook.com/';

		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			if ( preg_match( '/Android|iPhone|iP[ao]d|Mobile/', $_SERVER['HTTP_USER_AGENT'] ) ) { // phpcs:ignore
				$endpoint_authorization = 'https://m.facebook.com/';
			}
		}

		$endpoint_authorization .= self::DEFAULT_GRAPH_VERSION . '/dialog/oauth';

		if ( ( isset( $_GET['display'] ) && 'popup' === $_GET['display'] ) || $this->is_test ) { // phpcs:ignore
			$endpoint_authorization .= '?display=popup';
		}

		return $endpoint_authorization;
	}

	/**
	 * Checks if the current access token is long-lived.
	 *
	 * @since 2.6.30
	 *
	 * @return bool True if the access token is long-lived, false otherwise.
	 */
	public function is_access_token_long_lived() {

		return isset( $this->access_token_data['expires_in'] ) ? ( $this->access_token_data['created'] + $this->access_token_data['expires_in'] > time() + ( 60 * 60 * 2 ) ) : true;
	}

	/**
	 * Requests a long-lived access token from Facebook.
	 *
	 *
	 * @since 2.6.30
	 *
	 * @throws BB_SSO_Exception If an error occurs during the request.
	 * @return string|false The access token data as a JSON string, or false on failure.
	 */
	public function request_long_lived_access_token() {

		$http_args = array(
			'timeout'    => 15,
			'user-agent' => 'WordPress',
			'body'       => array(
				'grant_type'        => 'fb_exchange_token',
				'client_id'         => $this->client_id,
				'client_secret'     => $this->client_secret,
				'fb_exchange_token' => $this->access_token_data['access_token'],
			),
		);

		$request = wp_remote_get( $this->endpoint_access_token, $this->extend_all_http_args( $http_args ) );

		if ( is_wp_error( $request ) ) {

			throw new BB_SSO_Exception( esc_html( $request->get_error_message() ) );
		} elseif ( wp_remote_retrieve_response_code( $request ) !== 200 ) {

			$this->error_from_response( json_decode( wp_remote_retrieve_body( $request ), true ) );
		}

		$access_token_data = json_decode( wp_remote_retrieve_body( $request ), true );

		if ( ! is_array( $access_token_data ) ) {
			throw new BB_SSO_Exception(
				sprintf(
				// translators: %s: The response body.
					esc_html__( 'Unexpected response: %s', 'buddyboss-pro' ),
					wp_remote_retrieve_body( $request )
				)
			);
		}

		$access_token_data['created'] = time();

		$this->access_token_data = $access_token_data;

		return wp_json_encode( $access_token_data );
	}

	/**
	 * Adds the app secret proof to the HTTP request arguments for secure API calls.
	 *
	 * @since 2.6.30
	 *
	 * @param array $http_args The original HTTP request arguments.
	 *
	 * @return array The modified HTTP request arguments with app secret proof.
	 */
	protected function extend_all_http_args( $http_args ) {
		$http_args['body']['appsecret_proof'] = hash_hmac( 'sha256', $this->get_access_token(), $this->client_secret );

		return $http_args;
	}

	/**
	 * Retrieves the current access token, or the client ID if no token is available.
	 *
	 * @since 2.6.30
	 *
	 * @return string The access token or client ID.
	 */
	protected function get_access_token() {
		if ( ! empty( $this->access_token_data['access_token'] ) ) {
			return $this->access_token_data['access_token'];
		}

		return $this->client_id;
	}

	/**
	 * Handles errors from a failed Facebook API response.
	 *
	 * @since 2.6.30
	 *
	 * @param array $response The API response containing the error data.
	 *
	 * @throws BB_SSO_Exception If an error message is present in the response.
	 */
	protected function error_from_response( $response ) {
		if ( isset( $response['error'] ) ) {
			throw new BB_SSO_Exception( esc_html( $response['error']['message'] ) );
		}
	}

	/**
	 * Formats and returns the OAuth scopes as a comma-separated string.
	 *
	 * @since 2.6.30
	 *
	 * @param array $scopes The array of scopes.
	 *
	 * @return string The formatted scopes string.
	 */
	protected function format_scopes( $scopes ) {
		return implode( ',', $scopes );
	}
}
