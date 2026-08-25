<?php
/**
 * Main class.
 *
 * @package  CodingBlackFemales/SlidesImporter
 * @version  1.0.0
 */

namespace CodingBlackFemales\SlidesImporter;

use CodingBlackFemales\SlidesImporter\Admin\Main as Admin;
use CodingBlackFemales\SlidesImporter\Api\Router;
use CodingBlackFemales\SlidesImporter\Import\JobRunner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base plugin class holding core bootstrap logic.
 *
 * Admin-only plugin: no frontend hooks. All user interaction is through the
 * WP admin UI and the REST API under /wp-json/cbf-si/v1/.
 */
final class Main {

	/**
	 * Minimum required versions.
	 */
	const PLUGIN_REQUIREMENTS = array(
		'php_version' => '8.1',
		'wp_version'  => '6.0',
	);

	/**
	 * The dependency plugin that must be active before this plugin loads.
	 */
	const REQUIRED_PLUGIN = 'learndash-bulk-lessons-or-topics/learndash-bulk-create.php';

	/**
	 * Bootstrap the plugin: register activation/deactivation hooks and add
	 * the primary `plugins_loaded` entry point.
	 *
	 * Called once from the plugin bootstrap file after the autoloader loads.
	 */
	public static function bootstrap(): void {
		register_activation_hook( PLUGIN_FILE, array( Install::class, 'install' ) );
		register_deactivation_hook( PLUGIN_FILE, array( Install::class, 'deactivate' ) );

		add_action( 'plugins_loaded', array( __CLASS__, 'load' ) );
		add_action( 'init', array( __CLASS__, 'init' ) );

		do_action( 'cbf_si_fully_loaded' );
	}


	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'cbf-slides-importer' ), '1.0.0' );
	}


	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'cbf-slides-importer' ), '1.0.0' );
	}


	/**
	 * Include plugin files and hook into actions/filters.
	 *
	 * Runs on `plugins_loaded` so all plugins are available for the
	 * dependency check.
	 */
	public static function load(): void {
		if ( ! self::check_plugin_requirements() ) {
			return;
		}

		// REST API (available on all request types so WP-Cron jobs can
		// trigger authenticated REST calls internally if needed).
		Router::hooks();

		// Background job handler (WP-Cron).
		JobRunner::hooks();

		// Admin UI — only in admin context.
		if ( Utils::is_request( 'admin' ) ) {
			Admin::hooks();
		}

		self::load_plugin_textdomain();

		do_action( 'cbf_si_loaded' );
	}


	/**
	 * Fires on the `init` hook.
	 */
	public static function init(): void {
		do_action( 'before_cbf_si_init' );
		// Future: register post statuses, rewrite rules, etc.
		do_action( 'cbf_si_init' );
	}


	/**
	 * Check PHP/WP version requirements and that the required dependency
	 * plugin is active. Adds admin notice on failure.
	 */
	private static function check_plugin_requirements(): bool {
		$errors = array();
		global $wp_version;

		if ( ! version_compare( PHP_VERSION, self::PLUGIN_REQUIREMENTS['php_version'], '>=' ) ) {
			$errors[] = sprintf(
				/* translators: minimum PHP version */
				esc_html__( 'CBF Slides Importer requires PHP %s or higher.', 'cbf-slides-importer' ),
				self::PLUGIN_REQUIREMENTS['php_version']
			);
		}

		if ( ! version_compare( $wp_version, self::PLUGIN_REQUIREMENTS['wp_version'], '>=' ) ) {
			$errors[] = sprintf(
				/* translators: minimum WordPress version */
				esc_html__( 'CBF Slides Importer requires WordPress %s or higher.', 'cbf-slides-importer' ),
				self::PLUGIN_REQUIREMENTS['wp_version']
			);
		}

		// is_plugin_active() requires plugin.php; load it when not already available.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( self::REQUIRED_PLUGIN ) ) {
			$errors[] = esc_html__( 'CBF Slides Importer requires the LearnDash Bulk Lessons or Topics plugin to be active.', 'cbf-slides-importer' );
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
						<?php foreach ( $errors as $error ) : ?>
							<p><?php echo esc_html( $error ); ?></p>
						<?php endforeach; ?>
					</div>
					<?php
				}
			);
		}

		return false;
	}


	/**
	 * Load plugin localisation files.
	 */
	private static function load_plugin_textdomain(): void {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'cbf-slides-importer' );
		load_textdomain( 'cbf-slides-importer', WP_LANG_DIR . '/cbf-slides-importer/cbf-slides-importer-' . $locale . '.mo' );
		load_plugin_textdomain( 'cbf-slides-importer', false, plugin_basename( __DIR__ ) . '/i18n/languages' );
	}
}
