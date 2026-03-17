<?php
/**
 * BuddyBoss SSO Class.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO
 */

// Exit if accessed directly.
use BBSSO\BB_SSO_Notices;

defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp sso class.
 *
 * @since 2.6.30
 */
class BB_SSO {

	/**
	 * Settings object.
	 *
	 * @since 2.6.30
	 *
	 * @var BB_Social_Login_Settings
	 */
	public static $settings;

	/**
	 * Providers path.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	public static $providers_path;

	/**
	 * Providers.
	 *
	 * @since 2.6.30
	 *
	 * @var BB_SSO_Provider[]
	 */
	public static $providers = array();

	/**
	 * Allowed providers.
	 *
	 * @since 2.6.30
	 *
	 * @var BB_SSO_Provider[]
	 */
	public static $allowed_providers = array();

	/**
	 * Enabled providers.
	 *
	 * @since 2.6.30
	 *
	 * @var BB_SSO_Provider[]
	 */
	public static $enabled_providers = array();

	/**
	 * Counter.
	 *
	 * @since 2.6.30
	 *
	 * @var array
	 */
	public static $counter = 1;

	/**
	 * Current view.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	public static $wp_login_current_view = '';

	/**
	 * Current flow.
	 *
	 * @since 2.6.30
	 *
	 * @var string
	 */
	public static $wp_login_current_flow = 'login';

	/**
	 * Styles.
	 *
	 * @since 2.6.30
	 *
	 * @var array
	 */
	private static $styles = array(
		'fullwidth' => array(
			'container' => 'bb-sso-container-block-fullwidth',
			'align'     => array(),
		),
		'default'   => array(
			'container' => 'bb-sso-container-block',
			'align'     => array(
				'left',
				'right',
				'center',
			),
		),
		'icon'      => array(
			'container' => 'bb-sso-container-inline',
			'align'     => array(
				'left',
				'right',
				'center',
			),
		),
		'grid'      => array(
			'container' => 'bb-sso-container-grid',
			'align'     => array(
				'left',
				'right',
				'center',
				'space-around',
				'space-between',
			),
		),
	);

	/**
	 * Ordering.
	 *
	 * @since 2.6.30
	 *
	 * @var array
	 */
	private static $ordering = array();

	/**
	 * Unique ID for the sso.
	 *
	 * @since 2.6.30
	 *
	 * @var string SSO.
	 */
	public $id = 'sso';

	/**
	 * Filter applied.
	 *
	 * @since 2.6.60
	 *
	 * @var bool
	 */
	private static $filter_applied = false;

	/**
	 * SSO Constructor.
	 *
	 * @since 2.6.30
	 */
	public function __construct() {

		// Include the code.
		$this->includes();
		$this->plugins_loaded();
		$this->install();
	}

	/**
	 * Includes files.
	 *
	 * @since 2.6.30
	 *
	 * @param array $includes list of the files.
	 */
	public function includes( $includes = array() ) {

		$bb_platform_pro = bb_platform_pro();
		$slashed_path    = trailingslashit( $bb_platform_pro->sso_dir );

		$includes = array(
			'functions',
			'actions',
		);

		// Loop through files to be included.
		foreach ( (array) $includes as $file ) {

			if ( empty( $this->bb_sso_check_has_licence() ) ) {
				if ( in_array( $file, array( 'filters', 'rest-filters' ), true ) ) {
					continue;
				}
			}

			$paths = array(

				// Passed with no extension.
				'bb-' . $this->id . '-' . $file . '.php',
				'bb-' . $this->id . '/' . $file . '.php',

				// Passed with extension.
				$file,
				'bb-' . $this->id . '-' . $file,
				'bb-' . $this->id . '/' . $file,
			);

			foreach ( $paths as $path ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( @is_file( $slashed_path . $path ) ) {
					require $slashed_path . $path;
					break;
				}
			}
		}
	}

	/**
	 * Function to return the default value if no licence.
	 *
	 * @since 2.6.30
	 *
	 * @param bool $has_access Whether it has access.
	 *
	 * @return mixed Return the default.
	 */
	protected function bb_sso_check_has_licence( $has_access = true ) {

		if ( bb_pro_should_lock_features() ) {
			return false;
		}

		return $has_access;
	}

	/**
	 * Handles the actions that need to be set when all plugins are loaded.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	public function plugins_loaded() {
		add_action( 'bp_admin_enqueue_scripts', 'BB_SSO::bb_sso_admin_enqueue' );
		add_action( 'bp_admin_enqueue_scripts', 'BB_SSO::bb_sso_scripts', 100 );

		// Register the template for sso.
		bp_register_template_stack( array( $this, 'bb_register_sso_admin_template' ) );
		bp_register_template_stack( array( $this, 'bb_register_sso_template' ) );

		self::$settings = new BB_Social_Login_Settings(
			'bb_social_login',
			array(
				'enabled'  => array(),
				'ordering' => array(
					'google',
					'facebook',
					'twitter',
					'linkedin',
					'apple',
				),
			)
		);

		BB_Social_Login_Admin::init();

		do_action( 'bb_sso_start' );

		BB_SSO_Notices::init();

		self::$providers_path = bb_sso_path() . 'providers/';

		$providers = array_diff(
			scandir( self::$providers_path ),
			array(
				'..',
				'.',
			)
		);

		foreach ( $providers as $provider ) {
			if ( file_exists( self::$providers_path . $provider . '/class-bb-social-provider-' . $provider . '.php' ) ) {
				require_once self::$providers_path . $provider . '/class-bb-social-provider-' . $provider . '.php';
			}
		}

		do_action( 'bb_sso_add_providers' );

		self::$ordering = array_flip( self::$settings->get( 'ordering' ) );
		uksort( self::$providers, 'BB_SSO::sort_providers' );
		uksort( self::$allowed_providers, 'BB_SSO::sort_providers' );
		uksort( self::$enabled_providers, 'BB_SSO::sort_providers' );

		do_action( 'bb_sso_providers_loaded' );

		if ( ! bb_enable_sso_reg_options() ) {
			add_filter( 'bb_sso_is_register_allowed', 'BB_SSO::bb_sso_is_register_allowed' );
		}

		add_action( 'login_form_login', 'BB_SSO::login_form_login' );

		add_action( 'login_form_register', 'BB_SSO::login_form_register', 9 );
		add_action( 'login_form_link', 'BB_SSO::login_form_link' );
		add_action( 'bp_core_screen_signup', 'BB_SSO::bp_login_form_register' );

		add_action( 'login_form_unlink', 'BB_SSO::login_form_unlink' );

		add_action( 'parse_request', 'BB_SSO::edit_profile_redirect' );

		// check if DOM is ready.
		add_action( 'wp_print_scripts', 'BB_SSO::bb_sso_dom_ready' );

		add_action( 'delete_user', 'BB_SSO::delete_user' );

		if ( bb_enable_sso() && count( self::$enabled_providers ) > 0 ) {

			add_action( 'login_form', 'BB_SSO::add_login_form_buttons' );

			add_action( 'register_form', 'BB_SSO::add_register_form_buttons' );

			add_action( 'bp_sidebar_login_form', 'BB_SSO::add_login_buttons' );
			add_action( 'bp_settings_setup_nav', 'BB_SSO::bb_settings_setup_nav', 999999 );
			add_filter( 'bp_settings_admin_nav', 'BB_SSO::bb_settings_admin_nav', 999999 );

			if ( bp_enable_site_registration() ) {
				add_action( 'bp_after_register_page', 'BB_SSO::bp_register_form' );
			}

			add_action( 'profile_personal_options', 'BB_SSO::add_link_and_unlink_buttons' );

			add_action( 'login_enqueue_scripts', 'BB_SSO::bb_sso_scripts', 100 );
			add_action( 'bp_enqueue_scripts', 'BB_SSO::bb_sso_scripts', 100 );

			add_filter( 'bp_xprofile_field_edit_html_elements', 'BB_SSO::bb_sso_xprofile_field_edit_html_elements', 999999, 2 );
			add_filter( 'bp_get_signup_email_value', 'BB_SSO::bb_sso_bb_get_signup_email_value', 999999, 1 );
			add_filter( 'bp_get_signup_confirm_email_value', 'BB_SSO::bb_sso_bb_get_signup_confirm_email_value', 999999, 1 );

			if ( bb_sso_get_params_exists() ) {
				add_action( 'bp_before_register_page', 'BB_SSO::bb_modify_sso_registration_error_format' );
				add_action( 'bp_after_signup_profile_fields', 'BB_SSO::bb_sso_after_signup_profile' );
				add_action( 'bp_after_register_page', 'BB_SSO::bb_sso_after_register_page' );
				add_filter( 'bp_field_css_classes', 'BB_SSO::bb_sso_xprofile_field_css_class', 1 );
				add_filter( 'bb_nouveau_signup_field_class', 'BB_SSO::bb_sso_signup_field_css_class', 10, 2 );
				add_action( 'bp_signup_pre_validate', 'BB_SSO::bb_sso_signup_pre_validate' );
				add_action( 'user_register', 'BB_SSO::bb_sso_sync', 50 );
				add_action( 'template_notices', 'BB_SSO::bb_sso_register_template_notices', 10, 1 );
			}

			require_once bb_sso_path() . 'includes/class-bb-sso-avatar.php';
		}

		if ( bb_enable_sso() ) {
			add_filter( 'bbapp_build_request_json', 'BB_SSO::bb_sso_include_app_social_login_keys', 10, 1 );
		}

		do_action( 'bb_sso_init' );

		if ( ! empty( $_REQUEST['bb_social_login'] ) ) {

			/**
			 * Provide compatibility with All In One WP Security & Firewall plugin.
			 */
			if ( empty( $_GET['action'] ) ) {
				$_GET['action'] = 'bb-sso-login';
			}

