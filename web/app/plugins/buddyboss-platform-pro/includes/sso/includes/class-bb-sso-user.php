<?php
/**
 * BuddyBoss Single Sign-On User class.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO
 */

use BBSSO\BB_SSO_Notices;
use BBSSO\Persistent\BB_SSO_Persistent;

require_once 'class-bb-sso-user-data.php';

/**
 * Class BB_SSO_User
 *
 * @since 2.6.30
 */
class BB_SSO_User {

	/**
	 * Provider instance.
	 *
	 * @since 2.6.30
	 *
	 * @var BB_SSO_Provider
	 */
	protected $provider;

	/**
	 * User data.
	 *
	 * @since 2.6.30
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * User ID.
	 *
	 * @since 2.6.30
	 *
	 * @var int
	 */
	protected $user_id;

	/**
	 * Indicates whether the user should be automatically logged in.
	 *
	 * @since 2.6.30
	 *
	 * @var bool
	 */
	protected $should_auto_login = false;

	/**
	 * User extra data.
	 *
	 * @since 2.6.30
	 *
	 * @var array
	 */
	private $user_extra_data;
	/**
	 * @var false|mixed
	 */
	private $allow_custom;

	/**
	 * BB_SSO_User constructor.
	 *
	 * @since 2.6.30
	 *
	 * @param BB_SSO_Provider $provider Provider instance.
	 * @param array           $data     User data.
	 */
	public function __construct( $provider, $data ) {
		$this->provider = $provider;
		$this->data     = $data;
	}

	/**
	 * Handles user authentication and linking with the social provider.
	 *
	 * This method connects the user with a social provider account. The behavior varies depending on the user's login
	 * state and existing linked social data:
	 *
	 * - If the user is not logged in:
	 *   - If no linked social data exists in the database (`wp_bb_social_sign_on_users` table), prepare the user for
	 *   registration.
	 *   - If linked social data exists, logs the user in.
	 * - If the user is logged in:
	 *   - If the social account is not yet linked and no other user is linked to that provider's identifier, the
	 *   social account is linked to the current user and access tokens (if available) are synchronized.
	 *   - If the social account is already linked to another user, an error is displayed.
	 *
	 * Notices:
	 * - On successful account linking, a success notice is displayed.
	 * - If an account is already linked, or if the social account is linked to another user, appropriate error
	 * messages are displayed.
	 *
	 * @since 2.6.30
	 */
	public function live_connect_get_user_profile() {

		$user_id = $this->provider->get_user_id_by_provider_identifier( $this->get_auth_user_data( 'id' ) );
		if ( null !== $user_id && ! get_user_by( 'id', $user_id ) ) {
			$this->provider->remove_connection_by_user_id( $user_id );
			$user_id = null;
		}

		$this->add_profile_sync_actions();

		if ( ! is_user_logged_in() ) {

			if ( null === $user_id ) {
				if ( '' !== $this->get_auth_user_data( 'id' ) ) {
					$this->provider->link_user_to_provider_identifier( 0, $this->get_auth_user_data( 'id' ), false, $this->get_auth_user_data( 'first_name' ), $this->get_auth_user_data( 'last_name' ) );
				}
				$this->prepare_register();
			} else {
				$this->login( $user_id );
			}
		} else {
			$current_user = wp_get_current_user();
			if ( null === $user_id ) {
				// Let's connect the account to the current user!

				if ( $this->provider->link_user_to_provider_identifier( $current_user->ID, $this->get_auth_user_data( 'id' ), false, $this->get_auth_user_data( 'first_name' ), $this->get_auth_user_data( 'last_name' ) ) ) {
					bp_core_add_message(
						sprintf(
						/* translators: 1: provider name, 2: provider name */
							__( 'Your %1$s account is successfully linked with your account. Now you can sign in with %2$s easily.', 'buddyboss-pro' ),
							$this->provider->get_label(),
							$this->provider->get_label()
						),
						'success'
					);
				} elseif ( 'twitter' === $this->provider->get_id() && isset( BB_SSO_Notices::$notices['info'] ) ) {
					bp_core_add_message(
						current( BB_SSO_Notices::$notices['info'] ),
						'info'
					);
				} else {
					bp_core_add_message(
						sprintf(
						/* translators: 1: provider name, 2: provider name */
							__( 'You have already linked a(n) %1$s account. Please unlink the current and then you can link another %2$s account.', 'buddyboss-pro' ),
							$this->provider->get_label(),
							$this->provider->get_label()
						),
						'warning'
					);
				}
			} elseif ( $current_user->ID !== $user_id ) {

				bp_core_add_message(
					sprintf(
					/* translators: %s: provider name */
						__( 'This %s account is already linked to another user.', 'buddyboss-pro' ),
						$this->provider->get_label()
					),
					'warning'
				);
			}
		}
	}

	/**
	 * $key is like id, email, name, first_name, last_name
	 * Returns a single user_data of the current provider or empty sting if $key is invalid.
	 *
	 * @since 2.6.30
	 *
	 * @param string $key The key of the user data to retrieve.
	 *
	 * @return string
	 */
	public function get_auth_user_data( $key ) {
		return $this->provider->get_auth_user_data( $key );
	}

	/**
	 * Adds actions to sync the user profile data with the provider.
	 *
	 * @since 2.6.30
	 *
	 * @return void The user data associated with the current provider.
	 */
	public function add_profile_sync_actions() {
		add_action(
			'bb_sso_' . $this->provider->get_id() . '_register_new_user',
			array(
				$this,
				'sync_profile_register_new_user',
			),
			10
		);

		add_action(
			'bb_sso_' . $this->provider->get_id() . '_login',
			array(
				$this,
				'sync_profile_login',
			),
			10
		);

		add_action(
			'bb_sso_' . $this->provider->get_id() . '_link_user',
			array(
				$this,
				'sync_profile_link_user',
			),
			10,
			3
		);
	}

