<?php
/**
 * BuddyBoss Social Login Provider.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO
 */

use BBSSO\BB_SSO_Notices;

require_once 'class-bb-sso-provider-admin.php';
require_once 'class-bb-sso-provider-dummy.php';
require_once 'class-bb-sso-user.php';

/**
 * Class BB_SSO_Provider
 *
 * @since 2.6.30
 */
abstract class BB_SSO_Provider extends BB_SSO_Provider_Dummy {

	/**
	 * Table name.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	public $table_name = '';

	/**
	 * Provider ID.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $db_id;

	/**
	 * Option key for storing provider settings.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $option_key;

	/**
	 * Flag to indicate if the provider is enabled.
	 *
	 * @since 2.6.30
	 *
	 * @var bool
	 */
	protected $enabled = false;

	/**
	 * Client ID.
	 *
	 * @since 2.6.30
	 *
	 * @var BB_SSO_Auth Client for authentication
	 */
	protected $client;

	/**
	 * User data returned from the authentication process.
	 *
	 * @since 2.6.30
	 *
	 * @var array
	 */
	protected $auth_user_data = array();

	/**
	 * Required fields for the provider's configuration.
	 *
	 * @since 2.6.30
	 *
	 * @var array
	 */
	protected $required_fields = array();

	/**
	 * SVG icon for the provider.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $svg = '';

	/**
	 * BB_SSO_Provider constructor.
	 *
	 * @since 2.6.30
	 *
	 * @param array $default_settings Default settings for the provider.
	 */
	public function __construct( $default_settings ) {

		if ( empty( $this->db_id ) ) {
			$this->db_id = $this->id;
		}

		global $wpdb;
		$table_prefix     = function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->base_prefix;
		$this->table_name = $table_prefix . 'bb_social_sign_on_users';

		$this->option_key = 'bb_sso_' . $this->id;

		do_action( 'bb_sso_provider_init', $this );

		$this->settings = new BB_Social_Login_Settings(
			$this->option_key,
			array_merge(
				array(
					'settings_saved'     => '0',
					'tested'             => '0',
					'oauth_redirect_url' => '',
				),
				$default_settings
			)
		);

		$this->admin = new BB_SSO_Provider_Admin( $this );
	}

	/**
	 * Get the provider's database ID.
	 *
	 * @since 2.6.30
	 *
	 * @return string
	 */
	public function get_db_id() {
		return $this->db_id;
	}

	/**
	 * Get the option key for the provider's settings.
	 *
	 * @since 2.6.30
	 *
	 * @return string The option key for the provider.
	 */
	public function get_option_key() {
		return $this->option_key;
	}

	/**
	 * Returns the URL where the Provider App should redirect during the OAuth/OpenID flow.
	 *
	 * @since 2.6.30
	 *
	 * @return string Redirect URI for the authentication flow.
	 */
	abstract public function get_redirect_uri_for_auth_flow();

	/**
	 * This function should return an array of URLs generated from getRedirectUri().
	 *
	 * We display the generated results in the Getting Started section and the Fixed redirect URI pages.
	 * Also, we use these for the OAuth redirect URI change checking.
	 *
	 * @since 2.6.30
	 *
	 * @return array An array of redirect URIs for the application creation.
	 */
	public function get_all_redirect_uris_for_app_creation() {

		/**
		 * Filter the redirect URIs for the application creation.
		 *
		 * @since 2.6.30
		 *
		 * @param array           $redirect_uris An array of redirect URIs.
		 * @param BB_SSO_Provider $this          The provider object.
		 */
		return apply_filters( 'bb_sso_redirect_uri_override', array( $this->get_base_redirect_uri_for_app_creation() ), $this );
	}

	/**
	 * Should return a single redirect URL that:
	 * - is used as the default redirects URI suggestion in the Getting Started and Fixed redirect URI pages.
	 * - is stored to detect the redirect URL changes.
	 *
	 * @since 2.6.30
	 *
	 * @return string The base redirect URI for app creation.
	 */
	abstract public function get_base_redirect_uri_for_app_creation();

	/**
	 * Enable the selected provider.
	 *
	 * @since 2.6.30
	 *
	 * @return bool True if the provider was successfully enabled, false otherwise.
	 */
	public function enable() {
		$this->enabled = true;

		do_action( 'bb_sso_' . $this->get_id() . '_enabled' );

		return true;
	}

	/**
	 * Check the authentication redirect URL.
	 *
	 * This method should validate the redirect URL according to the specific provider's requirements.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	abstract public function check_auth_redirect_url();

	/**
	 * Update the authentication redirect URL in the settings.
	 *
	 * This method will update the redirect URL based on the base redirect URI for app creation.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	public function update_auth_redirect_url() {
		$this->settings->update(
			array(
				'oauth_redirect_url' => $this->get_base_redirect_uri_for_app_creation(),
			)
		);
	}

	/**
	 * Get the required fields for the application.
	 *
	 * @since 2.6.30
	 *
	 * @return array An array of required fields for the provider.
	 */
	public function get_required_fields() {
		return $this->required_fields;
	}

	/**
	 * Get the current state of a Provider.
	 *
	 * Checks the configuration status of the provider and returns one of the following states:
	 * - 'not-configured' if any required fields are empty,
	 * - 'not-tested' if the provider has not been tested,
	 * - 'disabled' if the provider is not enabled,
	 * - 'enabled' if the provider is properly configured, tested, and enabled.
	 *
	 * @since 2.6.30
	 *
	 * @return string The current state of the provider.
	 */
	public function get_state() {
		foreach ( $this->required_fields as $name => $label ) {
			$value = $this->settings->get( $name );
			if ( empty( $value ) ) {
				return 'not-configured';
			}
		}
		if ( ! $this->is_tested() ) {
			return 'not-tested';
		}

		if ( ! $this->is_enabled() ) {
			return 'disabled';
		}

		return 'enabled';
	}

	/**
	 * Check if the provider is verified.
	 *
	 * @since 2.6.30
	 *
	 * @return bool True if the provider has been tested, false otherwise.
	 */
	public function is_tested() {
		return (bool) $this->settings->get( 'tested' );
	}

	/**
	 * Check if the provider is enabled.
	 *
	 * @since 2.6.30
	 *
	 * @return bool True if the provider is enabled, false otherwise.
	 */
	public function is_enabled() {
		return $this->enabled;
	}

