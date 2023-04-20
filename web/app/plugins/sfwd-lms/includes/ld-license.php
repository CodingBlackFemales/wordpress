<?php
/**
 * LearnDash License utility functions.
 *
 * @since 4.3.1
 *
 * @package LearnDash\License
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const LEARNDASH_HUB_LICENSE_CACHE_OPTION  = 'learndash_hub_license_result';
const LEARNDASH_HUB_LICENSE_CACHE_TIMEOUT = 6 * HOUR_IN_SECONDS;
const LEARNDASH_LICENSE_KEY               = 'nss_plugin_license_sfwd_lms';
const LEARNDASH_LICENSE_EMAIL_KEY         = 'nss_plugin_license_email_sfwd_lms';

/**
 * Updates the LearnDash Hub license cache when the license is verified.
 *
 * @since 4.5.0
 *
 * @param WP_Error|bool $license_response The license response.
 *
 * @return void
 */
add_action(
	'learndash_licensing_management_license_verified',
	function( $license_response ) {
		update_option(
			LEARNDASH_HUB_LICENSE_CACHE_OPTION,
			array(
				time(),
				! is_wp_error( $license_response ),
			)
		);
	}
);

/**
 * Removes the license cache after the license logout.
 *
 * @since 4.5.0
 *
 * @return void
 */
add_action(
	'learndash_licensing_management_license_logout',
	function () {
		delete_option( LEARNDASH_HUB_LICENSE_CACHE_OPTION );
	}
);

/**
 * Check if LearnDash Hub is installed and active.
 *
 * @since 4.3.1
 *
 * @return bool True if the LearnDash Hub is installed and active. False otherwise.
 */
function learndash_is_learndash_hub_active() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	return function_exists( 'is_plugin_active' ) && is_plugin_active( 'learndash-hub/learndash-hub.php' );
}

/**
 * Validate a license key.
 *
 * @since 4.3.1
 *
 * @param string $email The email address of the license key.
 * @param string $license_key The license key.
 *
 * @return bool True if the license key is valid. False otherwise.
 */
function learndash_validate_hub_license( string $email, string $license_key ) {
	if ( ! learndash_is_learndash_hub_active() || ! class_exists( 'LearnDash\Hub\Component\API' ) ) {
		delete_option( LEARNDASH_HUB_LICENSE_CACHE_OPTION );
		return false; // legacy license system is not supported.
	}

	if ( empty( $email ) || empty( $license_key ) ) {
		delete_option( LEARNDASH_HUB_LICENSE_CACHE_OPTION );
		return false;
	}

	$hub_api           = new LearnDash\Hub\Component\API();
	$validation_result = $hub_api->verify_license( $email, $license_key );

	$license_valid = ! is_wp_error( $validation_result ) && $validation_result === true;
	update_option( LEARNDASH_HUB_LICENSE_CACHE_OPTION, array( time(), $license_valid ) );

	return $license_valid;
}

/**
 * Check if the license is valid.
 *
 * @since 4.3.1
 *
 * @return bool True if the license is valid. False otherwise.
 */
function learndash_is_license_hub_valid() {
	$license_valid = get_option( LEARNDASH_HUB_LICENSE_CACHE_OPTION );

	if (
		! is_array( $license_valid ) ||
		count( $license_valid ) !== 2 ||
		$license_valid[0] < time() - LEARNDASH_HUB_LICENSE_CACHE_TIMEOUT
	) {
		// recheck the license.
		return learndash_validate_hub_license(
			get_option( LEARNDASH_LICENSE_EMAIL_KEY, '' ),
			get_option( LEARNDASH_LICENSE_KEY, '' )
		);
	}

	return $license_valid[1];
}

/**
 * Get the last check time of the LearnDash Hub license status.
 *
 * @since 4.3.1
 *
 * @return int The last check time or 0 if never checked.
 */
function learndash_get_last_license_hub_check_time() {
	$license_valid = get_option( LEARNDASH_HUB_LICENSE_CACHE_OPTION );

	if (
		! is_array( $license_valid ) ||
		count( $license_valid ) !== 2
	) {
		return 0;
	}

	return intval( $license_valid[0] );
}
