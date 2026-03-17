<?php
/**
 * Class BB_Social_Provider_Facebook
 *
 * This class represents a Facebook social provider for OAuth integration.
 * It extends the BB_SSO_Provider_OAuth class to provide support for
 * Facebook-specific OAuth authentication, including settings and UI elements
 * for the login button styling based on different skins.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO/Providers/Facebook
 */

/**
 * Class BB_Social_Provider_Facebook
 *
 * @since 2.6.30
 */
class BB_Social_Provider_Facebook extends BB_SSO_Provider_OAuth {

	/**
	 * Unique database identifier for the Facebook provider.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $db_id = 'fb';

	/**
	 * Client instance for handling Facebook API requests.
	 *
	 * @since 2.6.30
	 *
	 * @var BB_Social_Provider_Facebook_Client
	 */
	protected $client;

	/**
	 * Hex color code for the Facebook button background color.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $color = '#fff';

	/**
	 * SVG markup for the Facebook logo with blue fill.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' .
				'<g clip-path="url(#clip0_805_2303)">' .
					'<circle cx="12" cy="12" r="12" fill="white"/>' .
					'<path d="M16.6875 15.4776H13.875V23.8526C16.8577 23.3807 19.5529 21.8017 21.4227 19.4304C23.2926 17.0592 24.1997 14.0701 23.9631 11.0596C23.7264 8.04907 22.3634 5.23845 20.146 3.1885C17.9286 1.13856 15.0198 0 12 0C8.9802 0 6.07141 1.13856 3.85399 3.1885C1.63658 5.23845 0.273576 8.04907 0.0369262 11.0596C-0.199724 14.0701 0.70738 17.0592 2.57725 19.4304C4.44713 21.8017 7.14229 23.3807 10.125 23.8526V15.4776H7.06251V11.9776H10.125V9.16509C10.2917 5.45677 12.7083 4.0401 17.375 4.9151V7.8526H15.75C14.5417 7.89426 13.9167 8.51926 13.875 9.72759V11.9776H17.2187" fill="#1877F2"/>' .
				'</g>' .
				'<defs>' .
					'<clipPath id="clip0_805_2303">' .
						'<rect width="24" height="24" fill="white"/>' .
					'</clipPath>' .
				'</defs>' .
			'</svg>';

	/**
	 * Width of the popup window for Facebook login.
	 *
	 * @since 2.6.30
	 *
	 * @var int
	 */
	protected $popup_width = 600;

	/**
	 * Height of the popup window for Facebook login.
	 *
	 * @since 2.6.30
	 *
	 * @var int
	 */
	protected $popup_height = 679;

	/**
	 * Initializes the Facebook provider settings and required fields.
	 *
	 * @since 2.6.30
	 */
	public function __construct() {
		$this->id    = 'facebook';
		$this->label = 'Facebook';

		$this->path = __DIR__;

		$this->required_fields = array(
			'appid'  => 'App ID',
			'secret' => 'App Secret',
		);

		add_filter(
			'bb_sso_finalize_settings_' . $this->option_key,
			array(
				$this,
				'finalize_settings',
			)
		);

		parent::__construct(
			array(
				'appid'              => '',
				'secret'             => '',
				'profile_image_size' => 'default',
			)
		);
	}

	/**
	 * Retrieves the default button with Facebook styling.
	 *
	 * @since 2.6.30
	 *
	 * @return string HTML markup for the default Facebook button.
	 */
	public function get_raw_default_button() {

		return '<div class="bb-sso-button bb-sso-button-default bb-sso-button-' . $this->id . '"><div class="bb-sso-button-svg-container">' . $this->svg . '</div><div class="bb-sso-button-label-container">{{label}}</div></div>';
	}

	/**
	 * Retrieves an icon-only button with Facebook styling.
	 *
	 * @since 2.6.30
	 *
	 * @return string HTML markup for the icon-only Facebook button.
	 */
	public function get_raw_icon_button() {

		return '<div class="bb-sso-button bb-sso-button-icon bb-sso-button-' . $this->id . '" style="background-color:' . $this->color . ';"><div class="bb-sso-button-svg-container">' . $this->svg . '</div></div>';
	}

