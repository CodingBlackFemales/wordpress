<?php
/**
 * Class BB_Social_Provider_Microsoft_Client
 *
 * This class is responsible for handling OAuth2 authentication with Microsoft's API.
 * It extends the BB_SSO_Oauth2 class and includes methods for managing access tokens,
 * making API requests, and handling errors specific to Microsoft's response structure.
 *
 * @since   2.7.10
 * @package BuddyBossPro/SSO/Providers/Microsoft
 */

/**
 * Microsoft OAuth2 Client class for BuddyBoss SSO.
 *
 * @since 2.7.10
 */
class BB_Social_Provider_Microsoft_Client extends BB_SSO_Oauth2 {

	/**
	 * The access token data.
	 *
	 * @since 2.7.10
	 *
	 * @var array
	 */
	protected $access_token_data = array(
		'access_token' => '',
		'expires_in'   => -1,
		'created'      => -1,
	);

	/**
	 * The Microsoft Graph API endpoint.
	 *
	 * @since 2.7.10
	 *
	 * @var string
	 */
	protected $endpoint_rest_api = 'https://graph.microsoft.com/v1.0';

	/**
	 * The prompt value for Microsoft OAuth2.
	 *
	 * @since 2.7.10
	 *
	 * @var string
	 */
	private $prompt = 'select_account';

	/**
	 * The scopes for Microsoft OAuth2.
	 *
	 * @since 2.7.10
	 *
	 * @var array
	 */
	protected $scopes = array(
		'openid',
		'profile',
		'User.Read',
	);

	/**
	 * Constructor for Microsoft OAuth2 Client.
	 *
	 * @since 2.7.10
	 *
	 * @param string $provider_id The provider identifier.
	 * @param string $tenant      The Microsoft tenant identifier.
	 */
	public function __construct( $provider_id, $tenant ) {
		parent::__construct( $provider_id );
		$this->endpoint_authorization = 'https://login.microsoftonline.com/' . $tenant . '/oauth2/v2.0/authorize';
		$this->endpoint_access_token  = 'https://login.microsoftonline.com/' . $tenant . '/oauth2/v2.0/token';
	}

	/**
	 * Create the authorization URL for Microsoft OAuth2.
	 *
	 * @since 2.7.10
	 *
	 * @return string The authorization URL with prompt parameter if set.
	 */
	public function create_auth_url() {
		$args = array();
		if ( '' !== $this->prompt ) {
			$args['prompt'] = rawurlencode( $this->prompt );
		}

		return add_query_arg( $args, parent::create_auth_url() );
	}

	/**
	 * Set the prompt parameter for Microsoft OAuth2.
	 *
	 * @since 2.7.10
	 *
	 * @param string $prompt The prompt value to set.
	 */
	public function set_prompt( $prompt ) {
		$this->prompt = $prompt;
	}

	/**
	 * Handle errors from Microsoft API response.
	 *
	 * @since 2.7.10
	 *
	 * @param array $response The API response array.
	 * @throws BB_SSO_Exception If the response contains an error.
	 */
	protected function error_from_response( $response ) {
		if ( isset( $response['error'] ) && isset( $response['error']['message'] ) ) {
			throw new BB_SSO_Exception( esc_html( $response['error']['code'] ) . ' - ' . esc_html( $response['error']['message'] ) );
		} else {
			parent::error_from_response( $response );
		}
	}
}
