<?php
/**
 * BuddyBoss SSO Provider Admin.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO
 */

use BBSSO\BB_SSO_Notices;

defined( 'ABSPATH' ) || exit;

/**
 * Class BB_SSO_Provider_Admin
 *
 * @since 2.6.30
 */
class BB_SSO_Provider_Admin {

	/**
	 * Global path to /admin folder
	 *
	 * @since 2.6.30
	 *
	 * @var string Path to global /admin folder.
	 */
	public static $global_path;

	/**
	 * Provider instance.
	 *
	 * @since 2.6.30
	 *
	 * @var BB_SSO_Provider
	 */
	protected $provider;

	/**
	 * Admin path.
	 *
	 * @since 2.6.30
	 *
	 * @var string path to current providers /admin folder
	 */
	protected $path;

	/**
	 * BB_SSO_Provider_Admin constructor.
	 *
	 * Initializes the admin provider with the specified SSO provider.
	 *
	 * @since 2.6.30
	 *
	 * @param BB_SSO_Provider $provider The SSO provider instance.
	 */
	public function __construct( $provider ) {
		$this->provider = $provider;

		$this->path = $this->provider->get_path() . '/admin';

		add_filter(
			'bb_sso_update_settings_validate_' . $this->provider->get_option_key(),
			array(
				$this,
				'validate_settings',
			),
			10,
			2
		);
	}

	/**
	 * Get the SSO provider instance.
	 *
	 * @since 2.6.30
	 *
	 * @return BB_SSO_Provider The SSO provider instance.
	 */
	public function get_provider() {
		return $this->provider;
	}

	/**
	 * Validate the posted settings data.
	 *
	 * This method processes and validates the settings for the SSO provider,
	 * ensuring that all necessary data is correctly formatted and returned.
	 *
	 * @since 2.6.30
	 *
	 * @param array $new_data    The new settings data to be validated.
	 * @param array $posted_data The data that was posted for validation.
	 *
	 * @return mixed The validated settings data.
	 */
	public function validate_settings( $new_data, $posted_data ) {

		$new_data = $this->provider->validate_settings( $new_data, $posted_data );

		foreach ( $posted_data as $key => $value ) {

			switch ( $key ) {
				case 'settings_saved':
					$new_data[ $key ] = intval( $value ) ? 1 : 0;
					break;
				case 'oauth_redirect_url':
					$new_data[ $key ] = $value;
					break;
			}
		}

		return $new_data;
	}
}

// Set the global path for the admin folder.
BB_SSO_Provider_Admin::$global_path = dirname( __DIR__, 1 ) . '/admin';
