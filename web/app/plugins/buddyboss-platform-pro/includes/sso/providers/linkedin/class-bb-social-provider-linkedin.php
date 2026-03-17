<?php
/**
 * Class BB_Social_Provider_Linkedin
 *
 * This class implements an OAuth2 provider for LinkedIn social login and registration.
 * It extends the BB_SSO_Provider_OAuth class to manage LinkedIn authentication,
 * retrieve user data, and handle settings related to LinkedIn integration.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO/Providers/LinkedIn
 */

/**
 * Class BB_Social_Provider_Linkedin
 *
 * @since 2.6.30
 */
class BB_Social_Provider_Linkedin extends BB_SSO_Provider_OAuth {

	/**
	 * The LinkedIn client instance.
	 *
	 * @var BB_Social_Provider_Linkedin_Client
	 */
	protected $client;

	/**
	 * Background color for the linkedin provider.
	 *
	 * @var string Hex color code.
	 */
	protected $color = '#0274b3';

	/**
	 * SVG icon for the linkedin provider.
	 *
	 * @var string
	 */
	protected $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' .
				'<g clip-path="url(#clip0_805_2332)">' .
				'<path fill-rule="evenodd" clip-rule="evenodd" d="M2.66667 24H21.3333C22.8061 24 24 22.8061 24 21.3333V2.66667C24 1.19391 22.8061 0 21.3333 0H2.66667C1.19391 0 0 1.19391 0 2.66667V21.3333C0 22.8061 1.19391 24 2.66667 24Z" fill="#007EBB"/>' .
				'<path fill-rule="evenodd" clip-rule="evenodd" d="M20.6668 20.6667H17.1054V14.6008C17.1054 12.9377 16.4734 12.0082 15.1571 12.0082C13.725 12.0082 12.9769 12.9754 12.9769 14.6008V20.6667H9.54461V9.11118H12.9769V10.6677C12.9769 10.6677 14.0088 8.75814 16.461 8.75814C18.9121 8.75814 20.6668 10.2549 20.6668 13.3505V20.6667ZM5.44995 7.59808C4.28085 7.59808 3.3335 6.64329 3.3335 5.46574C3.3335 4.28819 4.28085 3.3334 5.44995 3.3334C6.61904 3.3334 7.56583 4.28819 7.56583 5.46574C7.56583 6.64329 6.61904 7.59808 5.44995 7.59808ZM3.67768 20.6667H7.25663V9.11118H3.67768V20.6667Z" fill="white"/>' .
				'</g>' .
				'<defs>' .
				'<clipPath id="clip0_805_2332">' .
				'<rect width="24" height="24" fill="white"/>' .
				'</clipPath>' .
				'</defs>' .
			'</svg>';
	/**
	 * Constructs the BB_Social_Provider_Linkedin instance.
	 *
	 * Initializes the provider with required settings and sets up the
	 * client configuration.
	 *
	 * @since 2.6.30
	 */
	public function __construct() {
		$this->id    = 'linkedin';
		$this->label = 'LinkedIn';

		$this->path = __DIR__;

		$this->required_fields = array(
			'client_id'     => 'Client ID',
			'client_secret' => 'Client Secret',
		);

		parent::__construct(
			array(
				'client_id'     => '',
				'client_secret' => '',
			)
		);
	}

	/**
	 * Validates the settings provided by the user.
	 *
	 * @since 2.6.30
	 *
	 * @param array $new_data    The new settings data.
	 * @param array $posted_data The posted data from the settings form.
	 *
	 * @return array The validated settings data, possibly modified.
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
				case 'client_id':
				case 'client_secret':
					$new_data[ $key ] = trim( sanitize_text_field( $value ) );
					if ( $this->settings->get( $key ) !== $new_data[ $key ] ) {
						$new_data['tested'] = 0;
					}

					if ( empty( $new_data[ $key ] ) ) {
						$errors[] = sprintf(
						// translators: %s is the name of the required field.
							__( 'The %1$s entered did not appear to be a valid. Please enter a valid %2$s.', 'buddyboss-pro' ),
							$this->required_fields[ $key ],
							$this->required_fields[ $key ]
						);
					}
					break;
				case 'load_style':
					$new_data[ $key ] = trim( sanitize_text_field( $value ) );
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
	 * Synchronizes the user's profile with the provided LinkedIn data.
	 *
	 * @since 2.6.30
	 *
	 * @param int    $user_id  The ID of the user to synchronize.
	 * @param string $provider The name of the provider.
	 * @param array  $data     The data to synchronize from LinkedIn.
	 */
	public function sync_profile( $user_id, $provider, $data ) {

		if ( $this->get_auth_user_data( 'picture' ) ) {
			$this->update_avatar( $user_id, $this->get_auth_user_data( 'picture' ) );
		}
	}