			/**
			 * Provide compatibility with WPS Hide Login plugin.
			 */
			if ( empty( $_REQUEST['action'] ) ) {
				$_REQUEST['action'] = 'bb-sso-login';
			}
		}
	}

	/**
	 * Installs the social sign-on users table.
	 *
	 * @since 2.6.30
	 *
	 * @return void
	 */
	public function install() {
		global $wpdb;
		$bb_prefix       = function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->base_prefix;
		$table_name      = $bb_prefix . 'bb_social_sign_on_users';
		$charset_collate = $wpdb->get_charset_collate();
		$has_table       = $wpdb->query( $wpdb->prepare( 'show tables like %s', $table_name ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! empty( $has_table ) ) {
			return;
		}
		$sql = 'CREATE TABLE ' . $table_name . ' (
	        `id` int NOT NULL AUTO_INCREMENT,
	        `wp_user_id` int NOT NULL,
	        `first_name` varchar(255),
	        `last_name` varchar(255),
	        `type` varchar(20) NOT NULL,
	        `identifier` varchar(100) NOT NULL,
	        `register_date` datetime default NULL,
	        `login_date` datetime default NULL,
	        `link_date` datetime default NULL,
	        PRIMARY KEY  (id),
	        KEY `wp_user_id` (`wp_user_id`,`type`),
	        KEY `first_name` (`first_name`),
	        KEY `last_name` (`last_name`),
	        KEY `identifier` (`identifier`)
	        ) ' . $charset_collate . ';';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Retrieves a provider by its ID if it is enabled.
	 *
	 * @since 2.6.30
	 *
	 * @param string $provider_id The provider ID.
	 *
	 * @return BB_SSO_Provider|false The provider if enabled, false otherwise.
	 */
	public static function get_provider_by_provider_id( $provider_id ) {
		if ( self::is_provider_enabled( $provider_id ) ) {
			return self::$enabled_providers[ $provider_id ];
		}

		return false;
	}

	/**
	 * Checks if a provider is enabled.
	 *
	 * @since 2.6.30
	 *
	 * @param string $provider_id The provider ID.
	 *
	 * @return bool True if the provider is enabled, false otherwise.
	 */
	public static function is_provider_enabled( $provider_id ) {
		return isset( self::$enabled_providers[ $provider_id ] );
	}

	/**
	 * Filters xProfile field edit HTML elements for registration.
	 *
	 * @since 2.6.30
	 *
	 * @param array $elements The HTML elements.
	 * @param array $value    The class instance value.
	 *
	 * @return array The filtered HTML elements.
	 */
	public static function bb_sso_xprofile_field_edit_html_elements( $elements, $value ) {
		// If the value is not set and empty and the query params are set, use the query params.
		if (
			empty( $elements['value'] ) &&
			isset( $_GET[ bp_get_the_profile_field_input_name() ] ) && // phpcs:ignore
			bp_is_register_page()
		) {
			$elements['value'] = sanitize_text_field( wp_unslash( $_GET[ bp_get_the_profile_field_input_name() ] ) ); // phpcs:ignore
		}

		return $elements;
	}

	/**
	 * Filters the signup email value on the registration page.
	 *
	 * @since 2.6.30
	 *
	 * @param string $value The signup email value.
	 *
	 * @return string The filtered email value.
	 */
	public static function bb_sso_bb_get_signup_email_value( $value ) {
		// If the value is not set and empty and the query params are set, use the query params.
		if ( bp_is_register_page() && isset( $_POST['signup_email'] ) ) { // phpcs:ignore
			$value = sanitize_text_field( wp_unslash( $_POST['signup_email'] ) ); // phpcs:ignore
		} elseif ( bp_is_register_page() && empty( $_POST['signup_email'] ) && isset( $_GET['signup_email'] ) ) { // phpcs:ignore
			$value = sanitize_text_field( wp_unslash( $_GET['signup_email'] ) ); // phpcs:ignore
		}

		return $value;
	}

	/**
	 * Filters the signup confirm email value on the registration page.
	 *
	 * @since 2.6.30
	 *
	 * @param string $value The signup confirm email value.
	 *
	 * @return string The filtered email value.
	 */
	public static function bb_sso_bb_get_signup_confirm_email_value( $value ) {
		// If the value is not set and empty and the query params are set, use the query params.
		if ( bp_is_register_page() && isset( $_GET['signup_email_confirm'] ) ) { // phpcs:ignore
            $value = sanitize_text_field( wp_unslash( $_GET['signup_email_confirm'] ) ); // phpcs:ignore
		}

		return $value;
	}

	/**
	 * Sorts providers based on predefined ordering.
	 *
	 * @since 2.6.30
	 *
	 * @param string $a The first provider.
	 * @param string $b The second provider.
	 *
	 * @return int Comparison result for sorting.
	 */
	public static function sort_providers( $a, $b ) {
		if ( isset( self::$ordering[ $a ] ) && isset( self::$ordering[ $b ] ) ) {
			if ( self::$ordering[ $a ] < self::$ordering[ $b ] ) {
				return -1;
			}

			return 1;
		}
		if ( isset( self::$ordering[ $a ] ) ) {
			return -1;
		}

		return 1;
	}

	/**
	 * Gets the required capability for managing settings.
	 *
	 * @since 2.6.30
	 *
	 * @return string The required capability.
	 */
	public static function get_required_capability() {
		return apply_filters( 'bb_sso_required_capability', 'manage_options' );
	}

	/**
	 * Injects a script to check when the DOM is ready.
	 *
	 * @since 2.6.30
	 */
	public static function bb_sso_dom_ready() {
		echo '<script type="text/javascript">
            window._bbssoDOMReady = function (callback) {
                if ( document.readyState === "complete" || document.readyState === "interactive" ) {
                    callback();
                } else {
                    document.addEventListener( "DOMContentLoaded", callback );
                }
            };
            </script>';
	}

	/**
	 * Outputs scripts for the page, including localized strings and options.
	 *
	 * @since 2.6.30
	 * @since 2.7.20
	 * Added new param $hook to enqueue the script only on the BuddyPress settings page.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public static function bb_sso_scripts( $hook ) {
		if ( is_admin() && false === strpos( $hook, 'bp-settings' ) ) {
			return;
		}
		$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$rtl_css = is_rtl() ? '-rtl' : '';

		$css_prefix =
			function_exists( 'bb_is_readylaunch_enabled' ) &&
			bb_is_readylaunch_enabled() &&
			class_exists( 'BB_Readylaunch' ) &&
			bb_load_readylaunch()->bb_is_readylaunch_enabled_for_page()
			? 'bb-rl-' : 'bb-';
		wp_enqueue_style( 'bb-sso-login', bb_sso_url( '/assets/css/' . $css_prefix . 'sso-login' . $rtl_css . $min . '.css' ), array(), bb_platform_pro()->version );

		wp_enqueue_script( 'bb-sso', bb_sso_url( '/assets/js/bb-sso' . $min . '.js' ), array(), bb_platform_pro()->version, true );
		$localized_strings = array(
			'redirect_overlay_title'    => __( 'Hold On', 'buddyboss-pro' ),
			'redirect_overlay_text'     => __( 'You are being redirected to another page,<br>it may take a few seconds.', 'buddyboss-pro' ),
			'webview_notification_text' => __( 'The selected provider doesn\'t support embedded browsers!', 'buddyboss-pro' ),
		);
		$script_options    = array(
			'_localizedStrings' => $localized_strings,
			'_targetWindow'     => 'prefer-popup',
			'_redirectOverlay'  => 'overlay-with-spinner-and-message',
		);
		wp_localize_script(
			'bb-sso',
			'bbSSOVars',
			array(
				'scriptOptions' => wp_json_encode( $script_options ),
			)
		);
	}

	/**
	 * Adds a provider to the list of providers and enables it if applicable.
	 *
	 * @since 2.6.30
	 *
	 * @param BB_SSO_Provider_Dummy $provider The provider instance to add.
	 */
	public static function add_provider( $provider ) {
		if ( in_array( $provider->get_id(), self::$settings->get( 'enabled' ), true ) ) {
			if ( $provider->is_tested() && $provider->enable() ) {
				self::$enabled_providers[ $provider->get_id() ] = $provider;
			}
		}
		self::$providers[ $provider->get_id() ] = $provider;

		if ( $provider instanceof BB_SSO_Provider ) {
			self::$allowed_providers[ $provider->get_id() ] = $provider;
		}
	}

	/**
	 * Enables a provider by provider ID.
	 *
	 * @since 2.6.30
	 *
	 * @param string $provider_id The provider ID to enable.
	 */
	public static function enable_provider( $provider_id ) {
		if ( isset( self::$providers[ $provider_id ] ) ) {
			$enabled   = self::$settings->get( 'enabled' );
			$enabled[] = self::$providers[ $provider_id ]->get_id();
			$enabled   = array_unique( $enabled );

			self::$settings->update(
				array(
					'enabled' => $enabled,
				)
			);
		}
	}

	/**
	 * Disables a provider by provider ID.
	 *
	 * @since 2.6.30
	 *
	 * @param string $provider_id The provider ID to disable.
	 */
	public static function disable_provider( $provider_id ) {
		if ( isset( self::$providers[ $provider_id ] ) ) {

			$enabled = array_diff( self::$settings->get( 'enabled' ), array( self::$providers[ $provider_id ]->get_id() ) );

			self::$settings->update(
				array(
					'enabled' => $enabled,
				)
			);
		}
	}

	/**
	 * Initiates the login form view.
	 *
	 * @since 2.6.30
	 */
	public static function login_form_login() {
		self::$wp_login_current_view = 'login';
		self::login_init();
	}

	/**
	 * Initializes login form processing and social login detection.
	 *
	 * @since 2.6.30
	 */
	public static function login_init() {

		add_filter( 'wp_login_errors', 'BB_SSO::wp_login_errors' );

		$bb_custom_login = function_exists( 'buddyboss_theme_get_option' ) && buddyboss_theme_get_option( 'boss_custom_login' );
		if ( $bb_custom_login && isset( $_GET['bb-sso-notice'] ) && 1 === (int) $_GET['bb-sso-notice'] ) { // phpcs:ignore
			add_filter( 'login_messages', 'BB_SSO::bb_wp_login_messages' );
		}

		if ( isset( $_REQUEST['login_facebook'] ) && 1 === (int) $_REQUEST['login_facebook'] ) { // phpcs:ignore
			$_REQUEST['bb_social_login'] = 'facebook';
		}
		if ( isset( $_REQUEST['login_google'] ) && 1 === (int) $_REQUEST['login_google'] ) { // phpcs:ignore
			$_REQUEST['bb_social_login'] = 'google';
		}
		if ( isset( $_REQUEST['login_twitter'] ) && 1 === (int) $_REQUEST['loginTwitter'] ) { // phpcs:ignore
			$_REQUEST['bb_social_login'] = 'twitter';
		}
		if ( isset( $_REQUEST['login_apple'] ) && 1 === (int) $_REQUEST['login_apple'] ) { // phpcs:ignore
			$_REQUEST['bb_social_login'] = 'apple';
		}
		if ( isset( $_REQUEST['login_linkedin'] ) && 1 === (int) $_REQUEST['login_linkedin'] ) { // phpcs:ignore
			$_REQUEST['bb_social_login'] = 'linkedin';
		}

		if ( isset( $_REQUEST['bb_social_login'] ) && is_string( $_REQUEST['bb_social_login'] ) && isset( self::$providers[ $_REQUEST['bb_social_login'] ] ) && ( self::$providers[ $_REQUEST['bb_social_login'] ]->is_enabled() || self::$providers[ $_REQUEST['bb_social_login'] ]->is_test() ) ) { // phpcs:ignore
			nocache_headers();
			if ( is_user_logged_in() && 'apple' === $_REQUEST['bb_social_login'] ) { // phpcs:ignore
				$_REQUEST['test'] = 1;
			}
			self::$providers[ $_REQUEST['bb_social_login'] ]->connect(); // phpcs:ignore
		}
	}

	/**
	 * Initiates the register form view.
	 *
	 * @since 2.6.30
	 */
	public static function login_form_register() {
		self::$wp_login_current_view = 'register';
		self::login_init();
	}

	/**
	 * Initiates the BuddyPress register form view.
	 *
	 * @since 2.6.30
	 */
	public static function bp_login_form_register() {
		self::$wp_login_current_view = 'register-bp';
		self::login_init();
	}

	/**
	 * Initiates the link form view for social accounts.
	 *
	 * @since 2.6.30
	 */
	public static function login_form_link() {
		self::$wp_login_current_view = 'link';
		self::login_init();
	}

	/**
	 * Initiates the unlink form view for social accounts.
	 *
	 * @since 2.6.30
	 */
	public static function login_form_unlink() {
		self::$wp_login_current_view = 'unlink';
		self::login_init();
	}

	/**
	 * Adds custom error messages to the login errors.
	 *
	 * @since 2.6.30
	 *
	 * @param WP_Error $errors WP error object containing login errors.
	 *
	 * @return WP_Error Modified WP error object.
	 */
	public static function wp_login_errors( $errors ) {

		if ( empty( $errors ) ) {
			$errors = new WP_Error();
		}

		$error_messages = BB_SSO_Notices::get_errors(); // Display an error message on the login page.
		if ( false !== $error_messages ) {
			foreach ( $error_messages as $error_message ) {
				$errors->add( 'error', $error_message );
			}
		}

		$info_messages = BB_SSO_Notices::get_infos(); // Display an info message on the login page.
		if ( false !== $info_messages ) {
			foreach ( $info_messages as $error_message ) {
				$errors->add( 'info', $error_message, 'message' );
			}
		}

		return $errors;
	}

	/**
	 * Redirects user to the profile edit page when 'edit_profile_redirect' is set.
	 *
	 * @since 2.6.30
	 */
	public static function edit_profile_redirect() {
		global $wp;

		if ( isset( $wp->query_vars['edit_profile_redirect'] ) ) {
			if ( function_exists( 'bp_loggedin_user_domain' ) ) {
				header( 'LOCATION: ' . bp_loggedin_user_domain() . 'profile/edit/group/1/' );
			} else {
				header( 'LOCATION: ' . self_admin_url( 'profile.php' ) );
			}
			exit;
		}
	}

	/**
	 * Outputs rendered login buttons for the login form.
	 *
	 * @since 2.6.30
	 */
	public static function add_login_form_buttons() {
		echo self::get_rendered_login_buttons();
	}

	/**
	 * Renders the social login buttons based on the given label type.
	 *
	 * @since 2.6.30
	 *
	 * @param string $label_type The type of label to use for the buttons (default: 'login').
	 *
	 * @return string Rendered HTML for the login buttons.
	 */
	private static function get_rendered_login_buttons( $label_type = 'login' ) {

		ob_start();

		$index        = self::$counter++;
		$container_id = 'bb-sso-custom-login-form-' . $index;

		$ret = '<div id="' . esc_attr( $container_id ) . '">';

		$ret .= self::render_buttons_with_container( array( 'label_type' => $label_type ) );
		$ret .= '</div>';
		echo $ret;

		bp_get_template_part( 'login/below-separator', false, array( 'container_id' => $container_id ) );

		return ob_get_clean();
	}

	/**
	 * Renders social login buttons within a container.
	 *
	 * @since 2.6.30
	 *
	 * @param array $args Arguments for rendering the buttons.
	 *                    {
	 *                    Optional. Arguments for rendering the buttons.
	 *                    - style: Button style.
	 *                    - providers: Social login providers.
	 *                    - redirect_to: Redirect URL after login.
	 *                    - tracker_data: Tracking data for analytics.
	 *                    - align: Button alignment (default: 'left').
	 *                    - label_type: Type of button label ('login' or 'register').
	 *                    }
	 *
	 * @return string HTML of the rendered buttons.
	 */
	public static function render_buttons_with_container( $args = array() ) {

		$defaults = array(
			'style'        => 'default',
			'providers'    => false,
			'redirect_to'  => false,
			'tracker_data' => false,
			'align'        => 'left',
			'label_type'   => 'login',
		);

		// Parse the incoming arguments with the defaults.
		$parsed_args = wp_parse_args( $args, $defaults );

		return self::render_buttons_with_container_and_title( $parsed_args );
	}

	/**
	 * Renders social login buttons with an optional heading and container.
	 *
	 * @since 2.6.30
	 *
	 * @param array $args Arguments for rendering the buttons.
	 *                    {
	 *                    Optional. Arguments for rendering the buttons.
	 *                    - heading: Heading text to display.
	 *                    - style: Button style.
	 *                    - providers: Social login providers.
	 *                    - redirect_to: Redirect URL after login.
	 *                    - tracker_data: Tracking data for analytics.
	 *                    - align: Button alignment (default: 'center').
	 *                    - label_type: Type of button label ('login' or 'register').
	 *                    - custom_label: Custom label for the buttons.
	 *                    }
	 *
	 * @return string HTML of the rendered buttons with container and title.
	 */
	private static function render_buttons_with_container_and_title( $args = array() ) {
		$defaults = array(
			'heading'      => false,
			'style'        => 'default',
			'providers'    => false,
			'redirect_to'  => false,
			'tracker_data' => false,
			'align'        => 'center',
			'label_type'   => 'login',
			'custom_label' => false,
		);

		// Parse the incoming arguments with the defaults.
		$parsed_args = wp_parse_args( $args, $defaults );

		// Extract parsed arguments.
		$heading      = $parsed_args['heading'];
		$style        = $parsed_args['style'];
		$providers    = $parsed_args['providers'];
		$redirect_to  = $parsed_args['redirect_to'];
		$tracker_data = $parsed_args['tracker_data'];
		$align        = $parsed_args['align'];
		$label_type   = $parsed_args['label_type'];
		$custom_label = $parsed_args['custom_label'];

		if ( ! is_user_logged_in() ) {

			if ( ! isset( self::$styles[ $style ] ) ) {
				$style = 'default';
			}

			if ( ! in_array( $align, self::$styles[ $style ]['align'], true ) ) {
				$align = 'center';
			}

			$enabled_providers = false;
			if ( is_array( $providers ) ) {
				$enabled_providers = array();
				foreach ( $providers as $provider ) {
					if ( $provider && isset( self::$enabled_providers[ $provider->get_id() ] ) ) {
						$enabled_providers[ $provider->get_id() ] = $provider;
					}
				}
			}
			if ( false === $enabled_providers ) {
				$enabled_providers = self::$enabled_providers;
			}

			if ( count( $enabled_providers ) ) {
				$buttons = '';
				foreach ( $enabled_providers as $provider ) {
					$buttons .= $provider->get_connect_button( $style, $redirect_to, $tracker_data, $label_type, $custom_label );
				}

				if ( ! empty( $heading ) ) {
					$heading = '<h2>' . $heading . '</h2>';
				} else {
					$heading = '';
				}

				$buttons = '<div class="bb-sso-container-buttons">' . $buttons . '</div>';

				$ret = '<div class="bb-sso-container ' . self::$styles[ $style ]['container'] . '"' . ( 'fullwidth' !== $style ? ' data-align="' . esc_attr( $align ) . '"' : '' ) . '>' . $heading . $buttons . '</div>';

				return $ret;
			}
		}

		return '';
	}

	/**
	 * Outputs rendered login buttons for the general login action.
	 *
	 * @since 2.6.30
	 */
	public static function add_login_buttons() {
		echo self::get_rendered_login_buttons(); // phpcs:ignore
	}

	/**
	 * Outputs rendered register buttons for the registration form.
	 *
	 * @since 2.6.30
	 */
	public static function add_register_form_buttons() {
		echo self::get_rendered_login_buttons( 'register' ); // phpcs:ignore
	}

	/**
	 * Outputs buttons for linking and unlinking social accounts.
	 *
	 * @since 2.6.30
	 */
	public static function add_link_and_unlink_buttons() {
		echo self::render_link_and_unlink_buttons();
	}

	/**
	 * Renders the Link and Unlink buttons for the connected social providers for the logged-in user.
	 *
	 * This function generates buttons that allow the user to link or unlink their social accounts
	 * based on the available providers and the current user's connection status. It checks the
	 * specified style and alignment options, and if linking or unlinking is allowed.
	 *
	 * @since 2.6.30
	 *
	 * @param array $args Array of arguments
	 *                    {
	 *                    Optional. Arguments for rendering the buttons.
	 *                    heading (string) The heading text to display above the buttons.
	 *                    link (bool) Whether to display the link buttons (default: true).
	 *                    unlink (bool) Whether to display the unlink buttons (default: true).
	 *                    align (string) The alignment of the buttons (default: 'left').
	 *                    providers (array) The social login providers to display buttons for.
	 *                    style (string) The style of the buttons (default: 'default').
	 *                    }
	 *
	 * @return string Returns the HTML for the button container if providers are enabled and the user is logged in;
	 *                otherwise, returns an empty string.
	 */
	public static function render_link_and_unlink_buttons( $args = array() ) {

		// Add the container style to the arguments to be used in the template as a private variable.
		$args['container_style'] = self::$styles;

		ob_start();
		bp_get_template_part( 'members/single/settings/sso-links', null, $args );
		return ob_get_clean();
	}

	/**
	 * Retrieves the avatar for a specified user from the enabled social providers.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id The ID of the user for whom to retrieve the avatar.
	 *
	 * @return string|bool The avatar URL if found, otherwise false.
	 */
	public static function get_avatar( $user_id ) {
		foreach ( self::$enabled_providers as $provider ) {
			$avatar = $provider->get_avatar( $user_id );
			if ( false !== $avatar ) {
				return $avatar;
			}
		}

		return false;
	}

	/**
	 * Gets the current page URL.
	 *
	 * @since 2.6.30
	 *
	 * @return string|bool The current page URL if valid; otherwise, false.
	 */
	public static function get_current_page_url() {

		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || empty( $_SERVER['HTTP_HOST'] ) || empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

		if ( ! self::is_allowed_redirect_url( $current_url ) ) {
			return false;
		}

		return $current_url;
	}

	/**
	 * Checks if the given URL is allowed for redirection.
	 *
	 * The URL is considered not allowed if it matches the login or registration URLs.
	 *
	 * @since 2.6.30
	 *
	 * @param string $url The URL to check for redirection.
	 *
	 * @return bool True if the URL is allowed; otherwise, false.
	 */
	public static function is_allowed_redirect_url( $url ) {
		$login_url = self::get_login_url();

		// If the currentUrl is the loginUrl, then we should not return it for redirects.
		if ( 0 === strpos( $url, $login_url ) ) {
			return false;
		}

		$login_url2 = site_url( 'wp-login.php' );

		// If the currentUrl is the loginUrl, then we should not return it for redirects.
		if ( $login_url2 !== $login_url && 0 === strpos( $url, $login_url2 ) ) {
			return false;
		}

		$register_url = wp_registration_url();
		// If the currentUrl is the registerUrl, then we should not return it for redirects.
		if ( 0 === strpos( $url, $register_url ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Retrieves the login URL for the site.
	 *
	 * @since 2.6.30
	 *
	 * @param string $scheme Optional. The scheme for the URL. Default is 'login'.
	 *
	 * @return string The login URL.
	 */
	public static function get_login_url( $scheme = 'login' ) {

		return site_url( 'wp-login.php', $scheme );
	}

	/**
	 * Retrieves the registration URL for the site.
	 *
	 * @since 2.6.30
	 *
	 * @return string The registration URL.
	 */
	public static function get_register_url() {

		return wp_registration_url();
	}

	/**
	 * Deletes a user and their associated data.
	 *
	 * @since 2.6.30
	 *
	 * @param int $user_id The ID of the user to delete.
	 */
	public static function delete_user( $user_id ) {
		global $wpdb, $blog_id;

		$table_name      = $wpdb->base_prefix . 'bb_social_sign_on_users';
		$fetch_user_data = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT type FROM `' . $table_name . '` WHERE wp_user_id = %d',
				array(
					$user_id,
				)
			),
			ARRAY_A
		);
		if ( ! empty( $fetch_user_data ) ) {
			foreach ( $fetch_user_data as $data ) {
				if ( 'apple' === $data['type'] ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
					// Don't delete the recode from the bb_social_sign_on_users table, instead set the wp_user_id to 0 for apple provider.
					$wpdb->update(
						$table_name,
						array(
							'wp_user_id' => 0,
						),
						array(
							'wp_user_id' => $user_id,
							'type'       => 'apple',
						),
						array(
							'%d',
						),
						array(
							'%d',
							'%s',
						)
					);
				} else {
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
					// Delete the recode from the bb_social_sign_on_users table.
					$wpdb->query(
						$wpdb->prepare(
							'DELETE FROM `' . $table_name . '` WHERE wp_user_id = %d AND type != %s',
							array(
								$user_id,
								'apple',
							)
						)
					);
				}
			}
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
			// Delete the recode from the bb_social_sign_on_users table.
			$wpdb->query(
				$wpdb->prepare(
					'DELETE FROM `' . $table_name . '` WHERE wp_user_id = %d',
					array(
						$user_id,
					)
				)
			);
		}

		$attachment_id = get_user_meta( $user_id, $wpdb->get_blog_prefix( $blog_id ) . 'user_avatar', true );
		if ( wp_attachment_is_image( $attachment_id ) ) {
			wp_delete_attachment( $attachment_id, true );
		}
	}

	/**
	 * Sets up the navigation for the BuddyPress settings.
	 *
	 * Adds a "Social Accounts" tab to the settings navigation if the settings
	 * and members components are active.
	 *
	 * @since 2.6.30
	 */
	public static function bb_settings_setup_nav() {

		if ( ! bp_is_active( 'settings' ) || ! bp_is_active( 'members' ) ) {
			return;
		}

		// Determine user to use.
		if ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		// Get the settings slug.
		$settings_slug = bp_get_settings_slug();

		$subnav_slug = apply_filters( 'bb_sso_bp_social_accounts_tab_slug', 'social' );
		if ( buddypress()->members->nav->get( $settings_slug . '/' . $subnav_slug ) ) {
			/**
			 * If there is a sub-nav item with the used slug, then we should use "bb-sso-social" as a fallback.
			 */
			$subnav_slug = 'bb-sso-social';
		}

		bp_core_new_subnav_item(
			array(
				'name'            => __( 'Social Accounts', 'buddyboss-pro' ),
				'slug'            => $subnav_slug,
				'parent_url'      => trailingslashit( $user_domain . $settings_slug ),
				'parent_slug'     => $settings_slug,
				'screen_function' => 'BB_SSO::bp_display_account_link',
				'position'        => 29,
				'user_has_access' => bp_core_can_edit_settings(),
				'item_css_class'  => 'bb-sso-accounts',
			),
			'members'
		);
	}

	/**
	 * Displays the account link for social login.
	 *
	 * Sets the template title and content for the BuddyPress social login page.
	 *
	 * @since 2.6.30
	 */
	public static function bp_display_account_link() {

		add_action( 'bp_template_title', 'BB_SSO::bp_template_title' );
		add_action( 'bp_template_content', 'BB_SSO::bp_template_content' );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	/**
	 * Sets the template title for the social login page.
	 *
	 * @since 2.6.30
	 */
	public static function bp_template_title() {
		esc_html_e( 'Social Accounts', 'buddyboss-pro' );
	}

	/**
	 * Outputs the content for the social login page.
	 *
	 * Renders the link and unlink buttons for social login.
	 *
	 * @since 2.6.30
	 */
	public static function bp_template_content() {
		echo self::render_link_and_unlink_buttons();
	}

	/**
	 * Displays the registration form for social login.
	 *
	 * Renders the custom login form and includes the specified template.
	 *
	 * @since 2.6.30
	 */
	public static function bp_register_form() {
		$signup_step = function_exists( 'bp_get_current_signup_step' ) ? bp_get_current_signup_step() : '';
		if ( ! empty( $signup_step ) && 'completed-confirmation' === $signup_step ) {
			return;
		}
		$bb_sso_notice = isset( $_GET['bb-sso-notice'] ) ? (int) $_GET['bb-sso-notice'] : 0;
		if (
			(
				isset( $_GET['signup_email'] ) &&
				1 === $bb_sso_notice
			) ||
			(
				isset( $_GET['bp-invites'] ) &&
				'accept-member-invitation' === $_GET['bp-invites']
			)
		) {
			return;
		}

		$index        = self::$counter++;
		$container_id = 'bb-sso-custom-login-form-' . $index;

		echo '<div id="' . esc_attr( $container_id ) . '">' . self::render_buttons_with_container( array( 'label_type' => 'register' ) ) . '</div>';

		bp_get_template_part( 'register/above-separator', false, array( 'container_id' => $container_id ) );
	}

	/**
	 * Retrieves the domain of the site without the 'www' prefix.
	 *
	 * @since 2.6.30
	 *
	 * @return string The site domain.
	 */
	public static function get_domain() {
		return preg_replace( '/^www\./', '', wp_parse_url( site_url(), PHP_URL_HOST ) );
	}

	/**
	 * Checks if there are any configured providers that are disabled.
	 *
	 * @since 2.6.30
	 *
	 * @return bool True if there are providers configured but none are enabled; otherwise, false.
	 */
	public static function has_configuration_with_no_enabled_providers() {
		if ( 0 === count( self::$enabled_providers ) ) {
			foreach ( self::$providers as $provider ) {
				$state = $provider->get_state();
				// Has providers configured, but none of them are enabled.
				if ( 'disabled' === $state ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Adds a query argument to a URL to enable a notice.
	 *
	 * @since 2.6.30
	 *
	 * @param string $url The original URL.
	 *
	 * @return string The modified URL with the notice parameter.
	 */
	public static function enable_notice_for_url( $url ) {
		return add_query_arg( array( 'bb-sso-notice' => 1 ), $url );
	}

	/**
	 * Retrieves the user ID based on a provided ID or email.
	 *
	 * @since 2.6.30
	 *
	 * @param mixed $id_or_email The user ID, email, or user object.
	 *
	 * @return int The user ID or 0 if not found.
	 */
	public static function get_user_id_by_id_or_email( $id_or_email ) {
		$id = 0;

		/**
		 * Get the user id depending on the $id_or_email, it can be the user id, email and object.
		 */
		if ( is_numeric( $id_or_email ) ) {
			$id = $id_or_email;
		} elseif ( is_string( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
			if ( $user ) {
				$id = $user->ID;
			}
		} elseif ( is_object( $id_or_email ) ) {
			if ( ! empty( $id_or_email->comment_author_email ) ) {
				$user = get_user_by( 'email', $id_or_email->comment_author_email );
				if ( $user ) {
					$id = $user->ID;
				}
			} elseif ( ! empty( $id_or_email->user_id ) ) {
				$id = $id_or_email->user_id;
			}
		}

		return $id;
	}

	/**
	 * Optionally adds a bypass cache argument to a URL for logged-in users.
	 *
	 * For logged-in users, this function might add the 'bb_sso_bypass_cache' GET parameter to a URL
	 * with a unique value to bypass the cache.
	 *
	 * @since 2.6.30
	 *
	 * @param string $url The original URL.
	 *
	 * @return string The modified URL with the bypass cache argument if applicable.
	 */
	public static function maybe_add_bypass_cache_arg_to_url( $url ) {
		if ( $url ) {
			if ( is_user_logged_in() ) {
				if ( has_filter( 'bb_sso_bypass_cache_url' ) ) {
					$url = apply_filters( 'bb_sso_bypass_cache_url', $url );
				} else {
					$url = add_query_arg( array( 'bb_sso_bypass_cache' => wp_hash( get_current_user_id() . time() ) ), $url );
				}
			}
		}

		return $url;
	}

	/**
	 * Retrieves a specific setting for a provider.
	 *
	 * @since 2.6.30
	 *
	 * @param string $provider_id Provider ID.
	 * @param string $key         Key of the setting to retrieve.
	 *
	 * @return mixed The value of the setting or an empty string if not set.
	 */
	public static function get_provider_setting( $provider_id, $key ) {
		if ( isset( self::$providers[ $provider_id ] ) ) {
			$sso_value = self::$providers[ $provider_id ]->settings->get( $key );

			return ! empty( $sso_value ) ? $sso_value : '';
		}
	}

	/**
	 * Sending data to the App center for social login.
	 *
	 * @since 2.6.30
	 *
	 * @param string $json JSON data to send to the App center.
	 *
	 * @return string JSON data to send to the App center.
	 */
	public static function bb_sso_include_app_social_login_keys( $json ) {
		$json_arr = ! empty( $json ) ? json_decode( $json, true ) : array(); // Decode as array.

		// Validate that providers exist and are iterable.
		$providers   = is_array( self::$enabled_providers ) ? self::$enabled_providers : array();
		$environment = ! empty( $json_arr['env'] ) ? $json_arr['env'] : 'dev';

		$social_login = array();
		if ( ! empty( $providers ) ) {
			foreach ( $providers as $provider ) {
				$provider_id                 = $provider->get_id();
				$provider_options            = bp_get_option( 'bb_sso_' . $provider_id );
				$serialized_provider_options = is_serialized( $provider_options ) ? maybe_unserialize( $provider_options ) : $provider_options;

				$ids_arranged            = array();
				$ids_arranged['enabled'] = $provider->is_enabled();
				switch ( $provider_id ) {
					case 'google':
						$ids_arranged['web_id']     = $serialized_provider_options['client_id'];
						$ids_arranged['web_secret'] = $serialized_provider_options['client_secret'];
						if ( 'live' === $environment ) {
							$ids_arranged['ios_id']     = $serialized_provider_options['app_ios_client_id'];
							$ids_arranged['android_id'] = $serialized_provider_options['app_android_client_id'];
						} else {
							$ids_arranged['ios_id']     = $serialized_provider_options['app_ios_test_client_id'];
							$ids_arranged['android_id'] = $serialized_provider_options['app_android_test_client_id'];
						}
						break;
					case 'twitter':
					case 'linkedin':
						$ids_arranged['app_id']     = $serialized_provider_options['client_id'];
						$ids_arranged['app_secret'] = $serialized_provider_options['client_secret'];
						break;
					case 'facebook':
						$ids_arranged['app_id']     = $serialized_provider_options['appid'];
						$ids_arranged['app_secret'] = $serialized_provider_options['secret'];
						break;
					case 'apple':
						$ids_arranged['app_id']       = $serialized_provider_options['private_key_id'];
						$ids_arranged['app_secret']   = $serialized_provider_options['client_secret'];
						$ids_arranged['team_id']      = $serialized_provider_options['team_identifier'];
						$ids_arranged['service_id']   = $serialized_provider_options['service_identifier'];
						$ids_arranged['callback_url'] = $serialized_provider_options['oauth_redirect_url'];
						if ( null !== self::$allowed_providers['apple']->get_redirect_uri_for_auth_flow() ) {
							$ids_arranged['callback_url'] = self::$allowed_providers['apple']->get_redirect_uri_for_auth_flow();
						}
						break;
					case 'microsoft':
						$ids_arranged['microsoft_client_id']     = $serialized_provider_options['client_id'];
						$ids_arranged['microsoft_client_secret'] = $serialized_provider_options['client_secret'];
						$ids_arranged['microsoft_tenant']        = $serialized_provider_options['tenant'];
						$ids_arranged['microsoft_prompt']        = $serialized_provider_options['prompt'];
						if ( 'custom_tenant' === $serialized_provider_options['tenant'] ) {
							$ids_arranged['microsoft_custom_tenant_value'] = $serialized_provider_options['custom_tenant_value'];
						}
						break;
					default:
						$ids_arranged = array();
				}

				$social_login[ $provider_id ] = $ids_arranged;
			}

			// Set social_login to the main JSON array.
			$json_arr['social_login'] = $social_login;
		}

		return wp_json_encode( $json_arr );
	}

	/**
	 * Registers an admin template path for SSO.
	 *
	 * @since 2.6.30
	 *
	 * @return string Template path for SSO.
	 */
	public function bb_register_sso_admin_template() {
		return bb_sso_path( '/admin/templates' );
	}

	/**
	 * Registers a template path for SSO.
	 *
	 * @since 2.6.30
	 *
	 * @return string Template path for SSO.
	 */
	public function bb_register_sso_template() {
		return bb_sso_path( '/templates' );
	}

	/**
	 * Enqueue admin related scripts and styles.
	 *
	 * This function loads the necessary CSS and JavaScript files for the admin interface,
	 * and localizes variables for use in the scripts.
	 *
	 * @since 2.6.30
	 * @since 2.7.20
	 * Added new param $hook to enqueue the script only on the BuddyPress settings page.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public static function bb_sso_admin_enqueue( $hook ) {
		if ( false === strpos( $hook, 'bp-settings' ) ) {
			return;
		}
		$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$rtl_css = is_rtl() ? '-rtl' : '';
		wp_enqueue_style( 'bb-sso-admin', bb_sso_url( '/assets/css/bb-sso-admin' . $rtl_css . $min . '.css' ), array(), bb_platform_pro()->version );
		wp_enqueue_script( 'bb-sso-admin', bb_sso_url( '/assets/js/bb-sso-admin' . $min . '.js' ), array( 'underscore', 'wp-util' ), bb_platform_pro()->version, true );
		wp_localize_script(
			'bb-sso-admin',
			'bbSSOAdminVars',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'bb-sso-admin' ),
				'sso_fields' => include bb_sso_path( '/admin/sso-fields.php' ),
			)
		);
		bp_get_template_part( 'bb-sso-fields-html' );
	}

	/**
	 * Adds the "Social Accounts" tab to the BuddyPress settings navigation in the admin bar.
	 *
	 * @since 2.6.30
	 *
	 * @param array $wp_admin_nav The navigation items for the BuddyPress settings.
	 *
	 * @return array The modified navigation items.
	 */
	public static function bb_settings_admin_nav( $wp_admin_nav ) {
		if ( is_string( $wp_admin_nav ) ) {
			$wp_admin_nav = array();
		}
		$settings_link  = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() );
		$wp_admin_nav[] = array(
			'parent'   => 'my-account-settings',
			'id'       => 'my-account-settings-social',
			'title'    => __( 'Social Accounts', 'buddyboss-pro' ),
			'href'     => esc_url( trailingslashit( $settings_link . 'social' ) ),
			'position' => 22,
		);

		return $wp_admin_nav;
	}

	/**
	 * Change the SSO registration error message format and Hook into the BuddyBoss registration page before it loads.
	 *
	 * Removes specific filters from the registration error message content
	 * to ensure proper formatting of the SSO-related notices.
	 *
	 * This method checks if specific SSO parameters exist and, if so, applies a filter
	 * to modify the registration fields by removing password-related fields and hiding.
	 *
	 * @since 2.6.40
	 */
	public static function bb_modify_sso_registration_error_format() {
		remove_filter( 'bp_core_render_message_content', 'wpautop' );
		remove_filter( 'bp_core_render_message_content', 'wp_kses_data', 5 );
		add_filter( 'bp_nouveau_get_signup_fields', 'BB_SSO::bb_sso_modify_signup_fields' );
	}

	/**
	 * Add SSO fields to the registration form.
	 *
	 * @since 2.6.60
	 */
	public static function bb_sso_after_signup_profile() {
		// SSO provider.
		$sso_type = isset( $_GET['sso_type'] ) ? sanitize_text_field( wp_unslash( $_GET['sso_type'] ) ) : '';

		// Get the identifier from the query string.
		$identifier = isset( $_GET['identifier'] ) ? sanitize_text_field( wp_unslash( $_GET['identifier'] ) ) : '';

		if ( empty( $sso_type ) || empty( $identifier ) ) {
			return;
		}

		// fill the confirmation password if available.
		$fill_confirm_password = isset( $_GET['signup_password_confirm'] ) ? sanitize_text_field( wp_unslash( $_GET['signup_password_confirm'] ) ) : '';
		?>
		<input type="hidden" name="bb_sso_identifier" value="<?php echo esc_attr( $identifier ); ?>" />
		<input type="hidden" name="bb_sso_type" value="<?php echo esc_attr( $sso_type ); ?>" />
		<input type="hidden" name="fill_confirm_password" value="<?php echo esc_attr( $fill_confirm_password ); ?>" />

		<?php
		if ( ! self::$filter_applied ) {
			// signup fields array.
			$get_field_array = array();
			$signup_fields   = function_exists( 'bp_nouveau_get_signup_fields' ) ? bp_nouveau_get_signup_fields( 'account_details' ) : array();
			if ( ! empty( $signup_fields ) ) {
				foreach ( $signup_fields as $field_name => $field ) {
					if ( $field['required'] ) {
						$get_field_array['required'][ sanitize_title( $field_name ) ] = isset( $_GET[ $field_name ] ) ? sanitize_text_field( wp_unslash( $_GET[ $field_name ] ) ) : '';
					}
				}
			}
			// Prepare the inline script.
			$inline_script = "
				jQuery( window ).on( 'load', function () {
					var ssoIdentifier = jQuery( 'input[name=\"bb_sso_identifier\"]' ).val();
					var ssoType       = jQuery( 'input[name=\"bb_sso_type\"]' ).val();
					if ( ssoIdentifier && ssoType ) {";
						if ( ! empty( $get_field_array['required'] ) ) {
							foreach ( $get_field_array['required'] as $field_name => $field_value ) {
								if ( ! empty( $field_value ) ) {
									$inline_script .= "
					                    if ( jQuery( '.{$field_name}' ).length ) {
					                        jQuery( '.{$field_name}' ).hide();
					                    }";
								}
							}
					}
			$inline_script .= "}
				} );";
			// Add the inline script.
			wp_add_inline_script( 'bb-sso', $inline_script );
		}
	}

	/**
	 * Clean up after the registration page loads.
	 *
	 * Removes the filter applied during `bb_before_register_page` to ensure
	 * subsequent pages are unaffected by the custom behavior.
	 *
	 * @since 2.6.60
	 *
	 * @return void
	 */
	public static function bb_sso_after_register_page() {
		remove_filter( 'bp_nouveau_get_signup_fields', 'BB_SSO::bb_sso_modify_signup_fields' );
	}

	/**
	 * Modify the signup fields on the BuddyPress registration page.
	 *
	 * This method removes the password and password confirmation fields from
	 * the account details section.
	 * Additionally, if specific SSO-related parameters
	 * are provided in the URL (`$_GET`), the corresponding fields are hidden.
	 *
	 * @since 2.6.60
	 *
	 * @param array $signup_fields The array of signup fields grouped by sections.
	 *
	 * @return array Modified array of signup fields.
	 */
	public static function bb_sso_modify_signup_fields( $signup_fields ) {
		unset( $signup_fields['account_details']['signup_password'] );
		unset( $signup_fields['account_details']['signup_password_confirm'] );
		foreach ( $signup_fields['account_details'] as $key => $field ) {
			if ( ! empty( $_GET[ $key ] ) ) {
				$signup_fields['account_details'][ $key ]['bb_sso_hide'] = true;
			}
		}

		return $signup_fields;
	}

	/**
	 * Add class to the xprofile field to hide the field.
	 *
	 * @since 2.6.60
	 *
	 * @param array $css_class CSS class.
	 *
	 * @return array
	 */
	public static function bb_sso_xprofile_field_css_class( $css_class ) {
		global $profile_template, $bp;

		$nickname_field_id = bp_xprofile_nickname_field_id();

		// If the registration form is submitted without ajax and nickname is not valid, then the field should not be hidden.
		if ( isset( $_POST['signup_submit'] ) && ! empty( $bp->signup->errors ) ) { // phpcs:ignore WordPress.Security
			if ( $nickname_field_id === $profile_template->field->id && ! empty( $bp->signup->errors[ 'field_' . $nickname_field_id ] ) ) {
				return $css_class;
			}
		}
		$exclude_fields_from_url = array();
		foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security
			if ( 0 === strpos( $key, 'field_' ) && ! empty( $value ) ) { // Check if the key starts with 'field_'.
				$is_nickname_field = ( 'field_' . $nickname_field_id === $key );
				$is_valid_nickname = $is_nickname_field && validate_username( $value );

				if ( ! $is_nickname_field || $is_valid_nickname ) {
					$exclude_fields_from_url[] = $key; // Add field name to the exclude list.
				}
			}
		}

		if (
			// Skip rendering if the current field ID is in the excluded list or URL contains 'field_X'.
			(
				! empty( $exclude_fields_from_url ) &&
				in_array( 'field_' . $profile_template->field->id, $exclude_fields_from_url, true )
			) ||
			empty( $profile_template->field->is_required )
		) {
			$css_class[] = 'bp-hide';
		}

		return $css_class;
	}

	/**
	 * Add class to the signup field to hide the field.
	 *
	 * @since 2.6.60
	 *
	 * @param array $css_class  CSS class.
	 * @param array $attributes Attributes.
	 *
	 * @return array
	 */
	public static function bb_sso_signup_field_css_class( $css_class, $attributes ) {
		self::$filter_applied = true;
		if ( isset( $attributes['bb_sso_hide'] ) && true === $attributes['bb_sso_hide'] ) {
			$css_class[] = 'bp-hide';
		}

		return $css_class;
	}

	/**
	 * Set the password and confirm the password for the social login users
	 * during registration with required fields.
	 *
	 * @since 2.6.60
	 *
	 * @return void
	 */
	public static function bb_sso_signup_pre_validate() {
		$_POST['signup_password']         = wp_generate_password( 12, false );
		$_POST['signup_password_confirm'] = $_POST['signup_password'];
	}

	/**
	 * Sync SSO data after user registration.
	 *
	 * This function is triggered after a user registers, syncing their data with
	 * the SSO.
	 * It checks for an SSO identifier and type in the `$_POST` data,
	 * validates the existence of the identifier in the database, and performs necessary
	 * actions to update the user's data.
	 *
	 * @since 2.6.60
	 *
	 * @param int $user_id The ID of the newly registered user.
	 */
	public static function bb_sso_sync( $user_id ) {
		global $wpdb;

		// Get the identifier from the post data.
		$identifier = isset( $_POST['bb_sso_identifier'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_sso_identifier'] ) ) : '';
		$sso_type   = isset( $_POST['bb_sso_type'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_sso_type'] ) ) : '';

		if ( empty( $identifier ) || empty( $sso_type ) ) {
			return;
		}

		// Return if there is no identifier and type available in table.
		$sso_user = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$wpdb->base_prefix}bb_social_sign_on_users WHERE type = %s AND identifier = %s", $sso_type, $identifier ) );
		if ( empty( $sso_user ) ) {
			return;
		}

		if ( 'fb' === $sso_type ) {
			$sso_type = 'facebook';
		}
		$provider    = self::get_provider_by_provider_id( $sso_type );
		$bb_sso_user = new BB_SSO_User( $provider, array() );
		$register    = $bb_sso_user->register_complete( $user_id, true );
		if ( true === $register ) {

			// Save xprofile field data from the registration form.
			if ( bp_verify_nonce_request( 'bp_new_signup' ) && bp_is_active( 'xprofile' ) && ! empty( $_POST['signup_profile_field_ids'] ) ) { // phpcs:ignore WordPress.Security

				$profile_type_field_id = 0;
				if ( function_exists( 'bp_get_xprofile_member_type_field_id' ) ) {
					$profile_type_field_id = bp_get_xprofile_member_type_field_id();
				}

				// Let's compact any profile field info into an array.
				$profile_field_ids = explode( ',', sanitize_text_field( wp_unslash( $_POST['signup_profile_field_ids'] ) ) ); // phpcs:ignore WordPress.Security

				// Loop through the posted fields formatting any datebox values then validate the field.
				foreach ( (array) $profile_field_ids as $field_id ) {
					if ( isset( $_POST[ 'field_' . $field_id ] ) ) { // phpcs:ignore WordPress.Security
						if ( is_array( $_POST[ 'field_' . $field_id ] ) ) { // phpcs:ignore WordPress.Security
							$field_value = array_map( 'sanitize_text_field', wp_unslash( $_POST[ 'field_' . $field_id ] ) ); // phpcs:ignore WordPress.Security
						} else {
							$field_value = sanitize_text_field( wp_unslash( $_POST[ 'field_' . $field_id ] ) ); // phpcs:ignore WordPress.Security
						}

						// Save xprofile field data.
						$res = xprofile_set_field_data( $field_id, $user_id, $field_value );

						// When the field is the member type field.
						if ( ! empty( $res ) && ! empty( $profile_type_field_id ) && $profile_type_field_id === (int) $field_id ) {

							// Get the member type key.
							$member_type_key = get_post_meta( $field_value, '_bp_member_type_key', true );

							// Now set the member type using the key.
							if ( ! empty( $member_type_key ) ) {
								bp_set_member_type( $user_id, $member_type_key );
							}
						}
					}
				}
			}

			BB_SSO_Avatar::get_instance()->update_avatar( $provider, $user_id, \BBSSO\Persistent\BB_SSO_Persistent::get( $identifier . '_user_avatar' ) );
			$bb_sso_user->do_auto_login( $user_id );
			\BBSSO\Persistent\BB_SSO_Persistent::delete( $identifier . '_user_avatar' );
		}
	}

	/**
	 * Function to check if the registration is allowed.
	 *
	 * @since 2.6.60
	 *
	 * @return bool True if registration is allowed; otherwise, false.
	 */
	public static function bb_sso_is_register_allowed() {
		$allow_registration = function_exists( 'bp_enable_site_registration' ) && bp_enable_site_registration();
		$allow_register     = bb_enable_sso_reg_options();
		if ( $allow_registration && true === $allow_register ) {
			return true;
		}

		return false;
	}

	/**
	 * Adds custom info messages to the login messages.
	 *
	 * @since 2.6.60
	 *
	 * @param string $messages The login messages.
	 *
	 * @return string Modified login messages.
	 */
	public static function bb_wp_login_messages( $messages ) {
		if ( ! empty( $messages ) ) {
			return '<div class="message info notice-info-extra"><span class="bp-icon" aria-hidden="true"></span>' . wp_kses_post( $messages ) . '</div>';
		}

		return $messages;
	}

	/**
	 * Adds custom info messages to the register messages.
	 *
	 * @since 2.7.10
	 */
	public static function bb_sso_register_template_notices() {
		if ( function_exists( 'bp_is_register_page' ) && bp_is_register_page() && isset( \BBSSO\BB_SSO_Notices::$notices ) ) {
			foreach ( \BBSSO\BB_SSO_Notices::$notices as $type => $notices ) {
				if ( ! empty( $notices ) ) {
					echo '<aside class="bp-feedback bp-messages bp-template-notice ' . esc_attr( $type ) . '">';
					echo '<span class="bp-icon" aria-hidden="true"></span>';
					foreach ( $notices as $error ) {
						echo wp_kses_post( $error ) . '<br>';
					}
					echo '</aside>';
				}
			}
			\BBSSO\BB_SSO_Notices::clear();
		}
	}
}
