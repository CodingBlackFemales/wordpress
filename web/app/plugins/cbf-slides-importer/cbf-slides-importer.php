<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://codingblackfemales.com
 * @since             1.0.0
 * @package           CodingBlackFemales/SlidesImporter
 *
 * @wordpress-plugin
 * Plugin Name: CBF Slides Importer
 * Plugin URI:  https://codingblackfemales.com
 * Description: Import Google Slides decks into LearnDash lessons and topics via a browser-based admin UI.
 * Version:     1.0.2
 * Author:      Coding Black Females
 * Author URI:  https://codingblackfemales.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: cbf-slides-importer
 * Domain Path: /i18n/languages
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Network: false
 */

/**
 * Developer note: updating minimum PHP / WordPress versions.
 *
 * When updating any version metadata above, also update:
 * - `composer.json` → require.php and config.platform.php
 * - `includes/Main.php` → PLUGIN_REQUIREMENTS
 */

namespace CodingBlackFemales\SlidesImporter;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
const VERSION     = '1.0.2';
const PLUGIN_FILE = __FILE__;

/**
 * Return data for the "composer install not run" admin notice.
 *
 * @return array{message: string, command: string, directory: string}
 */
function get_composer_error(): array {
	return array(
		/* translators: 1: composer command 2: plugin directory */
		'message'   => esc_html__( 'Your installation of CBF Slides Importer is incomplete. Please run %1$s within the %2$s directory.', 'cbf-slides-importer' ),
		'command'   => 'composer install',
		'directory' => esc_html( str_replace( ABSPATH, '', __DIR__ ) ),
	);
}

/**
 * Autoload packages.
 *
 * Fails gracefully if `composer install` has not been run yet.
 */
$autoloader = __DIR__ . '/vendor/autoload.php';

if ( ! is_readable( $autoloader ) ) {

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$err = get_composer_error();
		error_log( sprintf( $err['message'], '`' . $err['command'] . '`', '`' . $err['directory'] . '`' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	add_action(
		'admin_notices',
		function () {
			$err = get_composer_error();
			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$err['message'],
						'<code>' . esc_html( $err['command'] ) . '</code>',
						'<code>' . esc_html( $err['directory'] ) . '</code>'
					);
					?>
				</p>
			</div>
			<?php
		}
	);

	return;
}

require $autoloader;

Main::bootstrap();
