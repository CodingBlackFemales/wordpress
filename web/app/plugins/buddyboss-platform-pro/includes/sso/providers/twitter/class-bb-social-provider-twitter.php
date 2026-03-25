<?php
/**
 * Class BB_Social_Provider_Twitter
 *
 * Provides integration with Twitter for Single Sign-On (SSO) functionality.
 * This class handles both API v2 of the Twitter API, allowing
 * for user authentication and data retrieval from Twitter accounts.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO/Providers/Twitter
 */

use BBSSO\Persistent\BB_SSO_Persistent;

/**
 * Class BB_Social_Provider_Twitter
 *
 * @since 2.6.30
 */
class BB_Social_Provider_Twitter extends BB_SSO_Provider_OAuth {

	/**
	 * Client object for Twitter API requests.
	 *
	 * @since 2.6.30
	 *
	 * @var BB_Social_Provider_Twitter_Client
	 */
	protected $client;

	/**
	 * The color of the Twitter button.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $color = '#000000';

	/**
	 * The SVG icon for the Twitter button.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.7508 3H20.8175L14.1175 10.6583L22 21.0775H15.8283L10.995 14.7575L5.46333 21.0775H2.395L9.56167 12.8858L2 3.00083H8.32833L12.6975 8.7775L17.7508 3ZM16.675 19.2425H18.3742L7.405 4.73917H5.58167L16.675 19.2425Z" fill="black"/></svg>';

	/**
	 * The required fields for the Twitter API v2.
	 *
	 * @since 2.6.30
	 *
	 * @var array
	 */
	protected $required_fields = array(
		'api_version'   => 'API Version',
		'client_id'     => 'Client ID',
		'client_secret' => 'Client Secret',
	);

	/**
	 * BB_Social_Provider_Twitter constructor.
	 *
	 * @since 2.6.30
	 */
	public function __construct() {
		$this->id    = 'twitter';
		$this->label = 'X';

		$this->path = __DIR__;

		parent::__construct(
			array(
				'api_version'   => '2.0',
				'client_id'     => '',
				'client_secret' => '',
			)
		);

		add_filter( 'bb_sso_register_signup_fields_not_found', array( $this, 'bb_sso_register_signup_fields_not_found' ) );
	}

	/**
	 * Gets the raw HTML for the default button with appropriate styling based on the selected skin.
	 *
	 * @since 2.6.30
	 *
	 * @return string The raw HTML for the default button.
	 */
	public function get_raw_default_button() {

		return '<div class="bb-sso-button bb-sso-button-default bb-sso-button-' . $this->id . '"><div class="bb-sso-button-svg-container">' . $this->svg . '</div><div class="bb-sso-button-label-container">{{label}}</div></div>';
	}

	/**
	 * Gets the raw HTML for the icon button with appropriate styling based on the selected skin.
	 *
	 * @since 2.6.30
	 *
	 * @return string The raw HTML for the icon button.
	 */
	public function get_raw_icon_button() {

		return '<div class="bb-sso-button bb-sso-button-icon bb-sso-button-' . $this->id . '" style="background-color:' . $this->color . ';"><div class="bb-sso-button-svg-container">' . $this->svg . '</div></div>';
	}

	/**
	 * Validates the settings provided during the configuration process.
	 *
	 * @since 2.6.30
	 *
	 * @param array $new_data    The new settings data to validate.
	 * @param array $posted_data The posted data from the settings form.
	 *
	 * @return array The validated settings data.
	 */
	public function validate_settings( $new_data, $posted_data ) {
		$new_data = parent::validate_settings( $new_data, $posted_data );
		$errors   = array(); // To collect multiple errors.

		foreach ( $posted_data as $key => $value ) {

			switch ( $key ) {
				case 'tested':
					if ( 1 === (int) $posted_data[ $key ] && ( ! isset( $new_data['tested'] ) || 0 !== (int) $new_data['tested'] ) ) {
						$new_data['tested'] = 1;
					} else {
						$new_data['tested'] = 0;
					}
					break;
				case 'api_version':
				case 'client_id':
				case 'client_secret':
					$new_data[ $key ] = trim( sanitize_text_field( $value ) );
					if ( $this->settings->get( $key ) !== $new_data[ $key ] ) {
						$new_data['tested'] = 0;
					}

					if ( isset( $this->required_fields[ $key ] ) && empty( $new_data[ $key ] ) ) {
						$errors[] = sprintf(
						// translators: %1$s is the required field name.
							__( 'The %1$s entered did not appear to be a valid. Please enter a valid %2$s.', 'buddyboss-pro' ),
							$this->required_fields[ $key ],
							$this->required_fields[ $key ]
						);
					}
					break;
			}
		}

		if ( ! empty( $errors ) ) {
			wp_send_json_error( $errors );
		}

		return $new_data;
	}

