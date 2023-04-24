<?php
/**
 * Monitor admin for performing actions against a license.
 *
 * This is helpful for triggering actions via URLs.
 *
 * @since 1.0.0
 *
 * @see Astoundify_PluginUpdater_Helpers
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
 * WP Job Manager
 *
 * @since 1.0.0
 * @version 1.0.0
 */
class Astoundify_PluginUpdater_Actions {

	/**
	 * Query var action to check for.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected static $query_var = 'astoundify-pluginupdater';

	/**
	 * Init
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function init() {
		// Monitor all actions.
		add_action( 'admin_init', array( __CLASS__, 'monitor' ) );

		// Activate.
		add_action( 'astoundify_pluginupdater_action_activate-license', array( __CLASS__, 'activate_license' ) );

		// Deactivate.
		add_action( 'astoundify_pluginupdater_action_deactivate-license', array( __CLASS__, 'deactivate_license' ) );
	}

	/**
	 * Monitor for all actions.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function monitor() {
		if ( ! isset( $_REQUEST[ self::$query_var ] ) ) {
			return;
		}

		$action = esc_attr( $_REQUEST[ self::$query_var ] );

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], $action ) ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		do_action( 'astoundify_pluginupdater_action_' . $action );
	}

	/**
	 * Activate a license.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function activate_license() {
		if ( ! isset( $_REQUEST['plugin_file'] ) ) {
			return;
		}

		$plugin_file = urldecode( $_REQUEST['plugin_file'] );

		$license = new Astoundify_PluginUpdater_License( $plugin_file );
		$license->activate();
	}

	/**
	 * Deactivate a license
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function deactivate_license() {
		if ( ! isset( $_REQUEST['plugin_file'] ) ) {
			return;
		}

		$plugin_file = urldecode( $_REQUEST['plugin_file'] );

		$license = new Astoundify_PluginUpdater_License( $plugin_file );
		$license->deactivate();
	}

}