	/**
	 * Prepares the user registration process and handles user login or registration based on email and settings.
	 *
	 * This method performs the following actions:
	 *
	 * - If the email is not yet registered:
	 *   - Checks if registration is enabled.
	 *   - If registration is allowed, call the `register()` method to create a new user account.
	 *   - If registration is disabled, an error message is displayed and the user is redirected to the login page or a
	 *   custom URL.
	 * - If the email is already registered:
	 *   - Checks if the auto-linking feature is enabled:
	 *     - If enabled, links the current provider account with the existing account and logs the user in.
	 *     - If auto-linking fails or is disabled, an error is displayed, and the user is redirected.
	 *
	 * This method includes various filters to allow customization of the registration process, error messages, and
	 * redirect URLs.
	 *
	 * Filters:
	 * - `bb_sso_match_social_account_to_user_id`: Modify or override the user ID to link with the social account.
	 * - `bb_sso_disabled_register_error_message`: Modify the error message displayed when registration is disabled.
	 * - `bb_sso_disabled_register_redirect_url`: Customize the URL where users are redirected when registration is
	 * disabled.
	 * - `bb_sso_autolink_error_redirect_url`: Customize the URL for redirecting users if auto-linking fails.
	 *
	 * @since 2.6.30
	 */
	protected function prepare_register() {
		$user_id = false;

		$bb_sso_login_url = BB_SSO::get_login_url();

		$provider_user_id = $this->get_auth_user_data( 'id' );

		$email = $this->get_auth_user_data( 'email' );

		if ( empty( $email ) ) {
			$email = '';
		} else {
			$user_id_found = email_exists( $email );
			/**
			 * Note: email_exists overrides could cause problems during the linking -> we should check if the returned ID is has integer type and if we are able to find an account with that ID.
			 */
			if ( is_int( $user_id_found ) && get_userdata( $user_id_found ) ) {
				$user_id = $user_id_found;
			}
		}

		/**
		 * Can be used for overriding the account where the social account should be automatically linked to.
		 */
		$user_id = apply_filters( 'bb_sso_match_social_account_to_user_id', $user_id, $this, $this->provider );

		if ( false === $user_id ) { // Real register.
			if ( apply_filters( 'bb_sso_is_register_allowed', true, $this->provider ) ) {
				$this->register( $provider_user_id, $email );
			} else {
				// unset the persistent data, so if an error happened, the user can re-authenticate with providers (Google) that offer account selector screen.
				$this->provider->delete_token_persistent_data();

				$register_disabled_redirect_url = apply_filters( 'bb_sso_disabled_register_redirect_url', '' );
				$default_disabled_message       = ! BB_SSO::bb_sso_is_register_allowed() ? __( 'Registration to this site has been disabled, please contact site owners for further assistance.', 'buddyboss-pro' ) : '';

				if ( empty( $register_disabled_redirect_url ) ) {
					/**
					 * By default, WordPress displays an error message if the $_GET['registration'] is set to "disabled"
					 * To avoid displaying the default and the custom error message, the url should not contain it.
					 */
					$register_disabled_redirect_url = $bb_sso_login_url;
				}

				if (
					! empty( $default_disabled_message ) &&
					/**
					 * If both registrations are already the same, then we don't need to display the error message.
					 * Because the default error message is already displayed.
					 */
					trailingslashit( BB_SSO::get_register_url() ) !== trailingslashit( $register_disabled_redirect_url )
				) {
					$errors = new WP_Error();
					$errors->add( 'registerdisabled', $default_disabled_message );
					if ( function_exists( 'is_login' ) && is_login() ) {
						BB_SSO_Notices::add_error( $errors->get_error_message(), 'info' );
					} else {
						bp_core_add_message(
							$errors->get_error_message(),
							'info'
						);
					}
				}

				if ( empty( $register_disabled_redirect_url ) ) {
					$register_disabled_redirect_url = add_query_arg( 'registration', 'disabled', $bb_sso_login_url );
				}

				$this->provider->redirect_with_authentication_error( $register_disabled_redirect_url );
				exit;
			}
		} elseif ( $this->auto_link( $user_id, $provider_user_id ) ) {
			$this->login( $user_id );
		} else {
			$autolink_error_redirect_url = apply_filters( 'bb_sso_autolink_error_redirect_url', $bb_sso_login_url );
			$this->provider->redirect_with_authentication_error( $autolink_error_redirect_url );
			exit;
		}

		$this->provider->redirect_to_login_form();
	}

