<?php
/**
 * BuddyBoss Platform Pro Main Class
 *
 * @package BuddyBossPro
 */

if ( ! class_exists( 'BB_Platform_Pro' ) ) {

	/**
	 * Main Class
	 *
	 * @class BB_Platform_Pro
	 * @version 1.0.0
	 */
	#[\AllowDynamicProperties]
	final class BB_Platform_Pro {

		/**
		 * Instance of the class.
		 *
		 * @var BB_Platform_Pro The single instance of the class
		 * @since 1.0.0
		 */
		protected static $instance = null;

		/**
		 * Integrations.
		 *
		 * @var array Integrations.
		 */
		public $integrations = array();

		/**
		 * Access Control.
		 *
		 * @var null Access Control.
		 * @since 1.1.0
		 */
		public $access_control = null;

		/**
		 * Main BB_Platform_Pro Instance
		 *
		 * Ensures only one instance of BB_Platform_Pro is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 * @see BB_Platform_Pro()
		 * @return BB_Platform_Pro - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Cloning is forbidden.
		 *
		 * @since 1.0.0
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'buddyboss-pro' ), '1.0.0' );
		}
		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @since 1.0.0
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'buddyboss-pro' ), '1.0.0' );
		}

		/**
		 * BB_Platform_Pro Constructor.
		 */
		public function __construct() {
			$this->constants();
			$this->setup_globals();
			$this->includes();
			// Set up localisation.
			$this->load_plugin_textdomain();
			$this->setup_actions();
		}

		/** Private Methods *******************************************************/

		/**
		 * Bootstrap constants.
		 *
		 * @since 1.0.0
		 */
		private function constants() {
			if ( ! defined( 'BB_PLATFORM_PRO_PLUGIN_DIR' ) ) {
				define( 'BB_PLATFORM_PRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'BB_PLATFORM_PRO_PLUGIN_URL' ) ) {
				define( 'BB_PLATFORM_PRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}

			if ( ! defined( 'BB_PLATFORM_PRO_PLUGIN_BASENAME' ) ) {
				define( 'BB_PLATFORM_PRO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			}

			if ( ! defined( 'BB_PLATFORM_PRO_PLUGIN_FILE' ) ) {
				define( 'BB_PLATFORM_PRO_PLUGIN_FILE', __FILE__ );
			}
		}

		/**
		 * Global variables.
		 *
		 * @since 1.0.0
		 */
		private function setup_globals() {
			$this->version        = '2.3.2';
			$this->db_version     = 265;
			$this->db_version_raw = (int) bp_get_option( '_bbp_pro_db_version' );

			// root directory.
			$this->file       = __FILE__;
			$this->basename   = plugin_basename( __FILE__ );
			$this->plugin_dir = trailingslashit( constant( 'BB_PLATFORM_PRO_PLUGIN_DIR' ) );
			$this->plugin_url = trailingslashit( constant( 'BB_PLATFORM_PRO_PLUGIN_URL' ) );

			$this->root_plugin_dir = $this->plugin_url;
			$this->integration_dir = $this->plugin_dir . 'includes/integrations';
			$this->integration_url = $this->plugin_url . 'includes/integrations';

			// Access Control.
			$this->access_control_dir = $this->plugin_dir . 'includes/access-control';
			$this->access_control_url = $this->plugin_url . 'includes/access-control';

			// Platform Settings.
			$this->platform_settings_dir = $this->plugin_dir . 'includes/platform-settings';
			$this->platform_settings_url = $this->plugin_url . 'includes/platform-settings';
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		public function includes() {
			spl_autoload_register( array( $this, 'autoload' ) );

			require $this->plugin_dir . 'includes/bb-pro-core-update.php';
			require $this->plugin_dir . 'includes/bb-pro-core-actions.php';
			require $this->plugin_dir . 'includes/bb-pro-core-functions.php';
			require $this->plugin_dir . 'includes/bb-pro-core-loader.php';
		}

		/**
		 * Load Localisation files.
		 *
		 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
		 */
		public function load_plugin_textdomain() {
			$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
			$locale = apply_filters( 'plugin_locale', $locale, 'buddyboss-pro' );

			unload_textdomain( 'buddyboss-pro' );
			load_textdomain( 'buddyboss-pro', WP_LANG_DIR . '/' . plugin_basename( dirname( __FILE__ ) ) . '/' . plugin_basename( dirname( __FILE__ ) ) . '-' . $locale . '.mo' );
			load_plugin_textdomain( 'buddyboss-pro', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Autoload classes.
		 *
		 * @since 1.0.0
		 *
		 * @param string $class Class to load.
		 */
		public function autoload( $class ) {
			$class_parts = explode( '_', strtolower( $class ) );

			if ( 'bp' !== $class_parts[0] && 'bb' !== $class_parts[0] ) {
				return;
			}

			// Sanitize class name.
			$class = strtolower( str_replace( '_', '-', $class ) );

			$paths = array(

				$this->plugin_dir . "/includes/classes/class-{$class}.php",
				$this->plugin_dir . "/includes/access-control/includes/class-{$class}.php",

			);

			$integration_dir = $this->integration_dir;

			foreach ( $this->integrations as $integration ) {
				$paths[] = "{$integration_dir}/{$integration}/includes/class-{$class}.php";
				$paths[] = "{$integration_dir}/{$integration}/includes/classes/class-{$class}.php";
			}

			foreach ( $paths as $path ) {
				// Sanity check.
				if ( file_exists( $path ) ) {
					require $path;
				}
			}
		}

		/**
		 * Add actions.
		 *
		 * @since 1.2.0
		 */
		private function setup_actions() {
			// add actions.
			add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );

			add_action( 'bp_admin_enqueue_scripts', array( $this, 'bb_enqueue_scripts_styles_admin' ) );
			add_action( 'admin_footer', array( $this, 'bb_pro_display_update_plugin_information' ) );

			// Add link to settings page.
			add_filter( 'plugin_action_links', array( $this, 'bb_pro_modify_plugin_action_links' ), 10, 2 );
			add_filter( 'network_admin_plugin_action_links', array( $this, 'bb_pro_modify_plugin_action_links' ), 10, 2 );
		}

		/**
		 * Enqueue related scripts and styles.
		 *
		 * @since 1.2.0
		 */
		public function enqueue_scripts_styles() {
			$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			$rtl_css = is_rtl() ? '-rtl' : '';
			wp_enqueue_style( 'bb-pro-enqueue-scripts', $this->plugin_url . 'assets/css/index' . $rtl_css . $min . '.css', false, bb_platform_pro()->version );
		}

		/**
		 * Enqueue style and script to the admin area for release note pop-up.
		 *
		 * @since 2.1.7
		 */
		public function bb_enqueue_scripts_styles_admin() {
			$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			$rtl_css = is_rtl() ? '-rtl' : '';

			wp_enqueue_style( 'bb-pro-enqueue-hello-style', $this->plugin_url . 'assets/css/hello' . $rtl_css . $min . '.css', false, bb_platform_pro()->version );
			wp_enqueue_script( 'bb-pro-enqueue-hello-script', $this->plugin_url . 'assets/js/hello' . $min . '.js', false, bb_platform_pro()->version, true );
			wp_localize_script(
				'bb-pro-enqueue-hello-script',
				'BP_PRO_HELLO',
				array(
					'bb_pro_display_auto_popup' => get_option( '_bb_pro_is_update' ),
				)
			);

		}

		/**
		 * Add Settings link to plugins area.
		 *
		 * @since 2.1.7
		 *
		 * @param array  $links Links array in which we would prepend our link.
		 * @param string $file  Current plugin basename.
		 * @return array Processed links.
		 */
		public function bb_pro_modify_plugin_action_links( $links, $file ) {

			// Return normal links if not BuddyPress.
			if ( 'buddyboss-platform-pro/buddyboss-platform-pro.php' !== $file ) {
				return $links;
			}

			// Add a few links to the existing links array.
			return array_merge(
				$links,
				array(
					'release_notes' => '<a href="javascript:void(0);" id="bb-pro-plugin-release-link">' . esc_html__( 'Release Notes', 'buddyboss-pro' ) . '</a>',
				)
			);
		}

		/**
		 * Display plugin information after plugin successfully updated.
		 *
		 * @since 2.1.7
		 */
		public function bb_pro_display_update_plugin_information() {
			if ( 0 !== strpos( get_current_screen()->id, 'plugins' ) ) {
				return;
			}
			// Check the transient to see if we've just updated the plugin.
			include trailingslashit( BB_PLATFORM_PRO_PLUGIN_DIR ) . 'includes/bb-pro-update-buddyboss.php';
			delete_option( '_bb_pro_is_update' );
		}
	}
}
