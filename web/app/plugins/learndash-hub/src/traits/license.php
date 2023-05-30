<?php

namespace LearnDash\Hub\Traits;

use LearnDash\Hub\Component\API;

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
		return get_site_option( $this->get_license_key_option_name() );
	}

	/**
	 * Get the register email.
	 *
	 * @return false|string
	 */
	public function get_hub_email() {
		return get_site_option( $this->get_hub_email_option_name() );
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
		);
	}

	/**
	 * Update the license status.
	 *
	 * @since 1.1.5
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
	 * @since 1.1.5
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
	}
}
