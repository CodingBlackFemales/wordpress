<?php
/**
 * Class BB_Social_Provider_Microsoft
 *
 * This class implements an OAuth2 provider for Microsoft social login and registration.
 * It extends the BB_SSO_Provider_OAuth class to manage Microsoft authentication,
 * retrieve user data, and handle settings related to Microsoft integration.
 *
 * @since   2.7.10
 * @package BuddyBossPro/SSO/Providers/Microsoft
 */

/**
 * Microsoft Social Provider class for BuddyBoss SSO.
 *
 * @since 2.7.10
 */
class BB_Social_Provider_Microsoft extends BB_SSO_Provider_OAuth {

	/**
	 * The Microsoft client instance.
	 *
	 * @since 2.7.10
	 *
	 * @var BB_Social_Provider_Microsoft_Client
	 */
	protected $client;

	/**
	 * Background color for the Microsoft provider.
	 *
	 * @since 2.7.10
	 * @var string Hex color code.
	 */
	protected $color = '#2F2F2F';

	/**
	 * SVG icon for the Microsoft provider.
	 *
	 * @since 2.7.10
	 *
	 * @var string
	 */
	protected $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 2H2V11H11V2Z" fill="#F25022"/><path d="M11 12H2V21H11V12Z" fill="#00A4EF"/><path d="M21 2H12V11H21V2Z" fill="#7FBA00"/><path d="M21 12H12V21H21V12Z" fill="#FFB900"/></svg>';

	/**
	 * Constructor for Microsoft Social Provider.
	 *
	 * @since 2.7.10
	 */
	public function __construct() {
		$this->id    = 'microsoft';
		$this->label = 'Microsoft';

		$this->path = __DIR__;

		$this->required_fields = array(
			'client_id'     => 'Application (client) ID',
			'client_secret' => 'Client secret',
			'tenant'        => 'Audience',
		);

		$this->auth_redirect_behavior = 'rest_redirect';

		parent::__construct(
			array(
				'client_id'           => '',
				'client_secret'       => '',
				'tenant'              => 'common',
				'custom_tenant_value' => '',
				'prompt'              => 'select_account',
			)
		);
	}

	/**
	 * Get the social login button label.
	 *
	 * @since 2.7.10
	 *
	 * @return string The filtered login button label.
	 */
	public function bb_sso_login_label() {
		return apply_filters( 'bb_sso_microsoft_login_label', __( 'Continue with Microsoft', 'buddyboss-pro' ) );
	}

	/**
	 * Validate the provider settings.
	 *
	 * @since 2.7.10
	 *
	 * @param array $new_data     New settings data.
	 * @param array $posted_data  Posted form data.
	 * @return array              Validated settings data.
	 */
	public function validate_settings( $new_data, $posted_data ) {
		$new_data = parent::validate_settings( $new_data, $posted_data );
		$errors   = array();

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
				case 'tenant':
					$new_data[ $key ] = trim( sanitize_text_field( $value ) );
					if ( $this->settings->get( $key ) !== $new_data[ $key ] ) {
						$new_data['tested'] = 0;
					}

					if ( empty( $new_data[ $key ] ) ) {
						$errors[] = sprintf(
							/* translators: %1$s: The required field name, %2$s: The required field name. */
							__( 'The %1$s entered did not appear to be a valid. Please enter a valid %2$s.', 'buddyboss-pro' ),
							$this->required_fields[ $key ],
							$this->required_fields[ $key ]
						);
					}
					break;
				case 'custom_tenant_value':
				case 'prompt':
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
	 * Get the Microsoft client instance.
	 *
	 * @since 2.7.10
	 *
	 * @return BB_SSO_Auth|BB_Social_Provider_Microsoft_Client The Microsoft client instance.
	 */
	public function get_client() {
		if ( null === $this->client ) {

			require_once __DIR__ . '/class-bb-social-provider-microsoft-client.php';

			$tenant = $this->settings->get( 'tenant' );
			if ( 'custom_tenant' === $tenant ) {
				$tenant = $this->settings->get( 'custom_tenant_value' );
			}

			$this->client = new BB_Social_Provider_Microsoft_Client( $this->id, $tenant );

			$this->client->set_client_id( $this->settings->get( 'client_id' ) );
			$this->client->set_client_secret( $this->settings->get( 'client_secret' ) );
			$this->client->set_redirect_uri( $this->get_redirect_uri_for_auth_flow() );

			$this->client->set_prompt( $this->settings->get( 'prompt' ) );

		}

		return $this->client;
	}

	/**
	 * Get the current user information from Microsoft.
	 *
	 * @since 2.7.10
	 *
	 * @return array User information from Microsoft.
	 * @throws Exception If the request fails.
	 */
	protected function get_current_user_info() {
		return $this->get_client()->get( '/me' );
	}

	/**
	 * Get authentication user data for a specific key.
	 *
	 * @since 2.7.10
	 *
	 * @param string $key The data key to retrieve.
	 * @return string     The requested user data.
	 */
	public function get_auth_user_data( $key ) {
		switch ( $key ) {
			case 'id':
				return ! empty( $this->auth_user_data['id'] ) ? $this->auth_user_data['id'] : '';
			case 'email':
				return is_email( $this->auth_user_data['userPrincipalName'] ) ? $this->auth_user_data['userPrincipalName'] : '';
			case 'name':
				return ! empty( $this->auth_user_data['displayName'] ) ? $this->auth_user_data['displayName'] : '';
			case 'first_name':
				return ! empty( $this->auth_user_data['givenName'] ) ? $this->auth_user_data['givenName'] : '';
			case 'last_name':
				return ! empty( $this->auth_user_data['surname'] ) ? $this->auth_user_data['surname'] : '';
		}

		return parent::get_auth_user_data( $key );
	}

	/**
	 * Delete login persistent data.
	 *
	 * @since 2.7.10
	 */
	public function delete_login_persistent_data() {
		parent::delete_login_persistent_data();

		if ( null !== $this->client ) {
			$this->client->delete_login_persistent_data();
		}
	}

	/**
	 * Get the URL for the Microsoft icon.
	 *
	 * @since 2.7.10
	 *
	 * @return string The URL of the Microsoft icon.
	 */
	public function get_icon() {
		return bb_sso_url() . 'providers/' . $this->id . '/' . $this->id . '.png';
	}

	/**
	 * Get the social register button label.
	 *
	 * @since 2.7.10
	 *
	 * @return string The filtered register button label.
	 */
	public function bb_sso_register_label() {
		return apply_filters( 'bb_sso_microsoft_register_label', __( 'Continue with Microsoft', 'buddyboss-pro' ) );
	}
}

BB_SSO::add_provider( new BB_Social_Provider_Microsoft() );
