<?php
/**
 * Plugin Name: WP Job Manager - Alerts
 * Plugin URI: https://wpjobmanager.com/add-ons/job-alerts/
 * Description: Allow users to subscribe to job alerts for their searches. Once registered, users can access a 'My Alerts' page which you can create with the shortcode [job_alerts].
 * Version: 3.2.0
 * Author: Automattic
 * Author URI: https://wpjobmanager.com
 * Requires at least: 6.2
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * Text Domain: wp-job-manager-alerts
 * Domain Path: /languages/
 *
 * WPJM-Product: wp-job-manager-alerts
 *
 * Copyright: 2024 Automattic
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package wp-job-manager-alerts
 */

namespace WP_Job_Manager_Alerts;

use WP_Job_Manager\Guest_Session;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Job_Manager_Alerts class.
 */
class WP_Job_Manager_Alerts {

	/**
	 * Minimum required version of the WP Job Manager plugin.
	 */
	public const JOB_MANAGER_CORE_MIN_VERSION = '2.2.0';

	/**
	 * Post types class.
	 *
	 * @var Post_Types
	 */
	public Post_Types $post_types;

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Allows for accessing single instance of class. Class should only be constructed once per call.
	 *
	 * @since 3.0.0
	 * @static
	 * @return self Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * __construct function.
	 */
	public function __construct() {
		// Define constants
		define( 'JOB_MANAGER_ALERTS_VERSION', '3.2.0' );
		define( 'JOB_MANAGER_ALERTS_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'JOB_MANAGER_ALERTS_PLUGIN_URL', untrailingslashit( plugins_url( '', ( __FILE__ ) ) ) );

		spl_autoload_register( [ $this, 'autoload' ] );

		class_alias( '\\WP_Job_Manager_Alerts\\Admin', 'WP_Job_Manager_Alerts_Admin' );
		class_alias( '\\WP_Job_Manager_Alerts\\Notifier', 'WP_Job_Manager_Alerts_Notifier' );
		class_alias( '\\WP_Job_Manager_Alerts\\Post_Types', 'WP_Job_Manager_Alerts_Post_Types' );
		class_alias( '\\WP_Job_Manager_Alerts\\Shortcodes', 'WP_Job_Manager_Alerts_Shortcodes' );

		// Set up startup actions
		add_action( 'plugins_loaded', [ $this, 'load_text_domain' ], 12 );
		add_action( 'plugins_loaded', [ $this, 'init_plugin' ], 13 );
		add_action( 'admin_notices', [ $this, 'version_check' ] );
		add_action( 'admin_init', [ $this, 'updater' ] );
		add_action( 'admin_init', [ $this, 'add_privacy_policy_content' ] );

		register_activation_hook(
			basename( __DIR__ ) . '/' . basename( __FILE__ ),
			[ $this, 'on_plugin_activation' ]
		);
	}

	/**
	 * Initializes plugin.
	 */
	public function init_plugin() {
		if ( ! self::check_core_installed() || ! self::check_core_version( true ) ) {
			return;
		}

		$this->post_types = Post_Types::instance();

		Admin::instance();
		Notifier::instance();
		Shortcodes::instance();
		Add_Alert::instance();
		Alert_Stats::instance();

		// Add actions
		add_action( 'wp_enqueue_scripts', [ $this, 'frontend_scripts' ] );
		add_filter( 'job_manager_enhanced_select_enabled', [ $this, 'use_enhanced_select' ] );
		add_filter( 'job_manager_enqueue_frontend_style', [ $this, 'use_wpjm_core_frontend_style' ] );
		add_action( 'pre_update_option_job_manager_alerts_email_template', [ $this, 'update_email_template' ], 10, 2 );

		// Update legacy options
		if ( false === get_option( 'job_manager_alerts_page_id', false ) && get_option( 'job_manager_alerts_page_slug' ) ) {
			$page_id = get_page_by_path( get_option( 'job_manager_alerts_page_slug' ) )->ID;
			update_option( 'job_manager_alerts_page_id', $page_id );
		}
	}

	/**
	 * Hook on plugin activation.
	 */
	public function on_plugin_activation() {
		if ( $this->is_new_install() && false === get_option( Settings::OPTION_ACCOUNT_REQUIRED, false ) ) {
			// For new installs, set alerts account required to false
			update_option( Settings::OPTION_ACCOUNT_REQUIRED, '0' );
		}
		self::update_email_template_on_plugin_update();

		update_option( 'wp_job_manager_alerts_version', JOB_MANAGER_ALERTS_VERSION );
	}

	/**
	 * Check if it's a new plugin install.
	 *
	 * @return bool
	 */
	private function is_new_install() {
		return false === get_option( Settings::OPTION_ALERTS_PLUGIN_VERSION, false ) && false === get_option( 'job_manager_alerts_page_id', false );
	}

	/**
	 * Checks alerts for their corresponding scheduled event and reschedules if missing.
	 *
	 * @deprecated 3.0.0 Moved to Notifier class.
	 */
	public function check_reschedule_events() {
		_deprecated_function( __METHOD__, '3.0.0', 'WP_Job_Manager_Alerts\Notifier::check_reschedule_events' );
		Notifier::instance()->check_reschedule_events();
	}

	/**
	 * Localisation
	 *
	 * @access private
	 * @return void
	 */
	public function load_text_domain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wp-job-manager-alerts' );
		load_textdomain( 'wp-job-manager-alerts', WP_LANG_DIR . "/wp-job-manager-alerts/wp-job-manager-alerts-$locale.mo" );
		load_plugin_textdomain( 'wp-job-manager-alerts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Checks WPJM core version.
	 */
	public function version_check() {
		if ( ! self::check_core_installed() ) {
			$screen = get_current_screen();
			if ( null !== $screen && 'plugins' === $screen->id ) {
				$this->display_error( __( '<em>WP Job Manager - Alerts</em> requires WP Job Manager to be installed and activated.', 'wp-job-manager-alerts' ) );
			}
		} elseif ( ! self::check_core_version() ) {
			// Translators: first placeholder is required WP Job Manager plugin version, second placeholder is current WP Job Manager plugin version.
			$this->display_error( sprintf( __( '<em>WP Job Manager - Alerts</em> requires WP Job Manager %1$s (you are using %2$s).', 'wp-job-manager-alerts' ), self::JOB_MANAGER_CORE_MIN_VERSION, JOB_MANAGER_VERSION ) );
		}
	}

	/**
	 * Checks if the WPJM Core is installed.
	 *
	 * @return bool True if the WPJM Core is installed. False otherwise.
	 */
	public static function check_core_installed() {
		return class_exists( 'WP_Job_Manager' ) && defined( 'JOB_MANAGER_VERSION' );
	}

	/**
	 * Check if the minimum requirement for the WPJM Core version is met.
	 *
	 * @param bool $skip_check Whether the version check can be skipped or not.
	 * @return bool True if the minimum requirement for the WPJM Core version is met. False otherwise.
	 */
	public static function check_core_version( $skip_check = false ) {
		return ! ( ( $skip_check ||
				/**
				 * Filters if WPJM core's version should be checked.
				 *
				 * @param bool $do_check True if the add-on should do a core version check.
				 * @param string $minimum_required_core_version Minimum version the plugin is reporting it requires.
				 * @since 1.5.0
				 */
				apply_filters( 'job_manager_addon_core_version_check', true, self::JOB_MANAGER_CORE_MIN_VERSION )
			)
			&& version_compare( JOB_MANAGER_VERSION, self::JOB_MANAGER_CORE_MIN_VERSION, '<' ) );
	}

	/**
	 * Handles tasks after plugin is updated.
	 */
	public function updater() {
		if ( version_compare( JOB_MANAGER_ALERTS_VERSION, get_option( 'wp_job_manager_alerts_version' ), '>' ) ) {
			self::update_email_template_on_plugin_update();
			update_option( 'wp_job_manager_alerts_version', JOB_MANAGER_ALERTS_VERSION );
		}
	}

	/**
	 * Adds Privacy Policy suggested content.
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		$content = __(
			'When you subscribe to a job alert on this site we store the details you enter to send you alerts matching the criteria you selected.',
			'wp-job-manager-alerts'
		);
		wp_add_privacy_policy_content(
			__( 'WP Job Manager - Alerts', 'wp-job-manager-alerts' ),
			wp_kses_post( wpautop( $content, false ) )
		);
	}

	/**
	 * Display error message notice in the admin.
	 *
	 * @param string $message
	 */
	private function display_error( $message ) {
		echo '<div class="error">';
		echo '<p>' . wp_kses_post( $message ) . '</p>';
		echo '</div>';
	}

	/**
	 * Frontend scripts and styles function.
	 *
	 * @access public
	 * @return void
	 */
	public function frontend_scripts() {
		wp_register_script( 'job-alerts', JOB_MANAGER_ALERTS_PLUGIN_URL . '/assets/dist/js/job-alerts.js', [ 'jquery', 'select2' ], JOB_MANAGER_ALERTS_VERSION, true );

		wp_localize_script(
			'job-alerts',
			'job_manager_alerts',
			[
				'i18n_confirm_delete' => __( 'Are you sure you want to delete this alert?', 'wp-job-manager-alerts' ),
				'is_rtl'              => is_rtl(),
			]
		);

		wp_enqueue_style( 'job-alerts-frontend', JOB_MANAGER_ALERTS_PLUGIN_URL . '/assets/dist/css/frontend.css', [], JOB_MANAGER_ALERTS_VERSION );

		if ( ! current_theme_supports( 'job_manager_alert_styles' ) ) {
			wp_enqueue_style( 'job-alerts-frontend-default', JOB_MANAGER_ALERTS_PLUGIN_URL . '/assets/dist/css/frontend.default.css', [ 'job-alerts-frontend' ], JOB_MANAGER_ALERTS_VERSION );
		}
	}

	/**
	 * Check if we should have WPJM core enqueue enhanced select.
	 *
	 * @param bool $use_enhanced_select True if we should have WPJM core use enhanced select.
	 * @return bool
	 */
	public function use_enhanced_select( $use_enhanced_select ) {
		if ( $this->is_shortcode_page() ) {
			return true;
		}
		return $use_enhanced_select;
	}

	/**
	 * Check if we should have WPJM core enqueue its frontend styles.
	 *
	 * @param bool $use_frontend_style True if we should have WPJM core enqueue frontend styles.
	 * @return bool
	 */
	public function use_wpjm_core_frontend_style( $use_frontend_style ) {
		if ( $this->is_shortcode_page() ) {
			return true;
		}
		return $use_frontend_style;
	}

	/**
	 * Checks if the current page is the `[job_alerts]` page.
	 *
	 * @return bool
	 */
	private function is_shortcode_page() {
		global $post;

		$content = null;
		if ( is_singular() && is_a( $post, 'WP_Post' ) ) {
			$content = $post->post_content;
		}

		return ! is_null( $content ) && has_shortcode( $content, 'job_alerts' );
	}

	/**
	 * Check if there is a user logged in or if account creation is not required.
	 *
	 * @return bool
	 */
	public function can_user_add_alert() : bool {
		// Logged-in users are always allowed to add alerts.
		if ( is_user_logged_in() ) {
			return true;
		}

		// If an account is required, guest users cannot add an alert.
		if ( Settings::instance()->is_account_required() ) {
			return false;
		}

		// Do not allow users that have an account to add alert as guests.
		return ! Guest_Session::current_guest_has_account();
	}

	/**
	 * Return the default email content for alerts
	 *
	 * @deprecated 3.0.0
	 */
	public function get_default_email() {
		_deprecated_function( __METHOD__, '3.0.0', 'Settings::instance->get_default_email()' );

		return Settings::instance()->get_default_email();
	}

	/**
	 * Add the alert link to job search.
	 *
	 * @deprecated 3.0.0 - Moved to Add_Alert.
	 *
	 * @param array $links Existing links.
	 * @param array $args Search terms.
	 *
	 * @return array Links.
	 */
	public function alert_link( $links, $args ) {
		_deprecated_function( __METHOD__, '3.0.0', 'WP_Job_Manager_Alerts\Add_Alert::instance()->alert_link()' );

		return Add_Alert::instance()->alert_link( $links, $args );
	}

	/**
	 * Single listing alert link - Moved to Add_Alert.
	 *
	 * @deprecated 3.0.0
	 */
	public function single_alert_link() {
		_deprecated_function( __METHOD__, '3.0.0', 'WP_Job_Manager_Alerts\Add_Alert::instance()->single_alert_link()' );

		Add_Alert::instance()->single_alert_link();
	}

	/**
	 * Get alert token.
	 *
	 * @deprecated 3.0.0
	 *
	 * @param int $alert_id Alert ID.
	 * @param int $user_id  User ID.
	 *
	 * @return string Alert token.
	 */
	public function get_alert_token( $alert_id, $user_id ) {
		_deprecated_function( __METHOD__, '3.0.0', 'Access_Token::create()' );

		$alert_token = wp_json_encode( [ $user_id, $alert_id ] );
		$alert_token = crypt( $alert_token, $this->get_user_secret_key( $user_id ) );

		return $alert_token;
	}

	/**
	 * Verify alert token.
	 *
	 * @deprecated 3.0.0 Access_Token::verify() should be used instead. This method should be removed once the new implementation was published for enough time.
	 *
	 * @param string $token    Token to verify.
	 * @param int    $alert_id Alert ID.
	 * @param int    $user_id  User ID.
	 *
	 * @return boolean Whether token is valid.
	 */
	public function verify_alert_token( $token, $alert_id, $user_id ) {
		if ( ( new \WP_Job_Manager\Access_Token( [ $alert_id, $user_id ] ) )->verify( $token ) ) {
			return true;
		}

		// Check if the token was created with get_alert_token.
		$correct_token = wp_json_encode( [ $user_id, $alert_id ] );
		$correct_token = crypt( $correct_token, $this->get_user_secret_key( $user_id ) );

		return hash_equals(
			$correct_token,
			$token
		);
	}

	/**
	 * Get a user secret key.
	 * It generates the secret key if it's being requested for the first time.
	 *
	 * @deprecated 3.0.0
	 *
	 * @param int $user_id User ID.
	 *
	 * @return string User secret key.
	 */
	private function get_user_secret_key( $user_id ) {
		$meta_key = 'job_manager_alerts_secret_key';

		$secret_key = get_user_meta( $user_id, $meta_key, true );

		if ( empty( $secret_key ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode,WordPress.WP.AlternativeFunctions.rand_mt_rand -- Legacy random key generation.
			$secret_key = substr( str_replace( '+', '.', base64_encode( pack( 'N4', mt_rand(), mt_rand(), mt_rand(), mt_rand() ) ) ), 0, 22 );
			update_user_meta( $user_id, $meta_key, $secret_key );
		}

		return $secret_key;
	}

	/**
	 * Load and render a template, and return it's content.
	 *
	 * @param string $name Template file name.
	 * @param array  $args Variables for the template.
	 *
	 * @return false|string
	 */
	public static function get_template( $name, $args ) {

		ob_start();

		get_job_manager_template(
			$name,
			$args,
			'wp-job-manager-alerts',
			JOB_MANAGER_ALERTS_PLUGIN_DIR . '/templates/'
		);

		return ob_get_clean();
	}

	/**
	 * Autoload plugin classes.
	 *
	 * @since 3.0.0
	 *
	 * @param string $class_name Class name.
	 */
	public function autoload( $class_name ) {

		$prefix = __NAMESPACE__ . '\\';

		if ( str_starts_with( $class_name, $prefix ) ) {

			$file_name = substr( $class_name, strlen( $prefix ) );
			$file_name = strtolower( $file_name );
			$dirs      = explode( '\\', $file_name );
			$file_name = array_pop( $dirs );
			$file_name = str_replace( '_', '-', $file_name );

			$file_dir = implode( '/', [ JOB_MANAGER_ALERTS_PLUGIN_DIR, 'includes', ...$dirs ] );

			$file_paths = [
				'class-' . $file_name . '.php',
				'trait-' . $file_name . '.php',
			];

			foreach ( $file_paths as $file_path ) {
				$file_path = $file_dir . '/' . $file_path;
				if ( file_exists( $file_path ) ) {
					require $file_path;
					break;
				}
			}
		}
	}

	/**
	 * Checks if the given email template value is a legacy template.
	 *
	 * @param string $value The email template value to check.
	 * @return bool True if the email template is a legacy template, false otherwise.
	 */
	public function check_if_email_template_is_legacy( $value ) {
		$legacy_option_value = 'Hello {display_name},

		The following jobs were found matching your "{alert_name}" job alert.

		================================================================================
		{jobs}
		Your next alert for this search will be sent {alert_next_date}.

		{alert_expiry}

		—
		You are receiving this email because you created the "{alert_name}" job alert.
		To manage your alerts please login and visit your alerts page here: {alert_page_url}
		Unsubscribe from this alert through the link: {alert_unsubscribe_url}';

		$new_value_normalized           = trim( preg_replace( '/\s+/', ' ', $value ) );
		$legacy_option_value_normalized = trim( preg_replace( '/\s+/', ' ', $legacy_option_value ) );

		// Check if new value and legacy value are the same
		if ( $new_value_normalized === $legacy_option_value_normalized ) {
			return true;
		}
		return false;
	}

	/**
	 * Updates the email template value.
	 *
	 * If site has legacy template value, update to new value.
	 *
	 * @param string $new_value The new value of the email template.
	 * @param string $old_value The old value of the email template.
	 * @return string The updated email template value.
	 */
	public function update_email_template( $new_value, $old_value ) {
		$new_value_normalized     = trim( preg_replace( '/\s+/', ' ', $new_value ) );
		$default_value_normalized = trim( preg_replace( '/\s+/', ' ', Settings::instance()->get_default_email() ) );

		if ( $new_value_normalized === $default_value_normalized ) {
			update_option( 'job_manager_alerts_email_template_value', '' );
			return '';
		}
		update_option( 'job_manager_alerts_email_template_value', $new_value );
		return $new_value;
	}

	/**
	 * Updates the email template on plugin update.
	 *
	 * If the email template is the legacy value, update to the new default value.
	 */
	public function update_email_template_on_plugin_update() {
		$email_template = get_option( 'job_manager_alerts_email_template' );
		$is_legacy      = self::check_if_email_template_is_legacy( $email_template );

		if ( true === $is_legacy ) {
			update_option( 'job_manager_alerts_email_template', Settings::instance()->get_default_email() );
			update_option( 'job_manager_alerts_email_template_value', '' );
		}
	}
}

class_alias( '\\WP_Job_Manager_Alerts\\WP_Job_Manager_Alerts', 'WP_Job_Manager_Alerts' );

$GLOBALS['job_manager_alerts'] = WP_Job_Manager_Alerts::instance();
