<?php
/**
 * LearnDash global includes
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LearnDash License utility class.
 */
require_once dirname( __FILE__ ) . '/includes/ld-license.php';

/**
 * Core utility functions
 */
require_once dirname( __FILE__ ) . '/includes/ld-core-functions.php';

/**
 * LearnDash Database utility class.
 */
require_once dirname( __FILE__ ) . '/includes/class-ldlms-db.php';

/**
 * LearnDash Post Types utility class.
 */
require_once dirname( __FILE__ ) . '/includes/class-ldlms-post-types.php';

/**
 * LearnDash Transients utility class.
 */
require_once dirname( __FILE__ ) . '/includes/class-ldlms-transients.php';


/**
 * The module base class; handles settings, options, menus, metaboxes, etc.
 */
require_once dirname( __FILE__ ) . '/includes/class-ld-semper-fi-module.php';

/**
 * SFWD_LMS
 */
require_once dirname( __FILE__ ) . '/includes/class-ld-lms.php';

/**
 * Register CPT's and Taxonomies
 */
require_once dirname( __FILE__ ) . '/includes/class-ld-cpt.php';

/**
 * Search
 */
if ( ( defined( 'LEARNDASH_FILTER_SEARCH' ) ) && ( LEARNDASH_FILTER_SEARCH === true ) ) {
	require_once dirname( __FILE__ ) . '/includes/class-ld-search.php';
}

/**
 * LearnDash Admin File Download handler
 */
require_once dirname( __FILE__ ) . '/includes/admin/class-learndash-admin-file-download-handler.php';
Learndash_Admin_File_Download_Handler::init();

/**
 * Register CPT's and Taxonomies
 */
require_once dirname( __FILE__ ) . '/includes/class-ld-cpt-instance.php';

/**
 * LearnDash Menus and Tabs logic
 */
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-menus-tabs.php';

/**
 * Widget loader.
 */
require_once dirname( __FILE__ ) . '/includes/widgets/widgets-loader.php';

/**
 * Course Legacy functions
 */
require_once dirname( __FILE__ ) . '/includes/course/ld-course-functions-legacy.php';

/**
 * Course functions
 */
require_once dirname( __FILE__ ) . '/includes/course/ld-course-functions.php';

/**
 * Course Steps functions
 */
require_once dirname( __FILE__ ) . '/includes/course/ld-course-steps-functions.php';

/**
 * Course User functions
 */
require_once dirname( __FILE__ ) . '/includes/course/ld-course-user-functions.php';

/**
 * Course Activity functions
 */
require_once dirname( __FILE__ ) . '/includes/course/ld-activity-functions.php';

/**
 * Course navigation
 */
require_once dirname( __FILE__ ) . '/includes/course/ld-course-navigation.php';

/**
 * Course progress functions
 */
require_once dirname( __FILE__ ) . '/includes/course/ld-course-progress.php';

/**
 * Course info and navigation widgets
 */
require_once dirname( __FILE__ ) . '/includes/course/ld-course-info-widget.php';

/**
 * Course metaboxes.
 */
require_once dirname( __FILE__ ) . '/includes/course/ld-course-metaboxes.php';

/**
 * Quiz metaboxes.
 */
require_once dirname( __FILE__ ) . '/includes/quiz/ld-quiz-metaboxes.php';

/**
 * Quiz and Question functions
 */
require_once dirname( __FILE__ ) . '/includes/quiz/ld-quiz-functions.php';

/**
 * Implements WP Pro Quiz
 */
require_once dirname( __FILE__ ) . '/includes/quiz/ld-quiz-pro.php';

/**
 * Quiz essay question functions
 */
require_once dirname( __FILE__ ) . '/includes/quiz/ld-quiz-essays.php';

/**
 * Load scripts & styles
 */
require_once dirname( __FILE__ ) . '/includes/ld-scripts.php';

/**
 * Customizations to wp editor for LearnDash
 */
require_once dirname( __FILE__ ) . '/includes/ld-wp-editor.php';

/**
 * Handles assignment uploads and includes helper functions for assignments
 */
require_once dirname( __FILE__ ) . '/includes/ld-assignment-uploads.php';

/**
 * Group functions
 */
require_once dirname( __FILE__ ) . '/includes/ld-groups.php';

/**
 * Exam functions
 */
