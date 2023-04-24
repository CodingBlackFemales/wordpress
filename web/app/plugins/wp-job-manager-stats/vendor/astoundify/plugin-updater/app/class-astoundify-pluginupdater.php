<?php
/**
 * Update a plugin.
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

if ( ! class_exists( 'Astoundify_PluginUpdater' ) ) :

	/**
	 * Main PluginUpdater Class.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class Astoundify_PluginUpdater {

		/**
		 * Monitor for updates in the admin.
		 *
		 * @since 1.0.0
		 *
		 * @param string $plugin_file Plugin File.
		 * @return object
		 */
		public function __construct( $plugin_file ) {
			if ( ! is_admin() ) {
				return;
			}

			$this->includes();

			$api     = new Astoundify_PluginUpdater_Api();
			$plugin  = new Astoundify_PluginUpdater_Plugin( $plugin_file );
			$license = new Astoundify_PluginUpdater_License( $plugin_file );

			// Monitor for actions.
			Astoundify_PluginUpdater_Actions::init();

			return new EDD_SL_Plugin_Updater( $api->get_api_url(), $plugin->get_file(), array(
				'version'   => $plugin->get_version(),
				'license'   => $license->get_key(),
				'item_name' => $plugin->get_name(),
				'author'    => 'Astoundify',
			) );
		}

		/**
		 * Include necessary files.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function includes() {
			if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
				require_once( dirname( __FILE__ ) . '/lib/class-edd-sl-plugin-updater.php' );
			}

			require_once( dirname( __FILE__ ) . '/class-astoundify-pluginupdater-helpers.php' );
			require_once( dirname( __FILE__ ) . '/class-astoundify-pluginupdater-api.php' );
			require_once( dirname( __FILE__ ) . '/class-astoundify-pluginupdater-plugin.php' );
			require_once( dirname( __FILE__ ) . '/class-astoundify-pluginupdater-license.php' );
			require_once( dirname( __FILE__ ) . '/class-astoundify-pluginupdater-actions.php' );

			require_once( dirname( __FILE__ ) . '/integrations/class-astoundify-pluginupdater-integration-wpjobmanager.php' );
		}

	}

endif;