	/**
	 * Finalizes settings for the Facebook provider, applying constants if defined.
	 *
	 * @since 2.6.30
	 *
	 * @param array $settings Current settings array.
	 *
	 * @return array Modified settings array.
	 */
	public function finalize_settings( $settings ) {

		if ( defined( 'BB_FB_APP_ID' ) ) {
			$settings['appid'] = BB_FB_APP_ID;
		}
		if ( defined( 'BB_FB_APP_SECRET' ) ) {
			$settings['secret'] = BB_FB_APP_SECRET;
		}

		return $settings;
	}

	/**
	 * Validates and sanitizes settings data submitted for the Facebook provider.
	 *
	 * @since 2.6.30
	 *
	 * @param array $new_data    New settings data.
	 * @param array $posted_data Data posted via settings form.
	 *
	 * @return array Validated and sanitized settings data.
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
				case 'appid':
				case 'secret':
					$new_data[ $key ] = trim( sanitize_text_field( $value ) );
					if ( $this->settings->get( $key ) !== $new_data[ $key ] ) {
						$new_data['tested'] = 0;
					}

					if ( empty( $new_data[ $key ] ) ) {
						$errors[] = sprintf(
						// translators: %s: Field name.
							__( 'The %1$s entered did not appear to be a valid. Please enter a valid %2$s.', 'buddyboss-pro' ),
							$this->required_fields[ $key ],
							$this->required_fields[ $key ]
						);
					}
					break;
				case 'profile_image_size':
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
	 * Retrieves the Facebook user's profile data.
	 *
	 * @since 2.6.30
	 *
	 * @return array User profile data.
	 */
	public function get_me() {
		return $this->auth_user_data;
	}

	/**
	 * Synchronizes the user's Facebook profile picture with their account.
	 *
	 * @since 2.6.30
	 *
	 * @param int    $user_id  WordPress user ID.
	 * @param string $provider OAuth provider.
	 * @param array  $data     User data to sync.
	 */
	public function sync_profile( $user_id, $provider, $data ) {

		if ( $this->get_auth_user_data( 'picture' ) ) {
			$this->update_avatar( $user_id, $this->get_auth_user_data( 'picture' ) );
		}
	}

	/**
	 * Gets user profile information, using a specified key for selective data.
	 *
	 * @since 2.6.30
	 *
	 * @param string $key Profile data key.
	 *
	 * @return mixed Requested user data or parent class data.
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
				return ! empty( $this->auth_user_data['first_name'] ) ? $this->auth_user_data['first_name'] : ( ! empty( $this->auth_user_data['given_name'] ) ? $this->auth_user_data['given_name'] : '' );
			case 'last_name':
				return ! empty( $this->auth_user_data['last_name'] ) ? $this->auth_user_data['last_name'] : ( ! empty( $this->auth_user_data['family_name'] ) ? $this->auth_user_data['family_name'] : '' );
			case 'picture':
				$profile_picture = ! empty( $this->auth_user_data['picture'] ) ? $this->auth_user_data['picture'] : '';
				if ( ! empty( $profile_picture ) && ! empty( $profile_picture['data'] ) ) {
					if ( isset( $profile_picture['data']['is_silhouette'] ) && ! $profile_picture['data']['is_silhouette'] ) {
						return $profile_picture['data']['url'];
					}
				} elseif ( ! empty( $profile_picture ) && filter_var( $profile_picture, FILTER_VALIDATE_URL ) ) {
					return $profile_picture;
				}

				return '';
		}

		return parent::get_auth_user_data( $key );
	}

	/**
	 * Deletes persistent login data.
	 *
	 * This method clears any persistent login data, including data stored by the client.
	 * It first calls the parent method to perform the base deletion, then checks if a client
	 * instance exists and calls its `delete_login_persistent_data` method if applicable.
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
	 * Social login button label.
	 *
	 * @since 2.6.30
	 *
	 * @return string
	 */
	public function bb_sso_login_label() {
		return apply_filters( 'bb_sso_facebook_login_label', __( 'Continue with Facebook', 'buddyboss-pro' ) );
	}

