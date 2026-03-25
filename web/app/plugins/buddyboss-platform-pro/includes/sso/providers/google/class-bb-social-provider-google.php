<?php
/**
 * Class BB_Social_Provider_Google
 *
 * Handles the integration with Google as a social provider for SSO (Single Sign-On)
 * in the BuddyBoss platform. This class extends `BB_SSO_Provider_OAuth` and
 * includes methods to manage Google OAuth settings, generate login and register buttons,
 * retrieve Google user information, and sync profile data.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO/Providers/Google
 */

/**
 * Class BB_Social_Provider_Google
 *
 * @since 2.6.30
 */
class BB_Social_Provider_Google extends BB_SSO_Provider_OAuth {

	/**
	 * The Google client instance.
	 *
	 * @var BB_Social_Provider_Google_Client
	 */
	protected $client;

	/**
	 * Background color for the Google button.
	 *
	 * @var string Hex color code.
	 */
	protected $color = '#4285f4';

	/**
	 * Uniform color for the Google button.
	 *
	 * @var string Hex color code.
	 */
	protected $color_uniform = '#dc4e41';

	/**
	 * SVG markup for the Google button.
	 *
	 * @var string SVG markup.
	 */
	protected $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' .
					'<g clip-path="url(#clip0_805_2289)">' .
					'<path d="M23.0087 11.6526C23.0087 10.718 22.9345 10.0359 22.7738 9.32861H12.3269V13.5471H18.459C18.3354 14.5955 17.6678 16.1743 16.1842 17.2352L16.1634 17.3765L19.4665 19.9907L19.6954 20.014C21.7971 18.031 23.0087 15.1133 23.0087 11.6526Z" fill="#4285F4"/>' .
					'<path d="M12.3268 22.7675C15.331 22.7675 17.853 21.757 19.6952 20.014L16.1841 17.2352C15.2445 17.9047 13.9834 18.372 12.3268 18.372C9.38434 18.372 6.88699 16.3891 5.99676 13.6483L5.86627 13.6596L2.43164 16.3751L2.38672 16.5027C4.21645 20.216 7.97488 22.7675 12.3268 22.7675Z" fill="#34A853"/>' .
					'<path d="M5.99683 13.6482C5.76193 12.9409 5.62599 12.183 5.62599 11.4C5.62599 10.6169 5.76193 9.85906 5.98447 9.15176L5.97825 9.00112L2.50057 6.24194L2.38679 6.29724C1.63267 7.83817 1.19995 9.56858 1.19995 11.4C1.19995 13.2314 1.63267 14.9617 2.38679 16.5027L5.99683 13.6482Z" fill="#FBBC05"/>' .
					'<path d="M12.3268 4.42797C14.4161 4.42797 15.8255 5.34999 16.6291 6.1205L19.7694 2.98812C17.8408 1.15671 15.331 0.0325928 12.3268 0.0325928C7.97488 0.0325928 4.21645 2.58393 2.38672 6.29726L5.9844 9.15179C6.88699 6.41098 9.38434 4.42797 12.3268 4.42797Z" fill="#EB4335"/>' .
					'</g>' .
					'<defs>' .
					'<clipPath id="clip0_805_2289">' .
					'<rect x="1.19995" width="21.8087" height="22.8" rx="10" fill="white"/>' .
					'</clipPath>' .
					'</defs>' .
					'</svg>';

	/**
	 * BB_Social_Provider_Google constructor.
	 *
	 * Initializes the Google SSO provider with ID, label, path, and required fields,
	 * and calls the parent constructor with default options.
	 *
	 * @since 2.6.30
	 */
	public function __construct() {
		$this->id    = 'google';
		$this->label = 'Google';

		$this->path = __DIR__;

		$this->required_fields = array(
			'client_id'     => 'Client ID',
			'client_secret' => 'Client Secret',
		);

		parent::__construct(
			array(
				'client_id'                  => '',
				'client_secret'              => '',
				'app_android_client_id'      => '',
				'app_android_test_client_id' => '',
				'app_ios_client_id'          => '',
				'app_ios_test_client_id'     => '',
				'select_account'             => 1,
			)
		);
	}

