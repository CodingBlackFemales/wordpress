<?php
/**
 * BuddyBoss SSO User Data
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO
 */

use BBSSO\BB_SSO_Notices;

/**
 * Class BB_SSO_User_Data
 *
 * @since 2.6.30
 */
class BB_SSO_User_Data {

	/**
	 * User data.
	 *
	 * @since 2.6.30
	 *
	 * @var array
	 */
	private $user_data;

	/**
	 * Provider.
	 *
	 * @since 2.6.30
	 *
	 * @var BB_SSO_Provider
	 */
	private $provider;

	/**
	 * Errors.
	 *
	 * @since 2.6.30
	 *
	 * @var WP_Error
	 */
	private $errors;

	/**
	 * BB_SSO_User_Data constructor.
	 *
	 * Initializes the user data and provider, checks for additional required
	 * to be input, and handles user redirection based on the registration settings.
	 *
	 * @since 2.6.30
	 *
	 * @param array           $user_data   The data associated with the user.
	 * @param BB_SSO_User     $social_user The social user instance.
	 * @param BB_SSO_Provider $provider    The social login provider instance.
	 *
	 * @throws Exception If BuddyPress is detected and needs specific handling.
	 */
	public function __construct( $user_data, $social_user, $provider ) {

		$this->user_data = $user_data;
		$this->provider  = $provider;

		$this->errors    = new WP_Error();
		$this->user_data = apply_filters( 'bb_sso_registration_user_data', $this->user_data, $this->provider, $this->errors );

		if ( '' !== $this->errors->get_error_code() ) {
			$this->provider->delete_login_persistent_data();
			if ( '' !== $this->errors->get_error_message() ) {
				BB_SSO_Notices::add_error( $this->errors->get_error_message() ); // Display an error message with toasts if redirection is not on the login page.
			}

			$register_disabled_redirect_url = apply_filters( 'bb_sso_disabled_register_redirect_url', BB_SSO::get_login_url() );

			wp_safe_redirect( BB_SSO::enable_notice_for_url( $register_disabled_redirect_url ) );
			exit();
		}
	}

	/**
	 * Converts user data to an array.
	 *
	 * @since 2.6.30
	 *
	 * @return array The user data in array format.
	 */
	public function to_array() {
		return $this->user_data;
	}
}
