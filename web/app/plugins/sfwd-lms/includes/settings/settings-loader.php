<?php
/**
 * LearnDash Settings Loader.
 *
 * @since 2.4.0
 * @package LearnDash\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LEARNDASH_SETTINGS_SECTION_TYPE' ) ) {
	/**
	 * Define LearnDash LMS - Set the Setting Section display type.
	 *
	 * @since 2.4.0
	 *
	 * @var string Default is 'metabox'. No other values supported at this time.
	 */
	define( 'LEARNDASH_SETTINGS_SECTION_TYPE', 'metabox' );
}

require_once __DIR__ . '/settings-functions.php';
require_once __DIR__ . '/settings-billing-functions.php';

require_once __DIR__ . '/class-ld-settings-fields.php';
require_once __DIR__ . '/class-ld-settings-pages.php';
require_once __DIR__ . '/class-ld-settings-pages-deprecated.php';
require_once __DIR__ . '/class-ld-settings-sections.php';
require_once __DIR__ . '/class-ld-theme-settings-sections.php';
require_once __DIR__ . '/class-ld-settings-metaboxes.php';

require_once __DIR__ . '/settings-fields/settings-fields-loader.php';
require_once __DIR__ . '/settings-pages/settings-pages-loader.php';
require_once __DIR__ . '/settings-sections/settings-sections-loader.php';
