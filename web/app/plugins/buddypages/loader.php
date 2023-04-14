<?php
/**
 * BuddyPages Loader
 *
 * @package BuddyPagesLoader
 * @subpackage Loader
 * @author WebDevStudios
 * @since 1.0.0
 */

/**
 * Plugin Name: BuddyPages
 * Plugin URI:  https://pluginize.com
 * Description: Front end page creation for BuddyPress profiles and groups.
 * Version:     1.2.3
 * Author:      Pluginize from WebDevStudios
 * Author URI:  https://pluginize.com
 * License:     GPLv2
 * Text Domain: buddypages
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2016 WebDevStudios (email: contact@pluginize.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


/**
 * Autoloads files with classes when needed.
 *
 * @since 1.0.0
 *
 * @param string $class_name Name of the class being requested.
 * @return void
 */
function buddypages_autoload_classes( $class_name ) {
	if ( 0 !== strpos( $class_name, 'BuddyPages_' ) ) {
		return;
	}

	$filename = strtolower( str_replace(
		'_', '-',
		substr( $class_name, strlen( 'BuddyPages_' ) )
	) );

	BuddyPages::include_file( $filename );
}
spl_autoload_register( 'buddypages_autoload_classes' );

/**
 * Main initiation class.
 *
 * @since 1.0.0
 */
class BuddyPages {

	/**
	 * Current version.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	const VERSION = '1.2.3';

	/**
	 * Current version.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	protected $version;

	/**
	 * URL of plugin directory.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	protected $url = '';

	/**
	 * Path of plugin directory.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	protected $path = '';

	/**
	 * Plugin basename.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	protected $basename = '';

	/**
	 * Plugin name.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $plugin_name = 'BuddyPages';

	/**
	 * User pages object.
	 *
	 * @var string|object
	 * @since 1.0.0
	 */
	public $user_pages = '';

	/**
	 * Group pages object.
	 *
	 * @var string|object
	 * @since 1.0.0
	 */
	public $group_pages = '';

	/**
	 * Database version in raw form.
	 *
	 * @var int
	 * @since 1.0.0
	 */
	protected $db_version_raw = 0;

	/**
	 * Post type registration property.
	 *
	 * @var BuddyPages_Pages_CPT
	 * @since 1.0.0
	 */
	protected $pages_cpt;

	/**
	 * Store URL.
	 */
	public $store_url = '';

