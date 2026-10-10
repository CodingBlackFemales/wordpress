<?php
/**
 * Main class.
 *
 * @package  CodingBlackFemales/SemanticGlossary
 * @version  1.0.0
 */

namespace CodingBlackFemales\SemanticGlossary;

use CodingBlackFemales\SemanticGlossary\Admin\EntryScreen;
use CodingBlackFemales\SemanticGlossary\Admin\SettingsPage;
use CodingBlackFemales\SemanticGlossary\Api\Controller;
use CodingBlackFemales\SemanticGlossary\Block\GlossaryBlock;
use CodingBlackFemales\SemanticGlossary\Cli\Commands;
use CodingBlackFemales\SemanticGlossary\Editor\EditorAssets;
use CodingBlackFemales\SemanticGlossary\Entry\PostType;
use CodingBlackFemales\SemanticGlossary\Reference\Index;
use CodingBlackFemales\SemanticGlossary\Reference\PostFields;
use CodingBlackFemales\SemanticGlossary\Render\ContentFilter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base plugin class holding core bootstrap logic.
 */
final class Main {

	/**
	 * Minimum required versions.
	 */
	const PLUGIN_REQUIREMENTS = array(
		'php_version' => '8.5',
		'wp_version'  => '6.7',
	);


	/**
	 * Bootstrap the plugin: register the activation hook and the primary
	 * `plugins_loaded` entry point.
	 *
	 * Called once from the plugin bootstrap file after the autoloader loads.
	 */
	public static function bootstrap(): void {
		register_activation_hook( PLUGIN_FILE, array( Install::class, 'install' ) );

		add_action( 'plugins_loaded', array( __CLASS__, 'load' ) );
		add_action( 'init', array( __CLASS__, 'init' ) );

		/**
		 * Fires after the plugin has been fully loaded and initialised.
		 */
		do_action( 'cbf_glossary_fully_loaded' );
	}


	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'cbf-semantic-glossary' ), '1.0.0' );
	}


	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'cbf-semantic-glossary' ), '1.0.0' );
	}


	/**
	 * Include plugin files and hook into actions and filters.
	 */
	public static function load(): void {
		if ( ! self::check_plugin_requirements() ) {
			return;
		}

		// The reference index table is created on activation; this covers network
		// activation, new sites and schema upgrades, none of which run that hook.
		Install::maybe_upgrade();

		PostType::hooks();
		PostFields::hooks();
		Index::hooks();
		ContentFilter::hooks();
		GlossaryBlock::hooks();
		Controller::hooks();
		EditorAssets::hooks();

		if ( Utils::is_request( 'admin' ) ) {
			EntryScreen::hooks();
			SettingsPage::hooks();
		}

		if ( Utils::is_request( 'cli' ) ) {
			Commands::register();
		}

		self::load_plugin_textdomain();

		/**
		 * Fires after the plugin has been loaded.
		 */
		do_action( 'cbf_glossary_loaded' );
	}


	/**
	 * Fires on the `init` hook.
	 */
	public static function init(): void {
		/**
		 * Fires before the plugin has been initialised.
		 */
		do_action( 'before_cbf_glossary_init' );

		/**
		 * Fires after the plugin has been initialised.
		 */
		do_action( 'cbf_glossary_init' );
	}


	/**
	 * Every requirement this installation does not meet.
	 *
	 * @return string[] Editor-facing messages, empty when everything is in place.
	 */
	private static function unmet_requirements(): array {
		global $wp_version;

		$errors = array();

		if ( ! version_compare( PHP_VERSION, self::PLUGIN_REQUIREMENTS['php_version'], '>=' ) ) {
			$errors[] = sprintf(
				/* translators: minimum PHP version */
				esc_html__( 'CBF Semantic Glossary requires PHP %s or higher.', 'cbf-semantic-glossary' ),
				self::PLUGIN_REQUIREMENTS['php_version']
			);
		}

		if ( ! version_compare( $wp_version, self::PLUGIN_REQUIREMENTS['wp_version'], '>=' ) ) {
			$errors[] = sprintf(
				/* translators: minimum WordPress version */
				esc_html__( 'CBF Semantic Glossary requires WordPress %s or higher.', 'cbf-semantic-glossary' ),
				self::PLUGIN_REQUIREMENTS['wp_version']
			);
		}

		return $errors;
	}


	/**
	 * Check PHP/WP version requirements. Adds an admin notice on failure.
	 */
	private static function check_plugin_requirements(): bool {
		$errors = self::unmet_requirements();

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
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 */
	private static function load_plugin_textdomain(): void {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'cbf-semantic-glossary' );
		load_textdomain( 'cbf-semantic-glossary', WP_LANG_DIR . '/cbf-semantic-glossary/cbf-semantic-glossary-' . $locale . '.mo' );
		load_plugin_textdomain( 'cbf-semantic-glossary', false, plugin_basename( dirname( PLUGIN_FILE ) ) . '/i18n/languages' );
	}
}
