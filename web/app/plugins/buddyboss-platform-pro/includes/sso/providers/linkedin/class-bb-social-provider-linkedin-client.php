<?php
/**
 * Class BB_Social_Provider_Linkedin_Client
 *
 * This class is responsible for handling OAuth2 authentication with LinkedIn's API.
 * It extends the BB_SSO_Oauth2 class and includes methods for managing access tokens,
 * making API requests, and handling errors specific to LinkedIn's response structure.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO/Providers/LinkedIn
 */

/**
 *  Class BB_Social_Provider_Linkedin_Client
 */
class BB_Social_Provider_Linkedin_Client extends BB_SSO_Oauth2 {

	/**
	 * The access token data.
	 *
	 * @since 2.6.30
	 *
	 * @var array
	 */
	protected $access_token_data = array(
		'access_token' => '',
		'expires_in'   => -1,
		'created'      => -1,
	);

	/**
	 * The LinkedIn API version.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $endpoint_authorization = 'https://www.linkedin.com/oauth/v2/authorization';

	/**
	 * The LinkedIn access token endpoint.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $endpoint_access_token = 'https://www.linkedin.com/oauth/v2/accessToken';

	/**
	 * The LinkedIn rest API endpoint.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $endpoint_rest_api = 'https://api.linkedin.com/v2';

	/**
	 * The LinkedIn user info endpoint.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $scopes = array(
		'openid',
		'profile',
		'email',
	);

	/**
	 * Sets the access token data.
	 *
	 * @param string $access_token_data JSON-encoded string containing access token information.
	 */
	public function set_access_token_data( $access_token_data ) {
		$this->access_token_data = json_decode( $access_token_data, true );
	}

	/**
	 * Handles errors returned from LinkedIn's API response.
	 *
	 * This method checks for specific error codes and messages from LinkedIn's response
	 * and throws an exception if an error is found. If the error format does not match,
	 * it calls the parent error handling method.
	 *
	 * @param array $response The API response from LinkedIn to check for errors.
	 *
	 * @throws BB_SSO_Exception If the response contains an error code and message.
	 */
	protected function error_from_response( $response ) {
		if ( isset( $response['serviceErrorCode'] ) && isset( $response['message'] ) ) {
			throw new BB_SSO_Exception( esc_html( $response['serviceErrorCode'] ) . ' - ' . esc_html( $response['message'] ) );
		} else {
			parent::error_from_response( $response );
		}
	}
}