	/**
	 * Singleton instance of plugin.
	 *
	 * @var object WDS_Product_Plugin_Framework
	 * @since 1.0.0
	 */
	protected static $single_instance = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since 1.0.0
	 * @return object WDS_Product_Plugin_Framework A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin.
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );
		$this->version  = self::VERSION;

		$this->pages_cpt = new BuddyPages_Pages_CPT( $this );
		$this->store_url = 'https://pluginize.com';

		add_action( 'bp_include', array( $this, 'includes' ) );
		add_action( 'bp_include', array( $this, 'plugin_classes' ) );
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since 1.0.0
	 */
	public function plugin_classes() {

		if ( bp_is_active( 'xprofile' ) ) {
			$this->user_pages = new BuddyPages_User_Pages( $this );
		}
		if ( bp_is_active( 'groups' ) ) {
			$this->group_pages = new BuddyPages_Group_Pages( $this );
		}
	}

	/**
	 * Add hooks and filters.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'init' ), 9 );
		add_action( 'init', array( $this, 'load_libs' ) );

		add_action( 'init', array( $this, 'updater' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );

		add_filter( 'plugin_action_links_'. $this->basename, array( $this, 'add_social_links' ) );
	}

	/**
	 * Include files.
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		if ( file_exists( __DIR__ . '/inc/buddypages-functions.php' ) ) {
			require_once  __DIR__ . '/inc/buddypages-functions.php';
		}

		if ( file_exists( __DIR__ . '/inc/buddypages-screens.php' ) ) {
			require_once  __DIR__ . '/inc/buddypages-screens.php';
		}

	}

	/**
	 * Bump version option.
	 *
	 * @since 1.0.0
	 */
	public function bump_version() {

		$version = get_option( '_buddypages_db_version' );

		if ( self::VERSION !== $version ) {
			update_option( '_buddypages_db_version', self::VERSION );
		}
		$this->db_version_raw = self::VERSION ? self::VERSION : 0;
	}

	/**
	 * Activate the plugin.
	 *
	 * @since 1.0.0
	 */
	function _activate() {
		flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin.
	 *
	 * Uninstall routines should be in uninstall.php
	 *
	 * @since 1.0.0
	 */
	function _deactivate() {}

	/**
	 * Init hooks.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		if ( $this->check_requirements() ) {
			load_plugin_textdomain( 'buddypages', false, dirname( $this->basename ) . '/languages/' );
		}
	}

	/**
	 * Register scripts.
	 *
	 * @since 1.0.0
	 */
	public function scripts() {
		wp_register_style( 'buddypages', $this->url . 'assets/css/style.css' );

		if ( bp_is_user() ) {
			wp_enqueue_style( 'dashicons' );
			wp_enqueue_style( 'buddypages' );
		}
	}

	/**
	 * Load libraries.
	 *
	 * @since 1.0.0
	 */
	public function load_libs() {
		require_once __DIR__ . '/vendor/edd-updater/license-handler.php';
	}

	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean $value Result of meets_requirements check.
	 */
	public function check_requirements() {
		if ( ! $this->meets_requirements() ) {

			add_action( 'all_admin_notices', array( $this, 'requirements_not_met_notice' ) );
			add_action( 'admin_init', array( $this, 'deactivate_me' ) );

			return false;
		}

		return true;
	}

	/**
	 * Deactivates this plugin, hook this function on admin_init.
	 *
	 * @since 1.0.0
	 */
	public function deactivate_me() {
		deactivate_plugins( $this->basename );
	}

	/**
	 * Check that all plugin requirements are met.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean $value True if requirements are met.
	 */
	public static function meets_requirements() {
		return class_exists( 'BuddyPress' );
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met.
	 *
	 * @since 1.0.0
	 */
	public function requirements_not_met_notice() {
		$error_text = sprintf( __( 'BuddyPages is missing requirements and has been <a href="%s">deactivated</a>. Please make sure BuddyPress is installed and activated.', 'buddypages' ), admin_url( 'plugins.php' ) );

		echo '<div id="message" class="error">';
		echo '<p>' . $error_text . '</p>';
		echo '</div>';
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since 1.0.0
	 *
	 * @throws Exception Throws an exception if the field is invalid.
	 *
	 * @param string $field Field to get.
	 * @return mixed
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return self::VERSION;
			case 'basename':
			case 'url':
			case 'path':
				return $this->$field;
			default:
				throw new Exception( 'Invalid '. __CLASS__ .' property: ' . $field );
		}
	}

	/**
	 * Include a file from the includes directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename Name of the file to be included.
	 * @return bool Result of include call.
	 */
	public static function include_file( $filename ) {
		$file = self::dir( 'classes/class-'. $filename .'.php' );
		if ( file_exists( $file ) ) {
			return include_once( $file );
		}
		return false;
	}

	/**
	 * This plugin's directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path (optional) appended path.
	 * @return string Directory and path
	 */
	public static function dir( $path = '' ) {
		static $dir;
		$dir = $dir ? $dir : trailingslashit( dirname( __FILE__ ) );
		return $dir . $path;
	}

	/**
	 * This plugin's url.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path (optional) appended path.
	 * @return string URL and path
	 */
	public static function url( $path = '' ) {
		static $url;
		$url = $url ? $url : trailingslashit( plugin_dir_url( __FILE__ ) );
		return $url . $path;
	}

	/**
	 * Add social media links to plugin screen.
	 *
	 * @param array $links Plugin action links.
	 * @return array
	 */
	public function add_social_links( $links ) {

		$site_link      = 'https://pluginize.com/';
		$twitter_status = sprintf( __( 'Check out %s from @pluginize', 'buddypages' ), $this->plugin_name );

		$docs      = sprintf(
			'<a href="%s" target="_blank" rel="noopener">%s</a>',
			esc_url( 'https://docs.pluginize.com/tutorials/buddypages/' ),
			esc_html__( 'Documentation', 'buddypages' )
		);
		$pluginize = sprintf(
			'<a title="%s" href="%s" target="_blank" rel="noopener">pluginize.com</a>',
			esc_attr__( 'More plugins for your WordPress site here!', 'buddypages' ),
			$site_link
		);
		$facebook  = sprintf(
			'<a title="%s" href="%s" target="_blank" class="dashicons-before dashicons-facebook-alt" rel="noopener"></a>',
			esc_attr__( 'Spread the word!', 'buddypages' ),
			'https://www.facebook.com/sharer/sharer.php?u=' . urlencode( $site_link )
		);
		$twitter   = sprintf(
			'<a title="%s" href="%s" target="_blank" class="dashicons-before dashicons-twitter" rel="noopener"></a>',
			esc_attr__( 'Spread the word!', 'buddypages' ),
			'https://twitter.com/home?status=' . urlencode( $twitter_status )
		);
		array_push(
			$links,
			$docs,
			$pluginize,
			$facebook,
			$twitter,
		);

		return $links;
	}

	public function updater() {
		if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
			require_once $this->path . 'vendor/edd-updater/EDD_SL_Plugin_Updater.php';
		}
		$license_key = trim( get_option( 'buddypages_license_key' ) );
		$edd_updater = new EDD_SL_Plugin_Updater( $this->store_url, __FILE__, array(
				'version'   => $this->version,     // Current version number.
				'license'   => $license_key,       // license key (used get_option above to retrieve from DB)
				'item_name' => $this->plugin_name, // name of this plugin
				'author'    => 'Pluginize'         // author of this plugin.
			)
		);
	}
}

/**
 * Grab the BuddyPages object and return it.
 *
 * Wrapper for BuddyPages::get_instance()
 *
 * @since 1.0.0
 *
 * @return object BuddyPages Singleton instance of plugin class.
 */
function buddypages() {
	return BuddyPages::get_instance();
}
add_action( 'plugins_loaded', array( buddypages(), 'hooks' ) );

register_activation_hook( __FILE__, array( buddypages(), '_activate' ) );
register_deactivation_hook( __FILE__, array( buddypages(), '_deactivate' ) );


/**
 * Dismisses the activation license notice.
 *
 * @since 1.0.0
 */
function buddypages_activation_notice() {
	if ( isset( $_GET['buddypages-dismiss-activation'] ) && 'dismiss' === $_GET['buddypages-dismiss-activation'] ) {
		if ( is_admin() || is_network_admin() ) {
			update_option( 'buddypages_plugin_activated_dismissed', 'dismissed' );
			wp_redirect( remove_query_arg( 'buddypages-dismiss-activation', $_SERVER['REQUEST_URI'] ) );
			exit;
		}
	}
}
add_action( 'admin_init', 'buddypages_activation_notice', 999 );
