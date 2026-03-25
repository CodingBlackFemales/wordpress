<?php
/**
 * Class BB_Social_Login_Admin
 *
 * Handles the admin functionality for the BuddyBoss Social Login feature,
 * including saving provider settings, enabling/disabling providers, and validating settings.
 *
 * @since 2.6.30
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Social_Login_Admin
 *
 * @since 2.6.30
 */
class BB_Social_Login_Admin {

	/**
	 * Initializes the admin class by setting up action and filter hooks.
	 *
	 * @since 2.6.30
	 */
	public static function init() {
		add_action( 'admin_init', 'BB_Social_Login_Admin::admin_init' );
		add_filter( 'bb_sso_update_settings_validate_bb_social_login', 'BB_Social_Login_Admin::validate_settings', 10, 2 );
	}

	/**
	 * Sets up AJAX actions for saving social login settings and enabling/disabling providers.
	 *
	 * @since 2.6.30
	 */
	public static function admin_init() {
		add_action( 'wp_ajax_bb-social-login', 'BB_Social_Login_Admin::bb_save_sso_orders' );
		add_action( 'wp_ajax_bb_sso_save_settings', 'BB_Social_Login_Admin::bb_sso_save_settings' );
		add_action( 'wp_ajax_bb_sso_enable_provider', 'BB_Social_Login_Admin::bb_sso_enable_provider' );
	}

	/**
	 * Saves the settings for a specific social login provider via AJAX.
	 *
	 * Checks user capability and nonce, retrieves provider ID, and attempts to save
	 * settings for the provider.
	 *
	 * @since 2.6.30
	 */
	public static function bb_sso_save_settings() {
		if (
			current_user_can( BB_SSO::get_required_capability() ) &&
			check_ajax_referer( 'bb-sso-admin', 'nonce' )
		) {
			$provider_id = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : '';

			if ( empty( $provider_id ) ) {
				wp_send_json_error( __( 'Invalid provider.', 'buddyboss-pro' ) );
			}

			if ( isset( BB_SSO::$providers[ $provider_id ] ) ) {
				/**
				 * Verify the setting for Google provider because allowing to save
				 * an android and iOS client id and secret.
				 */
				$verify_setting = true;
				if ( 'google' === $provider_id ) {
					$saved_settings      = BB_SSO::$providers[ $provider_id ]->settings->get_all();
					$saved_client_id     = $saved_settings['client_id'];
					$saved_client_secret = $saved_settings['client_secret'];

					$posed_client_id     = $_POST['client_id'];
					$posed_client_secret = $_POST['client_secret'];

					/**
					 * If the client id and secret are the same for web, then no need to verify the setting.
					 * Also set the tested flag to based on the saved settings.
					 */
					if ( $saved_client_id === $posed_client_id && $saved_client_secret === $posed_client_secret ) {
						$verify_setting  = false;
						$_POST['tested'] = (int) $saved_settings['tested'];
					}
				}
				if ( BB_SSO::$providers[ $provider_id ]->settings->update( $_POST ) ) {
					wp_send_json_success(
						array(
							'message'  => __( 'Settings saved ', 'buddyboss-pro' ),
							'redirect' => $verify_setting,
						)
					);
				} else {
					wp_send_json_error( __( 'Error saving provider settings.', 'buddyboss-pro' ) );
				}
			} else {
				wp_send_json_error( __( 'Invalid provider.', 'buddyboss-pro' ) );
			}
		}

		wp_send_json_error();
	}

	/**
	 * Enables or disables a social login provider based on the current state via AJAX.
	 *
	 * Checks nonce and retrieves provider and state, then enables or disables
	 * the provider accordingly.
	 *
	 * @since 2.6.30
	 */
	public static function bb_sso_enable_provider() {
		check_ajax_referer( 'bb-sso-admin', 'nonce' );

		$provider_id = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : '';
		$state       = isset( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : '';
		if ( isset( BB_SSO::$providers[ $provider_id ] ) && 1 === (int) BB_SSO::$providers[ $provider_id ]->settings->get( 'tested' ) ) {
			if ( 'disabled' === $state ) {
				BB_SSO::enable_provider( $provider_id );
			} elseif ( 'enabled' === $state ) {
				BB_SSO::disable_provider( $provider_id );
			}

			wp_send_json_success();
		}
	}

	/**
	 * Saves the order of social login providers via AJAX.
	 *
	 * Checks user capability and nonce, retrieves the view and ordering,
	 * then attempts to save the new provider order.
	 *
	 * @since 2.6.30
	 */
	public static function bb_save_sso_orders() {
		check_ajax_referer( 'bb-sso-admin', 'nonce' );
		if ( current_user_can( BB_SSO::get_required_capability() ) ) {
			$view = ! empty( $_POST['view'] ) ? sanitize_text_field( wp_unslash( $_POST['view'] ) ) : '';
			if ( empty( $view ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'View is empty.', 'buddyboss-pro' ),
					)
				);
			}

			if ( 'orderProviders' !== $view ) {
				wp_send_json_error(
					array(
						'message' => __( 'Providers are not valid.', 'buddyboss-pro' ),
					)
				);
			}

			if ( ! empty( $_POST['ordering'] ) ) {
				if (
					BB_SSO::$settings->update(
						array(
							'ordering' => $_POST['ordering'],
						)
					)
				) {
					wp_send_json_success(
						array(
							'message' => __( 'Saved Successfully.', 'buddyboss-pro' ),
						)
					);
				} else {
					wp_send_json_error(
						array(
							'message' => __( 'Error saving providers order.', 'buddyboss-pro' ),
						)
					);
				}
			} else {
				wp_send_json_error(
					array(
						'message' => __( 'Error saving order.', 'buddyboss-pro' ),
					)
				);
			}
		}
	}

	/**
	 * Validates and sanitizes social login settings before saving them.
	 *
	 * @since 2.6.30
	 *
	 * @param array $new_data    The new settings data to be saved.
	 * @param array $posted_data The raw posted settings data from the user.
	 *
	 * @return array Sanitized settings data.
	 */
	public static function validate_settings( $new_data, $posted_data ) {

		foreach ( $posted_data as $key => $value ) {
			switch ( $key ) {
				case 'enabled':
				case 'ordering':
					if ( is_array( $value ) ) {
						$new_data[ $key ] = $value;
					}
					break;

			}
		}

		return $new_data;
	}
}
