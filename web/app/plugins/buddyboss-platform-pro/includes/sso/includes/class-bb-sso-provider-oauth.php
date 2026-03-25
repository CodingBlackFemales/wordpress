<?php
/**
 * Class BB_SSO_Provider_OAuth
 *
 * Abstract class for OAuth providers extending the BB_SSO_Provider class.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO
 */

use BBSSO\Persistent\BB_SSO_Persistent;

require_once __DIR__ . '/../lib/PKCE/class-bb-sso-pkce.php';
require_once 'class-bb-sso-provider.php';


/**
 * Class BB_SSO_Provider_OAuth
 *
 * @since 2.6.30
 */
abstract class BB_SSO_Provider_OAuth extends BB_SSO_Provider {

	/**
	 * BB_SSO_Provider_OAuth constructor.
	 *
	 * @since 2.6.30
	 *
	 * @param array $default_settings Default settings for the OAuth provider.
	 */
	public function __construct( $default_settings ) {
		parent::__construct( $default_settings );

		add_action(
			'rest_api_init',
			array(
				$this,
				'register_redirect_rest_route',
			)
		);

		add_action( 'bp_init', array( $this, 'bb_sso_app_authentication' ) );
	}


	/**
	 * Returns a single redirect URL that:
	 * - we us as default redirect uri suggestion in the Getting Started and Fixed redirect uri pages.
	 * - we store to detect the OAuth redirect url changes
	 *
	 * @since 2.6.30
	 *
	 * @return string
	 */
	public function get_base_redirect_uri_for_app_creation() {

		$redirect_uri = $this->get_redirect_uri_for_auth_flow();

		if ( 'default_redirect_but_app_has_restriction' === $this->auth_redirect_behavior ) {
			$parts = explode( '?', $redirect_uri );

			return $parts[0];
		}

		return $redirect_uri;
	}

	/**
	 * Returns the URL where the Provider App should redirect during the OAuth flow.
	 *
	 * @since 2.6.30
	 *
	 * @return string Redirect URL for the OAuth flow.
	 */
	public function get_redirect_uri_for_auth_flow() {
		if ( 'rest_redirect' === $this->auth_redirect_behavior ) {

			return rest_url( '/bb-social-login/v1/' . $this->id . '/redirect_uri' );
		}

		$args = array( 'bb_social_login' => $this->id );

		return add_query_arg( $args, BB_SSO::get_login_url() );
	}