	/**
	 * Social register button label.
	 *
	 * @since 2.6.30
	 *
	 * @return string
	 */
	public function bb_sso_register_label() {
		return apply_filters( 'bb_sso_facebook_register_label', __( 'Continue with Facebook', 'buddyboss-pro' ) );
	}

	/**
	 * Requests a long-lived access token from Facebook.
	 *
	 * @since 2.6.30
	 *
	 * @param array $access_token_data Existing access token data.
	 *
	 * @return string Updated or original access token.
	 * @throws Exception If an error occurs during the request.
	 */
	protected function request_long_lived_token( $access_token_data ) {
		$client = $this->get_client();
		if ( ! $client->is_access_token_long_lived() ) {

			return $client->request_long_lived_access_token();
		}

		return $access_token_data;
	}

	/**
	 * Retrieves or initializes the Facebook client for API requests.
	 *
	 * @since 2.6.30
	 *
	 * @return BB_Social_Provider_Facebook_Client Facebook API client instance.
	 */
	public function get_client() {
		if ( null === $this->client ) {

			require_once __DIR__ . '/class-bb-social-provider-facebook-client.php';

			$this->client = new BB_Social_Provider_Facebook_Client( $this->id, $this->is_test() );

			$this->client->set_client_id( $this->settings->get( 'appid' ) );
			$this->client->set_client_secret( $this->settings->get( 'secret' ) );
			$this->client->set_redirect_uri( $this->get_redirect_uri_for_auth_flow() );
		}

		return $this->client;
	}

	/**
	 * Retrieves the authenticated Facebook user's profile data.
	 *
	 * @since 2.6.30
	 *
	 * @return array User profile information.
	 * @throws Exception If an error occurs during the request.
	 */
	protected function get_current_user_info() {
		$profile_image_size = $this->settings->get( 'profile_image_size' );

		'default' !== $profile_image_size ? $picture_size = 'picture.width(' . $profile_image_size . ')' : $picture_size = 'picture.type(large)';

		$fields       = array(
			'id',
			'name',
			'email',
			'first_name',
			'last_name',
			$picture_size,
		);
		$extra_fields = apply_filters( 'bb_sso_facebook_sync_node_fields', array(), 'me' );

		return $this->get_client()->get( '/me?fields=' . implode( ',', array_merge( $fields, $extra_fields ) ) );
	}

	/**
	 * Saves user-specific data such as the access token.
	 *
	 * @since 2.6.30
	 *
	 * @param int    $user_id WordPress' user ID.
	 * @param string $key     Data key to save.
	 * @param mixed  $data    Data value to save.
	 */
	protected function save_user_data( $user_id, $key, $data ) {
		switch ( $key ) {
			case 'access_token':
				update_user_meta( $user_id, 'fb_user_access_token', $data );
				break;
			default:
				parent::save_user_data( $user_id, $key, $data );
				break;
		}
	}

	/**
	 * Retrieves specific user data based on a key.
	 *
	 * This method checks for specific user data using the provided key.
	 * If the key is 'access_token', it retrieves the Facebook user access token
	 * from user meta. For other keys, it defers to the parent implementation.
	 *
	 * @since 2.6.30
	 *
	 * @param int    $user_id The ID of the user to retrieve data for.
	 * @param string $key     The key of the data to retrieve. Currently only supports 'access_token'.
	 *
	 * @return mixed The requested user data, or data from the parent class if not handled here.
	 */
	protected function get_user_data( $user_id, $key ) {
		switch ( $key ) {
			case 'access_token':
				return get_user_meta( $user_id, 'fb_user_access_token', true );
		}

		return parent::get_user_data( $user_id, $key );
	}

	/**
	 * Setup the user data for the current authenticated user.
	 *
	 * @param array $user_data Array of user data.
	 *
	 * @return void
	 */
	public function set_auth_user_data( $user_data ) {
		if ( ! empty( $user_data ) ) {
			$this->auth_user_data = $user_data;
		}
	}
}

BB_SSO::add_provider( new BB_Social_Provider_Facebook() );
