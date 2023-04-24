<?php
/**
 * License
 *
 * @since 1.0.0
 *
 * @package PluginUpdater
 * @category Core
 * @author Astoundify
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A license to manage.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
class Astoundify_PluginUpdater_License {

	/**
	 * Stored license key
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $key;

	/**
	 * Plugin
	 *
	 * @since 1.0.0
	 * @var Astoundify_PluginUpdater_Plugin
	 */
	protected $plugin;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_file Plugin File.
	 * @return self
	 */
	public function __construct( $plugin_file ) {
		$this->plugin = new Astoundify_PluginUpdater_Plugin( $plugin_file );

		// Populate key.
		$this->key = $this->get_key();

		// Always check license when the option is updated.
		add_action( 'update_option_' . $this->plugin->get_slug(), array( $this, 'sanitize' ), 10, 2 );
	}

	/**
	 * Get key.
	 *
	 * @since 1.0.0
	 *
	 * @return string $key.
	 */
	public function get_key() {
		return get_option( $this->plugin->get_slug() );
	}

	/**
	 * Get license status.
	 *
	 * @since 1.0.0
	 *
	 * @return string $status
	 */
	public function get_status() {
		return get_option( $this->plugin->get_slug() . '_status' );
	}

	/**
	 * Set status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status License status.
	 * @return bool
	 */
	public function set_status( $status ) {
		return update_option( $this->plugin->get_slug() . '_status', $status );
	}

	/**
	 * Activate a license
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function activate() {
		$api = new Astoundify_PluginUpdater_Api();

		$response = $api->request( array(
			'edd_action' => 'activate_license',
			'license'    => $this->get_key(),
			'item_name'  => urlencode( $this->plugin->get_name() ),
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		$this->set_status( $license_data->license );

		return true;
	}

	/**
	 * Deactivate a license
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function deactivate() {
		$api = new Astoundify_PluginUpdater_Api();

		$response = $api->request( array(
			'edd_action' => 'deactivate_license',
			'license' 	 => $this->get_key(),
			'item_name'  => urlencode( $this->plugin->get_name() ),
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( 'deactivated' === $license_data->license ) {
			$this->delete();
		}

		return true;
	}


	/**
	 * Sanitize.
	 *
	 * This will get called any time a license key is saved via the WordPress Settings API.
	 * If the license is added outside of the API activation will need to be called manually.
	 *
	 * @since 1.0.0
	 *
	 * @param string $old_value Old option value.
	 * @param string $new_value New option value.
	 * @return void
	 */
	public function sanitize( $old_value, $new_value ) {
		// Attempt to activate.
		if ( $old_value !== $new_value ) {
			$this->activate();
		}

		// Deactivate on clear.
		if ( '' === $new_value ) {
			$this->deactivate();
		}
	}

	/**
	 * Delete.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function delete() {
		delete_option( $this->plugin->get_slug() . '_status' );
		delete_option( $this->plugin->get_slug() );
	}

}
