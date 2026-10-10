<?php
/**
 * Main class.
 *
 * @package  CodingBlackFemales/SemanticGlossaryLearnDash
 * @version  1.0.0
 */

namespace CodingBlackFemales\SemanticGlossaryLearnDash;

use CodingBlackFemales\SemanticGlossaryLearnDash\Block\CourseGlossaryBlock;
use CodingBlackFemales\SemanticGlossaryLearnDash\Cli\Commands;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\Cache;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\FirstUseOrder;
use CodingBlackFemales\SemanticGlossaryLearnDash\Render\CourseGlossary;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base plugin class holding core bootstrap logic.
 *
 * Everything LearnDash-specific about the glossary lives in this plugin, so the
 * core plugin stays installable without an LMS. It loads only when both core
 * and LearnDash are active.
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
	 * Bootstrap the plugin.
	 *
	 * Called once from the plugin bootstrap file after the autoloader loads.
	 */
	public static function bootstrap(): void {
		// After core (default priority) has loaded, so its API is available.
		add_action( 'plugins_loaded', array( __CLASS__, 'load' ), 20 );
	}


	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'cbf-semantic-glossary-learndash' ), '1.0.0' );
	}


	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'cbf-semantic-glossary-learndash' ), '1.0.0' );
	}


	/**
	 * Hook into actions and filters once requirements are met.
	 */
	public static function load(): void {
		self::load_plugin_textdomain();

		if ( ! self::check_plugin_requirements() ) {
			return;
		}

		Cache::hooks();
		CourseGlossary::hooks();
		CourseGlossaryBlock::hooks();
		FirstUseOrder::hooks();

		if ( Utils::is_request( 'cli' ) ) {
			Commands::register();
		}

		/**
		 * Fires after the plugin has been loaded.
		 */
		do_action( 'cbf_glossary_learndash_loaded' );
	}


	/**
	 * Every requirement this installation does not meet.
	 *
	 * @return string[] Admin-facing messages, empty when everything is in place.
	 */
	private static function unmet_requirements(): array {
		global $wp_version;

		$errors = array();

		if ( ! version_compare( PHP_VERSION, self::PLUGIN_REQUIREMENTS['php_version'], '>=' ) ) {
			$errors[] = sprintf(
				/* translators: minimum PHP version */
				esc_html__( 'CBF Semantic Glossary for LearnDash requires PHP %s or higher.', 'cbf-semantic-glossary-learndash' ),
				self::PLUGIN_REQUIREMENTS['php_version']
			);
		}

		if ( ! version_compare( $wp_version, self::PLUGIN_REQUIREMENTS['wp_version'], '>=' ) ) {
			$errors[] = sprintf(
				/* translators: minimum WordPress version */
				esc_html__( 'CBF Semantic Glossary for LearnDash requires WordPress %s or higher.', 'cbf-semantic-glossary-learndash' ),
				self::PLUGIN_REQUIREMENTS['wp_version']
			);
		}

		if ( ! function_exists( 'glossary_get_terms_for_posts' ) ) {
			$errors[] = esc_html__( 'CBF Semantic Glossary for LearnDash requires the CBF Semantic Glossary plugin to be active.', 'cbf-semantic-glossary-learndash' );
		}

		if ( ! defined( 'LEARNDASH_VERSION' ) ) {
			$errors[] = esc_html__( 'CBF Semantic Glossary for LearnDash requires LearnDash to be active.', 'cbf-semantic-glossary-learndash' );
		}

		return $errors;
	}


	/**
	 * Check requirements. Adds an admin notice on failure.
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
	 */
	private static function load_plugin_textdomain(): void {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'cbf-semantic-glossary-learndash' );
		load_textdomain( 'cbf-semantic-glossary-learndash', WP_LANG_DIR . '/cbf-semantic-glossary-learndash/cbf-semantic-glossary-learndash-' . $locale . '.mo' );
		load_plugin_textdomain( 'cbf-semantic-glossary-learndash', false, plugin_basename( dirname( PLUGIN_FILE ) ) . '/i18n/languages' );
	}
}