	/**
	 * Generates the HTML for the default Google social login button.
	 *
	 * @since 2.6.30
	 *
	 * @return string HTML markup for the Google login button based on selected skin.
	 */
	public function get_raw_default_button() {

		return '<div class="bb-sso-button bb-sso-button-default bb-sso-button-' . $this->id . '"><div class="bb-sso-button-svg-container">' . $this->svg . '</div><div class="bb-sso-button-label-container">{{label}}</div></div>';
	}

	/**
	 * Generates the HTML for the icon-only Google social login button.
	 *
	 * @since 2.6.30
	 *
	 * @return string HTML markup for the icon-only Google button based on selected skin.
	 */
	public function get_raw_icon_button() {

		return '<div class="bb-sso-button bb-sso-button-icon bb-sso-button-' . $this->id . '" style="background-color:' . $this->color . ';"><div class="bb-sso-button-svg-container">' . $this->svg . '</div></div>';
	}

	/**
	 * Validates the Google OAuth settings.
	 *
	 * Validates and sanitizes posted data for Google settings and adds errors if invalid.
	 *
	 * @since 2.6.30
	 *
	 * @param array $new_data    The validated settings data.
	 * @param array $posted_data The data posted for validation.
	 *
	 * @return array The validated settings data, potentially modified based on validation.
	 */
	public function validate_settings( $new_data, $posted_data ) {
		$new_data = parent::validate_settings( $new_data, $posted_data );
		$errors   = array(); // To collect multiple errors.

		foreach ( $posted_data as $key => $value ) {

			switch ( $key ) {
				case 'tested':
					if (
						1 === (int) $posted_data[ $key ] &&
						(
							! isset( $new_data['tested'] ) ||
							0 !== (int) $new_data['tested']
						)
					) {
						$new_data['tested'] = 1;
					} else {
						$new_data['tested'] = 0;
					}
					break;
				case 'client_id':
				case 'client_secret':
					$new_data[ $key ] = trim( sanitize_text_field( $value ) );
					if ( $this->settings->get( $key ) !== $new_data[ $key ] ) {
						$new_data['tested'] = 0;
					}

					if ( empty( $new_data[ $key ] ) ) {
						$errors[] = sprintf(
						// translators: %s is the required field name.
							__( 'The %1$s entered did not appear to be a valid. Please enter a valid %2$s.', 'buddyboss-pro' ),
							$this->required_fields[ $key ],
							$this->required_fields[ $key ]
						);
					}
					break;
				case 'app_android_client_id':
				case 'app_android_test_client_id':
				case 'app_ios_client_id':
				case 'app_ios_test_client_id':
					$new_data[ $key ] = trim( sanitize_text_field( $value ) );
					break;
				case 'select_account':
					$new_data[ $key ] = $value ? 1 : 0;
					break;
			}
		}

		if ( ! empty( $errors ) ) {
			wp_send_json_error( $errors );
		}

		return $new_data;
	}

	/**
	 * Retrieves the authenticated user's data.
	 *
	 * @since 2.6.30
	 *
	 * @return array The authenticated user's data array.
	 */
	public function get_me() {
		return $this->auth_user_data;
	}

	/**
	 * Retrieves people data for the authenticated user from Google People API.
	 *
	 * @since 2.6.30
	 *
	 * @throws Exception If the Google People API request fails.
	 * @return array The retrieved data for people fields or an empty array if no fields are specified.
	 */
	public function getMyPeople() {
		$extra_people_fields = apply_filters( 'bb_sso_google_sync_node_fields', array(), 'people' );

		if ( ! empty( $extra_people_fields ) ) {
			return $this->get_client()->get( 'people/me?personFields=' . implode( ',', $extra_people_fields ), array(), 'https://people.googleapis.com/v1/' );
		}

		return $extra_people_fields;
	}

	/**
	 * Retrieves the Google client instance for OAuth operations.
	 *
	 * Initializes the client if not already created and sets client ID, secret, and redirect URI.
	 *
	 * @since 2.6.30
	 *
	 * @return BB_Social_Provider_Google_Client The Google client instance.
	 */
	public function get_client() {
		if ( null === $this->client ) {

			require_once __DIR__ . '/class-bb-social-provider-google-client.php';

			$this->client = new BB_Social_Provider_Google_Client( $this->id );

			$this->client->set_client_id( $this->settings->get( 'client_id' ) );
			$this->client->set_client_secret( $this->settings->get( 'client_secret' ) );
			$this->client->set_redirect_uri( $this->get_redirect_uri_for_auth_flow() );

			if ( ! $this->settings->get( 'select_account' ) ) {
				$this->client->setPrompt( '' );
			}
		}

		return $this->client;
	}

