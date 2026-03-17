<?php
/**
 * Class BB_Social_Provider_Google_Client
 *
 * Handles Google OAuth2 integration for authentication, including access token management
 * and authorization URL creation, for BuddyBoss social login integration.
 *
 * @since 2.6.30
 *
 * @package BuddyBossPro/SSO/Providers/Google
 */

/**
 * Class BB_Social_Provider_Google_Client
 */
class BB_Social_Provider_Google_Client extends BB_SSO_Oauth2 {

	/**
	 * Access token data.
	 *
	 * @since 2.6.30
	 *
	 * @var array $access_token_data Stores access token details such as token value, expiration, and creation time.
	 */
	protected $access_token_data = array(
		'access_token' => '',
		'expires_in'   => -1,
		'created'      => -1,
	);

	/**
	 * Scopes required for Google OAuth2.
	 *
	 * @since 2.6.30
	 *
	 * @var array $scopes Defines the required OAuth2 scopes, including email and profile access.
	 */
	protected $scopes = array(
		'email',
		'profile',
	);

	/**
	 * Authorization endpoint for Google OAuth2.
	 *
	 * @since 2.6.30
	 *
	 * @var string $endpoint_authorization URL for Google's OAuth2 authorization endpoint.
	 */
	protected $endpoint_authorization = 'https://accounts.google.com/o/oauth2/v2/auth';

	/**
	 * Endpoint for access token retrieval.
	 *
	 * @since 2.6.30
	 *
	 * @var string $endpoint_access_token URL for Google's OAuth2 access token endpoint.
	 */
	protected $endpoint_access_token = 'https://oauth2.googleapis.com/token';

	/**
	 * Endpoint for REST API requests.
	 *
	 * @since 2.6.30
	 *
	 * @var string $endpoint_rest_api Base URL for Google's REST API endpoint.
	 */
	protected $endpoint_rest_api = 'https://www.googleapis.com/oauth2/v2/';

	/**
	 * Default parameters for REST API requests.
	 *
	 * @since 2.6.30
	 *
	 * @var array $default_rest_params Default parameters for REST API requests, including response format as JSON.
	 */
	protected $default_rest_params = array(
		'alt' => 'json',
	);

	/**
	 * Access type for Google OAuth2.
	 *
	 * @since 2.6.30
	 *
	 * @var string $access_type Specifies the access type for Google OAuth2; 'offline' grants refresh token capability.
	 */
	private $access_type = 'offline';

	/**
	 * Prompt type for Google OAuth2.
	 *
	 * @since 2.6.30
	 *
	 * @var string $prompt Sets the prompt parameter for Google sign-in, defaulting to 'select_account' for account
	 *                     selection.
	 */
	private $prompt = 'select_account';

	/**
	 * Sets the access token data by decoding a JSON-encoded token string.
	 *
	 * @since 2.6.30
	 *
	 * @param string $access_token_data JSON-encoded string containing access token data.
	 */
	public function set_access_token_data( $access_token_data ) {
		$this->access_token_data = json_decode( $access_token_data, true );
	}

	/**
	 * Creates the authorization URL for Google OAuth2 login, appending access type and prompt parameters.
	 *
	 * @since 2.6.30
	 *
	 * @return string Authorization URL with additional OAuth2 parameters.
	 */
	public function create_auth_url() {
		$args = array(
			'access_type' => rawurlencode( $this->access_type ),
		);

		if ( '' !== $this->prompt ) {
			$args['prompt'] = rawurlencode( $this->prompt );
		}

		return add_query_arg( $args, parent::create_auth_url() );
	}

	/**
	 * Sets the prompt type for the Google OAuth2 authentication flow.
	 *
	 * @since 2.6.30
	 *
	 * @param string $prompt Specifies the type of prompt, such as 'select_account' or 'consent'.
	 */
	public function setPrompt( $prompt ) {
		$this->prompt = $prompt;
	}

	/**
	 * Throws an exception if an error message is present in the response data.
	 *
	 * @since 2.6.30
	 *
	 * @param array $response API response data.
	 *
	 * @throws Exception If the response contains an error message, an exception is thrown with that message.
	 */
	protected function error_from_response( $response ) {
		if ( isset( $response['error']['message'] ) ) {
			throw new Exception( esc_html( $response['error']['message'] ) );
		}
	}
}
