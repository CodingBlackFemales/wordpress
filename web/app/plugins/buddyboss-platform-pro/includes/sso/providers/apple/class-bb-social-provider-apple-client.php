<?php
/**
 * Class BB_Social_Provider_Apple_Client
 *
 * Handles the authentication process for Apple OAuth2.
 * This class extends the BB_SSO_Oauth2 base class and manages
 * the creation of authorization URLs, token exchange, and user data retrieval.
 *
 * @since   2.6.30
 *
 * @package BuddyBossPro/SSO/Providers/Apple
 */

use BBSSO\Persistent\BB_SSO_Persistent;

/**
 * Class BB_Social_Provider_Apple_Client
 *
 * @since 2.6.30
 */
class BB_Social_Provider_Apple_Client extends BB_SSO_Oauth2 {

	/**
	 * The provider ID.
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
	 * Endpoint URLs for Apple OAuth2.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $endpoint_authorization = 'https://appleid.apple.com/auth/authorize';

	/**
	 * Endpoint URLs for Apple Access Token.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $endpoint_access_token = 'https://appleid.apple.com/auth/token';

	/**
	 * Endpoint URLs for Apple REST API.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $endpoint_rest_api = '';

	/**
	 * Rest API default parameters.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $default_rest_params = array(
		'format' => 'json',
	);

	/**
	 * Scopes for Apple OAuth2.
	 *
	 * @since 2.6.30
	 *
	 * @var array
	 */
	protected $scopes = array(
		'name email',
	);

	/**
	 * Retrieves the Apple ID token from the access token data.
	 *
	 * @since 2.6.30
	 *
	 * @return string|false The Apple ID token if available, or false if not.
	 */
	public function get_apple_id_token() {
		if ( isset( $this->access_token_data['id_token'] ) && ! empty( $this->access_token_data['id_token'] ) ) {
			return $this->access_token_data['id_token'];
		} elseif ( isset( $this->access_token_data['token'] ) && ! empty( $this->access_token_data['token'] ) ) {
			return $this->access_token_data['token'];
		}

		return false;
	}

	/**
	 * Retrieves the user data from the access token data.
	 *
	 * @since 2.6.30
	 *
	 * @return array|false The user data if available, or false if not.
	 */
	public function get_apple_user_data() {
		if ( isset( $this->access_token_data['user'] ) && ! empty( $this->access_token_data['user'] ) ) {
			return $this->access_token_data['user'];
		}

		return false;
	}

	/**
	 * Creates the authorization URL for the Apple OAuth2 flow.
	 *
	 * @since 2.6.30
	 *
	 * @return string The complete authorization URL with query parameters.
	 */
	public function create_auth_url() {

		$args = array(
			'client_id'     => rawurlencode( $this->client_id ),
			'redirect_uri'  => rawurlencode( $this->redirect_uri ),
			'response_type' => 'code',
			'response_mode' => 'form_post',
			'state'         => rawurlencode( $this->get_state() ),
		);

		if ( ! empty( $_REQUEST['test'] ) ) { // phpcs:ignore
			$args['test'] = $_REQUEST['test']; // phpcs:ignore
		}

		$scopes = apply_filters( 'bb_sso_' . $this->provider_id . '_scopes', $this->scopes );
		if ( count( $scopes ) ) {
			$args['scope'] = rawurlencode( $this->format_scopes( $scopes ) );
		}

		$args = apply_filters( 'bb_sso_' . $this->provider_id . '_auth_url_args', $args );

		return add_query_arg( $args, $this->get_endpoint_authorization() );
	}

	/**
	 * Authenticates the user by exchanging the authorization code for an access token.
	 *
	 * @since 2.6.30
	 *
	 * @return string|false JSON-encoded access token data if successful, or false if no code is present.
	 *
	 * @throws BB_SSO_Exception If there are errors during the authentication process.
	 */
	public function authenticate() {
		if ( isset( $_POST['code'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			// Validate state to prevent CSRF attacks.
			if ( ! $this->validate_state() ) {
				throw new BB_SSO_Exception( esc_html__( 'Unable to validate CSRF state', 'buddyboss-pro' ) );
			}

			$http_args = array(
				'timeout'    => 15,
				'user-agent' => 'WordPress',
				'headers'    => array(
					'Authorization' => 'Basic  ' . base64_encode( $this->client_id . ':' . $this->client_secret ), // phpcs:ignore
				),
				'body'       => array(
					'grant_type'    => 'authorization_code',
					'code'          => $_POST['code'], // phpcs:ignore
					'redirect_uri'  => $this->redirect_uri,
					'client_id'     => $this->client_id,
					'client_secret' => $this->client_secret,
				),
			);

			$request = wp_remote_post( $this->endpoint_access_token, $this->extend_all_http_args( $http_args ) );

			if ( is_wp_error( $request ) ) {

				throw new BB_SSO_Exception( esc_html( $request->get_error_message() ) );
			} elseif ( wp_remote_retrieve_response_code( $request ) !== 200 ) {

				$this->error_from_response( json_decode( wp_remote_retrieve_body( $request ), true ) );
			}

			$access_token_data = json_decode( wp_remote_retrieve_body( $request ), true );

			if ( ! is_array( $access_token_data ) ) {
				throw new BB_SSO_Exception(
					sprintf(
					/* translators: %s: response body */
						esc_html__( 'Unexpected response: %s', 'buddyboss-pro' ),
						wp_remote_retrieve_body( $request )
					)
				);
			}

			/*
			 * Apple sends the name and email in the $_POST the very first time the user authorizes the App!
			 * However the email found here, shouldn't be used as the posted data is not signed by Apple!
			 */
			if ( ! empty( $_POST['user'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$user_data = json_decode( wp_unslash( $_POST['user'] ), true ); // phpcs:ignore
				if ( ! empty( $user_data['name'] ) ) {
					$first_name = ! empty( $user_data['name']['firstName'] ) ? sanitize_text_field( $user_data['name']['firstName'] ) : '';
					if ( $first_name ) {
						$access_token_data['user']['name']['firstName'] = $first_name;
					}

					$last_name = ! empty( $user_data['name']['lastName'] ) ? sanitize_text_field( $user_data['name']['lastName'] ) : '';
					if ( $last_name ) {
						$access_token_data['user']['name']['lastName'] = $last_name;
					}
				}
			}

			$access_token_data['created'] = time();

			$this->access_token_data = $access_token_data;

			return wp_json_encode( $access_token_data );
		}

		return false;
	}

	/**
	 * If the stored state is the same as the state we have received from the remote Provider, it is valid.
	 *
	 * @since 2.6.30
	 *
	 * @return bool
	 */
	protected function validate_state() {
		$this->state = BB_SSO_Persistent::get( $this->provider_id . '_state' );
		if ( false === $this->state ) {
			return false;
		}

		if ( empty( $_POST['state'] ) ) { // phpcs:ignore
			return false;
		}

		if ( $_POST['state'] === $this->state ) { // phpcs:ignore
			return true;
		}

		return false;
	}
}