	/**
	 * Registers a new user using the provided social account data or standard registration.
	 *
	 * The function first checks if the email is provided. If not, it throws an error and redirects the user.
	 * It then validates the signup process, generates a username if necessary, and ensures the username is unique.
	 * If no password is provided, a random password is generated. Hooks are available to customize
	 * the registration process at various stages, including the ability to externally insert the user.
	 * Finally, the user is either inserted into the WordPress database or linked to the social account.
	 * If the registration fails, appropriate errors are handled and the user is redirected.
	 *
	 * @since 2.6.30
	 *
	 * @param string $provider_id The ID of the social provider.
	 * @param string $email       The email address of the user being registered.
	 *
	 * @return bool False if the registration process fails; otherwise, the function will not return as it redirects on
	 *              success.
	 */
	protected function register( $provider_id, $email ) {
		if ( 'twitter' !== $this->provider->get_id() && empty( $email ) ) {
			// Throw error: Email is required.
			$errors    = new WP_Error();
			$email_msg = apply_filters(
				'bb_sso_register_email_not_found',
				sprintf(
				/* translators: %s: provider name */
					__( '<p>Email address could not be identified. Please share the email used for your %s account to register.</p>', 'buddyboss-pro' ),
					$this->provider->get_label()
				)
			);
			$errors->add( 'email_not_found', $email_msg );
			bp_core_add_message(
				$errors->get_error_message(),
				'error'
			);

			$bb_sso_login_url               = bp_enable_site_registration() ? bp_get_signup_page() : BB_SSO::get_login_url();
			$register_disabled_redirect_url = apply_filters( 'bb_sso_disabled_register_redirect_url', $bb_sso_login_url );
			wp_safe_redirect( BB_SSO::enable_notice_for_url( $register_disabled_redirect_url ) );
			exit();
		}

		$allow_signup = $this->validate_signup( $email, $this->provider->get_id() );

		if ( ! $allow_signup['allow_signup'] ) {
			wp_safe_redirect( BB_SSO::enable_notice_for_url( $allow_signup['redirect_url'] ) );
			exit();
		}

		BB_SSO::$wp_login_current_flow = 'register';

		$sanitized_user_login = false;
		if ( 'twitter' === $this->provider->get_id() || bb_enable_additional_sso_name() ) {
			$sanitized_user_login = $this->sanitize_user_name( $this->get_auth_user_data( 'first_name' ) . $this->get_auth_user_data( 'last_name' ) );
			if ( false === $sanitized_user_login ) {
				$sanitized_user_login = $this->sanitize_user_name( $this->get_auth_user_data( 'username' ) );
				if ( false === $sanitized_user_login ) {
					$sanitized_user_login = $this->sanitize_user_name( $this->get_auth_user_data( 'name' ) );
				}
			}
		}

		$email     = $this->get_auth_user_data( 'email' );
		$user_data = array(
			'email'    => $email,
			'username' => $sanitized_user_login,
		);

		do_action( 'bb_sso_before_register', $this->provider );

		do_action( 'bb_sso_' . $this->provider->get_id() . '_before_register' );

		// @var array $user_data Validated user data
		$user_data = $this->finalize_user_data( $user_data );

		/**
		 * - If neither of the usernames (first_name & last_name, secondary_name) is appropriate, the fallback username will be combined with and id that was sent by the provider.
		 * - In this way we can generate an appropriate username.
		 */
		if ( empty( $user_data['username'] ) ) {
			$bb_autogenerate_user_prefix = apply_filters( 'bb_sso_autogenerate_user_prefix', '' );
			$user_name                   = function_exists( 'bb_generate_user_random_profile_slugs' ) ? bb_generate_user_random_profile_slugs( 1, $bb_autogenerate_user_prefix ) : '';
			if ( ! empty( $user_name ) ) {
				$user_name = current( $user_name );
			} else {
				$user_name = $bb_autogenerate_user_prefix . md5( uniqid( wp_rand() ) );
			}
			$user_data['username'] = sanitize_user( $user_name, true );
		}

		/**
		 * If the username is already in use, it will get a number suffix, that is not registered yet.
		 */
		$default_user_name = $user_data['username'];
		$i                 = 1;
		while ( username_exists( $user_data['username'] ) ) {
			$user_data['username'] = $default_user_name . $i;
			++$i;
		}

		/**
		 * Generates a random password. And set the default_password_nag to true.
		 * So the user gets notified about randomly generated password.
		 */
		if ( empty( $user_data['password'] ) ) {
			$user_data['password'] = wp_generate_password( 12, false );

			add_action( 'user_register', array( $this, 'register_complete_default_password_nag' ) );
		}
		/**
		 * Preregister, checks what roles shall be informed about the registration and sends a notification to them.
		 */
		do_action( 'bb_sso_pre_register_new_user', $this );

		/**
		 * Eduma theme user priority 1000 to auto log in users. We need to stay under that priority @see https://themeforest.net/item/education-wordpress-theme-education-wp/14058034
		 * WooCommerce Follow-Up Emails use priority 10, so we need higher @see https://woocommerce.com/products/follow-up-emails/
		 *
		 * If there was no error during the registration process,
		 * -links the user to the providerIdentifier ( wp_bb_social_sign_on_users table in database store this link ).
		 * -set the roles for the user.
		 * -login the user.
		 */
		add_action( 'user_register', array( $this, 'register_complete' ), 31 );

		$auto_login_priority = apply_filters( 'bb_sso_autologin_priority', 40 );
		add_action( 'user_register', array( $this, 'do_auto_login' ), $auto_login_priority );

		$this->user_extra_data = $user_data;

		$user_data = array(
			'user_login' => wp_slash( $user_data['username'] ),
			'user_email' => wp_slash( $user_data['email'] ),
			'user_pass'  => $user_data['password'],
		);

		if (
			'twitter' === $this->provider->get_id() ||
			bb_enable_additional_sso_name()
		) {
			$name = $this->get_auth_user_data( 'name' );
			if ( ! empty( $name ) ) {
				$user_data['display_name'] = $name;
			}

			$sso_user_data = $this->bb_sso_get_name_from_identifier( $this->get_auth_user_data( 'id' ), $this->provider->get_id() );
			if ( ! empty( $sso_user_data ) ) {
				$user_data['first_name'] = $sso_user_data['first_name'];
				$user_data['last_name']  = $sso_user_data['last_name'];
			} else {
				$first_name = $this->get_auth_user_data( 'first_name' );
				if ( ! empty( $first_name ) ) {
					$user_data['first_name'] = $first_name;
				}

				$last_name = $this->get_auth_user_data( 'last_name' );
				if ( ! empty( $last_name ) ) {
					$user_data['last_name'] = $last_name;
				}
			}
		}

		$external_insert_user_status = array(
			'is_external_insert_user' => false,
			'error'                   => false,
		);
		/**
		 * If the account is created outside of BuddyBoss Single Sign-On, then BuddyBoss Single Sign-On should be prevented from inserting the user again.
		 * For this, "is_external_insert_user" needs to be set to true.
		 * If an error happens in the external registration, then the error message can be displayed by setting "error" to a WP_ERROR object.
		 */
		$external_insert_user_status = apply_filters( 'bb_sso_register_external_insert_user', $external_insert_user_status, $this, $user_data );
		$error                       = $external_insert_user_status['error'];

		if ( ! $external_insert_user_status['is_external_insert_user'] ) {
			$error = wp_insert_user( $user_data );
		}

		if ( is_wp_error( $error ) ) {
			$this->provider->delete_token_persistent_data();
			BB_SSO_Notices::add_error( $error ); // Display an error message with toasts if redirection is not on the login page.
			$this->redirect_to_last_location_login( true );

		} elseif ( 0 === $error ) {
			$this->register_error();
			exit;
		}

		// register_complete will log in user and redirects. If we reach here, the user creation failed.
		return false;
	}

