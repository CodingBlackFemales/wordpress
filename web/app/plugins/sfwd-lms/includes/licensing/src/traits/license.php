<?php
/**
 * LearnDash License trait.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Hub
 */

namespace LearnDash\Hub\Traits;

use LearnDash\Hub\Component\API;

defined( 'ABSPATH' ) || exit;

trait License {
	/**
	 * The option for license status
	 *
	 * @return string
	 */
	private function get_license_status_option_name() {
		return 'learndash_hub_license_status';
	}

	/**
	 * The license status cache period
	 *
	 * @return int
	 */
	private function get_license_status_cache_duration() {
		return DAY_IN_SECONDS;
	}

	/**
	 * The option name for license key
	 *
	 * @return string
	 */
	public function get_license_key_option_name() {
		return 'nss_plugin_license_sfwd_lms';
	}

	/**
	 * The option name for license email.
	 *
	 * @return string
	 */
	public function get_hub_email_option_name() {
		return 'nss_plugin_license_email_sfwd_lms';
	}

	/**
	 * Get the license key
	 *
	 * @return false|string
	 */
	public function get_license_key() {
		$license_key = get_site_option( $this->get_license_key_option_name() );

		if ( empty( $license_key ) && file_exists( LEARNDASH_LMS_PLUGIN_DIR . '/auth-token.php' ) ) {
			try {
				$license_key = include LEARNDASH_LMS_PLUGIN_DIR . '/auth-token.php';
				$license_key = is_scalar( $license_key ) ? strval( $license_key ) : false;
				$license_key = empty( $license_key ) ? false : $license_key;
			} catch ( \Exception $e ) {
				$license_key = false;
			}
		}

		return $license_key;
	}

	/**
	 * Get the register email.
	 *
	 * @return false|string
	 */
	public function get_hub_email() {
		$email = get_site_option( $this->get_hub_email_option_name() );

		if ( empty( $email ) && file_exists( LEARNDASH_LMS_PLUGIN_DIR . '/auth-email.php' ) ) {
			try {
				$email = include LEARNDASH_LMS_PLUGIN_DIR . '/auth-email.php';
				$email = is_scalar( $email ) ? strval( $email ) : false;
				$email = empty( $email ) ? false : $email;
			} catch ( \Exception $e ) {
				$email = false;
			}
		}

		return $email;
	}

	/**
	 * Gets the version of LearnDash Core.
	 *
	 * @since 4.18.0
	 *
	 * @return string
	 */
	public function get_learndash_core_version(): string {
		return defined( 'LEARNDASH_VERSION' ) && is_scalar( LEARNDASH_VERSION ) ? strval( LEARNDASH_VERSION ) : '';
	}

	/**
	 * Return the headers that require for API side.
	 *
	 * @return array
	 */
	public function get_auth_headers(): array {
		return array(
			'Learndash-Site-Url'        => network_site_url(),
			'Learndash-Hub-License-Key' => $this->get_license_key(),
			'Learndash-Hub-Email'       => $this->get_hub_email(),
			'Learndash-Version'         => $this->get_learndash_core_version(),
		);
	}

	/**
	 * Update the license status.
	 *
	 * @since 4.18.0
	 *
	 * @param mixed  $license_response The response from the API.
	 * @param string $license_email    The license email.
	 * @param string $license_key      The license key.
	 *
	 * @return void
	 */
	public function update_license_status( $license_response, $license_email, $license_key ) {
		update_option(
			$this->get_license_status_option_name(),
			array(
				time(),
				$license_response,
			)
		);

		if ( is_wp_error( $license_response ) ) {
			return;
		}

		update_site_option( $this->get_license_key_option_name(), $license_key );
		update_site_option( $this->get_hub_email_option_name(), $license_email );
	}

	/**
	 * Get the license status.
	 *
	 * @since 4.18.0
	 *
	 * @return \WP_Error|bool|string The license status. Empty string if the license status is not available.
	 */
	public function get_license_status() {
		$license_status = get_option( $this->get_license_status_option_name() );

		if (
		! is_array( $license_status ) ||
		count( $license_status ) !== 2 ||
		$license_status[0] < time() - $this->get_license_status_cache_duration()
		) {
			return '';
		}

		return $license_status[1];
	}

	/**
	 * Check if the current site is signed on.
	 *
	 * @return bool
	 */
	public function is_signed_on(): bool {
		$license_email = $this->get_hub_email();
		$license_key   = $this->get_license_key();

		if ( empty( $license_email ) || empty( $license_key ) ) {
			return false;
		}

		return ! is_wp_error( ( new API() )->verify_license( $license_email, $license_key ) );
	}

	/**
	 * Clear signed data.
	 */
	public function clear_auth() {
		delete_site_option( $this->get_license_key_option_name() );
		delete_site_option( $this->get_hub_email_option_name() );
		delete_site_option( $this->get_license_status_option_name() );

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();
		global $wp_filesystem;

		if ( $wp_filesystem->is_file( LEARNDASH_LMS_PLUGIN_DIR . '/auth-token.php' ) ) {
			$wp_filesystem->delete( LEARNDASH_LMS_PLUGIN_DIR . '/auth-token.php' );
		}

		if ( $wp_filesystem->is_file( LEARNDASH_LMS_PLUGIN_DIR . '/auth-email.php' ) ) {
			$wp_filesystem->delete( LEARNDASH_LMS_PLUGIN_DIR . '/auth-email.php' );
		}
	}
}
