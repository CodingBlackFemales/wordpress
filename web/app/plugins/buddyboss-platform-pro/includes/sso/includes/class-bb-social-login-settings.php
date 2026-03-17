<?php
/**
 * Class BB_Social_Login_Settings
 *
 * Handles the settings for the BB Social Login feature, including
 * storing, retrieving, and updating settings.
 *
 * @since 2.6.30
 *
 * @package BuddyBossPro/SSO
 */

/**
 * Class BB_Social_Login_Settings
 */
class BB_Social_Login_Settings {

	/**
	 * The option key for storing settings.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	protected $option_key;

	/**
	 * The settings array, containing default, stored, and final settings.
	 *
	 * @since 2.6.30
	 *
	 * @var array
	 */
	protected $settings = array(
		'default' => array(),
		'stored'  => array(),
		'final'   => array(),
	);

	/**
	 * BB_Social_Login_Settings constructor.
	 *
	 * Initializes the settings with default values and loads any stored settings.
	 *
	 * @since 2.6.30
	 *
	 * @param string $option_key       The option key for storing settings.
	 * @param array  $default_settings The default settings to use.
	 */
	public function __construct( $option_key, $default_settings ) {
		$this->option_key = $option_key;

		$this->settings['default'] = $default_settings;

		$stored_settings = get_option( $this->option_key );
		if ( false !== $stored_settings ) {
			$stored_settings = (array) maybe_unserialize( $stored_settings );
		} else {
			$stored_settings = array();
		}

		$this->settings['stored'] = array_merge( $this->settings['default'], $stored_settings );

		$this->settings['final'] = apply_filters( 'bb_sso_finalize_settings_' . $option_key, $this->settings['stored'] );
	}

	/**
	 * Retrieves a specific setting by key from the specified storage.
	 *
	 * @since 2.6.30
	 *
	 * @param string $key     The key of the setting to retrieve.
	 * @param string $storage The storage type (default is 'final').
	 *
	 * @return mixed|false The value of the setting, or false if not found.
	 */
	public function get( $key, $storage = 'final' ) {
		if ( ! isset( $this->settings[ $storage ][ $key ] ) ) {
			return false;
		}

		return $this->settings[ $storage ][ $key ];
	}

	/**
	 * Sets a specific setting value and stores it.
	 *
	 * @since 2.6.30
	 *
	 * @param string $key   The key of the setting to set.
	 * @param mixed  $value The value to assign to the setting.
	 */
	public function set( $key, $value ) {
		$this->settings['stored'][ $key ] = $value;
		$this->store_settings();
	}

	/**
	 * Stores the current settings in the database.
	 *
	 * Serializes the stored settings and updates the option in the database.
	 *
	 * @since 2.6.30
	 */
	protected function store_settings() {
		update_option( $this->option_key, maybe_serialize( $this->settings['stored'] ) );

		$this->settings['final'] = apply_filters( 'bb_sso_finalize_settings_' . $this->option_key, $this->settings['stored'] );
	}

	/**
	 * Retrieves all settings from the specified storage.
	 *
	 * @since 2.6.30
	 *
	 * @param string $storage The storage type (default is 'final').
	 *
	 * @return array All settings in the specified storage.
	 */
	public function get_all( $storage = 'final' ) {
		return $this->settings[ $storage ];
	}

	/**
	 * Updates settings with new posted data after validation.
	 *
	 * @since 2.6.30
	 *
	 * @param array $posted_data The new settings data to update.
	 *
	 * @return true True if settings were updated, false otherwise.
	 */
	public function update( $posted_data ) {
		if ( is_array( $posted_data ) ) {
			$new_data = array();
			$new_data = apply_filters( 'bb_sso_update_settings_validate_' . $this->option_key, $new_data, $posted_data );

			if ( count( $new_data ) ) {

				$is_changed = false;
				foreach ( $new_data as $key => $value ) {
					if ( $this->settings['stored'][ $key ] !== $value ) {
						$this->settings['stored'][ $key ] = $value;
						$is_changed                       = true;
					}
				}

				if ( $is_changed ) {
					$allowed_keys             = array_keys( $this->settings['default'] );
					$this->settings['stored'] = array_intersect_key( $this->settings['stored'], array_flip( $allowed_keys ) );

					$this->store_settings();

				}

				return true;
			}
		}

		return false;
	}
}