	/**
	 * Validates the signup process for a new user.
	 *
	 * This function checks whether all required fields for signing up are present in the user's data.
	 * It retrieves the necessary profile fields from the BuddyPress xProfile system and compares them
	 * with the required fields to ensure that all necessary information is provided. If any required
	 * fields are missing, an error message is generated, and the user is redirected to the signup page
	 * with pre-filled data where possible.
	 *
	 * @since 2.6.30
	 *
	 * @global wpdb  $wpdb  Global WordPress database object.
	 * @global BP    $bp    Global BuddyPress object.
	 *
	 * @param string $email       The email address provided by the user for registration.
	 * @param string $provider_id The ID of the provider.
	 *
	 * @return array An associative array containing:
	 *               - 'allow_signup' (bool): Indicates if the signup is allowed.
	 *               - 'redirect_url' (string): URL to redirect the user if signup is not allowed.
	 *               - 'fields' (array): List of fields that were validated (if applicable).
	 */
	public function validate_signup( $email, $provider_id = '' ) {
		global $wpdb, $bp;

		$response = array(
			'allow_signup' => true,
			'redirect_url' => '',
			'fields'       => array(),
		);

		// Get signup base group id.
		$signup_fields = bp_xprofile_base_group_id();

		// Get the first name field id.
		$first_name_id = bp_xprofile_firstname_field_id();

		// Get the last name field id.
		$last_name_id = bp_xprofile_lastname_field_id();

		// Get the nickname field id.
		$nickname_id = bp_xprofile_nickname_field_id();

		// Get base group fields from bp_xprofile_fields table.
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$signup_fields = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->profile->table_name_fields} WHERE group_id = %d AND is_required = %d", $signup_fields, 1 ) );

		$signup_fields = array_map( 'intval', $signup_fields );
		// Auto fill array.
		$signup_auto_fill_arr = $signup_fields;

		// Get the first name and last name from the provider.
		$first_name = '';
		$last_name  = '';
		if ( 'twitter' === $this->provider->get_id() || bb_enable_additional_sso_name() ) {
			$first_name = $this->get_auth_user_data( 'first_name' );
			$last_name  = $this->get_auth_user_data( 'last_name' );
		}

		// If the first and last name is empty try to find from the wp_bb_social_sign_on_users table using the identifier.
		if (
			(
				'twitter' === $this->provider->get_id() ||
				bb_enable_additional_sso_name()
			) &&
			empty( $first_name ) &&
			empty( $last_name )
		) {
			$sso_user_data = $this->bb_sso_get_name_from_identifier( $this->get_auth_user_data( 'id' ), $this->provider->get_id() );
			if ( ! empty( $sso_user_data ) ) {
				$first_name = $sso_user_data['first_name'];
				$last_name  = $sso_user_data['last_name'];
			}
		}

		// Match the $signup_fields and if it matches with the $first_name_id, $last_name_id, $nickname_id then remove it from the array.
		if (
			! empty( $signup_fields ) &&
			(
				! bb_enable_additional_sso_name() ||
				! empty( $first_name )
			)
		) {
			if ( in_array( $first_name_id, $signup_fields, true ) ) {
				$signup_fields = array_diff( $signup_fields, array( $first_name_id ) );
			}
			if ( ! empty( $last_name ) && in_array( $last_name_id, $signup_fields, true ) ) {
				$signup_fields = array_diff( $signup_fields, array( $last_name_id ) );
			}
			if ( in_array( $nickname_id, $signup_fields, true ) ) {
				$signup_fields = array_diff( $signup_fields, array( $nickname_id ) );
			}
		}

		$valid_error   = array();
		$fields_labels = array();

		$generated_nickname = '';

		// If the email is empty, then get the email field label and add it to
		// the $fields_labels array to display the error message.
		// (i.e. - X provider does not return email)
		if ( empty( $email ) ) {
			$account_fields = function_exists( 'bp_nouveau_get_signup_fields' ) ? bp_nouveau_get_signup_fields( 'account_details' ) : array();
			if ( ! empty( $account_fields ) ) {
				if ( isset( $account_fields['signup_email'] ) ) {
					$fields_labels['signup_email'] = $account_fields['signup_email'];
				}
				if ( isset( $account_fields['signup_email_confirm'] ) ) {
					$fields_labels['signup_email_confirm'] = $account_fields['signup_email_confirm'];
				}
			}
		}

		// Throw the error if the $signup_fields is not empty.
		if ( ! empty( $signup_fields ) ) {
			// Get the field labels from the all required xprofile fields.
			if (
				'twitter' !== $provider_id &&
				! bb_enable_additional_sso_name()
			) {
				$signup_fields = $signup_auto_fill_arr;
			}
			foreach ( $signup_fields as $field_id ) {
				$field = xprofile_get_field( $field_id );
				if ( ! empty( $field ) ) {
					$fields_labels[ $field_id ] = $field->name;
				}
			}
		}

