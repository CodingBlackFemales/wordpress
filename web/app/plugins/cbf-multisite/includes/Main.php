<?php
/**
 * Main class.
 *
 * @package  CodingBlackFemales/Multisite
 * @version  1.0.0
 */

namespace CodingBlackFemales\Multisite;

use CodingBlackFemales\Multisite\Admin\Main as Admin;
use CodingBlackFemales\Multisite\Front\Main as Front;
use CodingBlackFemales\Multisite\Customizations\Quiz_Results_Command;
use CodingBlackFemales\Multisite\Customizations\WP_Cron;


/**
 * Base Plugin class holding generic functionality
 */
final class Main {

	/**
	 * Set the minimum required versions for the plugin.
	 */
	const PLUGIN_REQUIREMENTS = array(
		'php_version' => '8.0',
		'wp_version'  => '6.0',
	);


	/**
	 * Constructor
	 */
	public static function bootstrap() {

		register_activation_hook( PLUGIN_FILE, array( Install::class, 'install' ) );
		register_deactivation_hook( PLUGIN_FILE, array( Install::class, 'deactivate' ) );

		add_action( 'plugins_loaded', array( __CLASS__, 'load' ) );

		add_action( 'init', array( __CLASS__, 'init' ) );

		// Perform other actions when plugin is loaded.
		do_action( '_fully_loaded' );
	}


	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'cbf-multisite' ), '1.0.0' );
	}


	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'cbf-multisite' ), '1.0.0' );
	}


	/**
	 * Include plugins files and hook into actions and filters.
	 *
	 * @since  1.0.0
	 */
	public static function load() {

		if ( ! self::check_plugin_requirements() ) {
			return;
		}

		WP_Cron::hooks();

		if ( Utils::is_request( 'admin' ) ) {
			Admin::hooks();
		}

		if ( Utils::is_request( 'frontend' ) ) {
			Front::hooks();
		}

		if ( Utils::is_request( 'cli' ) ) {
			Quiz_Results_Command::hooks();
		}

		// Common includes.
		Block::hooks();
		Customizations\Capabilities::hooks();

		// Set up localisation.
		self::load_plugin_textdomain();

		// Init action.
		do_action( '_loaded' );
	}


	/**
	 * Method called by init hook
	 *
	 * @return void
	 */
	public static function init() {

		// Before init action.
		do_action( 'before__init' );

		// Add needed hooks here.
		// After init action.
		do_action( '_init' );
	}


	/**
	 * Checks all plugin requirements. If run in admin context also adds a notice.
	 *
	 * @return boolean
	 */
	// phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
	private static function check_plugin_requirements() {

		$errors = array();
		global $wp_version;

		if ( ! version_compare( PHP_VERSION, self::PLUGIN_REQUIREMENTS['php_version'], '>=' ) ) {
			/* Translators: The minimum PHP version */
			$errors[] = sprintf( esc_html__( 'CBF Multisite requires a minimum PHP version of %s.', 'cbf-multisite' ), self::PLUGIN_REQUIREMENTS['php_version'] );
		}

		if ( ! version_compare( $wp_version, self::PLUGIN_REQUIREMENTS['wp_version'], '>=' ) ) {
			/* Translators: The minimum WP version */
			$errors[] = sprintf( esc_html__( 'CBF Multisite requires a minimum WordPress version of %s.', 'cbf-multisite' ), self::PLUGIN_REQUIREMENTS['wp_version'] );
		}

		if ( empty( $errors ) ) {
			return true;
		}

		if ( Utils::is_request( 'admin' ) ) {

			add_action(
				'admin_notices',
				function () use ( $errors ) {
					?>
					<div class="notice notice-error">
						<?php
						foreach ( $errors as $error ) {
							echo '<p>' . esc_html( $error ) . '</p>';
						}
						?>
					</div>
					<?php
				}
			);

			return;
		}

		return false;
	}


	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/cbf-multisite/cbf-multisite-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/cbf-multisite-LOCALE.mo
	 */
	private static function load_plugin_textdomain() {

		// Add plugin's locale.
		$locale = apply_filters( 'plugin_locale', get_locale(), 'cbf-multisite' );

		load_textdomain( 'cbf-multisite', WP_LANG_DIR . '/cbf-multisite/cbf-multisite-' . $locale . '.mo' );

		load_plugin_textdomain( 'cbf-multisite', false, plugin_basename( __DIR__ ) . '/i18n/languages' );
	}
}
