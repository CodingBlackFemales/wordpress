<?php

namespace BuddyBossTheme;

if ( ! class_exists( '\BuddyBossTheme\BaseTheme' ) ) {

	#[\AllowDynamicProperties]
	class BaseTheme {
		// --------- Constants ------------------

		const VERSION = '0.1';
		const NAME    = 'BuddyBoss Theme';

		// --------- Variables ------------------

		/**
		 * @var string parent/main theme path
		 */
		protected $_tpl_dir;

		/**
		 * @var string parent theme url
		 */
		protected $_tpl_url;

		/**
		 * @var string includes path
		 */
		protected $_inc_dir;

		/**
		 * @var array modules array
		 */
		protected $_mods;
		protected $_buddypress_helper    = false;
		protected $_bbpress_helper       = false;
		protected $_learndash_helper     = false;
		protected $_lifterlms_helper     = false;
		protected $_woocommerce_helper   = false;
		protected $_related_posts_helper = false;
		protected $_elementor_helper     = false;
		protected $_elementor_helper_pro = false;
		protected $_beaver_themer_helper = false;
		protected $_admin                = false;
		protected $_bb_theme_update      = false;
		protected $_tutorlms_helper      = false;

		/**
		 * Text Domain of Plugin Scope
		 *
		 * @var string
		 */
		public $lang_domain = 'buddyboss-theme';

		// ---------- Properties ------------------
		/**
		 * Return the current db version.
		 *
		 * @return init
		 */
		public $bb_theme_db_version;

		/**
		 * Return the exists db version.
		 *
		 * @return string
		 */
		public $bb_theme_db_version_raw;

		/**
		 * Return the template directory path.
		 *
		 * @return string parent/main theme path
		 */
		public function tpl_dir() {
			return $this->_tpl_dir;
		}

		/**
		 * Return the template directory url.
		 *
		 * @return string parent theme url
		 */
		public function tpl_url() {
			return $this->_tpl_url;
		}

		/**
		 * Return the includes directory path.
		 *
		 * @return string includes path
		 */
		public function inc_dir() {
			return $this->_inc_dir;
		}

		/**
		 * Get the instance of BuddyPressHelper class
		 *
		 * @return bool
		 */
		public function buddypress_helper() {
			return $this->_buddypress_helper;
		}

		/**
		 * Get the instance of BBPressHelper class
		 *
		 * @return bool
		 */
		public function bbpress_helper() {
			return $this->_bbpress_helper;
		}

		/**
		 * Get the instance of LearnDashHelper class
		 *
		 * @return bool
		 */
		public function learndash_helper() {
			return $this->_learndash_helper;
		}

		/**
		 * Get the instance of LifterLMSHelper class
		 *
		 * @return bool
		 */
		public function lifterlms_helper() {
			return $this->_lifterlms_helper;
		}

		/**
		 * Get the instance of WooCommerceHelper class
		 *
		 * @return bool
		 */
		public function woocommerce_helper() {
			return $this->_woocommerce_helper;
		}

		/**
		 * Get the instance of RelatedPostsHelper class
		 *
		 * @return bool
		 */
		public function related_posts_helper() {
			return $this->_related_posts_helper;
		}

		/**
		 * Get the instance of ElementorHelper class
		 *
		 * @return bool
		 */
		public function elementor_helper() {
			return $this->_elementor_helper;
		}

		/**
		 * Get the instance of ElementorHelperPro class
		 *
		 * @return bool
		 */
		public function elementor_pro_helper() {
			return $this->_elementor_helper_pro;
		}

		/**
		 * Get the instance of BeaverThemerHelper class
		 *
		 * @return bool
		 */
		public function beaver_themer_helper() {
			return $this->_beaver_themer_helper;
		}

		/**
		 * Get the instance of TutorLMS helper class
		 *
		 * @since 2.4.90
		 * 
		 * @return bool|object
		 */
		public function tutorlms_helper() {
			return $this->_tutorlms_helper;
		}

		/**
		 * Update theme modal.
		 *
		 * @since 1.8.7
		 *
		 * @return bool
		 */
		public function bb_theme_update() {
			return $this->_bb_theme_update;
		}

		/**
		 * Get the instance of \BuddyBossTheme\Admin class
		 *
		 * @return bool
		 */
		public function admin() {
			return $this->_admin;
		}

		/**
		 * Get the version number of theme. This is used while enqueueing scripts and styles. Usefule for cache busting.
		 *
		 * @todo Find a way to read it from readme.txt instead of using hardcoded value.
		 *
		 * @return string version number of theme
		 */
		public function version() {
			$theme = wp_get_theme( 'buddyboss-theme' );

			return $theme['Version'];
		}

		// ---------- Constructor ------------------

		/**
		 * Get the instance of this class.
		 *
		 * @static \BuddyBossTheme\BaseTheme $instance
		 * @return \BuddyBossTheme\BaseTheme
		 */
		public static function instance() {
			static $instance = null;

			if ( null === $instance ) {
				$instance = new \BuddyBossTheme\BaseTheme();
			}

			return $instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			/**
			 * Globals, constants, theme path etc
			 */
			$this->_setup_globals();

			/**
			 * Load required theme files
			 */
			$this->_do_includes();

			/**
			 * Actions/filters
			 */
			$this->_setup_actions_filters();
		}

		// ---------- Setup --------------------

		/**
		 * Setup config/global/constants etc variables
		 */
		private function _setup_globals() {

			$this->bb_theme_db_version     = 445;
			$this->bb_theme_db_version_raw = (int) get_option( '_bb_theme_db_version' );

			// Get theme path.
			$this->_tpl_dir = get_template_directory();

			// Get theme url.
			$this->_tpl_url = get_template_directory_uri();

			// Get includes path.
			$this->_inc_dir = $this->_tpl_dir . '/inc';

			if ( ! defined( 'BUDDYBOSS_DEBUG' ) ) {
				define( 'BUDDYBOSS_DEBUG', false );
			}

			if ( ! defined( 'THEME_TEXTDOMAIN' ) ) {
				define( 'THEME_TEXTDOMAIN', $this->lang_domain );
			}

			if ( ! defined( 'THEME_HOOK_PREFIX' ) ) {
				define( 'THEME_HOOK_PREFIX', 'buddyboss_theme_' );
			}
		}

		/**
		 * Includes
		 */
		protected function _do_includes() {

			require_once $this->_inc_dir . '/common-functions.php';
			require_once $this->_inc_dir . '/admin/options/setting-options.php';
			require_once $this->_inc_dir . '/admin/admin-init.php';

			require_once $this->_inc_dir . '/compatibility/incompatible-themes-helper.php';

			// Theme stuff
			// Wherever possible, we'll put related functions in a separate file, instead of dumping them all in functions.php
			// E.g: all login/logout related functions can go in login.php, all admin bar related functions can go in admin bar.php and so on.
			require_once $this->_inc_dir . '/theme/functions.php';
			require_once $this->_inc_dir . '/theme/template-functions.php';
			require_once $this->_inc_dir . '/theme/shortcodes.php';
			require_once $this->_inc_dir . '/theme/bookmarks.php';
			require_once $this->_inc_dir . '/theme/sidebars.php';
			require_once $this->_inc_dir . '/theme/widgets.php';
			require_once $this->_inc_dir . '/theme/login.php';
			require_once $this->_inc_dir . '/theme/admin-bar.php';
			require_once $this->_inc_dir . '/theme/multi-post-thumbnails.php';
			require_once $this->_inc_dir . '/theme/update.php';
			require_once $this->_inc_dir . '/theme/class-buddypanel-section.php';

			// BuddyPress Helper.
			require_once $this->_inc_dir . '/plugins/buddypress-helper.php';
			$this->_buddypress_helper = new \BuddyBossTheme\BuddyPressHelper();

			// bbPress Helper.
			require_once $this->_inc_dir . '/plugins/bbpress-helper.php';
			$this->_bbpress_helper = new \BuddyBossTheme\BBPressHelper();

			// Interface for LMS helpers.
			require_once $this->_inc_dir . '/plugins/bb-lms-helper.php';

			// LearnDash Helper.
			if ( class_exists( 'SFWD_LMS' ) ) {
				// LearnDash Helper.
				require_once $this->_inc_dir . '/plugins/learndash-helper.php';
				require_once $this->_inc_dir . '/plugins/learndash-compat.php';
				$this->_learndash_helper = new \BuddyBossTheme\LearndashHelper();
			}

			// LifterLMS Helper.
			if ( class_exists( 'LifterLMS' ) ) {
				// LearnDash Helper.
				require_once $this->_inc_dir . '/plugins/lifterlms-helper.php';
				$this->_lifterlms_helper = new \BuddyBossTheme\LifterLMSHelper();
			}

			// Elementor Helper.
			if ( defined( 'ELEMENTOR_VERSION' ) ) {
				require_once $this->_inc_dir . '/plugins/elementor-helper.php';
				$this->_elementor_helper = new \BuddyBossTheme\ElementorHelper();
				require_once $this->_inc_dir . '/plugins/elementor/bb-elementor.php';
				// If plugin - 'Elementor' not exist then return.
				if ( class_exists( 'ElementorPro\Modules\ThemeBuilder\Module' ) ) {
					require_once $this->_inc_dir . '/plugins/elementor-helper-pro.php';
					$this->_elementor_helper_pro = new \BuddyBossTheme\ElementorHelperPro();
				}
			}

			// Elementor – Header, Footer & Blocks.
			if ( defined( 'ELEMENTOR_VERSION' ) && function_exists( 'hfe_init' ) ) {
				// Elementor Header Footer Helper.
				require_once $this->_inc_dir . '/plugins/elementor-header-footer.php';
				$this->_elementor_header_footer = new \BuddyBossTheme\ElementorHeaderFooter();
			}

			// Beaver Theme compatibility requires PHP 5.3 for anonymus functions.
			if ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
				if ( class_exists( 'FLThemeBuilderLoader' ) && class_exists( 'FLThemeBuilderLayoutData' ) ) {
					require_once $this->_inc_dir . '/plugins/beaver-themer-helper.php';
					$this->_beaver_themer_helper = new \BuddyBossTheme\BeaverThemerHelper();
				}
			}

			// Beaver Builder Modules.
			if ( class_exists( 'FLBuilderLoader' ) ) {
				require_once $this->_inc_dir . '/plugins/beaver-builder/bb-modules.php';
			}

			// Contextual Related Posts.
			require_once $this->_inc_dir . '/plugins/related-posts-helper.php';
			$this->_related_posts_helper = new \BuddyBossTheme\RelatedPostsHelper();

			// Maintenance Mode.
			require_once $this->_inc_dir . '/maintenance-mode/maintenance-mode.php';

			// WooCommerce's helpers and widgets.
			if ( function_exists( 'WC' ) ) {
				require_once $this->_inc_dir . '/plugins/woocommerce-helper.php';
				$this->_woocommerce_helper = new \BuddyBossTheme\WooCommerceHelper();
			}

			// The Events Calendar.
			if ( class_exists( 'Tribe__Events__Main' ) ) {
				require_once $this->_inc_dir . '/plugins/events-calendar.php';
				$this->_tribe_events_helper = new \BuddyBossTheme\EventsCalendarHelper();
			}

			if ( 
				function_exists( 'bb_theme_enable_tutorlms_override' ) &&
				bb_theme_enable_tutorlms_override()
			) {

				// Tutorlms Helper.
				require_once $this->_inc_dir . '/plugins/tutorlms-helper.php';
				$this->_tutorlms_helper = new \BuddyBossTheme\TutorLMSHelper();
			}

			// The Events Calendar.
			require_once $this->_inc_dir . '/tribe-events/events-functions.php';

			require_once $this->_inc_dir . '/plugins/buddyboss-menu-icons/menu-icons.php';

			// custom fonts support.
			require_once $this->_inc_dir . '/custom-fonts/custom-fonts.php';

			// Others.
			require_once $this->_inc_dir . '/others/utility.php';
			require_once $this->_inc_dir . '/others/debug.php';

			// Allow automatic updates from buddyboss servers.
			require_once $this->_inc_dir . '/others/buddyboss-theme-updater.php';

			// BB Theme Update Modal.
			$theme           = wp_get_theme();
			$current_version = $theme->get( 'Version' );
			$new_version     = $this->bb_get_new_theme_version( $theme );
			if ( $current_version < '2.0.0' && $new_version >= '2.0.0' ) {
				// If want to using feature delete this option bb_theme_options_major from inc/theme/update.php.
				require_once $this->_inc_dir . '/theme/bb-theme-update.php';
				$this->_bb_theme_update = new \BuddyBossTheme\BBThemeUpdate();
			}
		}

		/**
		 * Actions and filters
		 */
		protected function _setup_actions_filters() {

			if ( is_admin() ) {
				add_action( 'after_setup_theme', array( $this, 'include_buddyboss_updater' ) );
			}

			if ( BUDDYBOSS_DEBUG ) {
				add_action( 'bp_footer', 'buddyboss_dump_log' );
			}
		}

		/**
		 * Include BuddyBoss Updater.
		 *
		 * @return void
		 */
		public function include_buddyboss_updater() {
			global $pagenow;

			if ( ! function_exists( 'buddyboss_updater_init' ) ) {
				require_once $this->_inc_dir . '/lib/buddyboss-updater/buddyboss-updater.php';
			}
		}

		/**
		 * Get new version of theme.
		 *
		 * @since 1.8.7
		 *
		 * @param object $theme Get Theme data.
		 *
		 * @return string $new_version Return new version of theme.
		 */
		public function bb_get_new_theme_version( $theme ) {
			$new_version          = '';
			$stylesheet           = $theme->get_stylesheet();
			static $themes_update = null;
			if ( ! isset( $themes_update ) ) {
				$themes_update = get_site_transient( 'update_themes' );
			}
			if ( isset( $themes_update->response[ $stylesheet ] ) ) {
				$update      = $themes_update->response[ $stylesheet ];
				$new_version = isset( $update['new_version'] ) ? $update['new_version'] : '';
			}

			return $new_version;
		}

	}

}