	/**
	 * Gets the authenticated user's data.
	 *
	 * @since 2.6.30
	 *
	 * @return array The authenticated user's data.
	 */
	public function get_me() {
		return $this->auth_user_data;
	}

	/**
	 * Syncs the user's profile data with the provided information.
	 *
	 * @since 2.6.30
	 *
	 * @param int    $user_id  The ID of the user to sync the profile for.
	 * @param string $provider The social provider being used.
	 * @param array  $data     The data to sync with the user's profile.
	 */
	public function sync_profile( $user_id, $provider, $data ) {

		if ( $this->get_auth_user_data( 'picture' ) ) {
			$this->update_avatar( $user_id, $this->get_auth_user_data( 'picture' ) );
		}
	}

	/**
	 * Gets the authenticated user's data for API version 2.
	 *
	 * @since 2.6.30
	 *
	 * @param string $key The key of the user data to retrieve (e.g., 'id', 'email', 'name').
	 *
	 * @return mixed|string The requested user data.
	 */
	public function get_auth_user_data( $key ) {
		switch ( $key ) {
			case 'id':
				return ! empty( $this->auth_user_data['id'] ) ? $this->auth_user_data['id'] : '';
			case 'email':
				return '';
			case 'name':
				return ! empty( $this->auth_user_data['name'] ) ? $this->auth_user_data['name'] : '';
			case 'username':
				return ! empty( $this->auth_user_data['username'] ) ? $this->auth_user_data['username'] : '';
			case 'first_name':
				$auth_name = ! empty( $this->auth_user_data['name'] ) ? $this->auth_user_data['name'] : '';
				if ( ! empty( $auth_name ) ) {
					$name = explode( ' ', $auth_name, 2 );

					return isset( $name[0] ) ? $name[0] : '';
				}
			case 'last_name':
				$auth_name = ! empty( $this->auth_user_data['name'] ) ? $this->auth_user_data['name'] : '';
				if ( ! empty( $auth_name ) ) {
					$name = explode( ' ', $auth_name, 2 );

					return isset( $name[1] ) ? $name[1] : '';
				}
			case 'picture':
				$profile_image = ! empty( $this->auth_user_data['profile_image_url'] ) ? $this->auth_user_data['profile_image_url'] : '';

				return ! empty( $profile_image ) ? str_replace( '_normal.', '.', $profile_image ) : '';
		}

		return parent::get_auth_user_data( $key );
	}