	/**
	 * Check if the current redirect url of the provider matches with the one that we stored when the provider was
	 * configured. Returns "false" if they are different, so a new URL needs to be added to the App.
	 *
	 * @since 2.6.30
	 *
	 * @return bool
	 */
	public function check_auth_redirect_url() {
		$oauth_redirect_url = $this->settings->get( 'oauth_redirect_url' );

		$redirect_urls = $this->get_all_redirect_uris_for_app_creation();

		if ( is_array( $redirect_urls ) ) {
			/**
			 * Before 3.1.2 we saved the default redirect url of the provider ( e.g.:
			 * https://example.com/wp-login.php?bb_social_login=twitter ) for the OAuth check. However, some providers ( e.g.
			 * Microsoft ) can use the REST API URL as redirect url. In these cases if the URL of the OAuth page was changed,
			 * we gave a false warning for such providers.
			 *
			 * We shouldn't throw warnings for users who have the redirect uri stored still with the old format.
			 * For this reason we need to push the legacy redirect url into the $redirect_urls array, too!
			 */
			$legacy_redirect_url = add_query_arg( array( 'bb_social_login' => $this->get_id() ), BB_SSO::get_login_url() );
			if ( ! in_array( $legacy_redirect_url, $redirect_urls, true ) ) {
				$redirect_urls[] = $legacy_redirect_url;
			}

			if ( in_array( $oauth_redirect_url, $redirect_urls, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Executes the OAuth authentication flow specific to the protocol.
	 *
	 * @since 2.6.30
	 */
	public function do_auth_protocol_specific_flow() {
		$client = $this->get_client();

		$access_token_data = $this->get_anonymous_access_token();

		$client->check_error();

		do_action( $this->id . '_login_action_redirect', $this );

		/**
		 * Check if we have an accessToken and a code.
		 * If there is no access token and code it redirects to the Authorization Url.
		 */
		if ( ! $access_token_data && ! $client->has_authenticate_data() ) {

			header( 'LOCATION: ' . $client->create_auth_url() );
			exit;

		} else {

			/**
			 * If the code is OK but there is no access token, authentication is necessary.
			 */
			if ( ! $access_token_data ) {

				$access_token_data = $client->authenticate();

				$access_token_data = $this->request_long_lived_token( $access_token_data );

				/**
				 * Store the access token.
				 *
				 * If the user is not logged in, the access token will be stored in the session.
				 */
				$this->set_anonymous_access_token( $access_token_data );
			} else {
				$client->set_access_token_data( $access_token_data );
			}

			$data = array(
				'access_token_data' => $access_token_data,
			);

			$this->handle_popup_redirect_after_authentication();

			/**
			 * Retrieves the userinfo trough the REST API and connect with the provider.
			 * Redirects to the last location.
			 */
			$this->auth_user_data = $this->get_current_user_info();

			do_action( $this->id . '_login_action_get_user_profile', $data );
		}
	}

	/**
	 * Retrieves the stored access token data.
	 *
	 * @since 2.6.30
	 *
	 * @return mixed Access token data.
	 */
	protected function get_anonymous_access_token() {
		return BB_SSO_Persistent::get( $this->id . '_at' );
	}

	/**
	 * Requests a long-lived token from the provider.
	 *
	 * @since 2.6.30
	 *
	 * @param mixed $access_token_data Access token data.
	 *
	 * @return mixed Long-lived access token data.
	 */
	protected function request_long_lived_token( $access_token_data ) {
		return $access_token_data;
	}

	/**
	 * Stores the access token data.
	 *
	 * @since 2.6.30
	 *
	 * @param mixed $access_token Access token data to store.
	 */
	protected function set_anonymous_access_token( $access_token ) {
		BB_SSO_Persistent::set( $this->id . '_at', $access_token );
	}

	/**
	 * Finds the user by the provided access token.
	 *
	 * @since 2.6.30
	 *
	 * @param string $access_token Access token for the user.
	 *
	 * @return int|null User ID if found, null otherwise.
	 */
	public function find_user_by_access_token( $access_token ) {
		return $this->get_user_id_by_provider_identifier( $this->find_social_id_by_access_token( $access_token ) );
	}

	/**
	 * Finds the social ID associated with the provided access token.
	 *
	 * @since 2.6.30
	 *
	 * @param string $access_token Access token for the user.
	 *
	 * @return mixed Social ID if found, null otherwise.
	 */
	public function find_social_id_by_access_token( $access_token ) {
		$client = $this->get_client();
		$client->set_access_token_data( $access_token );
		$this->auth_user_data = $this->get_current_user_info();

		return $this->get_auth_user_data( 'id' );
	}

	/**
	 * Gets authentication user data by specified authentication options.
	 *
	 * @since 2.6.30
	 *
	 * @param string $key          Key for the authentication data.
	 * @param array  $auth_options Authentication options.
	 *
	 * @return mixed Auth user data for the specified key, or an empty string if not found.
	 */
	public function get_auth_user_data_by_auth_options( $key, $auth_options ) {
		if ( empty( $this->auth_user_data ) ) {
			if ( ! empty( $auth_options['access_token_data'] ) ) {
				$client = $this->get_client();
				$client->set_access_token_data( $auth_options['access_token_data'] );
				$this->auth_user_data = $this->get_current_user_info();
			}
		}

		if ( ! empty( $this->auth_user_data ) ) {
			return $this->get_auth_user_data( $key );
		}

		return '';
	}

	/**
	 * Triggers synchronization actions based on the specified action type.
	 *
	 * @since 2.6.30
	 *
	 * @param int    $user_id             User ID to synchronize.
	 * @param array  $auth_options        Authentication options containing access token data.
	 * @param string $action              The action to trigger ('login', 'link', or 'register').
	 * @param bool   $should_sync_profile Whether to synchronize the profile data.
	 */
	public function trigger_sync( $user_id, $auth_options, $action = 'login', $should_sync_profile = false ) {
		if ( ! empty( $auth_options['access_token_data'] ) ) {
			switch ( $action ) {
				case 'login':
					do_action( 'bb_sso_' . $this->get_id() . '_login', $user_id, $this, $auth_options );
					break;
				case 'link':
					do_action( 'bb_sso_' . $this->get_id() . '_link_user', $user_id, $this->get_id() );
					break;
				case 'register':
					do_action( 'bb_sso_' . $this->get_id() . '_register_new_user', $user_id, $this );
					break;
			}

			if ( $should_sync_profile ) {
				$this->sync_profile( $user_id, $this, $auth_options );
			}
		}
	}

	/**
	 * Deletes persistent login data associated with the provider.
	 *
	 * @since 2.6.30
	 */
	public function delete_login_persistent_data() {
		parent::delete_login_persistent_data();

		BB_SSO_Persistent::delete( $this->id . '_at' );
	}

	/**
	 * Registers a REST route for redirecting during the OAuth flow.
	 *
	 * @since 2.6.30
	 */
	public function register_redirect_rest_route() {
		if ( 'rest_redirect' === $this->auth_redirect_behavior ) {
			register_rest_route(
				'bb-social-login/v1',
				$this->id . '/redirect_uri',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array(
						$this,
						'redirect_to_provider_endpoint_with_state_and_code',
					),
					'args'                => array(
						'state' => array(
							'required' => true,
						),
						'code'  => array(
							'required' => true,
						),
					),
					'permission_callback' => '__return_true',
				)
			);
		}
	}

	/**
	 * Registers a REST API endpoints for a provider. This endpoint handles the redirect to the login endpoint
	 * of the currently used provider. The state and code GET parameters will be added to the login URL, so we
	 * can imitate as if the provider would already returned the state and code parameters to the original
	 * login url.
	 *
	 * @since 2.6.30
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function redirect_to_provider_endpoint_with_state_and_code( $request ) {
		$params = $request->get_params();

		if ( ! empty( $params['state'] ) && ! empty( $params['code'] ) ) {

			$provider = BB_SSO::$allowed_providers[ $this->id ];

			try {
				$provider_endpoint = $provider->get_login_url();

				if ( defined( 'WPML_PLUGIN_BASENAME' ) ) {
					$provider_endpoint = $provider->get_translated_login_url_for_rest_redirect();
				}

				$provider_endpoint_with_state_and_code = add_query_arg(
					array(
						'state' => $params['state'],
						'code'  => $params['code'],
					),
					$provider_endpoint
				);
				wp_safe_redirect( $provider_endpoint_with_state_and_code );
				exit;

			} catch ( Exception $e ) {
				$error_message = esc_html( $e->getMessage() );
			}
		} elseif ( empty( $params['state'] ) && empty( $params['code'] ) ) {
			$error_message = esc_html__( 'The code and state parameters are empty!', 'buddyboss-pro' );
		} elseif ( empty( $params['state'] ) ) {
			$error_message = esc_html__( 'The state parameter is empty!', 'buddyboss-pro' );
		} else {
			$error_message = esc_html__( 'The code parameter is empty!', 'buddyboss-pro' );
		}

		return new WP_Error( 'error', $error_message );
	}

	/**
	 * Generates a single translated login URL where the REST /redirect_uri endpoint of the currently used provider
	 * should redirect to instead of the original login url.
	 *
	 * @since 2.6.30
	 *
	 * @return string
	 */
	public function get_translated_login_url_for_rest_redirect() {
		$original_login_url = $this->get_login_url();

		if ( defined( 'WPML_PLUGIN_BASENAME' ) && defined( 'ICL_LANGUAGE_CODE' ) ) {

			global $sitepress;

			$language_code = ICL_LANGUAGE_CODE;

			if ( $sitepress && method_exists( $sitepress, 'get_active_languages' ) && $language_code ) {

				$wpml_active_languages = $sitepress->get_active_languages();

				if ( count( $wpml_active_languages ) > 1 ) {
					$wpml_language_url_format = false;
					if ( method_exists( $sitepress, 'get_setting' ) ) {
						$wpml_language_url_format = $sitepress->get_setting( 'language_negotiation_type' );
					}
					if ( $wpml_language_url_format && 3 === (int) $wpml_language_url_format ) {
						$persistent_redirect = BB_SSO_Persistent::get( 'redirect' );
						if ( $persistent_redirect ) {
							$persistent_redirect_query_params = array();
							$persistent_redirect_query_string = wp_parse_url( $persistent_redirect, PHP_URL_QUERY );
							parse_str( $persistent_redirect_query_string, $persistent_redirect_query_params );
							if ( isset( $persistent_redirect_query_params['lang'] ) && ! empty( $persistent_redirect_query_params['lang'] ) ) {
								$language_param = sanitize_text_field( $persistent_redirect_query_params['lang'] );
								if ( in_array( $language_param, array_keys( $wpml_active_languages ), true ) ) {
									$language_code = $language_param;
								}
							}
						}
					}

					$args = array( 'bb_social_login' => $this->get_id() );
					// OAuth flow handled over wp-login.php.

					if ( $wpml_language_url_format && 3 === (int) $wpml_language_url_format && ( ! class_exists( '\WPML\UrlHandling\WPLoginUrlConverter' ) || ( class_exists( '\WPML\UrlHandling\WPLoginUrlConverter' ) && ( ! get_option( \WPML\UrlHandling\WPLoginUrlConverter::SETTINGS_KEY, false ) ) ) ) ) {
						return $original_login_url;
					} else {
						global $wpml_url_converter;
						if ( $wpml_url_converter && method_exists( $wpml_url_converter, 'convert_url' ) ) {

							$converted_url = $wpml_url_converter->convert_url( site_url( 'wp-login.php' ), $language_code );

							$converted_url = add_query_arg( $args, $converted_url );

							return $converted_url;

						}
					}
				}
			}
		}

		return $original_login_url;
	}

	/**
	 * Extends the exported personal data for a user by adding the access token if available.
	 *
	 * @since 2.6.30
	 *
	 * @param int   $user_id User ID for which the personal data is being exported.
	 * @param array $data    Existing personal data to be extended.
	 *
	 * @return array Extended personal data array, including access token if available.
	 */
	public function extend_exported_personal_data( $user_id, $data ) {
		$access_token = $this->get_access_token( $user_id );
		if ( ! empty( $access_token ) ) {
			$data[] = array(
				'name'  => $this->get_label() . ' ' . __( 'Access token', 'buddyboss-pro' ),
				'value' => $access_token,
			);
		}

		return $data;
	}

	/**
	 * Retrieves the access token for a specific user.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id User ID.
	 *
	 * @return mixed Access token if found.
	 */
	public function get_access_token( $user_id ) {
		return $this->get_user_data( $user_id, 'access_token' );
	}

	/**
	 * Deletes persistent token data associated with the provider.
	 *
	 * @since 2.6.30
	 *
	 * This method removes the access token and state data from persistent storage.
	 */
	public function delete_token_persistent_data() {
		BB_SSO_Persistent::delete( $this->id . '_at' );
		BB_SSO_Persistent::delete( $this->id . '_state' );
	}

	/**
	 * BB SSO app authentication.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	public function bb_sso_app_authentication() {
		if ( isset( $_REQUEST['bb_app_redirect_schema'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$provider = ! empty( $_REQUEST['bb_social_login'] ) ? $_REQUEST['bb_social_login'] : ''; // phpcs:ignore

			// For Microsoft, we need to get the provider from the request url.
			if (
				empty( $provider ) &&
				isset( $_SERVER['REQUEST_URI'] ) &&
				false !== strpos( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), '/bb-social-login/v1/microsoft/redirect_uri' )
			) {
				$provider = 'microsoft';
			}

			if (
				empty( $provider ) ||
				! isset( BB_SSO::$providers[ $provider ] )
			) {
				return;
			}

			$provider_class = BB_SSO::$providers[ $provider ];

			if ( 'twitter' === $provider ) {
				$redirect_uri = $_REQUEST['bb_app_redirect_schema']; // phpcs:ignore
				$code         = ! empty( $_REQUEST['code'] ) ? $_REQUEST['code'] : ''; // phpcs:ignore
				$state        = ! empty( $_REQUEST['state'] ) ? $_REQUEST['state'] : ''; // phpcs:ignore

				if (
					! empty( $redirect_uri ) &&
					! empty( $code ) &&
					! empty( $state )
				) {
					$url = add_query_arg(
						array(
							'bb_social_login' => $provider,
							'code'            => $code,
							'state'           => $state,
						),
						$redirect_uri
					);
					header( 'LOCATION: ' . $url );
					exit;
				}
			} elseif ( 'microsoft' === $provider ) {
				// Sanitize all user inputs.
				$code                = ! empty( $_REQUEST['code'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['code'] ) ) : '';                                     // phpcs:ignore
				$state               = ! empty( $_REQUEST['state'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['state'] ) ) : '';                                   // phpcs:ignore
				$code_verifier       = ! empty( $_REQUEST['code_verifier'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['code_verifier'] ) ) : '';                   // phpcs:ignore PKCE support.
				$app_redirect_schema = ! empty( $_REQUEST['bb_app_redirect_schema'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['bb_app_redirect_schema'] ) ) : ''; // phpcs:ignore

				// Validate app redirect schema (should be alphanumeric with dots/hyphens for bundle ID).
				if ( ! preg_match( '/^[a-zA-Z][a-zA-Z0-9.\-]*$/', $app_redirect_schema ) ) {
					wp_die( esc_html__( 'Invalid redirect schema.', 'buddyboss-pro' ) );
				}

				$app_redirect_uri = $app_redirect_schema . '://oauth/redirect';

				// Get Microsoft provider settings.
				$client_id     = $provider_class->settings->get( 'client_id' );
				$client_secret = $provider_class->settings->get( 'client_secret' );
				$tenant        = $provider_class->settings->get( 'tenant' );

				// Handle custom tenant.
				if ( 'custom_tenant' === $tenant ) {
					$tenant = $provider_class->settings->get( 'custom_tenant_value' );
				}

				// Default to 'common' if tenant is empty.
				if ( empty( $tenant ) ) {
					$tenant = 'common';
				}

				if (
					empty( $app_redirect_uri ) ||
					empty( $code ) ||
					empty( $state ) ||
					empty( $client_id ) ||
					empty( $client_secret )
				) {
					wp_die( esc_html__( 'Missing required parameters for authentication.', 'buddyboss-pro' ) );
				}

				// Microsoft token endpoint.
				$api_url = 'https://login.microsoftonline.com/' . $tenant . '/oauth2/v2.0/token';

				// The redirect_uri must match EXACTLY what was used in the authorization request.
				// This includes the query parameters added when initiating the OAuth flow.
				// Note: code_verifier is NOT part of redirect_uri, it's sent separately in token exchange.
				$oauth_redirect_uri = add_query_arg(
					array(
						'bb_app_redirect_schema' => $app_redirect_schema,
						'bb_social_login'        => $provider,
					),
					rest_url( '/bb-social-login/v1/microsoft/redirect_uri' )
				);

				$args = array(
					'grant_type'    => 'authorization_code',
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'redirect_uri'  => $oauth_redirect_uri,
					'code'          => $code,
				);

				// Add code_verifier for PKCE if provided.
				if ( ! empty( $code_verifier ) ) {
					$args['code_verifier'] = $code_verifier;
				}

				$response = wp_remote_post(
					$api_url,
					array(
						'headers' => array(
							'Content-Type' => 'application/x-www-form-urlencoded',
						),
						'body'    => http_build_query( $args ),
					)
				);

				if ( is_wp_error( $response ) ) {
					wp_die( esc_html__( 'Authentication failed. Please try again.', 'buddyboss-pro' ) );
				}

				$body       = wp_remote_retrieve_body( $response );
				$token_data = json_decode( $body, true );

				if ( empty( $token_data ) || empty( $token_data['access_token'] ) ) {
					wp_die( esc_html__( 'Authentication failed. Please try again.', 'buddyboss-pro' ) );
				}

				// The access_token must be JSON-encoded because set_access_token_data() uses json_decode().
				// This matches the expected format: {"access_token": "eyJ...", "token_type": "Bearer", "expires_in": 3600}.
				$json_token_data = wp_json_encode(
					array(
						'access_token' => $token_data['access_token'],
						'token_type'   => ! empty( $token_data['token_type'] ) ? $token_data['token_type'] : 'Bearer',
						'expires_in'   => ! empty( $token_data['expires_in'] ) ? $token_data['expires_in'] : '',
					)
				);

				$url = add_query_arg(
					array(
						'bb_social_login' => $provider,
						'access_token'    => rawurlencode( $json_token_data ),
						'code'            => $code,
						'state'           => $state,
					),
					$app_redirect_uri
				);

				header( 'LOCATION: ' . $url );
				exit;
			} elseif ( 'linkedin' === $provider ) {
				$code         = ! empty( $_REQUEST['code'] ) ? $_REQUEST['code'] : ''; // phpcs:ignore
				$state        = ! empty( $_REQUEST['state'] ) ? $_REQUEST['state'] : ''; // phpcs:ignore
				$redirect_uri = $_REQUEST['bb_app_redirect_schema']; // phpcs:ignore
				$ret_url      = site_url( '/' ) . 'wp-login.php';
				$ret_url      = add_query_arg(
					array(
						'bb_app_redirect_schema' => $redirect_uri,
						'bb_social_login'        => $provider,
					),
					$ret_url
				);

				$api_url = 'https://www.linkedin.com/oauth/v2/accessToken';

				$client_id     = $provider_class->settings->get( 'client_id' );
				$client_secret = $provider_class->settings->get( 'client_secret' );

				if (
					empty( $redirect_uri ) ||
					empty( $code ) ||
					empty( $state ) ||
					empty( $client_id ) ||
					empty( $client_secret )
				) {
					echo 'HTTP request failed';
				}

				$args = array(
					'grant_type'    => 'authorization_code',
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'redirect_uri'  => $ret_url,
					'code'          => $code,
					'state'         => $state,
				);

				$response = wp_remote_post(
					$api_url,
					array(
						'headers' => array(
							'Content-Type' => 'application/x-www-form-urlencoded',
						),
						'body'    => http_build_query( $args ),
					)
				);

				if ( ! is_wp_error( $response ) ) {
					$body       = wp_remote_retrieve_body( $response );
					$token_data = json_decode( $body, true );

					if (
						! empty( $token_data ) &&
						! empty( $token_data['access_token'] )
					) {
						$token_data['code']  = $code;
						$token_data['state'] = $state;

						$url = add_query_arg(
							$token_data,
							$redirect_uri
						);

						header( 'LOCATION: ' . $url );
						exit;
					}
				} else {
					echo 'HTTP request failed';
				}
			}
			exit;
		}
	}
}
