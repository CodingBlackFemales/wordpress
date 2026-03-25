<?php
/**
 * Class BB_Social_Provider_Apple
 *
 * This class implements an OAuth provider for Apple social login integration
 * in the BuddyBoss platform. It extends the BB_SSO_Provider_OAuth class to
 * handle authentication using Apple's OAuth service.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO/Providers/Apple
 */

use BBSSO\BB_SSO_Notices;

/**
 * Class BB_Social_Provider_Apple
 *
 * @since 2.6.30
 */
class BB_Social_Provider_Apple extends BB_SSO_Provider_OAuth {

	/**
	 * Client object for Apple.
	 *
	 * @since 2.6.30
	 *
	 * @var BB_Social_Provider_Apple_Client
	 */
	protected $client;

	/**
	 * Color for the Apple button.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $color = '#000000';

	/**
	 * Field names for Apple.
	 *
	 * @since 2.6.30
	 *
	 * @var array
	 */
	protected $field_names;

	/**
	 * SVG for the Apple button.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18.9802 12.6409C18.9697 10.6809 19.8443 9.2016 21.6145 8.11215C20.624 6.67557 19.1277 5.88518 17.152 5.73031C15.2817 5.58078 13.2374 6.83578 12.4893 6.83578C11.699 6.83578 9.8866 5.78371 8.46407 5.78371C5.52419 5.83178 2.3999 8.16022 2.3999 12.8972C2.3999 14.2964 2.6528 15.7419 3.15858 17.2336C3.83296 19.1936 6.26706 24 8.80653 23.9199C10.1342 23.8879 11.072 22.964 12.8001 22.964C14.4756 22.964 15.3449 23.9199 16.8254 23.9199C19.3859 23.8825 21.5882 19.514 22.2309 17.5487C18.7958 15.9092 18.9802 12.7423 18.9802 12.6409ZM15.9982 3.87183C17.4365 2.14152 17.3048 0.566088 17.2627 0C15.9929 0.0747664 14.523 0.875835 13.6853 1.86382C12.7633 2.92123 12.2206 4.22964 12.3365 5.70361C13.7116 5.81041 14.9655 5.09479 15.9982 3.87183Z" fill="black"/></svg>';

	/**
	 * Class constructor for initializing the Apple social provider.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	public function __construct() {
		$this->id    = 'apple';
		$this->label = 'Apple';

		$this->path = __DIR__;

		$this->required_fields = array(
			'client_id'     => 'Client ID',
			'client_secret' => 'Client Secret',
		);

		$this->field_names = array(
			'private_key_id'     => 'Private Key ID',
			'private_key'        => 'Private Key',
			'team_identifier'    => 'Team Identifier',
			'service_identifier' => 'Service Identifier',
		);

		parent::__construct(
			array(
				'client_id'                 => '',
				'client_secret'             => '',
				'private_key_id'            => '',
				'private_key'               => '',
				'team_identifier'           => '',
				'service_identifier'        => '',
				'expiration_timestamp'      => 0,
				'has_credentials'           => 0,
				'show_client_secret_notice' => 0,
			)
		);

		add_action( 'admin_notices', array( $this, 'show_admin_apple_client_secret_notice' ) );

		add_action( 'bbssopro_weekly_cron', array( $this, 'weekly_apple_client_secret_check' ) );
	}

	/**
	 * Generates the HTML for a default button with dynamic skin and color.
	 *
	 * This method constructs a button element based on the current skin setting.
	 * It returns the HTML string, which includes the SVG icon and a label placeholder.
	 *
	 * @since 2.6.30
	 *
	 * @return string The HTML markup for the default button.
	 */
	public function get_raw_default_button() {

		return '<div class="bb-sso-button bb-sso-button-default bb-sso-button-' . $this->id . '"><div class="bb-sso-button-svg-container">' . $this->svg . '</div><div class="bb-sso-button-label-container">{{label}}</div></div>';
	}