require_once dirname( __FILE__ ) . '/includes/exam/ld-exam-functions.php';

/**
 * Coupon functions
 */
require_once dirname( __FILE__ ) . '/includes/coupon/ld-coupon-functions.php';
learndash_coupons_init();

/**
 * Group Membership functions
 */
require_once dirname( __FILE__ ) . '/includes/group/ld-groups-membership.php';

/**
 * User functions
 */
require_once dirname( __FILE__ ) . '/includes/ld-users.php';

/**
 * Certificate functions
 */
require_once dirname( __FILE__ ) . '/includes/ld-certificates.php';

/**
 * Misc functions
 */
require_once dirname( __FILE__ ) . '/includes/ld-misc-functions.php';

/**
 * WP-admin functions
 */
require_once dirname( __FILE__ ) . '/includes/admin/ld-admin.php';

/**
 * Course Builder Helpers.
 */
require_once dirname( __FILE__ ) . '/includes/admin/ld-course-builder-helpers.php';

/**
 * Quiz Builder Helpers.
 */
require_once dirname( __FILE__ ) . '/includes/admin/ld-quiz-builder-helpers.php';

/**
 * Gutenberg Customization.
 */
require_once dirname( __FILE__ ) . '/includes/admin/ld-gutenberg.php';

/**
 * LearnDash Settings Page Base
 */
require_once dirname( __FILE__ ) . '/includes/settings/settings-loader.php';

/**
 * LearnDash Registration Form Functions
 */
require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/payments/ld-login-registration-functions.php';

/**
 * LearnDash Emails Functions
 */
require_once dirname( __FILE__ ) . '/includes/payments/ld-emails-functions.php';

/**
 * LearnDash Payments Functions
 */
require_once dirname( __FILE__ ) . '/includes/payments/ld-payments-functions.php';

/**
 * LearnDash Transactions Functions
 */
require_once dirname( __FILE__ ) . '/includes/payments/ld-transaction-functions.php';

/**
 * LearnDash Loggers.
 */
require_once dirname( __FILE__ ) . '/includes/loggers/init.php';

/**
 * LearnDash DTO.
 */
require_once dirname( __FILE__ ) . '/includes/dto/init.php';

/**
 * LearnDash Helpers.
 */
require_once dirname( __FILE__ ) . '/includes/helpers/init.php';

/**
 * LearnDash Shortcodes Base
 */
require_once dirname( __FILE__ ) . '/includes/shortcodes/shortcodes-loader.php';

/**
 * Custom label
 */
require_once dirname( __FILE__ ) . '/includes/class-ld-custom-label.php';

/**
 * Binary Selector
 */
require_once dirname( __FILE__ ) . '/includes/admin/class-learndash-admin-binary-selector.php';

/**
 * Data/System Upgrades
 */
require_once dirname( __FILE__ ) . '/includes/admin/class-learndash-admin-data-upgrades.php';

/**
 * Reports
 */
require_once dirname( __FILE__ ) . '/includes/admin/class-learndash-admin-settings-data-reports.php';

/**
 * Reports Functions
 */
require_once dirname( __FILE__ ) . '/includes/ld-reports.php';

/**
 * Permalinks
 */
require_once dirname( __FILE__ ) . '/includes/class-ld-permalinks.php';

/**
 * GDPR
 */
require_once dirname( __FILE__ ) . '/includes/class-ld-gdpr.php';

/**
 * Site Health
 */
require_once dirname( __FILE__ ) . '/includes/site-health/class-site-health.php';
Learndash_Site_Health::init();

/**
 * Core Updater
 */
require_once dirname( __FILE__ ) . '/includes/ld-autoupdate.php';

/**
 * Purchase Invoice Functions
 */
require_once dirname( __FILE__ ) . '/includes/payments/ld-purchase-invoice-functions.php';