	/**
	 * Retrieves specific authenticated user data based on the provided key.
	 *
	 * @since 2.6.30
	 *
	 * @param string $key The key for the user data to retrieve.
	 *
	 * @return mixed The user data for the specified key or null if not set.
	 */
	public function get_auth_user_data( $key ) {
		switch ( $key ) {
			case 'id':
				return ! empty( $this->auth_user_data['sub'] ) ? $this->auth_user_data['sub'] : '';
			case 'email':
				return ! empty( $this->auth_user_data['email'] ) ? $this->auth_user_data['email'] : '';
			case 'name':
				return ! empty( $this->auth_user_data['name'] ) ? $this->auth_user_data['name'] : '';
			case 'first_name':
				return ! empty( $this->auth_user_data['given_name'] ) ? $this->auth_user_data['given_name'] : '';
			case 'last_name':
				return ! empty( $this->auth_user_data['family_name'] ) ? $this->auth_user_data['family_name'] : '';
			case 'picture':
				return ! empty( $this->auth_user_data['picture'] ) ? $this->auth_user_data['picture'] : '';
		}

		return parent::get_auth_user_data( $key );
	}

	/**
	 * Gets the URL for the LinkedIn icon.
	 *
	 * @since 2.6.30
	 *
	 * @return string The URL of the LinkedIn icon.
	 */
	public function get_icon() {
		return bb_sso_url() . 'providers/' . $this->id . '/' . $this->id . '.png';
	}

	/**
	 * Deletes any persistent login data for the user.
	 *
	 * This method ensures that all login-related persistent data is cleared,
	 * including client-specific data.
	 *
	 * @since 2.6.30
	 */
	public function delete_login_persistent_data() {
		parent::delete_login_persistent_data();

		if ( null !== $this->client ) {
			$this->client->delete_login_persistent_data();
		}
	}

	/**
	 * Gets the avatar URL for a specified user ID.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id The ID of the user whose avatar to retrieve.
	 *
	 * @return string|false The URL of the user's avatar, or false if not found.
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
		return apply_filters( 'bb_sso_linkedin_login_label', __( 'Continue with LinkedIn', 'buddyboss-pro' ) );
	}

	/**
	 * Social register button label.
	 *
	 * @since 2.6.30
	 *
	 * @return string
	 */
	public function bb_sso_register_label() {
		return apply_filters( 'bb_sso_linkedin_register_label', __( 'Continue with LinkedIn', 'buddyboss-pro' ) );
	}

	/**
	 * Retrieves the current user information from LinkedIn.
	 *
	 * @since 2.6.30
	 *
	 * @throws Exception If the request to LinkedIn fails.
	 * @return array The user information array.
	 */
	protected function get_current_user_info() {
		$user = $this->get_client()->get( '/userinfo' );

		return $user;
	}

	/**
	 * Gets the LinkedIn client for API interactions.
	 *
	 * @since 2.6.30
	 *
	 * @return BB_Social_Provider_Linkedin_Client The LinkedIn client instance.
	 */
	public function get_client() {
		if ( null === $this->client ) {

			require_once __DIR__ . '/class-bb-social-provider-linkedin-client.php';

			$this->client = new BB_Social_Provider_Linkedin_Client( $this->id );

			$this->client->set_client_id( $this->settings->get( 'client_id' ) );
			$this->client->set_client_secret( $this->settings->get( 'client_secret' ) );
			$this->client->set_redirect_uri( $this->get_redirect_uri_for_auth_flow() );
		}

		return $this->client;
	}
}

BB_SSO::add_provider( new BB_Social_Provider_Linkedin() );