	/**
	 * Deletes persistent login data related to the current user.
	 *
	 * @since 2.6.30
	 */
	public function delete_login_persistent_data() {
		parent::delete_login_persistent_data();

		if ( null !== $this->client ) {
			$this->client->delete_login_persistent_data();
		}

		// Clear code verifier after successful authentication.
		\BBSSO\Persistent\BB_SSO_Persistent::delete( $this->id . '_code_verifier' );

		// Clear session cookie after successful authentication.
		$storage      = new \BBSSO\Persistent\Storage\BB_SSO_Storage_Session();
		$session_name = $storage->get_session_name();

		if ( isset( $_COOKIE[ $session_name ] ) ) {
			setcookie( $session_name, '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
			unset( $_COOKIE[ $session_name ] );
		}
	}

	/**
	 * Retrieves the avatar URL for a specified user ID.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id The ID of the user whose avatar to retrieve.
	 *
	 * @return string|bool The avatar URL if it exists, or false if not connected or no picture is found.
	 */
	public function get_avatar( $user_id ) {

		if ( ! $this->is_user_connected( $user_id ) ) {
			return false;
		}

		$picture = $this->get_user_data( $user_id, 'profile_picture' );
		if ( ! $picture ) {
			return false;
		}

		return $picture;
	}

	/**
	 * Deletes persistent token data associated with the current user.
	 *
	 * @since 2.6.30
	 */
	public function delete_token_persistent_data() {
		parent::delete_token_persistent_data();
		BB_SSO_Persistent::delete( $this->id . '_code_verifier' );
	}

	/**
	 * Social login button label.
	 *
	 * @since 2.6.30
	 *
	 * @return string
	 */
	public function bb_sso_login_label() {
		return apply_filters( 'bb_sso_twitter_login_label', __( 'Continue with X', 'buddyboss-pro' ) );
	}

	/**
	 * Social register button label.
	 *
	 * @since 2.6.30
	 *
	 * @return string
	 */
	public function bb_sso_register_label() {
		return apply_filters( 'bb_sso_twitter_register_label', __( 'Continue with X', 'buddyboss-pro' ) );
	}

	/**
	 * Gets the current user information from the social provider using API version 2.
	 *
	 * @since 2.6.30
	 *
	 * @return array The current user information.
	 * @throws BB_SSO_Exception If an error occurs while retrieving user information.
	 */
	protected function get_current_user_info() {
		$fields          = array(
			'id',
			'name',
			'profile_image_url',
		);
		$extra_me_fields = apply_filters( 'bb_sso_twitter_sync_node_fields', array(), 'mev2' );

		$response = $this->get_client()->get( 'users/me?user.fields=' . implode( ',', array_merge( $fields, $extra_me_fields ) ) );

		if ( ! empty( $response['data'] ) ) {
			return $response['data'];
		}

		$retval = array(
			'id'                => '',
			'name'              => '',
			'profile_image_url' => '',
		);

		$retval['message'] = ! empty( $response['title'] ) ? $response['title'] : $response['message'];
		$retval['code']    = $response['status'];

		return $retval;
	}

	/**
	 * Gets the client instance for the social provider, creating it if it does not exist.
	 *
	 * @since 2.6.30
	 *
	 * @return BB_Social_Provider_Twitter_Client The client instance for the social provider.
	 */
	public function get_client() {
		if ( null === $this->client ) {

			require_once __DIR__ . '/class-bb-social-provider-twitter-client.php';
			$this->client = new BB_Social_Provider_Twitter_Client( $this->id );

			$this->client->set_client_id( $this->settings->get( 'client_id' ) );
			$this->client->set_client_secret( $this->settings->get( 'client_secret' ) );

			$this->client->set_redirect_uri( $this->get_redirect_uri_for_auth_flow() );
		}

		return $this->client;
	}

	/**
	 * Adds a message to the registration form if the Twitter provider has error.
	 *
	 * @since 2.6.90
	 *
	 * @param string $message The existing message to modify.
	 *
	 * @return string The modified message.
	 */
	public function bb_sso_register_signup_fields_not_found( $message ) {
		if ( 'twitter' === $this->id && isset( \BBSSO\BB_SSO_Notices::$notices['info'] ) ) {
			$message = sprintf(
				/** Translators: %1$s: Error message */
				'<div class="bb-sso-reg-error"><p>%1$s </p></div>',
				current( \BBSSO\BB_SSO_Notices::$notices['info'] )
			);
		}

		return $message;
	}

	/**
	 * Connects the user to the social provider.
	 *
	 * @since 2.6.90
	 */
	public function connect() {
		// Clear any existing session data for Twitter.
		\BBSSO\Persistent\BB_SSO_Persistent::delete( $this->id . '_auth_user_data' );

		// Continue with normal authentication.
		parent::connect();
	}
}

BB_SSO::add_provider( new BB_Social_Provider_Twitter() );