		if ( ! empty( $fields_labels ) ) {
			// Get the labels from the $fields_labels array.
			// For signup_email and signup_email_confirm, we need to get the label from the account_details group.
			$all_fields_labels = array_map( function ( $item ) {
				return is_array( $item ) && isset( $item['label'] ) ? $item['label'] : $item;
			}, $fields_labels );
			$signup_fields_msg = apply_filters(
				'bb_sso_register_signup_fields_not_found',
				sprintf(
				/* translators: %1$s: required fields list, %2$s: required fields list */
					'<div class="bb-sso-reg-error"><p>%1$s </p>%2$s</div>',
					esc_html__( 'Please fill in the required fields to complete your registration:', 'buddyboss-pro' ),
					'<ul><li>' . implode( '</li><li>', array_map( function ( $label ) {
						return '<strong>' . esc_html( $label ) . '</strong>';
					}, $all_fields_labels ) ) . '</li></ul>'
				)
			);

			// If the nickname is not valid, then add it to the $fields_labels array.
			$nickname_field = xprofile_get_field( $nickname_id );
			if ( ! empty( $nickname_field ) ) {
				$generated_nickname = $this->generate_unique_username( $first_name . $last_name );
				if ( ! empty( $generated_nickname ) && ! validate_username( $generated_nickname ) ) {
					// Add new message using efficient string manipulation.
					$invalid_nickname_message = sprintf( 
						/* translators: %s: field name */
						__( 'Invalid <strong>%s</strong>. Only "a-z", "0-9", "-", "_" and "." are allowed.', 'buddyboss-pro' ),
						esc_html( $nickname_field->name ) 
					);
					
					// Insert new paragraph before closing div using shared helper function.
					$signup_fields_msg = bb_sso_append_error_to_signup_div( $signup_fields_msg, $invalid_nickname_message );

					$signup_fields_msg = apply_filters( 'bb_sso_register_signup_invalid_nickname_error', $signup_fields_msg );

					$response[ 'signup_fields_invalid_nickname_message' ] = $invalid_nickname_message;
				}
			}

			$valid_error[ 'signup_fields_not_found' ] = $signup_fields_msg;
		}

		// Throw the error if the $valid_error is not empty.
		if ( ! empty( $valid_error ) ) {
			$errors = new WP_Error();
			foreach ( $valid_error as $key => $msg ) {
				$errors->add( $key, $msg );
				$icon = 'success';
				if ( 'twitter' === $provider_id && isset( \BBSSO\BB_SSO_Notices::$notices['info'] ) ) {
					$icon = 'info';
				}
				\BBSSO\BB_SSO_Notices::add_error( $errors->get_error_message(), $icon );
			}

			$query_string = array();

			if ( in_array( $first_name_id, $signup_auto_fill_arr, true ) ) {
				$query_string[ 'field_' . $first_name_id ] = $first_name;
				$response['fields'][]                      = 'first_name';
			}
			if ( in_array( $last_name_id, $signup_auto_fill_arr, true ) ) {
				$query_string[ 'field_' . $last_name_id ] = $last_name;
				$response['fields'][]                     = 'last_name';
			}

			if ( in_array( $nickname_id, $signup_auto_fill_arr, true ) ) {
				$query_string[ 'field_' . $nickname_id ] = $generated_nickname;
				$response['fields'][]                    = 'nickname';
			}

			$query_string['signup_email'] = $email;
			$email_opt                    = function_exists( 'bp_register_confirm_email' ) && true === bp_register_confirm_email() ? true : false;
			$pass_opt                     = function_exists( 'bp_register_confirm_password' ) && true === bp_register_confirm_password() ? true : false;
			if ( $email_opt ) {
				$query_string['signup_email_confirm'] = $email;
			}

			// SSO type.
			$query_string['sso_type'] = $this->provider->get_db_id();

			// SSO Identifier.
			$query_string['identifier'] = $this->get_auth_user_data( 'id' );

			$query_string['confirm_email_on']        = $email_opt;
			$query_string['signup_password']         = 1;
			$query_string['signup_password_confirm'] = $pass_opt;

			$bb_sso_login_url = BB_SSO::get_login_url();

			if ( bp_enable_site_registration() ) {
				$bb_sso_login_url = add_query_arg( $query_string, bp_get_signup_page() );
			}

			$response['allow_signup']         = false;
			$response['message']              = current( $valid_error );
			$response['redirect_url']         = $bb_sso_login_url;
			$response['require_fields_label'] = $fields_labels;
		}