	/**
	 * Synchronizes the Google profile data with the BuddyBoss user profile.
	 *
	 * @since 2.6.30
	 *
	 * @param int    $user_id  The BuddyBoss user ID.
	 * @param string $provider The provider identifier (Google).
	 * @param array  $data     The Google user data.
	 */
	public function sync_profile( $user_id, $provider, $data ) {

		$this->update_avatar( $user_id, $this->get_auth_user_data( 'picture' ) );
	}

	/**
	 * Retrieves specific data for the authenticated user based on the provided key.
	 *
	 * @since 2.6.30
	 *
	 * @param string $key The key for the data to retrieve (e.g., 'id', 'email', 'name', 'first_name', 'last_name',
	 *                    'picture').
	 *
	 * @return string The requested data, or an empty string if the key does not exist.
	 */
	public function get_auth_user_data( $key ) {

		switch ( $key ) {
			case 'id':
				return ! empty( $this->auth_user_data['id'] ) ? $this->auth_user_data['id'] : '';
			case 'email':
				return ! empty( $this->auth_user_data['email'] ) ? $this->auth_user_data['email'] : '';
			case 'name':
				return ! empty( $this->auth_user_data['name'] ) ? $this->auth_user_data['name'] : '';
			case 'first_name':
				return ! empty( $this->auth_user_data['given_name'] ) ? $this->auth_user_data['given_name'] : '';
			case 'last_name':
				return ! empty( $this->auth_user_data['family_name'] ) ? $this->auth_user_data['family_name'] : '';
			case 'picture':
				$profile_image = ! empty( $this->auth_user_data['picture'] ) ? $this->auth_user_data['picture'] : '';
				if ( ! empty( $profile_image ) ) { // Fetch a medium image.
					$profile_image = str_replace( '=s96-c', '=s360-c', $profile_image );
				}

				return $profile_image;
		}

		return parent::get_auth_user_data( $key );
	}

	/**
	 * Deletes persistent login data for the current client and parent class.
	 *
	 * Removes any stored login session data, clearing it from both the current client
	 * instance and the parent class, if applicable.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	public function delete_login_persistent_data() {
		parent::delete_login_persistent_data();

		if ( null !== $this->client ) {
			$this->client->delete_login_persistent_data();
		}
	}

	/**
	 * Retrieves the avatar URL for the specified user, if connected.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id The ID of the user whose avatar is being retrieved.
	 *
	 * @return string|false The URL of the user's profile picture, or false if the user is not connected
	 *                      or if no profile picture is available.
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
	 * Social login button label.
	 *
	 * @since 2.6.30
	 *
	 * @return string
	 */
	public function bb_sso_login_label() {
		return apply_filters( 'bb_sso_google_login_label', __( 'Continue with Google', 'buddyboss-pro' ) );
	}

	/**
	 * Social register button label.
	 *
	 * @since 2.6.30
	 *
	 * @return string
	 */
	public function bb_sso_register_label() {
		return apply_filters( 'bb_sso_google_register_label', __( 'Continue with Google', 'buddyboss-pro' ) );
	}

	/**
	 * Retrieves the current Google user's information.
	 *
	 * @since 2.6.30
	 *
	 * @throws Exception If there is an error in retrieving user information.
	 * @return array The user info fields retrieved from the Google API.
	 */
	protected function get_current_user_info() {
		$fields          = array(
			'id',
			'name',
			'email',
			'family_name',
			'given_name',
			'picture',
		);
		$extra_me_fields = apply_filters( 'bb_sso_google_sync_node_fields', array(), 'me' );

		return $this->get_client()->get( 'userinfo?fields=' . implode( ',', array_merge( $fields, $extra_me_fields ) ) );
	}
}

BB_SSO::add_provider( new BB_Social_Provider_Google() );