	/**
	 * Authenticate and connect with the provider.
	 *
	 * This method attempts to perform authentication using the provider's protocol.
	 * If an error occurs, it will be handled by the on_error method.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	public function connect() {
		try {
			$this->do_authenticate();
		} catch ( Exception $e ) {
			$this->on_error( $e );
		}
	}

	/**
	 * Perform the authentication process with the provider.
	 *
	 * This method is responsible for initiating the authentication flow.
	 *
	 * @since 2.6.30
	 *
	 * @throws Exception if an error occurs during authentication.
	 *
	 * @return void
	 */
	protected function do_authenticate() {

		if ( ! headers_sent() ) {
			// All In One WP Security sets a LOCATION header, so we need to remove it to do a successful test.
			if ( function_exists( 'header_remove' ) ) {
				header_remove( 'LOCATION' );
			} else {
				header( 'LOCATION:', true ); // Under PHP 5.3
			}
		}

		// If it is a real login action, add the actions for the connection.
		if ( ! $this->is_test() ) {
			add_action( $this->id . '_login_action_before', array( $this, 'live_connect_before' ) );
			add_action( $this->id . '_login_action_redirect', array( $this, 'live_connect_redirect' ) );
			add_action( $this->id . '_login_action_get_user_profile', array( $this, 'live_connect_get_user_profile' ) );

			/**
			 * Store the settings for the provider login.
			 */
			$display = isset( $_REQUEST['display'] );
			if ( $display && 'popup' === $_REQUEST['display'] ) {
				\BBSSO\Persistent\BB_SSO_Persistent::set( $this->id . '_display', 'popup' );
			}
		} else { // This is just to verify the settings.
			add_action( $this->id . '_login_action_get_user_profile', array( $this, 'test_connect_get_user_profile' ) );
		}

		do_action( $this->id . '_login_action_before', $this );

		$this->do_auth_protocol_specific_flow();
	}

