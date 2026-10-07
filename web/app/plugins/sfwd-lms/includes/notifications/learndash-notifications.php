<?php
/**
 * LearnDash Notifications module main included file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Notifications
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define LearnDash LMS - Notifications add-on version.
 *
 * The value is the last version the add-on shipped as a standalone plugin. The bundled copy ships with
 * LearnDash, so the number no longer tracks the code it versions.
 *
 * Still defined so third-party code that checks it to detect the add-on keeps working.
 *
 * @since 5.2.0
 * @deprecated 5.2.0 Use {@see 'LEARNDASH_SCRIPT_VERSION_TOKEN'} for asset versions and
 *             {@see 'LEARNDASH_VERSION'} for the plugin version.
 *
 * @var string $value Default is `1.6.10`.
 */
define( 'LEARNDASH_NOTIFICATIONS_VERSION', '1.6.10' );
define( 'LEARNDASH_NOTIFICATIONS_FILE', __FILE__ );
define( 'LEARNDASH_NOTIFICATIONS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'LEARNDASH_NOTIFICATIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LEARNDASH_NOTIFICATIONS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

use LearnDash\Core\Autoloader;
use LearnDash\Notifications\Provider;

add_action(
	'plugins_loaded',
	function () {
		learndash_notifications_extra_autoloading();

		learndash_register_provider( Provider::class );

		learndash_notifications_include();
	}
);

require_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/activation.php';
require_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/deactivation.php';

/**
 * Setup the autoloader for extra classes, which are not in the src/Notifications directory.
 *
 * Both namespaced and non-namespaced classes are registered.
 *
 * @since 5.2.0
 *
 * @return void
 */
function learndash_notifications_extra_autoloading(): void {
	// From https://www.php.net/manual/en/function.glob.php#106595.
	$glob_recursive = function ( string $pattern, int $flags = 0 ) use ( &$glob_recursive ): array {
		$files = glob( $pattern, $flags );
		$files = $files === false ? [] : $files;

		$directories = glob(
			dirname( $pattern ) . '/*',
			GLOB_ONLYDIR | GLOB_NOSORT // cspell: disable-line -- GLOB_ONLYDIR and GLOB_NOSORT are constants.
		);

		if ( is_array( $directories ) ) {
			foreach ( $directories as $dir ) {
				$files = array_merge(
					$files,
					$glob_recursive( $dir . '/' . basename( $pattern ), $flags )
				);
			}
		}

		return $files;
	};

	$autoloader = Autoloader::instance();

	foreach ( $glob_recursive( LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'src/deprecated/*.php' ) as $file ) {
		if ( ! strstr( $file, 'functions' ) ) {
			// Get the clean path to the file without the extension and the src/deprecated directory.
			$class_mapped_from_file = mb_substr( $file, mb_strpos( $file, 'src/deprecated/' ) + 15, -4 );

			// Convert directory separator to namespace separator.
			// If the class is in a subdirectory, add the root namespace.
			$class_mapped_from_file = strpos( $class_mapped_from_file, '/' )
				? str_replace( '/', '\\', 'LearnDash/' . $class_mapped_from_file )
				: $class_mapped_from_file;

			$autoloader->register_class( $class_mapped_from_file, (string) $file );
		} else {
			include_once $file;
		}
	}

	$autoloader->register_autoloader();
}

/**
 * Include necessary files for the plugin.
 *
 * @since 5.2.0
 *
 * @return void
 */
function learndash_notifications_include(): void {
	// Register autoloader and init triggers.

	require_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'src/class-map.php';

	$triggers = [
		\LearnDash_Notification\Trigger\Enroll_Group::class,
		\LearnDash_Notification\Trigger\Enroll_Course::class,
		\LearnDash_Notification\Trigger\Complete_Course::class,
		\LearnDash_Notification\Trigger\Complete_Lesson::class,
		\LearnDash_Notification\Trigger\Drip_Lesson_Available::class,
		\LearnDash_Notification\Trigger\Complete_Topic::class,
		\LearnDash_Notification\Trigger\Quiz_Passed::class,
		\LearnDash_Notification\Trigger\Quiz_Failed::class,
		\LearnDash_Notification\Trigger\Quiz_Submitted::class,
		\LearnDash_Notification\Trigger\Quiz_Completed::class,
		\LearnDash_Notification\Trigger\Essay_Submitted::class,
		\LearnDash_Notification\Trigger\Essay_Graded::class,
		\LearnDash_Notification\Trigger\Assignment_Uploaded::class,
		\LearnDash_Notification\Trigger\Assignment_Approved::class,
		\LearnDash_Notification\Trigger\User_Login_Track::class,
		\LearnDash_Notification\Trigger\Before_Course_Expire::class,
		\LearnDash_Notification\Trigger\After_Course_Expire::class,
	];

	foreach ( $triggers as $trigger ) {
		$class = new $trigger();
		$class->listen();
	}

	// Include general files.
	include_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/functions.php';
	include_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/logger.php';
	include_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/cron.php';
	include_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/database.php';
	include_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/meta-box.php';
	include_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/notification.php';
	include_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/post-type.php';
	include_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/shortcode.php';
	include_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/tools.php';
	include_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/update.php';
	include_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/user.php';
	include_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/subscription-manager.php';
	include_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/ajax.php';

	if ( is_admin() ) {
		include_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/admin/class-settings.php';
		include_once LEARNDASH_NOTIFICATIONS_PLUGIN_DIR . 'includes/admin/class-status-page.php';
	}
}
