<?php
/**
 * Plugin Name: LearnDash LMS - ProPanel
 * Plugin URI: http://www.learndash.com
 * Description: Easily manage and view your LearnDash LMS activity.
 * Version: 2.2.2
 * Author: LearnDash
 * Author URI: http://www.learndash.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ld_propanel
 * Domain Path: /languages
 *
 * @package LearnDash
 * @version 2.2.0
 */

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

/**
 * Setup Constants
 */

define( 'LD_PP_VERSION', '2.2.2' );

if ( ! defined( 'LD_PP_PLUGIN_DIR' ) ) {
	define( 'LD_PP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'LD_PP_PLUGIN_URL' ) ) {
	define( 'LD_PP_PLUGIN_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );
}

$learndash_shortcode_used = false;

/**
 * Load ProPanel
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ld-propanel.php';


/**
 * Support for Gutenberg Editor
 */
add_action(
	'plugins_loaded',
	function () {
		// @phpstan-ignore-next-line -- Should be checked later.
		if ( defined( 'LEARNDASH_VERSION' ) && version_compare( LEARNDASH_VERSION, '4.8.0', '>=' ) ) {
			require_once __DIR__ . '/includes/gutenberg/index.php';
		}
	}
);


add_action(
	'plugins_loaded',
	function () {
		LearnDash_ProPanel::get_instance();
	}
);

function LD_ProPanel() {
	LearnDash_ProPanel::get_instance();
}