	/**
	 * Check if a logged-in user with manage_options capability wants to verify their provider settings.
	 *
	 * @since 2.6.30
	 *
	 * @return bool True if the user wants to test the provider settings, false otherwise.
	 */
	public function is_test() {
		if ( is_user_logged_in() && current_user_can( BB_SSO::get_required_capability() ) ) {
			if ( isset( $_REQUEST['test'] ) ) {
				\BBSSO\Persistent\BB_SSO_Persistent::set( 'test', 1 );

				return true;
			} elseif ( 1 === (int) \BBSSO\Persistent\BB_SSO_Persistent::get( 'test' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Abstract method to perform authentication protocol-specific flow.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	abstract protected function do_auth_protocol_specific_flow();

	/**
	 * Handle errors that occur during authentication.
	 *
	 * @since 2.6.30
	 *
	 * @param Exception $e The exception thrown during authentication.
	 *
	 * @return void
	 */
	protected function on_error( $e ) {
		if ( $this->is_test() ) {
			header( 'HTTP/1.0 401 Unauthorized' );
			echo esc_html( $e->getMessage() ) . "\n";
			?>
			<script type="text/javascript">
				try {
					// Existing logic to notify the parent window about the error.
					if ( window.opener && window.opener !== window ) {
						var currentOrigin = window.location.protocol + '//' + window.location.hostname;
						var sameOrigin    = window.opener.location.href.startsWith( currentOrigin );
						if ( sameOrigin ) {
							// Call the function in the parent window to show the error message.
							if ( window.opener && typeof window.opener.bbSSOShowMessage === 'function' ) {
								window.opener.bbSSOShowMessage( '<?php echo esc_html_e( 'Error Please check and try again', 'buddyboss-pro' ); ?>', 'error' );
							}
							window.close(); // Close the current popup.
						}
					}
				} catch ( e ) {
					console.error( 'Error occurred while trying to handle authentication error:', e );
				}
				window.close(); // Close the current popup.
			</script>
			<?php
		} else {
			// @TODO we might need to make difference between user cancelled auth and error and redirect the user based on that.
			$url = $this->get_last_location_redirect_to();
			?>
			<!doctype html>
			<html lang=en>
			<head>
				<meta charset=utf-8>
				<title><?php echo esc_html__( 'Authentication failed', 'buddyboss-pro' ); ?></title>
				<script type="text/javascript">
					try {
						if ( window.opener !== null && window.opener !== window ) {
							var sameOrigin = true;
							try {
								var currentOrigin = window.location.protocol + '//' + window.location.hostname;
								if ( window.opener.location.href.substring( 0, currentOrigin.length ) !== currentOrigin ) {
									sameOrigin = false;
								}

							} catch ( e ) {
								/**
								 * Blocked cross origin
								 */
								sameOrigin = false;
							}
							if ( sameOrigin ) {
								window.close();
							}
						}
					} catch ( e ) {
					}
					window.location = <?php echo wp_json_encode( $url ); ?>;
				</script>
				<meta http-equiv="refresh" content="0;<?php echo esc_attr( $url ); ?>">
			</head>
			<body>
			</body>
			</html>
			<?php
		}
		$this->delete_login_persistent_data();
		exit;
	}

	/**
	 * Get the last location to redirect to after authentication.
	 *
	 * This method determines the appropriate URL to redirect to based on several conditions:
	 * - If a fixed redirect URL is set.
	 * - If a redirect is specified in the URL.
	 * - If neither is set, defaults to the site URL.
	 *
	 * @since 2.6.30
	 *
	 * @return string The URL to redirect to.
	 */
	protected function get_last_location_redirect_to() {
		$redirect_to           = '';
		$requested_redirect_to = \BBSSO\Persistent\BB_SSO_Persistent::get( 'redirect' );

		if ( ! empty( $requested_redirect_to ) ) {
			if ( empty( $requested_redirect_to ) || ! BB_SSO::is_allowed_redirect_url( $requested_redirect_to ) ) {
				if ( ! empty( $_GET['redirect'] ) && BB_SSO::is_allowed_redirect_url( $_GET['redirect'] ) ) {
					$requested_redirect_to = $_GET['redirect'];
				} else {
					$requested_redirect_to = '';
				}
			}

			if ( empty( $requested_redirect_to ) ) {
				$redirect_to = site_url();
			} else {
				$redirect_to = $requested_redirect_to;
			}
			$redirect_to = wp_sanitize_redirect( $redirect_to );
			$redirect_to = wp_validate_redirect( $redirect_to, site_url() );

			$redirect_to = $this->validate_redirect( $redirect_to );
		} elseif ( ! empty( $_GET['redirect'] ) && BB_SSO::is_allowed_redirect_url( $_GET['redirect'] ) ) {
			$redirect_to = $_GET['redirect'];

			$redirect_to = wp_sanitize_redirect( $redirect_to );
			$redirect_to = wp_validate_redirect( $redirect_to, site_url() );

			$redirect_to = $this->validate_redirect( $redirect_to );
		}

		$redirect_to = apply_filters( 'bb_sso_' . $this->get_id() . 'default_last_location_redirect', $redirect_to, $requested_redirect_to );

		if ( '' === $redirect_to || $redirect_to === $this->get_login_url() ) {
			$redirect_to = site_url();
		}

		\BBSSO\Persistent\BB_SSO_Persistent::delete( 'redirect' );

		return apply_filters( 'bb_sso_' . $this->get_id() . 'last_location_redirect', $redirect_to, $requested_redirect_to );
	}

	/**
	 * Validate a redirect URL.
	 *
	 * This method sanitizes and validates the given location to ensure it is a safe
	 * redirect URL. It falls back to the admin URL if the validation fails.
	 *
	 * @since 2.6.30
	 *
	 * @param string $location The URL to validate.
	 *
	 * @return string The validated redirect URL.
	 */
	protected function validate_redirect( $location ) {
		$location = wp_sanitize_redirect( $location );

		return wp_validate_redirect( $location, apply_filters( 'wp_safe_redirect_fallback', admin_url(), 302 ) );
	}

	/**
	 * Get the login URL for the provider.
	 *
	 * Constructs the login URL with the necessary query arguments, including the
	 * social login ID and interim login status if applicable.
	 *
	 * @since 2.6.30
	 *
	 * @return string The constructed login URL.
	 */
	public function get_login_url() {
		$args = array( 'bb_social_login' => $this->get_id() );

		return add_query_arg( $args, BB_SSO::get_login_url() );
	}

	/**
	 * Delete persistent login data for the provider.
	 *
	 * This method removes various pieces of persistent data related to the login
	 * process, ensuring that the session is clean for future requests.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	public function delete_login_persistent_data() {
		\BBSSO\Persistent\BB_SSO_Persistent::delete( $this->id . '_display' );
		\BBSSO\Persistent\BB_SSO_Persistent::delete( $this->id . '_action' );
		\BBSSO\Persistent\BB_SSO_Persistent::delete( 'test' );
	}

	/**
	 * Get the test URL for the client.
	 *
	 * This method retrieves the test URL from the client associated with the provider.
	 *
	 * @since 2.6.30
	 *
	 * @return string The test URL.
	 */
	public function get_test_url() {
		return $this->get_client()->get_test_url();
	}

	/**
	 * Get the client instance for authentication.
	 *
	 * This abstract method should be implemented by subclasses to return the
	 * appropriate authentication client instance.
	 *
	 * @since 2.6.30
	 *
	 * @return BB_SSO_Auth The client instance for the authentication provider.
	 */
	abstract protected function get_client();

	/**
	 * Connect with the selected provider and retrieve the user profile.
	 *
	 * After a successful login, this method cleans up the persistent login data
	 * and redirects to the last location.
	 *
	 * @since 2.6.30
	 *
	 * @param array $data The data returned from the provider after login.
	 *
	 * @return void
	 */
	public function live_connect_get_user_profile( $data ) {

		$social_user = new BB_SSO_User( $this, $data );
		$social_user->live_connect_get_user_profile();

		$this->delete_login_persistent_data();
		$this->redirect_to_last_location_other( true );
	}

	/**
	 * Redirect to the last location with an optional notice.
	 *
	 * This method redirects the user to the last location while potentially
	 * displaying a success notice.
	 *
	 * @since 2.6.30
	 *
	 * @param bool $notice Optional. Whether to show a notice on redirect. Default is false.
	 *
	 * @return void
	 */
	protected function redirect_to_last_location_other( $notice = false ) {
		$this->redirect_to_last_location( $notice );
	}

	/**
	 * Redirect to the last location after authentication with an optional notice.
	 *
	 * This method retrieves the last redirect URL and sends the user there,
	 * optionally showing a success notice.
	 *
	 * @since 2.6.30
	 * @since 2.6.90 Added new param to redirect to specific page.
	 *
	 * @param bool   $notice Optional. Whether to show a notice on redirect. Default is false.
	 * @param string $action Action for SSO redirection.
	 *
	 * @return void
	 */
	public function redirect_to_last_location( $notice = false, $action = '' ) {
		$url = $this->get_last_location_redirect_to();

		if ( 'login' === $action ) {
			$current_user = wp_get_current_user();
			if ( ! empty( $current_user ) ) {
				$url = bb_login_redirect( $url, $url, $current_user );
			}
		}

		if ( $notice ) {
			$url = BB_SSO::enable_notice_for_url( $url );
		}
		self::redirect( __( 'Authentication successful', 'buddyboss-pro' ), $url );
	}

	/**
	 * Redirect to a specified URL with an optional title.
	 *
	 * This static method generates an HTML page that performs a JavaScript-based
	 * redirect to the given URL. It handles cross-origin scenarios if applicable.
	 *
	 * @since 2.6.30
	 *
	 * @param string $title The title to display in the browser tab during the redirect.
	 * @param string $url   The URL to redirect to.
	 *
	 * @return void
	 */
	public static function redirect( $title, $url ) {
		$url = BB_SSO::maybe_add_bypass_cache_arg_to_url( $url );
		?>
		<!doctype html>
		<html lang=en>
		<head>
			<meta charset=utf-8>
			<title><?php echo esc_html( $title ); ?></title>
			<script type="text/javascript">
				try {
					if ( window.opener !== null && window.opener !== window ) {
						var sameOrigin = true;
						try {
							var currentOrigin = window.location.protocol + '//' + window.location.hostname;
							if ( window.opener.location.href.substring( 0, currentOrigin.length ) !== currentOrigin ) {
								sameOrigin = false;
							}

						} catch ( e ) {
							/**
							 * Blocked cross origin
							 */
							sameOrigin = false;
						}
						if ( sameOrigin ) {
							window.opener.location = <?php echo wp_json_encode( $url ); ?>;
							window.close();
						}
					}
				} catch ( e ) {
				}
				window.location = <?php echo wp_json_encode( $url ); ?>;
			</script>
			<meta http-equiv="refresh" content="0;<?php echo esc_attr( $url ); ?>">
		</head>
		<body>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Link a user to a provider identifier.
	 *
	 * This method inserts the user ID into the `wp_bb_social_sign_on_users` table,
	 * creating a link between the user's account and the specified provider.
	 * It checks if the user is already linked to a different provider identifier.
	 *
	 * @since 2.6.30
	 * @since 2.6.60 Added the `$first_name` and `$last_name` parameters.
	 *
	 * @param int    $user_id             The user ID to link to the provider.
	 * @param string $provider_identifier The provider identifier to link.
	 * @param bool   $is_register         Optional. Whether this action is part of the registration process. Default is
	 *                                    false.
	 * @param string $first_name          Optional. The user's first name. Default is an empty string.
	 * @param string $last_name           Optional. The user's last name. Default is an empty string.
	 *
	 * @return bool True if the user is linked successfully, false if the user is already linked to a different
	 *              provider.
	 */
	public function link_user_to_provider_identifier( $user_id, $provider_identifier, $is_register = false, $first_name = '', $last_name = '' ) {
		global $wpdb;
		if ( empty( $provider_identifier ) ) {
			bp_core_add_message( __( 'Provider identifier is empty', 'buddyboss-pro' ), 'error' );
			return false;
		}
		$connected_provider_id = ! empty( $user_id ) ? $this->get_provider_identifier_by_user_id( $user_id ) : null;
		if ( null !== $connected_provider_id ) {
			if ( $connected_provider_id === $provider_identifier ) {
				// This provider already linked to this user.
				return true;
			}

			// User already have this provider attached to his account with different provider id.
			return false;
		}

		// Check if there is an entry with the same provider identifier and having the wp_user_id as 0 if so update entry with the passed user_id if it is not already linked to another user.
		$fetch_user_data = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT wp_user_id, first_name, last_name FROM `' . $this->table_name . '` WHERE type = %s AND identifier = %s AND wp_user_id = %d',
				array(
					$this->db_id,
					$provider_identifier,
					0,
				)
			)
		);

		if (
			'twitter' !== $this->get_id() &&
			! bb_enable_additional_sso_name()
		) {
			$first_name = '';
			$last_name  = '';
		}

		if ( $is_register ) {
			/**
			 * This is a register action.
			 */
			// Update existing entry for "apple" or other providers.
			if ( $fetch_user_data ) {
				$update_data = array(
					'wp_user_id'    => $user_id,
					'register_date' => current_time( 'mysql' ),
				);

				if ( 'apple' === $this->db_id ) {
					$update_data['first_name'] = ! empty( $fetch_user_data->first_name ) ? $fetch_user_data->first_name : $first_name;
					$update_data['last_name']  = ! empty( $fetch_user_data->last_name ) ? $fetch_user_data->last_name : $last_name;
				} else {
					$update_data['first_name'] = $first_name;
					$update_data['last_name']  = $last_name;
				}

				$wpdb->update(
					$this->table_name,
					$update_data,
					array(
						'type'       => $this->db_id,
						'identifier' => $provider_identifier,
					),
					array(
						'%d',
						'%s',
						'%s',
						'%s',
					),
					array(
						'%s',
						'%s',
					)
				);
			} else {
				// Insert new entry if no existing record found.
				if ( ! empty( $provider_identifier ) && false !== $provider_identifier ) {
					$wpdb->insert(
						$this->table_name,
						array(
							'wp_user_id'    => $user_id,
							'first_name'    => $first_name,
							'last_name'     => $last_name,
							'type'          => $this->db_id,
							'identifier'    => $provider_identifier,
							'register_date' => current_time( 'mysql' ),
							'link_date'     => current_time( 'mysql' ),
						),
						array(
							'%d',
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
							'%s',
						)
					);
				}
			}
		} else {
			// Update the user avatar in the persistent storage.
			\BBSSO\Persistent\BB_SSO_Persistent::set( $provider_identifier . '_user_avatar', sanitize_url( $this->get_auth_user_data( 'picture' ) ) );

			/**
			 * This is a link action.
			 */
			// Update existing entry when linking.
			if ( $fetch_user_data ) {
				$updated_first_name = $fetch_user_data->first_name;
				$updated_last_name  = $fetch_user_data->last_name;
				// If Apple, update the first_name and last_name if they are empty when linking.
				if (
					'apple' === $this->db_id &&
					(
						empty( $fetch_user_data->first_name ) ||
						empty( $fetch_user_data->last_name )
					)
				) {
					if ( empty( $fetch_user_data->first_name ) ) {
						$updated_first_name = $first_name;
					}
					if ( empty( $fetch_user_data->last_name ) ) {
						$updated_last_name = $last_name;
					}
				}
				$wpdb->update(
					$this->table_name,
					array(
						'wp_user_id' => $user_id,
						'first_name' => $updated_first_name,
						'last_name'  => $updated_last_name,
						'link_date'  => current_time( 'mysql' ),
					),
					array(
						'type'       => $this->db_id,
						'identifier' => $provider_identifier,
					),
					array(
						'%d',
						'%s',
						'%s',
						'%s',
					),
					array(
						'%s',
						'%s',
					)
				);
			} else {
				$wpdb->insert(
					$this->table_name,
					array(
						'wp_user_id' => $user_id,
						'first_name' => $first_name,
						'last_name'  => $last_name,
						'type'       => $this->db_id,
						'identifier' => $provider_identifier,
						'link_date'  => current_time( 'mysql' ),
					),
					array(
						'%d',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
					)
				);
			}
		}

		do_action( 'bb_sso_' . $this->get_id() . '_link_user', $user_id, $this->get_id(), $is_register );

		return true;
	}

	/**
	 * Retrieve the provider identifier associated with a given user ID.
	 *
	 * This method queries the database to find the provider identifier linked to
	 * the specified user ID.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id The user ID to search for.
	 *
	 * @return string|null The provider identifier if found, or null if not.
	 */
	protected function get_provider_identifier_by_user_id( $user_id ) {
		/** @var $wpdb WPDB */ global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				'SELECT identifier FROM `' . $this->table_name . '` WHERE type = %s AND wp_user_id = %s',
				array(
					$this->db_id,
					$user_id,
				)
			)
		);
	}

	/**
	 * Get the user ID associated with a given provider identifier.
	 *
	 * This method queries the database to find the WordPress user ID linked to
	 * the specified provider identifier.
	 *
	 * @since 2.6.30
	 *
	 * @param string $identifier The provider identifier to search for.
	 *
	 * @return int|null The user ID if found, or null if not.
	 */
	public function get_user_id_by_provider_identifier( $identifier ) {
		/** @var $wpdb WPDB */ global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				'SELECT wp_user_id FROM `' . $this->table_name . '` WHERE type = %s AND identifier = %s',
				array(
					$this->db_id,
					$identifier,
				)
			)
		);
	}

	/**
	 * Generate a connection button for social sign-on.
	 *
	 * This method constructs a link to the social sign-on service, allowing users to connect their accounts.
	 * The button style can be customized, and it can also handle redirection and tracking data.
	 *
	 * @since 2.6.30
	 *
	 * @param string      $button_style The style of the button to be displayed. Options include 'default' and 'icon'.
	 *                                  Defaults to 'default'.
	 * @param string|null $redirect_to  Optional. A URL to redirect to after connecting. If not provided, the current
	 *                                  page URL will be used.
	 * @param mixed       $tracker_data Optional. Data for tracking purposes. If provided, a hash of this data will
	 *                                  also be included.
	 * @param string      $label_type   Optional. Determines the label type ('login' or 'register'). Defaults to
	 *                                  'login'.
	 * @param bool|string $custom_label Optional. A custom label for the button. Defaults to false.
	 *
	 * @return string HTML markup for the connection button, including all necessary attributes.
	 */
	public function get_connect_button( $button_style = 'default', $redirect_to = null, $tracker_data = false, $label_type = 'login', $custom_label = false ) {
		$arg = array();
		if ( ! empty( $redirect_to ) ) {
			$arg['redirect'] = rawurlencode( $redirect_to );
		} elseif ( ! empty( $_GET['redirect_to'] ) ) {
			$arg['redirect'] = rawurlencode( $_GET['redirect_to'] );
		} else {
			$current_page_url = BB_SSO::get_current_page_url();
			if ( false !== $current_page_url ) {
				$arg['redirect'] = rawurlencode( $current_page_url );
			}
		}

		if ( false !== $tracker_data ) {
			$arg['trackerdata']      = rawurlencode( $tracker_data );
			$arg['trackerdata_hash'] = rawurlencode( wp_hash( $tracker_data ) );

		}

		$label = $this->bb_sso_login_label();
		if ( 'register' === $label_type ) {
			$label = $this->bb_sso_register_label();
		}

		switch ( $button_style ) {
			case 'icon':
				$button = $this->get_icon_button();
				break;
			default:
				$button = $this->get_default_button( $label );
				break;
		}

		$default_link_attributes = array(
			'href'             => esc_url( add_query_arg( $arg, $this->get_login_url() ) ),
			'rel'              => 'nofollow',
			'aria-label'       => esc_attr( $label ),
			'data-plugin'      => 'bb-sso',
			'data-action'      => 'connect',
			'data-provider'    => esc_attr( $this->get_id() ),
			'data-popupwidth'  => $this->get_popup_width(),
			'data-popupheight' => $this->get_popup_height(),

		);

		$custom_link_attributes = array();
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			/**
			 * Fix: Elementor Pro - Page Transitions shouldn't affect our button link.
			 */
			$custom_link_attributes['data-e-disable-page-transition'] = true;
		}
		$custom_link_attributes = apply_filters( 'bb_sso_connect_button_custom_attributes', $custom_link_attributes, $this );
		$all_link_attributes    = array_merge( $default_link_attributes, $custom_link_attributes );

		$button_link_opening_tag_start = '<a';
		$button_link_opening_tag_end   = '>';
		foreach ( $all_link_attributes as $attribute => $value ) {
			$button_link_opening_tag_start .= ' ' . $attribute . '="' . $value . '"';
		}
		$button_link_closing_tag = '</a>';

		return $button_link_opening_tag_start . $button_link_opening_tag_end . $button . $button_link_closing_tag;
	}

	/**
	 * Get the custom icon button if available, otherwise return the raw icon button.
	 *
	 * @since 2.6.30
	 *
	 * @return string HTML markup for the icon button.
	 */
	public function get_icon_button() {
		$button = $this->settings->get( 'custom_icon_button' );
		if ( ! empty( $button ) ) {
			return $button;
		}

		return $this->get_raw_icon_button();
	}

	/**
	 * Generate the raw HTML for the icon button.
	 *
	 * @since 2.6.30
	 *
	 * @return string HTML markup for the raw icon button.
	 */
	public function get_raw_icon_button() {
		return '<div class="bb-sso-button bb-sso-button-icon bb-sso-button-' . $this->id . '" style="background-color:' . $this->color . ';"><div class="bb-sso-button-svg-container">' . $this->svg . '</div></div>';
	}

	/**
	 * Get the default button with the specified label.
	 *
	 * This method replaces the placeholder in the default button markup with the provided label.
	 *
	 * @since 2.6.30
	 *
	 * @param string $label The label to display on the button.
	 *
	 * @return string HTML markup for the default button with the specified label.
	 */
	public function get_default_button( $label ) {

		return str_replace( '{{label}}', $label, $this->get_raw_default_button() );
	}

	/**
	 * Generate the raw HTML for the default button.
	 *
	 * @since 2.6.30
	 *
	 * @return string HTML markup for the raw default button.
	 */
	public function get_raw_default_button() {

		return '<div class="bb-sso-button bb-sso-button-default bb-sso-button-' . $this->id . '"><div class="bb-sso-button-svg-container">' . $this->svg . '</div><div class="bb-sso-button-label-container">{{label}}</div></div>';
	}

	/**
	 * Generate the HTML for a link button that redirects to the login URL.
	 *
	 * This button initiates a link action and includes the current page URL as a redirect parameter.
	 *
	 * @since 2.6.30
	 *
	 * @return string HTML markup for the link button.
	 */
	public function get_link_button() {

		$args = array(
			'action' => 'link',
		);

		$redirect = BB_SSO::get_current_page_url();
		if ( false !== $redirect ) {
			$args['redirect'] = rawurlencode( $redirect );
		}

		$default_link_attributes = array(
			'href'             => esc_url( add_query_arg( $args, $this->get_login_url() ) ),
			'rel'              => 'nofollow',
			'aria-label'       => $this->bb_sso_link_label(),
			'data-plugin'      => 'bb-sso',
			'data-action'      => 'link',
			'data-provider'    => esc_attr( $this->get_id() ),
			'data-popupwidth'  => $this->get_popup_width(),
			'data-popupheight' => $this->get_popup_height(),
			'class'            => 'bb-sso-action-button active',
		);

		$custom_link_attributes = array();
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			/**
			 * Fix: Elementor Pro - Page Transitions shouldn't affect our button link.
			 */
			$custom_link_attributes['data-e-disable-page-transition'] = true;
		}
		$custom_link_attributes = apply_filters( 'bb_sso_link_button_custom_attributes', $custom_link_attributes, $this );
		$all_link_attributes    = array_merge( $default_link_attributes, $custom_link_attributes );

		$button_link_opening_tag_start = '<a';
		$button_link_opening_tag_end   = '>';
		foreach ( $all_link_attributes as $attribute => $value ) {
			$button_link_opening_tag_start .= ' ' . $attribute . '="' . $value . '"';
		}
		$button_link_closing_tag = '</a>';

		return $button_link_opening_tag_start . $button_link_opening_tag_end . $this->bb_sso_link_label() . $button_link_closing_tag;
	}

	/**
	 * Generate the HTML for an unlink button that redirects to the login URL.
	 *
	 * This button initiates an unlink action and includes the current page URL as a redirect parameter.
	 *
	 * @since 2.6.30
	 *
	 * @return string HTML markup for the unlink button.
	 */
	public function get_unlink_button() {

		$args = array(
			'action' => 'unlink',
		);

		$redirect = BB_SSO::get_current_page_url();
		if ( false !== $redirect ) {
			$args['redirect'] = rawurlencode( $redirect );
		}

		$default_link_attributes = array(
			'href'          => esc_url( add_query_arg( $args, $this->get_login_url() ) ),
			'rel'           => 'nofollow',
			'aria-label'    => $this->bb_sso_unlink_label(),
			'data-plugin'   => 'bb-sso',
			'data-action'   => 'unlink',
			'data-provider' => esc_attr( $this->get_id() ),
			'class'         => 'bb-sso-action-button',
		);

		$custom_link_attributes = array();
		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			/**
			 * Fix: Elementor Pro - Page Transitions shouldn't affect our button link.
			 */
			$custom_link_attributes['data-e-disable-page-transition'] = true;
		}
		$custom_link_attributes = apply_filters( 'bb_sso_unlink_button_custom_attributes', $custom_link_attributes, $this );
		$all_link_attributes    = array_merge( $default_link_attributes, $custom_link_attributes );

		$button_link_opening_tag_start = '<a';
		$button_link_opening_tag_end   = '>';
		foreach ( $all_link_attributes as $attribute => $value ) {
			$button_link_opening_tag_start .= ' ' . $attribute . '="' . $value . '"';
		}
		$button_link_closing_tag = '</a>';

		return $button_link_opening_tag_start . $button_link_opening_tag_end . $this->bb_sso_unlink_label() . $button_link_closing_tag;
	}

	/**
	 * Redirect the user to the login form with an authentication error message.
	 *
	 * This method calls `redirect_with_authentication_error` to handle the redirection
	 * to the login URL with a specified authentication error notice.
	 *
	 * @since 2.6.30
	 */
	public function redirect_to_login_form() {
		$this->redirect_with_authentication_error( BB_SSO::get_login_url() );
	}

	/**
	 * Redirect to a specified URL with an authentication error message.
	 *
	 * @since 2.6.30
	 *
	 * @param string $url The URL to redirect to.
	 */
	public function redirect_with_authentication_error( $url ) {
		self::redirect( __( 'Authentication error', 'buddyboss-pro' ), BB_SSO::enable_notice_for_url( $url ) );
	}

	/**
	 * Allows for logged-in users to unlink their account from a provider if it was linked, and
	 * redirects to the last location.
	 * - During linking process, store the action as a link. After the linking process is finished,
	 * delete this stored info and redirects to the last location.
	 *
	 * @since 2.6.30
	 */
	public function live_connect_before() {

		if ( is_user_logged_in() && $this->is_current_user_connected() ) {

			if ( isset( $_GET['action'] ) && 'unlink' === $_GET['action'] ) {
				if ( $this->unlink_user() ) {
					bp_core_add_message(
						__( 'Unlink successful.', 'buddyboss-pro' ),
						'success'
					);
				} else {
					bp_core_add_message(
						__( 'Unlink is not allowed!', 'buddyboss-pro' ),
						'error'
					);
				}
			}

			$this->redirect_to_last_location_other( true );
			exit;
		}

		if ( isset( $_GET['action'] ) && 'link' === $_GET['action'] ) {
			\BBSSO\Persistent\BB_SSO_Persistent::set( $this->id . '_action', 'link' );
		}

		if ( is_user_logged_in() && 'link' !== \BBSSO\Persistent\BB_SSO_Persistent::get( $this->id . '_action' ) ) {
			$this->delete_login_persistent_data();

			$this->redirect_to_last_location_other();
			exit;
		}
	}

	/**
	 * If the current user has linked the account with a provider, return the user identifier else false.
	 *
	 * @since 2.6.30
	 *
	 * @return bool|null|string
	 */
	public function is_current_user_connected() {
		global $wpdb;

		$current_user  = wp_get_current_user();
		$identifier_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT identifier FROM `' . $this->table_name . '` WHERE type LIKE %s AND wp_user_id = %d',
				array(
					$this->db_id,
					$current_user->ID,
				)
			)
		);
		if ( null === $identifier_id ) {
			return false;
		}

		return $identifier_id;
	}

	/**
	 * Unlink the current user's account from the social provider.
	 *
	 * This method checks if unlinking is allowed via a filter and, if permitted,
	 * it retrieves the current user's information and unlinks their account
	 * from the provider by calling the `remove_connection_by_user_id` method.
	 * It also triggers an action to notify any listeners of the unlink event.
	 *
	 * @since 2.6.30
	 * @return bool True if the unlinking was successful, false otherwise.
	 */
	protected function unlink_user() {
		// Filter to disable unlinking social accounts.
		$unlink_allowed = apply_filters( 'bb_sso_allow_unlink', true );

		if ( $unlink_allowed ) {
			$user_info = wp_get_current_user();
			if ( $user_info->ID ) {
				$unlinked_identifier = $this->get_provider_identifier_by_user_id( $user_info->ID );
				$this->remove_connection_by_user_id( $user_info->ID );

				do_action( 'bb_sso_unlink_user', $user_info->ID, $this->get_id(), $unlinked_identifier );

				return true;
			}
		}

		return false;
	}

	/**
	 * Delete the link between the user account and the provider.
	 *
	 * This method removes the connection between the specified user ID and the
	 * social provider from the database.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id The ID of the user whose connection will be removed.
	 */
	public function remove_connection_by_user_id( $user_id ) {
		global $wpdb;
		if ( method_exists( $this, 'get_id' ) && 'apple' === $this->get_id() ) {
			$wpdb->update(
				$this->table_name,
				array(
					'wp_user_id' => 0,
					'link_date'  => current_time( 'mysql' ),
				),
				array(
					'type' => $this->db_id,
				),
				array(
					'%d',
					'%s',
				),
				array(
					'%s',
				)
			);
		} else {
			$wpdb->query(
				$wpdb->prepare(
					'DELETE FROM `' . $this->table_name . '` WHERE type = %s AND wp_user_id = %d',
					array(
						$this->db_id,
						$user_id,
					)
				)
			);
		}
	}

	/**
	 * Store the tracking data and redirect URL for the user.
	 *
	 * This method checks for 'trackerdata' and 'trackerdata_hash' in the GET parameters,
	 * validates the hash, and if valid, stores the tracker data. It also stores any
	 * provided redirect URL.
	 *
	 * @since 2.6.30
	 */
	public function live_connect_redirect() {
		if ( ! empty( $_GET['trackerdata'] ) && ! empty( $_GET['trackerdata_hash'] ) ) {
			if ( wp_hash( $_GET['trackerdata'] ) === $_GET['trackerdata_hash'] ) {
				\BBSSO\Persistent\BB_SSO_Persistent::set( 'trackerdata', sanitize_text_field( $_GET['trackerdata'] ) );
			}
		}
		if ( ! empty( $_GET['redirect'] ) ) {
			\BBSSO\Persistent\BB_SSO_Persistent::set( 'redirect', sanitize_url( $_GET['redirect'] ) );
		}
	}

	/**
	 * Check if there is a fixed redirect URL.
	 *
	 * This method currently returns false, indicating that there is no fixed redirect
	 * URL set for the user after linking.
	 *
	 * @since 2.6.30
	 * @return bool False, as fixed redirects are not implemented.
	 */
	public function has_fixed_redirect() {

		return false;
	}

	/**
	 * Sync the user's profile with the provider's data.
	 *
	 * This method is intended to synchronize the user's profile information with the
	 * data received from the social provider. The actual implementation should be
	 * defined based on the specific requirements for syncing user data.
	 *
	 * @since 2.6.30
	 *
	 * @param int             $user_id  The ID of the user whose profile is being synced.
	 * @param BB_SSO_Provider $provider The provider instance that contains user data.
	 * @param array           $data     The data array containing the user's profile information from the provider.
	 */
	public function sync_profile( $user_id, $provider, $data ) {
	}

	/**
	 * Test the connection and retrieve the user profile.
	 *
	 * This method performs a test connection, updates settings related to OAuth,
	 * and displays a success message. It generates a simple HTML document with
	 * JavaScript to handle the closing of the window and reloading of the parent.
	 *
	 * If the test is successful, the script will either reload the opener window
	 * or notify the user to refresh the parent window.
	 *
	 * @since 2.6.30
	 */
	public function test_connect_get_user_profile() {

		$this->delete_login_persistent_data();

		$this->settings->update(
			array(
				'tested'             => 1,
				'oauth_redirect_url' => $this->get_base_redirect_uri_for_app_creation(),
			)
		);

		BB_SSO_Notices::add_success( __( 'Saved Successfully', 'buddyboss-pro' ) );
		?>
		<!doctype html>
		<html lang=en>
		<head>
			<meta charset=utf-8>
			<title><?php esc_html_e( 'Saved Successfully', 'buddyboss-pro' ); ?></title>
			<?php
			BB_SSO::bb_sso_dom_ready();
			?>
			<script type="text/javascript">
				if ( window.opener ) {
					// Call the function in the parent window to show the success message.
					if ( window.opener && typeof window.opener.bbSSOShowMessage === 'function' ) {
						window.opener.bbSSOShowMessage( '<?php esc_html_e( 'Settings have been verified. Please wait while we redirect you.', 'buddyboss-pro' ); ?>', 'success' );
					}

					setTimeout( function () {
						window.close();
						window.opener.location.reload( true );
					}, 1000 );
				} else {
					/**
					 * Cross-Origin-Opener-Policy blocked the access to the opener
					 */
					if ( typeof BroadcastChannel === 'function' ) {
						const bbSSOVerifySettingsBroadCastChannel = new BroadcastChannel( 'bb_sso_verify_settings_broadcast_channel' );
						bbSSOVerifySettingsBroadCastChannel.postMessage( { action: 'reload' } );
						bbSSOVerifySettingsBroadCastChannel.close();
						window.close();
					} else {
						window._bbssoDOMReady( function () {
							document.body.innerHTML = esc_html__( 'Close this window and refresh the parent window!', 'buddyboss-pro' );
						} );
					}
				}
			</script>
		</head>
		<body>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Delete the persistent token data for the current user.
	 *
	 * This method is intended to remove any stored authentication tokens for the
	 * current user. The implementation should handle the specific logic for
	 * removing these tokens.
	 *
	 * @since 2.6.30
	 */
	public function delete_token_persistent_data() {
	}

	/**
	 * Retrieve the avatar for the specified user ID.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id The ID of the user whose avatar is to be retrieved.
	 *
	 * @return bool Always returns false.
	 */
	public function get_avatar( $user_id ) {

		return false;
	}

	/**
	 * Retrieve authentication user data for a specific key.
	 *
	 * @since 2.6.30
	 *
	 * @param string $key The key for the user data to retrieve.
	 *
	 * @return string The user data associated with the key, or an empty string if not found.
	 */
	public function get_auth_user_data( $key ) {
		return '';
	}

	/**
	 * Retrieve authentication user data using specified authentication options.
	 *
	 * This abstract method allows for the retrieval of user data associated with
	 * a specific key and authentication options. It is intended to be implemented
	 * in subclasses for custom behavior.
	 *
	 * @since 2.6.30
	 *
	 * @param string $key           The key for the user data to retrieve.
	 * @param mixed  $auth_options  Options that can influence the retrieval process.
	 *                              Can be used for accessing the get_auth_user_data() function
	 *                              outside the normal flow.
	 *
	 * @return mixed The user data associated with the key, depending on the implementation.
	 */
	abstract public function get_auth_user_data_by_auth_options( $key, $auth_options );

	/**
	 * Trigger synchronization of user data.
	 *
	 * This abstract method allows for synchronizing user data based on the specified
	 * action and conditions. It is intended to be implemented in subclasses for
	 * custom behavior, including profile updates and avatar synchronization.
	 *
	 * @since 2.6.30
	 *
	 * @param int    $user_id             The ID of the user whose data is to be synchronized.
	 * @param mixed  $auth_options        Options that can influence the synchronization process.
	 * @param string $action              The action to trigger; defaults to 'login'.
	 * @param bool   $should_sync_profile Indicates if profile synchronization should occur.
	 *                                    Can be used for triggering sync data storing and avatar
	 *                                    updating functions outside of the normal flow.
	 *
	 * @return mixed The result of the synchronization process, depending on the implementation.
	 */
	abstract public function trigger_sync( $user_id, $auth_options, $action = 'login', $should_sync_profile = false );

	/**
	 * Validate the provided settings.
	 *
	 * This method allows for validating new settings against posted data. It can be
	 * overridden in subclasses to enforce specific validation rules.
	 *
	 * @since 2.6.30
	 *
	 * @param array $new_data    The new data to validate.
	 * @param array $posted_data The data that was posted for validation.
	 *
	 * @return array The validated new data.
	 */
	public function validate_settings( $new_data, $posted_data ) {

		return $new_data;
	}

	/**
	 * Export personal data associated with the specified user ID.
	 *
	 * This method collects and prepares personal data related to the user for export.
	 * It retrieves the user's social identifier and profile picture, adding them to
	 * the export data array. Additional data can be appended through the
	 * extend_exported_personal_data method.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id The ID of the user whose data is to be exported.
	 *
	 * @return array An array containing the user's personal data for export.
	 */
	public function export_personal_data( $user_id ) {
		$data = array();

		$social_id = $this->is_user_connected( $user_id );
		if ( false !== $social_id ) {
			$data[] = array(
				'name'  => $this->get_label() . ' ' . __( 'Identifier', 'buddyboss-pro' ),
				'value' => $social_id,
			);
		}

		$profile_picture = $this->get_user_data( $user_id, 'profile_picture' );
		if ( ! empty( $profile_picture ) ) {
			$data[] = array(
				'name'  => $this->get_label() . ' ' . __( 'Profile Picture', 'buddyboss-pro' ),
				'value' => $profile_picture,
			);
		}

		$data = $this->extend_exported_personal_data( $user_id, $data );

		return $data;
	}

	/**
	 * Check if a user is connected to a social provider.
	 *
	 * This method checks the database for a linked account identifier for the specified
	 * user. If the user has linked their account, the identifier is returned; otherwise,
	 * it returns false.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id The ID of the user to check for a connection.
	 *
	 * @return bool|null|string False if the user is not connected, or the user's identifier
	 *                          if they are connected.
	 */
	public function is_user_connected( $user_id ) {
		/** @var $wpdb WPDB */ global $wpdb;

		$identifier_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT identifier FROM `' . $this->table_name . '` WHERE type LIKE %s AND wp_user_id = %d',
				array(
					$this->db_id,
					$user_id,
				)
			)
		);
		if ( null === $identifier_id ) {
			return false;
		}

		return $identifier_id;
	}

	/**
	 * Retrieve user metadata associated with a specified key.
	 *
	 * This protected method fetches user meta-data from the database using the
	 * specified user ID and key. The key is prefixed with the provider's ID to
	 * ensure uniqueness.
	 *
	 * @since 2.6.30
	 *
	 * @param int    $user_id The ID of the user whose data is to be retrieved.
	 * @param string $key     The key for the user data to retrieve.
	 *
	 * @return mixed The user metadata associated with the specified key, or an empty string if not found.
	 */
	protected function get_user_data( $user_id, $key ) {
		return get_user_meta( $user_id, $this->id . '_' . $key, true );
	}

	/**
	 * Extend the exported personal data for a user.
	 *
	 * This method allows for appending additional personal data to the export data
	 * array. It can be overridden in subclasses to provide custom functionality.
	 *
	 * @since 2.6.30
	 *
	 * @param int   $user_id The ID of the user whose data is being exported.
	 * @param array $data    The existing personal data array to extend.
	 *
	 * @return array The extended personal data array.
	 */
	public function extend_exported_personal_data( $user_id, $data ) {
		return $data;
	}

	/**
	 * Update the login date for a user in the social sign-on users table.
	 *
	 * This method logs the current date and time as the last login date for the
	 * specified user. It updates the corresponding entry in the
	 * bb_social_sign_on_users table.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id The ID of the user whose login date is to be updated.
	 *
	 * @return void
	 */
	public function log_login_date( $user_id ) {
		global $wpdb;
		$wpdb->update(
			$this->table_name,
			array( 'login_date' => current_time( 'mysql' ) ),
			array(
				'wp_user_id' => $user_id,
				'type'       => $this->db_id,
			),
			array(
				'%s',
				'%s',
			)
		);
	}

	/**
	 * Handle the redirect after user authentication in a popup window.
	 *
	 * This protected method checks if the login was performed in a popup window.
	 * If so, it redirects the source window to the login URL and closes the popup.
	 * It also manages cross-origin scenarios to ensure smooth redirection.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	protected function handle_popup_redirect_after_authentication() {
		/**
		 * if the login display was in the popup window,
		 * in the source window the user is redirected to the login url.
		 * and the popup window must be closed
		 */
		if ( 'popup' === \BBSSO\Persistent\BB_SSO_Persistent::get( $this->id . '_display' ) ) {
			\BBSSO\Persistent\BB_SSO_Persistent::delete( $this->id . '_display' );
			?>
			<!doctype html>
			<html lang=en>
			<head>
				<meta charset=utf-8>
				<title><?php esc_html_e( 'Authentication successful', 'buddyboss-pro' ); ?></title>
				<script type="text/javascript">
					try {
						if ( window.opener !== null && window.opener !== window ) {
							var sameOrigin = true;
							try {
								var currentOrigin = window.location.protocol + '//' + window.location.hostname;
								if ( window.opener.location.href.substring( 0, currentOrigin.length ) !== currentOrigin ) {
									sameOrigin = false;
								}

							} catch ( e ) {
								/**
								 * Blocked cross origin
								 */
								sameOrigin = false;
							}
							if ( sameOrigin ) {
								var url = <?php echo wp_json_encode( $this->get_login_url() ); ?>;
								if ( typeof window.opener.bbSSORedirect === 'function' ) {
									window.opener.bbSSORedirect( url );
								} else {
									window.opener.location = url;
								}
								window.close();
							} else {
								window.location.reload( true );
							}
						} else {
							if ( window.opener === null ) {
								/**
								 * Cross-Origin-Opener-Policy blocked the access to the opener
								 */
								if ( typeof BroadcastChannel === 'function' ) {
									const _bbSSOLoginBroadCastChannel = new BroadcastChannel( 'bb_sso_login_broadcast_channel' );
									_bbSSOLoginBroadCastChannel.postMessage( {
										action: 'redirect',
										href  :<?php echo wp_json_encode( $this->get_login_url() ); ?>} );
									_bbSSOLoginBroadCastChannel.close();
									window.close();
								} else {
									window.location.reload( true );
								}
							} else {
								window.location.reload( true );
							}
						}
					} catch ( e ) {
						window.location.reload( true );
					}
				</script>
			</head>
			<body>
			<a href="<?php echo esc_url( $this->get_login_url() ); ?>"><?php esc_html_e( 'Continue...', 'buddyboss-pro' ); ?></a>
			</body>
			</html>
			<?php
			exit;
		}
	}

	/**
	 * Save user metadata with a specified key.
	 *
	 * This protected method updates the user meta for the specified user ID with
	 * the provided key and data. The key is prefixed with the provider's ID to
	 * ensure uniqueness.
	 *
	 * @since 2.6.30
	 *
	 * @param int    $user_id The ID of the user whose data is to be saved.
	 * @param string $key     The key under which the data is to be saved.
	 * @param mixed  $data    The data to be saved for the specified user.
	 *
	 * @return void
	 */
	protected function save_user_data( $user_id, $key, $data ) {
		update_user_meta( $user_id, $this->id . '_' . $key, $data );
	}

	/**
	 * Get current user information.
	 *
	 * This protected method retrieves information about the current user.
	 * This implementation returns an empty array, but can be overridden
	 * in subclasses to provide actual user information.
	 *
	 * @since 2.6.30
	 *
	 * @return array An array containing current user information.
	 */
	protected function get_current_user_info() {
		return array();
	}

	/**
	 * Update the avatar for a specified user.
	 *
	 * This protected method triggers the action to update the avatar for the
	 * given user ID using the provided URL. It allows for additional hooks
	 * and functionality to be added through the `bb_sso_update_avatar` action.
	 *
	 * @since 2.6.30
	 *
	 * @param int    $user_id The ID of the user whose avatar is to be updated.
	 * @param string $url     The URL of the new avatar image.
	 *
	 * @return void
	 */
	protected function update_avatar( $user_id, $url ) {
		do_action( 'bb_sso_update_avatar', $this, $user_id, $url );
	}
}