		return $response;
	}

	/**
	 * Function to return unique username.
	 *
	 * @since 2.6.60
	 *
	 * @param string $username Username to be checked for uniqueness.
	 *
	 * @return string Username, with possible number trailing, if clashes exist.
	 */
	public function generate_unique_username( $username ) {

		$username = sanitize_title( $username );

		static $i;
		if ( null === $i ) {
			$i = 1;
		} else {
			$i++;
		}
		if ( ! username_exists( $username ) ) {
			return $username;
		}
		$new_username = sprintf( '%s-%s', $username, $i );
		if ( ! username_exists( $new_username ) ) {
			return $new_username;
		} else {
			return $this->generate_unique_username( $username );
		}
	}

	/**
	 * Sanitizes the provided username for registration.
	 *
	 * This function takes a username input, transforms it to lowercase, removes any whitespace,
	 * and appends a user prefix configured in the provider's settings. It then sanitizes the
	 * resulting username and checks if it is valid. If the username is empty or invalid,
	 * the function returns false; otherwise, it returns the sanitized username.
	 *
	 * @since 2.6.30
	 *
	 * @param string $username The username provided by the user for registration.
	 *
	 * @return string|false The sanitized username if valid, false otherwise.
	 */
	protected function sanitize_user_name( $username ) {
		if ( empty( $username ) ) {
			return false;
		}

		$username = strtolower( $username );

		$username = preg_replace( '/\s+/', '', $username );

		$sanitized_user_login = sanitize_user( $this->provider->settings->get( 'user_prefix' ) . $username, true );

		if ( empty( $sanitized_user_login ) ) {
			return false;
		}

		if ( ! validate_username( $sanitized_user_login ) ) {
			return false;
		}

		return $sanitized_user_login;
	}

	/**
	 * Finalizes and transforms user data into an array format.
	 *
	 * This method takes user data as input, creates an instance of the
	 * `BB_SSO_User_Data` class with the provided data, the current object,
	 * and the associated provider. It then converts this user data into an
	 * array representation for further processing or storage.
	 *
	 * @since 2.6.30
	 *
	 * @param mixed $user_data The user data to be finalized, which can be of any type.
	 *
	 * @throws Exception Throws an exception if user data processing fails.
	 *
	 * @return array An array representation of the finalized user data.
	 */
	public function finalize_user_data( $user_data ) {

		$data = new BB_SSO_User_Data( $user_data, $this, $this->provider );

		return $data->to_array();
	}

	/**
	 * Redirects the user to the appropriate login location.
	 *
	 * The user will be redirected to:
	 * - The fixed redirect URL if it is set.
	 * - The location where the login occurred if a redirect is specified in the URL.
	 * - The default redirect URL if it is set and no redirect was specified in the URL.
	 *
	 * @since 2.6.30
	 *
	 * @param bool $notice Optional. Indicates whether to show a notice during redirection. Default is false.
	 */
	public function redirect_to_last_location_login( $notice = false ) {

		$this->provider->redirect_to_last_location( $notice, 'login' );
	}

	/**
	 * Registers an error by deleting persistent login data.
	 *
	 * This method is used to clean up any stored login data in the event of a login error,
	 * ensuring that stale or incorrect data does not persist between login attempts.
	 *
	 * @since 2.6.30
	 */
	private function register_error() {

		$this->provider->delete_login_persistent_data();
	}

	/**
	 * Automatically links the current user account with the provider account.
	 *
	 * If auto-linking is enabled and allowed, this method will attempt to link the provided
	 * user ID with the corresponding provider user ID. If the linking is successful, it returns true.
	 * If the linking fails due to an existing account, an error message is generated.
	 *
	 * @since 2.6.30
	 *
	 * @param int    $user_id          The ID of the current user to link.
	 * @param string $provider_user_id The ID of the user from the provider to link with.
	 *
	 * @return bool True on successful linking; false if linking is not allowed or fails.
	 */
	public function auto_link( $user_id, $provider_user_id ) {

		$is_auto_link_allowed = true;
		$is_auto_link_allowed = apply_filters( 'bb_sso_' . $this->provider->get_id() . '_auto_link_allowed', $is_auto_link_allowed, $this->provider, $user_id );
		if ( $is_auto_link_allowed ) {
			$is_link_successful = $this->provider->link_user_to_provider_identifier( $user_id, $provider_user_id );
			if ( $is_link_successful ) {
				return $is_link_successful;
			} else {
				$this->provider->delete_login_persistent_data();
				$already_linked_message = apply_filters(
					'bb_sso_already_linked_error_message',
					sprintf(
					/* translators: %1$s: provider name, %2$s: provider name */
						'<p>%s</p>',
						sprintf(
							__( 'We found a user with your %1$s email address. Unfortunately, it belongs to another %2$s account, so we are unable to log you in. Please use the linked %1$s account or log in with your password!', 'buddyboss-pro' ),
							$this->provider->get_label(),
							$this->provider->get_label()
						)
					)
				);
				BB_SSO_Notices::add_error( $already_linked_message );  // Display an error message with toasts if redirection is not on the login page.
			}
		}

		return false;
	}

	/**
	 * Handles user login and activates their account if necessary.
	 *
	 * This method verifies whether login is allowed for the user. If permitted, it attempts to:
	 * - Activate any unactivated signups associated with the user.
	 * - Set the current user and authentication cookies.
	 * - Log the login date and trigger relevant actions.
	 *
	 * If login is not allowed, it will delete any persistent login data, add an error message, and
	 * redirect to a specified URL if provided.
	 *
	 * @since 2.6.30
	 * @since 2.6.60 Introduced the `$allow_custom` parameter.
	 *
	 * @global bool $auth_secure_cookie A flag indicating if a secure cookie should be used for authentication.
	 *
	 * @param bool  $allow_custom       Optional. Indicates whether to allow custom registration fields. Default is
	 *                                  false.
	 * @param int   $user_id            The ID of the user attempting to log in.
	 */
	protected function login( $user_id, $allow_custom = false ) {
		global $wpdb;
		$user = new WP_User( $user_id );

		$this->user_id = $user_id;

		$is_login_allowed = apply_filters( 'bb_sso_' . $this->provider->get_id() . '_is_login_allowed', true, $this->provider, $user_id );

		if ( $is_login_allowed ) {

			if ( class_exists( 'BP_Signup' ) ) {
				// Look for the unactivated signup corresponding to the login name.
				$signup = BP_Signup::get( array( 'user_login' => sanitize_user( $user->user_login ) ) );

				// If the signup found then activate it.
				if ( ! empty( $signup['signups'] ) && ! empty( $signup['signups'][0] ) && isset( $signup['signups'][0]->signup_id ) ) {
					$signup_id = $signup['signups'][0]->signup_id;
					BP_Signup::activate( array( $signup_id ) );
				}
			}

			wp_set_current_user( $user_id );

			$secure_cookie = is_ssl();
			$secure_cookie = apply_filters( 'secure_signon_cookie', $secure_cookie, array() );
			global $auth_secure_cookie; // XXX ugly hack to pass this to wp_authenticate_cookie.

			$auth_secure_cookie = $secure_cookie; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			wp_set_auth_cookie( $user_id, true, $secure_cookie );
			$user_info = get_userdata( $user_id );

			// For registration with required fields.
			if ( true === $allow_custom ) {
				$identifier = isset( $_POST['bb_sso_identifier'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_sso_identifier'] ) ) : '';
				$sso_type   = isset( $_POST['bb_sso_type'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_sso_type'] ) ) : '';

				// Get the first_name and last_name from the bb_social_sign_on_users table and set in the xprofile fields.
				$sso_user = $wpdb->get_results( $wpdb->prepare( "SELECT first_name, last_name FROM {$wpdb->base_prefix}bb_social_sign_on_users WHERE type = %s AND identifier = %s", $sso_type, $identifier ) );

				// Check not empty $sso_user and set the first and last name then set the xprofile field value.
				if ( ! empty( $sso_user ) ) {
					$first_name = $sso_user[0]->first_name;
					$last_name  = $sso_user[0]->last_name;
					if ( ! empty( $first_name ) ) {
						update_user_meta( $user_id, 'first_name', $first_name );
						xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user_id, $first_name );
					}
					if ( ! empty( $last_name ) ) {
						update_user_meta( $user_id, 'last_name', $last_name );
						xprofile_set_field_data( bp_xprofile_lastname_field_id(), $user_id, $last_name );
					}
				}

				$wpdb->update(
					$wpdb->prefix . 'bb_social_sign_on_users',
					array(
						'wp_user_id'    => $user_id,
						'link_date'     => current_time( 'mysql' ),
						'register_date' => current_time( 'mysql' ),
						'login_date'    => current_time( 'mysql' ),
					),
					array(
						'type'       => $sso_type,
						'identifier' => $identifier,
					),
					array(
						'%d',
						'%s',
						'%s',
						'%s',
					)
				);
			}

			$this->provider->log_login_date( $user_id );

			do_action( 'bb_sso_before_wp_login' );
			do_action( 'wp_login', $user_info->user_login, $user_info );

			$this->finish_login();
		} else {
			$this->provider->delete_login_persistent_data();
			$login_disabled_message      = apply_filters( 'bb_sso_disabled_login_error_message', __( 'User login is currently not allowed.', 'buddyboss-pro' ) );
			$login_disabled_redirect_url = apply_filters( 'bb_sso_disabled_login_redirect_url', '' );
			$errors                      = new WP_Error();
			$errors->add( 'logindisabled', $login_disabled_message );
			if ( ! empty( $login_disabled_message ) ) {
				BB_SSO_Notices::clear();
				BB_SSO_Notices::add_error( $errors->get_error_message() ); // Display an error message with toasts if redirection is not on the login page.
			}
			do_action( 'wp_login_failed', $user->get( 'user_login' ), $errors );

			if ( ! empty( $login_disabled_redirect_url ) ) {
				$this->provider->redirect_with_authentication_error( $login_disabled_redirect_url );
			}
		}

		$this->provider->redirect_to_login_form();
	}

	/**
	 * Finalizes the login process and triggers relevant actions.
	 *
	 * This method is called after the user has been successfully authenticated. It triggers the
	 * 'bb_sso_login' action and any provider-specific login actions. Finally, it redirects the user
	 * to their last login location.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	protected function finish_login() {

		do_action( 'bb_sso_login', $this->user_id, $this->provider );
		do_action( 'bb_sso_' . $this->provider->get_id() . '_login', $this->user_id, $this->provider, $this->data );

		$this->redirect_to_last_location_login();
	}

	/**
	 * Sets a notification for users regarding the use of a randomly generated password.
	 *
	 * This method updates the user option 'default_password_nag' to true, indicating that
	 * the user should be informed about the usage of a random password upon their next login.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id The ID of the user for whom the notification is being set.
	 *
	 * @return void
	 */
	public function register_complete_default_password_nag( $user_id ) {
		update_user_option( $user_id, 'default_password_nag', true, true );
	}

	/**
	 * Completes the user registration process.
	 *
	 * This method retrieves user data such as name, first name, and last name, updates
	 * the user meta, links the user to the provider, and sets roles. It also sends
	 * notifications about the registration to the selected roles and logs the user in
	 * if the registration is successful.
	 *
	 * @since 2.6.30
	 * @since 2.6.60 Introduced the `$allow_custom` parameter.
	 *
	 * @param int|string|WP_Error $user_id      The ID of the newly registered user, or
	 *                                          WP_Error if registration failed.
	 * @param bool                $allow_custom Optional. Indicates whether to allow custom registration fields.
	 *                                          Default is false.
	 *
	 * @return bool True on successful registration, false on failure.
	 */
	public function register_complete( $user_id, $allow_custom = false ) {
		$user_id = (int) $user_id;
		if ( is_wp_error( $user_id ) || 0 === $user_id ) {
			/** Registration failed */
			$this->register_error();

			return false;
		}
		$provider_user_id    = empty( $this->get_auth_user_data( 'id' ) ) ? ( ! empty( $_GET['identifier'] ) ? sanitize_text_field( wp_unslash( $_GET['identifier'] ) ) : '' ) : $this->get_auth_user_data( 'id' );
		$provider_first_name = $this->get_auth_user_data( 'first_name' );
		$provider_last_name  = $this->get_auth_user_data( 'last_name' );
		if ( ! empty( $provider_user_id ) ) {
			global $wpdb;
			$provider_user_data = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT first_name, last_name FROM `' . $this->provider->table_name . '` WHERE type = %s AND identifier = %s',
					array(
						$this->provider->get_id(),
						$provider_user_id,
					)
				)
			);

			if ( ! empty( $provider_user_data ) ) {
				$provider_first_name = $provider_user_data->first_name;
				$provider_last_name  = $provider_user_data->last_name;
			}
		}
		if ( bb_enable_additional_sso_name() ) {
			if ( ! empty( $provider_first_name ) ) {
				update_user_meta( $user_id, 'billing_first_name', $provider_first_name );
			}

			if ( ! empty( $provider_last_name ) ) {
				update_user_meta( $user_id, 'billing_last_name', $provider_last_name );
			}
		}

		update_user_option( $user_id, 'default_password_nag', true, true );

		$this->provider->link_user_to_provider_identifier( $user_id, $provider_user_id, true, $provider_first_name, $provider_last_name );

		do_action( 'bb_sso_registration_store_extra_input', $user_id, $this->user_extra_data );

		do_action( 'bb_sso_register_new_user', $user_id, $this->provider );
		do_action( 'bb_sso_' . $this->provider->get_id() . '_register_new_user', $user_id, $this->provider );

		$this->provider->delete_login_persistent_data();

		do_action( 'register_new_user', $user_id );

		// BuddyPress - add register activity to accounts registered with social login.
		if ( class_exists( 'BuddyPress', false ) ) {
			if ( bp_is_active( 'activity' ) ) {
				if ( ! function_exists( 'bp_core_new_user_activity' ) ) {
					require_once buddypress()->plugin_dir . '/bp-members/bp-members-activity.php';
				}
				bp_core_new_user_activity( $user_id );
			}
		}

		$this->should_auto_login = true;
		$this->allow_custom      = $allow_custom;

		return true;
	}

	/**
	 * Performs automatic login for the user after registration.
	 *
	 * This method checks if automatic login is enabled and calls the login method
	 * for the given user ID.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id The ID of the user to be logged in.
	 *
	 * @return void
	 */
	public function do_auto_login( $user_id ) {
		if ( $this->should_auto_login ) {
			$this->login( $user_id, $this->allow_custom );
		}
	}

	/**
	 * Retrieves the provider instance.
	 *
	 * This method returns the instance of the BB_SSO_Provider associated
	 * with this class, allowing access to provider-specific functionality.
	 *
	 * @since 2.6.30
	 *
	 * @return BB_SSO_Provider The provider instance.
	 */
	public function get_provider() {
		return $this->provider;
	}

	/**
	 * Syncs the user profile data with the provider after registration.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id The ID of the user.
	 *
	 * @return void
	 */
	public function sync_profile_register_new_user( $user_id ) {

		$this->sync_profile_user( $user_id );

		if ( ! empty( $user_id ) ) {
			$this->remove_profile_sync_actions();
		}
	}

	/**
	 * Syncs the user profile data with the provider.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id The ID of the user.
	 *
	 * @return void
	 */
	public function sync_profile_user( $user_id ) {
		$this->provider->sync_profile( $user_id, $this->provider, $this->data );
	}

	/**
	 * Removes the actions used to sync the user profile data with the provider.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	public function remove_profile_sync_actions() {

		/**
		 * Prevent multiple profile sync in the same request.
		 */
		remove_action(
			'bb_sso_' . $this->provider->get_id() . '_register_new_user',
			array(
				$this,
				'sync_profile_register_new_user',
			)
		);

		remove_action(
			'bb_sso_' . $this->provider->get_id() . '_login',
			array(
				$this,
				'sync_profile_login',
			)
		);

		remove_action(
			'bb_sso_' . $this->provider->get_id() . '_link_user',
			array(
				$this,
				'sync_profile_link_user',
			)
		);
	}

	/**
	 * Syncs the user profile data with the provider after login.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id User id.
	 */
	public function sync_profile_login( $user_id ) {

		$this->sync_profile_user( $user_id );

		if ( ! empty( $user_id ) ) {
			$this->remove_profile_sync_actions();
		}
	}

	/**
	 * Syncs the user profile data with the provider after linking the user account.
	 *
	 * @since 2.6.30
	 *
	 * @param int  $user_id             The ID of the user.
	 * @param int  $provider_identifier The ID of the user from the provider.
	 * @param bool $is_register         Indicates if the user is being registered.
	 */
	public function sync_profile_link_user( $user_id, $provider_identifier, $is_register ) {

		/**
		 * When the registration happens with social login, the linking happens before we trigger the register specific action.
		 * This could make the profile being synced even if the registration specific action is disabled.
		 */
		if ( ! $is_register ) {
			$this->sync_profile_user( $user_id );

			if ( ! empty( $user_id ) ) {
				$this->remove_profile_sync_actions();
			}
		}
	}

	/**
	 * Retrieves the user data from identifier.
	 *
	 * This method retrieves the user data from the identifier which store in a database
	 * and returns it as an array.
	 *
	 * @since 2.6.60
	 *
	 * @param string $unique_identifier The unique identifier of the user.
	 * @param string $provider_id       The ID of the provider.
	 *
	 * @return array The user data from the provider.
	 */
	public function bb_sso_get_name_from_identifier( $unique_identifier, $provider_id ) {
		global $wpdb;
		$sso_user_data = array();
		if ( ! empty( $unique_identifier ) ) {
			$fetched_data = $wpdb->get_row( $wpdb->prepare( "SELECT first_name, last_name FROM {$wpdb->prefix}bb_social_sign_on_users WHERE type = %s AND identifier = %s", $provider_id, $unique_identifier ) );
			if ( ! empty( $fetched_data ) ) {
				$sso_user_data = array(
					'first_name' => $fetched_data->first_name,
					'last_name'  => $fetched_data->last_name,
				);
			}
		}

		return $sso_user_data;
	}
}
