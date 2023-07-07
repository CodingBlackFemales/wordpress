<?php
/**
 * LearnDash Theme Register.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LearnDash_Theme_Register' ) ) {

	/**
	 * Class to create the settings section.
	 *
	 * @since 3.0.0
	 */
	class LearnDash_Theme_Register {

		/**
		 * Theme Key.
		 *
		 * @since 3.0.0
		 *
		 * @var string
		 */
		protected $theme_key = null;

		/**
		 * Is theme selectable. Controls if it shows in the Setting selector. Default true.
		 *
		 * @since 3.0.0
		 *
		 * @var string
		 */
		protected $theme_selectable = true;

		/**
		 * Theme Base URL. Relative to LearnDash plugin directory.
		 *
		 * @since 4.0.0
		 *
		 * @var string
		 */
		protected $theme_base_url = null;

		/**
		 * Theme Base Directory. Relative to LearnDash plugin directory.
		 *
		 * @since 4.0.0
		 *
		 * @var string
		 */
		protected $theme_base_dir = null;

		/**
		 * Theme Template URL.
		 *
		 * @since 4.0.0
		 *
		 * @var string
		 */
		protected $theme_template_url = null;

		/**
		 * Theme Template Directory.
		 *
		 * @since 4.0.0
		 *
		 * @var string
		 */
		protected $theme_template_dir = null;

		/**
		 * Theme Name.
		 *
		 * @since 3.0.0
		 *
		 * @var string
		 */
		protected $theme_name = null;

		/**
		 * Theme Directory.
		 *
		 * @since 3.0.0
		 *
		 * @var string
		 */
		protected $theme_dir = null;

		/**
		 * Theme URL.
		 *
		 * @since 3.0.0
		 *
		 * @var string
		 */
		protected $theme_url = null;

		/**
		 * Array to hold all field type instances.
		 *
		 * @since 3.0.0
		 *
		 * @var array
		 */
		protected static $_instances = array(); // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

		/**
		 * Boolean to indicate when init has or has not been called. Default false.
		 *
		 * @since 3.0.0
		 *
		 * @var boolean
		 */
		protected static $_init_called = false; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

		/**
		 * Array of theme settings sections.
		 *
		 * @since 4.0.0
		 *
		 * @var array
		 */
		protected $theme_settings_sections = array();

		/**
		 * Indicates if the theme supports views.
		 *
		 * @since 4.6.0
		 *
		 * @var bool
		 */
		protected $supports_views = true;

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
		}

		/**
		 * Initialize the Themes.
		 */
		final public static function init() {
			if ( false === self::$_init_called ) {
				self::$_init_called = true;

				/**
				 * Fires on initializing a theme.
				 */
				do_action( 'learndash_themes_init' );
			}
		}

		/**
		 * Get theme instance by key
		 *
		 * @since 3.0.0
		 *
		 * @param string $theme_key Key to unique theme instance.
		 *
		 * @return object|null instance of theme if present.
		 */
		final public static function get_theme_instance( $theme_key = '' ) {
			if ( ! empty( $theme_key ) ) {
				self::init();

				if ( isset( self::$_instances[ $theme_key ] ) ) {
					return self::$_instances[ $theme_key ];
				}
			}

			return null;
		}

		/**
		 * Add Theme instance by key
		 *
		 * @since 3.0.0
		 *
		 * @param string $theme_key Key to unique Theme instance.
		 *
		 * @return object|null instance of theme if present.
		 */
		final public static function add_theme_instance( $theme_key = '' ) {
			if ( ! empty( $theme_key ) ) {
				self::init();

				if ( ! isset( self::$_instances[ $theme_key ] ) ) {
					$theme_class                    = get_called_class();
					self::$_instances[ $theme_key ] = new $theme_class();
				}

				return self::$_instances[ $theme_key ];
			}

			return null;
		}

		/**
		 * Utility function to check if a theme_key is the active theme.
		 *
		 * @since 3.0.0
		 * @param string $theme_key Key/Slug for theme to check.
		 * @return boolean true if theme_key is the active theme, otherwise false.
		 */
		final public static function is_active_theme( $theme_key = '' ) {
			if ( ( ! empty( $theme_key ) ) && ( self::get_active_theme_key() === $theme_key ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Load the themes settings sections.
		 *
		 * This function will call the theme method `load_settings_sections()` where
		 * it can load needed files for the settings sections.

		 * @since 4.0.0
		 */
		final public static function load_themes_settings_sections() {
			if ( ! empty( self::$_instances ) ) {
				foreach ( self::$_instances as $theme_key => $theme_instance ) {
					$theme_instance->load_settings_sections();
					/**
					 * Action when theme settings are loaded.
					 *
					 * @since 4.0.0
					 * @param string $theme_key Theme key.
					 */
					do_action( 'learndash_theme_settings_load', $theme_instance->get_theme_key() );
				}
				do_action( 'learndash_settings_themes_init' );
			}
		}

		/**
		 * Utility function to register/associate a settings section/metabox with a theme.
		 *
		 * @since 3.0.0
		 * @param string $theme_key Key/Slug for theme to check.
		 * @param string $section_key Key for Settings Section.
		 * @param object $section_instance Instance of settings section.
		 */
		final public static function register_theme_settings_section( $theme_key, $section_key, $section_instance ) {
			if ( ( ! empty( $theme_key ) ) && ( isset( self::$_instances[ $theme_key ] ) ) ) {
				if ( ( ! empty( $section_key ) ) && ( ! isset( self::$_instances[ $theme_key ]->theme_settings_sections[ $section_key ] ) ) ) {
					self::$_instances[ $theme_key ]->theme_settings_sections[ $section_key ] = $section_instance;
				}
			}
		}

		/**
		 * Get the instance of the current active theme.
		 *
		 * @since 3.0.0
		 * @return object instance of active theme.
		 */
		final public static function get_active_theme_instance() {
			$theme_key = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Themes', 'themes' );
			if ( empty( $theme_key ) ) {
				$themes = get_option( 'learndash_settings_courses_themes' );
				if ( ( isset( $themes['active_theme'] ) ) && ( ! empty( $themes['active_theme'] ) ) ) {
					$theme_key = esc_attr( $themes['active_theme'] );
				}
			}

			self::init();
			if ( ! isset( self::$_instances[ $theme_key ] ) ) {
				$ld_prior_version = learndash_data_upgrades_setting( 'prior_version' );
				if ( 'new' === $ld_prior_version ) {
					$theme_key = LEARNDASH_DEFAULT_THEME;
				} else {
					$theme_key = LEARNDASH_LEGACY_THEME;
				}
			}
			return self::$_instances[ $theme_key ];
		}


		/**
		 * Get the Slug of the current active theme.
		 *
		 * @since 3.0.0
		 * @return string Key (slug) for active theme.
		 */
		final public static function get_active_theme_key() {
			$theme = self::get_active_theme_instance();
			if ( $theme ) {
				return $theme->get_theme_key();
			}
		}

		/**
		 * Get the Name of the current active theme.
		 *
		 * @since 3.0.0
		 * @return string Name active theme.
		 */
		final public static function get_active_theme_name() {
			$theme = self::get_active_theme_instance();
			if ( $theme ) {
				return $theme->get_theme_name();
			}
		}

		/**
		 * Get the base directory of the current active theme.
		 *
		 * @since 3.0.0
		 * @return string base directory path of active theme.
		 */
		final public static function get_active_theme_base_dir() {
			$theme = self::get_active_theme_instance();
			if ( $theme ) {
				return $theme->get_theme_base_dir();
			}
		}

		/**
		 * Get the base URL of the current active theme.
		 *
		 * @since 3.0.0
		 * @return string base URL of active theme.
		 */
		final public static function get_active_theme_base_url() {
			$theme = self::get_active_theme_instance();
			if ( $theme ) {
				return $theme->get_theme_base_url();
			}
		}

		/**
		 * Get the template directory of the current active theme.
		 *
		 * @since 3.0.0
		 * @return string directory path of active theme templates.
		 */
		final public static function get_active_theme_template_dir() {
			$theme = self::get_active_theme_instance();
			if ( $theme ) {
				return $theme->get_theme_template_dir();
			}
		}

		/**
		 * Get the URL of the current active theme.
		 *
		 * @since 3.0.0
		 * @return string URL of active theme templates.
		 */
		final public static function get_active_theme_template_url() {
			$theme = self::get_active_theme_instance();
			if ( $theme ) {
				return $theme->get_theme_template_url();
			}
		}

		/**
		 * Get the template directory of the current active theme.
		 *
		 * @since 3.0.0
		 * @return string directory path of active theme templates.
		 */
		final public static function get_active_theme_dir() {
			$theme = self::get_active_theme_instance();
			if ( $theme ) {
				return $theme->get_theme_dir();
			}
		}

		/**
		 * Get the URL of the current active theme.
		 *
		 * @since 3.0.0
		 * @return string URL of active theme templates.
		 */
		final public static function get_active_theme_url() {
			$theme = self::get_active_theme_instance();
			if ( $theme ) {
				return $theme->get_theme_url();
			}
		}

		/**
		 * Load the active theme.
		 *
		 * This function will call the theme method `load_theme()` where
		 * it can load needed files, enqueue assets, etc.
		 *
		 * @since 4.0.0
		 */
		final public static function load_active_theme() {
			$theme_instance = self::get_active_theme_instance();
			if ( ( $theme_instance ) && ( is_a( $theme_instance, 'LearnDash_Theme_Register' ) ) ) {
				$theme_instance->load_theme();

				/**
				 * Action when active theme is loading.
				 *
				 * @since 4.0.0
				 * @param string $theme_key Theme key.
				 */
				do_action( 'learndash_theme_load', $theme_instance->get_theme_key() );
			}
		}

		/**
		 * Get Theme instance names
		 *
		 * @since 3.0.0
		 * @param boolean $selectable only return selectable themes.
		 * @return array Array of themes by theme_key.
		 */
		final public static function get_themes( $selectable = true ) {
			self::init();

			$themes = array();

			if ( ! empty( self::$_instances ) ) {
				foreach ( self::$_instances as $theme_key => $theme_instance ) {
					if ( true === $theme_instance->theme_selectable ) {
						$themes[ $theme_instance->get_theme_name() ] = array(
							'theme_key'       => $theme_key,
							'theme_name'      => $theme_instance->get_theme_name(),
							'theme_directory' => $theme_instance->get_theme_template_dir(),
						);
					}
				}
			}

			if ( ! empty( $themes ) ) {
				ksort( $themes );
			}
			return $themes;
		}

		/**
		 * Public methods overridable by the individual theme.
		 */

		/**
		 * Utility function to check if a class theme_key is the active theme.
		 *
		 * @since 3.0.0
		 * @return boolean true if theme_key is the active theme, otherwise false.
		 */
		public function is_active() {
			if ( self::get_active_theme_key() === $this->theme_key ) {
				return true;
			}

			return false;
		}

		/**
		 * Get the Slug of the current theme.
		 *
		 * @since 3.0.0
		 * @return string Key (slug) for a theme.
		 */
		public function get_theme_key() {
			return $this->theme_key;
		}

		/**
		 * Get the Name of the current theme.
		 *
		 * @since 3.0.0
		 * @return string Name of a theme.
		 */
		public function get_theme_name() {
			return $this->theme_name;
		}

		/**
		 * Get the base directory of the current theme.
		 *
		 * @since 3.0.0
		 * @return string base directory path of current theme templates.
		 */
		public function get_theme_base_dir() {
			return $this->theme_base_dir;
		}

		/**
		 * Get the base URL of the current theme.
		 *
		 * @since 3.0.0
		 * @return string base URL of current theme templates.
		 */
		public function get_theme_base_url() {
			return $this->theme_base_url;
		}

		/**
		 * Get the template directory of the current theme.
		 *
		 * @since 3.0.0
		 * @return string template directory path of current theme.
		 */
		public function get_theme_template_dir() {
			return $this->theme_template_dir;
		}

		/**
		 * Get the template URL of the current theme.
		 *
		 * @since 3.0.0
		 * @return string template directory path of current theme.
		 */
		public function get_theme_template_url() {
			return $this->theme_template_url;
		}

		/**
		 * Get the template directory of the current theme.
		 *
		 * @since 3.0.0
		 * @return string directory path of current theme templates.
		 */
		public function get_theme_dir() {
			return $this->get_theme_template_dir();
		}

		/**
		 * Get the URL of the current theme.
		 *
		 * @since 3.0.0
		 * @return string URL of current theme templates.
		 */
		public function get_theme_url() {
			return $this->get_theme_template_url();
		}

		/**
		 * Get the settings sections registered for the theme.
		 *
		 * @since 3.0.0
		 *
		 * @return array Array of settings sections.
		 */
		public function get_theme_settings_sections() {
			if ( ! empty( $this->theme_settings_sections ) ) {
				return $this->theme_settings_sections;
			}

			return array();
		}

		/**
		 * Returns a flag that indicates if the theme supports views.
		 *
		 * @since 4.6.0
		 *
		 * @return bool
		 */
		public function supports_views(): bool {
			return $this->supports_views;
		}

		/**
		 * Returns an array of theme keys that inherit settings from this theme. Defaults to an empty array.
		 *
		 * @since 4.6.0
		 *
		 * @return string[]
		 */
		public function get_themes_inheriting_settings(): array {
			return array();
		}

		/**
		 * Load the theme settings sections.
		 *
		 * @since 4.0.0
		 */
		public function load_settings_sections() {
		}

		/**
		 * Load the theme files and assets.
		 *
		 * @since 4.0.0
		 */
		public function load_theme() {
		}
	}
}

/**
 * Checks if a theme_key is the active theme.
 *
 * @since 3.0.0
 *
 * @param string $theme_key Key/Slug for the theme to check.
 *
 * @return boolean true if theme_key is the active theme, otherwise false.
 */
function learndash_is_active_theme( $theme_key = '' ) {
	if ( ( ! empty( $theme_key ) ) && ( LearnDash_Theme_Register::get_active_theme_key() === $theme_key ) ) {
		return true;
	}

	return false;
}

add_action(
	'after_setup_theme',
	function() {
		LearnDash_Theme_Register::init();
		LearnDash_Theme_Register::load_active_theme();
		LearnDash_Theme_Register::load_themes_settings_sections();
	},
	20
);