	/**
	 * Generates the raw HTML for an icon button based on the current skin setting.
	 *
	 * @since 2.6.30
	 *
	 * @return string The HTML markup for the icon button.
	 */
	public function get_raw_icon_button() {

		return '<div class="bb-sso-button bb-sso-button-icon bb-sso-button-' . $this->id . '" style="background-color:' . $this->color . ';"><div class="bb-sso-button-svg-container">' . $this->svg . '</div></div>';
	}

	/**
	 * Validates the settings based on the posted data and returns sanitized data.
	 *
	 * @since 2.6.30
	 *
	 * @param array $new_data    The new data to validate.
	 * @param array $posted_data The posted data from the form submission.
	 *
	 * @return array The validated and possibly sanitized data.
	 */
	public function validate_settings( $new_data, $posted_data ) {
		$new_data = parent::validate_settings( $new_data, $posted_data );
		$errors   = array(); // To collect multiple errors.

		foreach ( $posted_data as $key => $value ) {

			switch ( $key ) {
				case 'private_key_id':
				case 'team_identifier':
				case 'service_identifier':
					$sanitized_value = trim( sanitize_text_field( $value ) );
					if ( empty( $sanitized_value ) ) {
						$errors[] = sprintf(
						// translators: %1$s is the field name, %2$s is the field name.
							__( 'The %1$s entered did not appear to be a valid. Please enter a valid %2$s.', 'buddyboss-pro' ),
							$this->field_names[ $key ],
							$this->field_names[ $key ]
						);
					} else {
						$new_data[ $key ] = $sanitized_value;
					}
					break;
				case 'private_key':
					if ( empty( $value ) ) {
						$errors[] = sprintf(
						// translators: %1$s is the field name, %2$s is the field name.
							__( 'The %1$s entered did not appear to be a valid. Please enter a valid %2$s.', 'buddyboss-pro' ),
							$this->field_names[ $key ],
							$this->field_names[ $key ]
						);
					} else {
						$new_data[ $key ] = $value;
					}
					break;
				case 'has_credentials':
					if ( 0 === (int) $value ) {
						$new_data[ $key ]                      = 0;
						$new_data['client_id']                 = '';
						$new_data['client_secret']             = '';
						$new_data['tested']                    = 0;
						$new_data['expiration_timestamp']      = 0;
						$new_data['show_client_secret_notice'] = 0;
					}
					break;
				case 'tested':
					if ( 1 === (int) $posted_data[ $key ] && ( ! isset( $new_data['tested'] ) || 0 !== (int) $new_data['tested'] ) ) {
						$new_data['tested'] = 1;
					} else {
						$new_data['tested'] = 0;
					}
					break;
			}
		}

		if ( isset( $new_data['private_key_id'] ) && isset( $new_data['private_key'] ) && isset( $new_data['team_identifier'] ) && isset( $new_data['service_identifier'] ) ) {
			try {
				$time                  = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
				$expiration_time_stamp = $time->getTimestamp() + MONTH_IN_SECONDS * 6;

				$new_data['client_id']     = $new_data['service_identifier'];
				$new_data['client_secret'] = $this->generate_client_secret( $new_data['private_key_id'], $new_data['private_key'], $new_data['team_identifier'], $new_data['service_identifier'], $expiration_time_stamp );
				if ( ! empty( $new_data['client_id'] ) && ! empty( $new_data['client_secret'] ) ) {
					$new_data['has_credentials']           = 1;
					$new_data['expiration_timestamp']      = $expiration_time_stamp;
					$new_data['show_client_secret_notice'] = 0;
				}
			} catch ( Exception $e ) {
				$errors[] = sprintf(
				// translators: %1$s is the error message.
					__( 'An error occurred when storing of the expiration timestamp : %1$s', 'buddyboss-pro' ),
					$e->getMessage()
				);
			}

			if ( empty( $new_data['client_id'] ) || ( false !== (bool) $new_data['client_secret'] && empty( $new_data['client_secret'] ) ) ) {
				$errors[] = sprintf(
				// translators: %1$s is the error message.
					__( 'Token generation failed: %1$s', 'buddyboss-pro' ),
					__( 'Please check your credentials!', 'buddyboss-pro' )
				);
			}
		}

		if ( ! empty( $errors ) ) {
			wp_send_json_error( $errors );
		}

		return $new_data;
	}