// @phpstan-ignore-next-line
if ( ( true === (bool) LEARNDASH_ADDONS_UPDATER ) && ( true === (bool) LEARNDASH_UPDATES_ENABLED ) ) {
	require_once dirname( __FILE__ ) . '/includes/class-ld-addons-updater.php';
} else {
	/**
	 * Added a dummy class if/when auto_update is disabled.
	 * To prevent fatal errors.
	 */
	if ( ! class_exists( 'LearnDash_Addon_Updater' ) ) {
		/**
		 * Dummy class
		 *
		 * @ignore
		 */
		class LearnDash_Addon_Updater {
			/**
			 * Instance
			 *
			 * @var object
			 * @ignore
			 */
			protected static $instance = null;

			/**
			 * Get instance
			 *
			 * @ignore
			 */
			public static function get_instance() {
				// @phpstan-ignore-next-line
				if ( ! isset( static::$instance ) ) {
					static::$instance = new self();
				}

				return static::$instance;
			}

			/**
			 * Call
			 *
			 * @param string $name      Name.
			 * @param array  $arguments Arguments.
			 *
			 * @ignore
			 */
			public function __call( $name, $arguments ) {
				// phpcs:ignore Squiz.PHP.NonExecutableCode.ReturnNotRequired
				return;
			}
		}
	}
}

/**
 * Translations
 */
if ( ( defined( 'LEARNDASH_TRANSLATIONS' ) ) && ( LEARNDASH_TRANSLATIONS === true ) ) {
	require_once dirname( __FILE__ ) . '/includes/class-ld-translations.php';

	if ( ! defined( 'LEARNDASH_TRANSLATIONS_URL_BASE' ) ) {
		/**
		 * Define LearnDash LMS - Set the Translation server URL.
		 *
		 * @since 2.5.2
		 * @internal
		 * @var string $value Default is 'https://translations.learndash.com'.
		 */
		define( 'LEARNDASH_TRANSLATIONS_URL_BASE', 'https://translations.learndash.com' );
	}
	if ( ! defined( 'LEARNDASH_TRANSLATIONS_URL_CACHE' ) ) {
		/**
		 * Define LearnDash LMS - Set the Translation cache timeout.
		 *
		 * This controls how often the plugin will call out to the translations
		 * server to check for updates.
		 *
		 * @since 2.5.2
		 *
		 * @var string $value Default is number of seconds in a 24 hour period (86.400).
		 */
		define( 'LEARNDASH_TRANSLATIONS_URL_CACHE', DAY_IN_SECONDS );
	}
}

/**
 * Registers Shortcodes.
 */
require_once dirname( __FILE__ ) . '/includes/settings/class-ld-shortcodes-tinymce.php';

/**
 * Add Support for Themes.
 */
require_once LEARNDASH_LMS_PLUGIN_DIR . 'themes/themes-loader.php';

/**
 * Add Support for the LD LMS Post Factory.
 */
require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/classes/class-loader.php';

/**
 * Support for the LearnDash action scheduler wrapper
 */
require_once dirname( __FILE__ ) . '/includes/admin/class-learndash-admin-action-scheduler.php';
Learndash_Admin_Action_Scheduler::init_ld_scheduler();

/**
 * Add Support for the Admin filters.
 */
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-filters/class-learndash-admin-filters.php';

/**
 * Add Support for the LD Bulk edit.
 */
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-bulk-edit-actions/class-learndash-admin-bulk-edit-actions.php';

/**
 * Registers REST API Hooks.
 */
require_once dirname( __FILE__ ) . '/includes/rest-api/class-ld-rest-api.php';

/**
 * Load our Import/Export Utilities
 */
require_once dirname( __FILE__ ) . '/includes/import/import-loader.php';

/**
 * Support for Video Progression
 */
if ( ( defined( 'LEARNDASH_LESSON_VIDEO' ) ) && ( LEARNDASH_LESSON_VIDEO === true ) ) {
	require_once dirname( __FILE__ ) . '/includes/course/ld-course-video.php';
}

/**
 * Support for cloning utilities
 */
require_once dirname( __FILE__ ) . '/includes/admin/ld-cloning.php';

/**
 * Import/Export
 */
require_once dirname( __FILE__ ) . '/includes/admin/ld-import-export.php';

/**
 * Support for Course and/or Quiz Builder
 */
require_once dirname( __FILE__ ) . '/includes/admin/class-learndash-admin-builder.php';

/**
 * Support for Gutenberg Editor
 */
if ( ( defined( 'LEARNDASH_GUTENBERG' ) ) && ( LEARNDASH_GUTENBERG === true ) ) {
	require_once dirname( __FILE__ ) . '/includes/gutenberg/index.php';
}

/**
 * LearnDash Deprecated Functions/Classes
 */
require_once dirname( __FILE__ ) . '/includes/deprecated/deprecated-functions.php';
