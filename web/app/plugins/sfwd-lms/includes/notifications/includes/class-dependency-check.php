<?php
/**
 * Deprecated dependency checker class file.
 *
 * @since 5.2.0
 * @deprecated 5.2.0
 *
 * @package LearnDash\Notifications
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

_deprecated_file(
	__FILE__,
	'5.2.0',
	esc_html(
		LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'src/Notifications/Utilities/Dependency_Checker.php'
	)
);

require_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'src/deprecated/LearnDash_Dependency_Check_LD_Notifications.php';