	/**
	 * Generates a client secret for authentication.
	 *
	 * @since 2.6.30
	 *
	 * @param string $kid The private key identifier.
	 * @param string $key The private key.
	 * @param string $iss The team identifier.
	 * @param string $sub The service ID.
	 * @param string $exp The expiration timestamp.
	 *
	 * @return bool|string The generated client secret or false on failure.
	 */
	public function generate_client_secret( $kid, $key, $iss, $sub, $exp ) {

		if ( ! openssl_pkey_get_private( $key ) ) {

			BB_SSO_Notices::add_error(
				sprintf(
				// translators: %1$s is the error message.
					__( 'Token generation failed: %1$s', 'buddyboss-pro' ),
					__( 'Private key format is not valid!', 'buddyboss-pro' )
				)
			);

			return false;
		}

		try {
			$payload = array(
				'iss' => $iss,
				'aud' => 'https://appleid.apple.com',
				'exp' => $exp,
				'sub' => $sub,
			);

			return BBSSOPro\JWT\JWT::encode( $payload, $key, 'ES256', $kid );
		} catch ( Exception $e ) {
			BB_SSO_Notices::add_error(
				sprintf(
				// translators: %1$s is the error message.
					__( 'Token generation failed: %1$s', 'buddyboss-pro' ),
					$e->getMessage()
				)
			);
		}

		return false;
	}

	/**
	 * Gets authentication user data based on the provided key.
	 *
	 * @since 2.6.30
	 *
	 * @param string $key The key for the data to retrieve (e.g., 'id', 'email', 'name').
	 *
	 * @return mixed The requested user data or an empty string if not found.
	 */
	public function get_auth_user_data( $key ) {
		switch ( $key ) {
			case 'id':
				return ! empty( $this->auth_user_data['sub'] ) ? $this->auth_user_data['sub'] : '';
			case 'email':
				return isset( $this->auth_user_data['email'] ) ? $this->auth_user_data['email'] : '';
			case 'name':
				return ( ! empty( $this->auth_user_data['name']['firstName'] ) && ! empty( $this->auth_user_data['name']['lastName'] ) ) ? $this->auth_user_data['name']['firstName'] . ' ' . $this->auth_user_data['name']['lastName'] : '';
			case 'first_name':
				return ! empty( $this->auth_user_data['name']['firstName'] ) ? $this->auth_user_data['name']['firstName'] : '';
			case 'last_name':
				return ! empty( $this->auth_user_data['name']['lastName'] ) ? $this->auth_user_data['name']['lastName'] : '';
		}

		return parent::get_auth_user_data( $key );
	}

	/**
	 * Syncs the user profile with the given data from the social provider.
	 *
	 * @since 2.6.30
	 *
	 * @param int    $user_id  The ID of the user to sync.
	 * @param string $provider The social provider being used.
	 * @param array  $data     The data to sync with the user profile.
	 *
	 * @return void
	 */
	public function sync_profile( $user_id, $provider, $data ) {
	}

