<?php
/**
 * LearnDash Settings Pages Loader.
 *
 * @since 3.0.0
 * @package LearnDash\Settings\Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-ld-settings-page-setup.php';
require_once __DIR__ . '/class-ld-settings-page-help.php';

require_once __DIR__ . '/class-ld-settings-page-courses-options.php';
require_once __DIR__ . '/class-ld-settings-page-courses-shortcodes.php';

require_once __DIR__ . '/class-ld-settings-page-lessons-options.php';

require_once __DIR__ . '/class-ld-settings-page-topics-options.php';

require_once __DIR__ . '/class-ld-settings-page-quizzes-options.php';

require_once __DIR__ . '/class-ld-settings-page-questions-options.php';

require_once __DIR__ . '/class-ld-settings-page-groups-options.php';

require_once __DIR__ . '/class-ld-settings-page-certificate-options.php';
require_once __DIR__ . '/class-ld-settings-page-certificate-shortcodes.php';

require_once __DIR__ . '/class-ld-settings-page-assignments-options.php';

require_once __DIR__ . '/class-ld-settings-page-general.php';
require_once __DIR__ . '/class-ld-settings-page-registration.php';
require_once __DIR__ . '/class-ld-settings-page-payments.php';
require_once __DIR__ . '/class-ld-settings-page-emails.php';
if ( ( defined( 'LEARNDASH_TRANSLATIONS' ) ) && ( LEARNDASH_TRANSLATIONS === true ) ) {
	require_once __DIR__ . '/class-ld-settings-page-translations.php';
}
require_once __DIR__ . '/class-ld-settings-page-support.php';
require_once __DIR__ . '/class-ld-settings-page-advanced.php';

// Add-ons Page.
if ( ( defined( 'LEARNDASH_ADDONS_UPDATER' ) ) && ( LEARNDASH_ADDONS_UPDATER === true ) ) {
	require_once __DIR__ . '/class-ld-settings-page-addons.php';
}