	/**
	 * Deletes any persistent login data associated with the client.
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
	 * Displays an admin notice if the Apple client secret is expired or invalid.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	public function show_admin_apple_client_secret_notice() {
		if ( current_user_can( BB_SSO::get_required_capability() ) ) {
			if ( ! empty( $this->settings->get( 'client_id' ) ) && ! empty( $this->settings->get( 'client_secret' ) ) ) {
				if ( $this->settings->get( 'expiration_timestamp' ) === 0 || $this->settings->get( 'show_client_secret_notice' ) ) {
					echo '<div class="error">
                        <p>' . sprintf(
						// translators: %s is the plugin name.
						esc_html__( '%s detected that your Apple credentials have expired. Please delete the current credentials and generate new one!', 'buddyboss-pro' ),
						'<b>BB Social Login</b>'
					) . '</p>
                        <p class="submit"><a href="" class="button button-primary">' . esc_html__( 'Fix Error', 'buddyboss-pro' ) . ' - ' . esc_html__( 'Apple Credentials', 'buddyboss-pro' ) . '</a></p>
                    </div>';
				}
			}
		}
	}

	/**
	 * Checks the validity of the Apple client secret and generates a new one if necessary.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	public function weekly_apple_client_secret_check() {

		if ( ! empty( $this->settings->get( 'client_id' ) ) && ! empty( $this->settings->get( 'client_secret' ) ) ) {
			try {
				$time                  = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
				$current_time_stamp    = $time->getTimestamp();
				$expiration_time_stamp = $this->settings->get( 'expiration_timestamp' );

				if ( $expiration_time_stamp > 0 && $expiration_time_stamp - MONTH_IN_SECONDS < $current_time_stamp ) {
					$new_expiration_time_stamp = $current_time_stamp + MONTH_IN_SECONDS * 6;
					$new_secret                = $this->generate_client_secret( $this->settings->get( 'private_key_id' ), $this->settings->get( 'private_key' ), $this->settings->get( 'team_identifier' ), $this->settings->get( 'service_identifier' ), $new_expiration_time_stamp );
					if ( $new_secret ) {
						$this->settings->set( 'expiration_timestamp', $new_expiration_time_stamp );
						$this->settings->set( 'client_secret', $new_secret );
						$this->settings->set( 'show_client_secret_notice', 0 );
					} else {
						// display admin notice if we couldn't generate a new secret.
						$this->settings->set( 'show_client_secret_notice', 1 );
					}
				}
			} catch ( Exception $e ) {
				BB_SSO_Notices::add_error(
					sprintf(
					// translators: %1$s is the error message.
						__( 'Token generation failed: %1$s', 'buddyboss-pro' ),
						$e->getMessage()
					)
				);
			}
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
		return apply_filters( 'bb_sso_apple_login_label', __( 'Continue with Apple', 'buddyboss-pro' ) );
	}

	/**
	 * Social register button label.
	 *
	 * @since 2.6.30
	 *
	 * @return string
	 */
	public function bb_sso_register_label() {
		return apply_filters( 'bb_sso_apple_register_label', __( 'Continue with Apple', 'buddyboss-pro' ) );
	}

	/**
	 * Retrieves the current user's information from the Apple ID token.
	 *
	 * @since 2.6.30
	 *
	 * @throws Exception If an error occurs while processing the user info.
	 * @return array The user's information, including ID and email.
	 */
	protected function get_current_user_info() {
		/**
		 * ID token contains the user id (sub ) and email
		 */
		$apple_id_token = $this->get_client()->get_apple_id_token();
		$token_parts    = array();
		if ( $apple_id_token ) {
			/**
			 * $token_parts[0] -> Header
			 * $token_parts[1] -> Payload
			 * $token_parts[2] -> Signature
			 */
			$token_parts = explode( '.', $apple_id_token );
		}

		$result = json_decode( base64_decode( $token_parts[1] ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		$name = $this->get_client()->get_apple_user_data();
		if ( $name ) {
			$result = array_merge( $result, $name );
		}

		return $result;
	}

	/**
	 * Retrieves the client instance for the Apple social provider, initializing it if necessary.
	 *
	 * @since 2.6.30
	 *
	 * @return BB_SSO_Auth|BB_Social_Provider_Apple_Client The client instance.
	 */
	public function get_client() {
		if ( null === $this->client ) {

			require_once __DIR__ . '/class-bb-social-provider-apple-client.php';

			$this->client = new BB_Social_Provider_Apple_Client( $this->id );

			$this->client->set_client_id( $this->settings->get( 'client_id' ) );
			$this->client->set_client_secret( $this->settings->get( 'client_secret' ) );
			$this->client->set_redirect_uri( $this->get_redirect_uri_for_auth_flow() );
		}

		return $this->client;
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
}

BB_SSO::add_provider( new BB_Social_Provider_Apple() );
