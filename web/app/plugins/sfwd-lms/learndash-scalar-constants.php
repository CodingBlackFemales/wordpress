<?php
/**
 * LearnDash scalar constants
 *
 * @since 4.5.0.1
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define LearnDash LMS - Set the settings database version.
 *
 * This define controls logic specific to the Activity database tables schema.
 *
 * @since 2.3.0
 * @internal Will be set by LearnDash LMS.
 *
 * @var string $value PHP version x.x.x or x.x.x.x format.
 */
define( 'LEARNDASH_SETTINGS_DB_VERSION', '2.5' );

/**
 * Define LearnDash LMS - Set the settings database upgrade trigger version.
 *
 * This define controls admin prompts to perform a data upgrades.
 *
 * @since 2.3.1
 * @internal Will be set by LearnDash.
 *
 * @var string $value PHP version x.x.x or x.x.x.x format.
 */
define( 'LEARNDASH_SETTINGS_TRIGGER_UPGRADE_VERSION', '2.5' );

/**
 * Define LearnDash LMS - Set the text domain.
 *
 * This define is used when loading the text domain files.
 * Should NOT be used for actual text domain string markers.
 *
 * @since 2.3.2
 * @internal Will be set by LearnDash LMS.
 *
 * @var string $value PHP version x.x.x or x.x.x.x format.
 */
define( 'LEARNDASH_LMS_TEXT_DOMAIN', 'learndash' );

/**
 * Define LearnDash LMS - Set the minimum supported PHP version.
 *
 * @since 3.3.0.2
 * @internal Will be set by LearnDash LMS.
 *
 * @var string $value PHP version x.x.x or x.x.x.x format.
 */
define( 'LEARNDASH_MIN_PHP_VERSION', '7.3' );

/**
 * Define LearnDash LMS - Set the minimum supported MySQL version.
 *
 * @since 3.3.0.2
 * @internal Will be set by LearnDash LMS.
 *
 * @var string $value PHP version x.x.x or x.x.x.x format.
 */
define( 'LEARNDASH_MIN_MYSQL_VERSION', '5.6' );

/**
 * Define LearnDash LMS - Set the minimum supported MariaDB version.
 *
 * @since 3.4.0
 * @internal Will be set by LearnDash LMS.
 *
 * @var string $value PHP version x.x.x or x.x.x.x format.
 */
define( 'LEARNDASH_MIN_MARIA_VERSION', '10.0' );

if ( ! defined( 'LEARNDASH_LMS_LIBRARY_DIR' ) ) {
	/**
	 * Define LearnDash LMS - Set the plugin includes/lib path.
	 *
	 * Will be set based on the LearnDash define `LEARNDASH_LMS_PLUGIN_DIR`.
	 *
	 * @since 2.1.4
	 * @uses LEARNDASH_LMS_PLUGIN_DIR
	 *
	 * @var string $value Directory path to plugin includes/lib internal directory.
	 */
	define( 'LEARNDASH_LMS_LIBRARY_DIR', trailingslashit( LEARNDASH_LMS_PLUGIN_DIR ) . 'includes/lib' );
}

if ( ! defined( 'LEARNDASH_LMS_LIBRARY_URL' ) ) {
	/**
	 * Define LearnDash LMS - Set the plugin includes/lib relative URL.
	 *
	 * Will be set based on the LearnDash define `LEARNDASH_LMS_PLUGIN_URL`.
	 *
	 * @since 2.1.4
	 * @uses LEARNDASH_LMS_PLUGIN_URL
	 *
	 * @var string $value URL to plugin includes/lib directory.
	 */
	define( 'LEARNDASH_LMS_LIBRARY_URL', trailingslashit( LEARNDASH_LMS_PLUGIN_URL ) . 'includes/lib' );
}

if ( ! defined( 'LEARNDASH_OBJECT_CACHE_ENABLED' ) ) {
	/**
	 * Define LearnDash LMS - Enabled support for object cache used for temporary storage.
	 *
	 * @since 3.4.1
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Will enable object storage support. Default.
	 *    @type bool false Will disable object cache support.
	 * }
	 */
	define( 'LEARNDASH_OBJECT_CACHE_ENABLED', true );
}

if ( ! defined( 'LEARNDASH_TRANSIENTS_DISABLED' ) ) {
	/**
	 * Define LearnDash LMS - Enabled support for Transients used for temporary storage.
	 *
	 * @since 2.3.3 Initial value `false`.
	 * @since 3.4.0 Set to `true` as default to disable transients.
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Will disable transient storage. Default.
	 *    @type bool false Will enable transient storage.
	 * }
	 */
	define( 'LEARNDASH_TRANSIENTS_DISABLED', true );
}

if ( ! defined( 'LEARNDASH_REPORT_TRANSIENT_STORAGE' ) ) {
	/**
	 * Define LearnDash LMS - Controls the Course/Quiz Report transient cache storage used.
	 *
	 * @since 3.4.1
	 * @deprecated 3.5.0 Use {@see 'LEARNDASH_TRANSIENT_CACHE_STORAGE'} instead.
	 *
	 * @var string|bool $value {
	 *    Only one of the following values.
	 *    @type bool   false     Default as of 3.5.0.
	 *    @type string 'options' Will use the `wp_options` table.
	 *    @type string 'file'    Will save cache data in file within `wp-content/uploads/learndash/reports/`.
	 * }
	 */
	define( 'LEARNDASH_REPORT_TRANSIENT_STORAGE', false );
}

if ( ! defined( 'LEARNDASH_TRANSIENT_CACHE_STORAGE' ) ) {
	$learndash_default_resource_transient_storage = 'file';
	if ( ( defined( 'LEARNDASH_REPORT_TRANSIENT_STORAGE' ) ) && ( is_string( LEARNDASH_REPORT_TRANSIENT_STORAGE ) ) ) {
		$learndash_default_resource_transient_storage = esc_attr( LEARNDASH_REPORT_TRANSIENT_STORAGE );
		if ( ! in_array( $learndash_default_resource_transient_storage, array( 'file', 'options' ), true ) ) {
			$learndash_default_resource_transient_storage = 'file';
		}
	}

	/**
	 * Define LearnDash LMS - Controls Resource transient cache storage used.
	 *
	 * This is used for Data Upgrades, Reports, and other processing.
	 *
	 * @since 3.5.0
	 *
	 * @var string $value {
	 *    Only one of the following values.
	 *    @type string options Will use the wp_options table. Default.
	 *    @type string file    Will save cache data in file within `wp-content/uploads/learndash/reports/`.
	 * }
	 */
	define( 'LEARNDASH_TRANSIENT_CACHE_STORAGE', $learndash_default_resource_transient_storage );
}

if ( ! defined( 'LEARNDASH_DEBUG' ) ) {
	/**
	 * Define LearnDash LMS - Enable debug message output.
	 *
	 * @since 2.5.9
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Will output debug message similar to the WordPress WP_DEBUG define.
	 *    @type bool false Default
	 * }
	 */
	define( 'LEARNDASH_DEBUG', false );
}

if ( ! defined( 'LEARNDASH_ERROR_REPORTING_ZERO' ) ) {
	/**
	 * Define LearnDash LMS - Enable legacy error handling logic where the PHP
	 * error_reporting(0) was set.
	 *
	 * @since 3.4.0
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Enable the function error_reporting(0) to be used. Legacy.
	 *    @type bool false Default.
	 * }
	 */
	define( 'LEARNDASH_ERROR_REPORTING_ZERO', false );
}

if ( ! defined( 'LEARNDASH_SCRIPT_DEBUG' ) ) {
	if ( ( defined( 'SCRIPT_DEBUG' ) ) && ( SCRIPT_DEBUG === true ) ) {
		$learndash_define_script_debug_value = true;
	} else {
		$learndash_define_script_debug_value = false;
	}

	/**
	 * Define LearnDash LMS - Enable load of non-minified CSS/JS assets.
	 *
	 * If the WordPress SCRIPT_DEBUG or LearnDash LEARNDASH_SCRIPT_DEBUG
	 * are set then LEARNDASH_SCRIPT_DEBUG will also be set to (bool) true.
	 *
	 * @since 2.2.0
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  The non-minified versions of CSS/JS assets will be used.
	 *    @type bool false The minified CSS/JS assets will be used. Default.
	 * }
	 */
	define( 'LEARNDASH_SCRIPT_DEBUG', $learndash_define_script_debug_value );
}

if ( ! defined( 'LEARNDASH_COURSE_FUNCTIONS_LEGACY' ) ) {
	/**
	 * Define LearnDash LMS - Enabled legacy Course Progression and Query logic.
	 *
	 * This define will be removed in a future release.
	 *
	 * @since 3.4.0
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  The LD 3.3.x legacy course progression and query logic will be used.
	 *    @type bool false The LD 3.4.x improved course progression and query logic will be used. Default.
	 * }
	 */
	define( 'LEARNDASH_COURSE_FUNCTIONS_LEGACY', false );
}

if ( ! defined( 'LEARNDASH_BUILDER_STEPS_UPDATE_POST' ) ) {
	/**
	 * Define LearnDash LMS - Enables Controls the method used to update the builder step.
	 *
	 * @since 3.2.3
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Use the function `wp_update_post()` function.
	 *    @type bool false Use the default `wpdb::update()` and `clean_post_cache()` functions. Default.
	 * }
	 */
	define( 'LEARNDASH_BUILDER_STEPS_UPDATE_POST', false );
}

if ( ! defined( 'LEARNDASH_SCRIPT_VERSION_TOKEN' ) ) {
	$learndash_define_script_version_token_value = constant( 'LEARNDASH_VERSION' );

	if ( defined( 'LEARNDASH_SCRIPT_DEBUG' ) && ( LEARNDASH_SCRIPT_DEBUG === true ) ) {
		$learndash_define_script_version_token_value .= '-' . time();
	}

	/**
	 * Define LearnDash LMS - Sets a unique value to be appended to CSS/JS URLS.
	 *
	 * The default value is the plugin version `LEARNDASH_VERSION`. If `LEARNDASH_SCRIPT_DEBUG`
	 * is set to `true` the value will also append a timestamp ensuring a unique URL for each
	 * request.
	 *
	 * @since 2.5.0
	 *
	 * @uses LEARNDASH_SCRIPT_DEBUG
	 * @uses LEARNDASH_VERSION
	 *
	 * @var string $value Default is define `LEARNDASH_VERSION` value.
	 */
	define( 'LEARNDASH_SCRIPT_VERSION_TOKEN', $learndash_define_script_version_token_value );
}

if ( ! defined( 'LEARNDASH_FILTER_PRIORITY_THE_CONTENT' ) ) {
	/**
	 * Define LearnDash LMS - Sets the priority when LearnDash hooks into the WordPress filter
	 * 'the_content' filter for the main course posts.
	 *
	 * @since 3.1.4
	 *
	 * @var int $value Default is 30.
	 */
	define( 'LEARNDASH_FILTER_PRIORITY_THE_CONTENT', 30 );
}

if ( ! defined( 'LEARNDASH_REST_API_ENABLED' ) ) {
	/**
	 * Define LearnDash LMS - Enable support REST API.
	 *
	 * @since 2.5.8
	 *
	 * @var bool $value Default is true.
	 */
	define( 'LEARNDASH_REST_API_ENABLED', true );
}

if ( ! defined( 'LEARNDASH_BLOCK_WORDPRESS_CPT_ROUTES' ) ) {
	/**
	 * Define LearnDash LMS - Enable block access to default WordPress CPT routes.
	 *
	 * Logic added to prevent access to the automatic routes created as part of
	 * WP core for Gutenberg enabled custom post types. This new logic will prevent
	 * visibility read access if used is not authenticated or does not have update
	 * capabilities.
	 *
	 * @since 3.2.0
	 *
	 * @var bool $value Default is true.
	 */
	define( 'LEARNDASH_BLOCK_WORDPRESS_CPT_ROUTES', true );
}

if ( ! defined( 'LEARNDASH_LESSON_VIDEO' ) ) {
	/**
	 * Define LearnDash LMS - Enable support for Lesson/Topic Video Progression.
	 *
	 * @since 2.4.5
	 *
	 * @var bool $value Default is true.
	 */
	define( 'LEARNDASH_LESSON_VIDEO', true );
}

if ( ! defined( 'LEARNDASH_COURSE_BUILDER' ) ) {
	/**
	 * Define LearnDash LMS - Enable support for Course Builder.
	 *
	 * @since 2.5.0
	 *
	 * @var bool $value Default is true.
	 */
	define( 'LEARNDASH_COURSE_BUILDER', true );
}

/**
 * Define LearnDash LMS
 *
 * @ignore
 */
if ( ! defined( 'LEARNDASH_COURSE_STEPS_PRELOAD' ) ) {
	define( 'LEARNDASH_COURSE_STEPS_PRELOAD', true );
}

if ( ! defined( 'LEARNDASH_QUIZ_BUILDER' ) ) {
	/**
	 * Define LearnDash LMS - Enable support for Quiz Builder.
	 *
	 * @since 2.6.0
	 *
	 * @var bool $value Default is true.
	 */
	define( 'LEARNDASH_QUIZ_BUILDER', true );
}

if ( ! defined( 'LEARNDASH_BUILDER_DEBUG' ) ) {
	/**
	 * Define LearnDash LMS - Enable load of non-minified CSS/JS assets for Builders.
	 *
	 * @since 3.0.0
	 *
	 * @var bool $value Default is false.
	 */
	define( 'LEARNDASH_BUILDER_DEBUG', false );
}

if ( ! defined( 'LEARNDASH_GUTENBERG' ) ) {
	/**
	 * Define LearnDash LMS - Enable support for Gutenberg Editor.
	 *
	 * @since 2.5.8
	 *
	 * @var bool $value Default is true.
	 */
	define( 'LEARNDASH_GUTENBERG', true );
}

if ( ! defined( 'LEARNDASH_GUTENBERG_CONTENT_PARSE_LEGACY' ) ) {
	/**
	 * Define LearnDash LMS - Use legacy content parse for Gutenberg block rendering.
	 *
	 * @since 4.0.0
	 *
	 * @var bool $value Default is false.
	 */
	define( 'LEARNDASH_GUTENBERG_CONTENT_PARSE_LEGACY', false );
}

if ( ! defined( 'LEARNDASH_TRANSLATIONS' ) ) {
	/**
	 * Define LearnDash LMS - Enable support for Translations downloads via GlotPress.
	 *
	 * @since 2.5.2
	 *
	 * @var bool $value Default is true.
	 */
	define( 'LEARNDASH_TRANSLATIONS', true );
}

if ( ! defined( 'LEARNDASH_HTTP_REMOTE_GET_TIMEOUT' ) ) {
	/**
	 * Define LearnDash LMS - Set timeout (seconds) on HTTP GET requests.
	 *
	 * @since 3.1.0
	 *
	 * @var int $value Default is 15.
	 */
	define( 'LEARNDASH_HTTP_REMOTE_GET_TIMEOUT', 15 );
}

if ( ! defined( 'LEARNDASH_HTTP_REMOTE_POST_TIMEOUT' ) ) {
	/**
	 * Define LearnDash LMS - Set timeout (seconds) on HTTP POST requests.
	 *
	 * @since 3.1.0
	 *
	 * @var int $value Default is 15.
	 */
	define( 'LEARNDASH_HTTP_REMOTE_POST_TIMEOUT', 15 );
}

if ( ! defined( 'LEARNDASH_HTTP_BITBUCKET_README_DOWNLOAD_TIMEOUT' ) ) {
	/**
	 * Define LearnDash LMS - Set timeout (seconds) for BitBucket Readme download_url() request.
	 *
	 * @since 3.1.8
	 *
	 * @var int $value Default is 15.
	 */
	define( 'LEARNDASH_HTTP_BITBUCKET_README_DOWNLOAD_TIMEOUT', 15 );
}

if ( defined( 'LEARNDASH_REPO_ERROR_THRESHOLD_COUNT' ) ) {
	/**
	 * Define LearnDash LMS - Set the number of consecutive errors before update attempts abort.
	 *
	 * @since 3.1.8
	 *
	 * @var int $value Default is 3.
	 */
	define( 'LEARNDASH_REPO_ERROR_THRESHOLD_COUNT', 3 );
}

if ( defined( 'LEARNDASH_REPO_ERROR_THRESHOLD_TIME' ) ) {
	/**
	 * Define LearnDash LMS - Set the time (seconds) after abort before restarting tries.
	 *
	 * @since 3.1.8
	 *
	 * @var int $value Default is 7200.
	 */
	define( 'LEARNDASH_REPO_ERROR_THRESHOLD_TIME', 2 * 60 * 60 );
}

if ( ! defined( 'LEARNDASH_LMS_DEFAULT_QUESTION_POINTS' ) ) {
	/**
	 * Define LearnDash LMS - Set the default quiz question points.
	 *
	 * @since 2.1.6
	 *
	 * @var int $value Default is 1.
	 */
	define( 'LEARNDASH_LMS_DEFAULT_QUESTION_POINTS', 1 );
}

if ( ! defined( 'LEARNDASH_LMS_DEFAULT_ANSWER_POINTS' ) ) {
	/**
	 * Define LearnDash LMS - Set the default quiz question answer points.
	 *
	 * @since 2.1.6
	 *
	 * @var int $value Default is 0.
	 */
	define( 'LEARNDASH_LMS_DEFAULT_ANSWER_POINTS', 0 );
}

if ( ! defined( 'LEARNDASH_LMS_DEFAULT_LAZY_LOAD_PER_PAGE' ) ) {
	/**
	 * Define LearnDash LMS - Set the number of items to lazy load per AJAX request.
	 *
	 * @since 2.2.1
	 *
	 * @var int $value Default is 5000.
	 */
	define( 'LEARNDASH_LMS_DEFAULT_LAZY_LOAD_PER_PAGE', 5000 );
}

if ( ! defined( 'LEARNDASH_LMS_DEFAULT_DATA_UPGRADE_BATCH_SIZE' ) ) {
	/**
	 * Define LearnDash LMS - Set the number of items for Data Upgrade batch.
	 *
	 * @since 2.6.0
	 *
	 * @var int $value Default is 1000.
	 */
	define( 'LEARNDASH_LMS_DEFAULT_DATA_UPGRADE_BATCH_SIZE', 1000 );
}

if ( ! defined( 'LEARNDASH_LMS_COURSE_STEPS_LOAD_BATCH_SIZE' ) ) {
	/**
	 * Define LearnDash LMS - Set the number of course steps objects load batch size.
	 *
	 * Used when loading course step WP_Post objects. On a very large course attempting
	 * to load too many post objects via a single query can impact server performance.
	 *
	 * @since 3.4.0
	 *
	 * @var int $value Default is 500.
	 */
	define( 'LEARNDASH_LMS_COURSE_STEPS_LOAD_BATCH_SIZE', 500 );
}

if ( ! defined( 'LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE' ) ) {
	/**
	 * Define LearnDash LMS - Set the default number of items per page.
	 *
	 * @since 2.5.5
	 *
	 * @var int $value Default is 20.
	 */
	define( 'LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE', 20 );
}

if ( ! defined( 'LEARNDASH_LMS_DEFAULT_CB_INSERT_CHUNK_SIZE' ) ) {
	/**
	 * Define LearnDash LMS - Set the number of items to insert/update when saving builder data.
	 *
	 * This value controls the query insert/update logic and does not limit the number of steps.
	 *
	 * @since 2.5.0
	 *
	 * @var int $value Default is 10.
	 */
	define( 'LEARNDASH_LMS_DEFAULT_CB_INSERT_CHUNK_SIZE', 10 );
}

if ( ! defined( 'LEARNDASH_ADMIN_CAPABILITY_CHECK' ) ) {
	/**
	 * Define LearnDash LMS - Set the Administrator role capability check.
	 *
	 * The value should match a role capability used to determine if a user is
	 * and Administrator user. Default is 'manage_options'.
	 *
	 * @since 2.3.0
	 *
	 * @var string $value Default is 'manage_options'.
	 */
	define( 'LEARNDASH_ADMIN_CAPABILITY_CHECK', 'manage_options' );
}

if ( ! defined( 'LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK' ) ) {
	/**
	 * Define LearnDash LMS - Set the Group Leader role capability check.
	 *
	 * The value should match a role capability used to determine if a user is
	 * a Group Leader user. Default is 'group_leader'.
	 *
	 * @since 2.3.0
	 *
	 * @var string $value Default is 'group_leader'.
	 */
	define( 'LEARNDASH_GROUP_LEADER_CAPABILITY_CHECK', 'group_leader' );
}

if ( ! defined( 'LEARNDASH_GROUP_LEADER_DASHBOARD_ACCESS' ) ) {

	/**
	 * Define LearnDash LMS - Control Group Leader access to WP Dashboard with WooCommerce.
	 *
	 * Used by `learndash_check_group_leader_access`
	 *
	 * @since 2.3.0
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Will allow Group Leader access to WP Dashboard. Default.
	 *    @type bool false Will prevent Group Leader access to WP Dashboard.
	 * }
	 */
	define( 'LEARNDASH_GROUP_LEADER_DASHBOARD_ACCESS', true );
}

if ( ! defined( 'LEARNDASH_DEFAULT_THEME' ) ) {
	/**
	 * Define LearnDash LMS - Set the default template used.
	 *
	 * This value is used to set the default theme on new installs.
	 *
	 * @since 3.0.0
	 *
	 * @var string $value Default is 'ld30'.
	 */
	define( 'LEARNDASH_DEFAULT_THEME', 'ld30' );
}

if ( ! defined( 'LEARNDASH_LEGACY_THEME' ) ) {
	/**
	 * Define LearnDash LMS - Set the legacy template slug.
	 *
	 * @since 3.0.0
	 *
	 * @var string $value Default is 'legacy'.
	 */
	define( 'LEARNDASH_LEGACY_THEME', 'legacy' );
}

if ( ! defined( 'LEARNDASH_DEFAULT_COURSE_PRICE_TYPE' ) ) {
	/**
	 * Define LearnDash LMS - Set the default course price type.
	 *
	 * @since 3.2.0
	 *
	 * @var string $value {
	 *    Possible values one of the following.
	 *    @type string open      Price Type 'open'. Default.
	 *    @type string free      Price Type 'free'.
	 *    @type string paynow    Price Type 'paynow'.
	 *    @type string subscribe Price Type 'subscribe'.
	 *    @type string closed    Price Type 'closed'.
	 * }
	 */
	define( 'LEARNDASH_DEFAULT_COURSE_PRICE_TYPE', 'open' );
}

if ( ! defined( 'LEARNDASH_DEFAULT_COURSE_ORDER' ) ) {
	/**
	 * Define LearnDash LMS - Set the default course steps order. NOT USED
	 *
	 * @since 3.2.0
	 * @ignore
	 *
	 * @var string $value {
	 *    Only one of the following values.
	 *    @type string ASC  Sort values Ascending. Default.
	 *    @type string DESC Sort values Descending.
	 * }
	 */
	define( 'LEARNDASH_DEFAULT_COURSE_ORDER', 'ASC' );
}

if ( ! defined( 'LEARNDASH_DEFAULT_COURSE_ORDERBY' ) ) {
	/**
	 * Define LearnDash LMS - Set the default course steps order by. NOT USED.
	 *
	 * @since 3.2.0
	 * @ignore
	 *
	 * @var string $value {
	 *    Only one of the following values.
	 *    @type string date       Sort values by Date. Default.
	 *    @type string menu_order Sort values by menu_order.
	 *    @type string title      Sort values by title.
	 * }
	 */
	define( 'LEARNDASH_DEFAULT_COURSE_ORDERBY', 'date' );
}

if ( ! defined( 'LEARNDASH_COURSE_STEP_READ_CHECK' ) ) {
	/**
	 * Define LearnDash LMS - Enable logic to check if user can read course step WP_Post.
	 *
	 * @since 3.4.0.2
	 *
	 * @var bool $value Default is true.
	 */
	define( 'LEARNDASH_COURSE_STEP_READ_CHECK', true );
}

if ( ! defined( 'LEARNDASH_DEFAULT_GROUP_PRICE_TYPE' ) ) {
	/**
	 * Define LearnDash LMS - Set the default group price type.
	 *
	 * @since 3.2.0
	 *
	 * @var string $value {
	 *    Possible values one of the following.
	 *    @type string closed    Price Type 'closed'. Default.
	 *    @type string free      Price Type 'free'.
	 *    @type string paynow    Price Type 'paynow'.
	 *    @type string subscribe Price Type 'subscribe'.
	 * }
	 */
	define( 'LEARNDASH_DEFAULT_GROUP_PRICE_TYPE', 'closed' );
}

if ( ! defined( 'LEARNDASH_DEFAULT_GROUP_ORDER' ) ) {
	/**
	 * Define LearnDash LMS - Set the default groups courses display order.
	 *
	 * @since 3.2.0
	 *
	 * @var string $value {
	 *    Only one of the following values.
	 *    @type string ASC  Sort values Ascending. Default.
	 *    @type string DESC Sort values Descending.
	 * }
	 */
	define( 'LEARNDASH_DEFAULT_GROUP_ORDER', 'ASC' );
}

if ( ! defined( 'LEARNDASH_DEFAULT_GROUP_ORDERBY' ) ) {
	/**
	 * Define LearnDash LMS - Set the default groups courses display order by.
	 *
	 * @since 3.2.0
	 *
	 * @var string $value {
	 *    Only one of the following values.
	 *    @type string date       Sort values by Date. Default.
	 *    @type string menu_order Sort values by menu_order.
	 *    @type string title      Sort values by title.
	 * }
	 */
	define( 'LEARNDASH_DEFAULT_GROUP_ORDERBY', 'date' );
}

if ( ! defined( 'LEARNDASH_QUIZ_RESULT_MESSAGE_MAX' ) ) {
	/**
	 * Define LearnDash LMS - Set the maximum number of items used for the
	 * Quiz Result Message setting field.
	 *
	 * @since 3.0.0
	 *
	 * @var int $value Default is 15.
	 */
	define( 'LEARNDASH_QUIZ_RESULT_MESSAGE_MAX', 15 );
}

if ( ! defined( 'LEARNDASH_QUIZ_RESUME_COOKIE_SEND_TIMER_MIN' ) ) {
	/**
	 * Define LearnDash LMS - Set the minimum second for sending quiz resume data to server.
	 *
	 * @since 3.6.1
	 *
	 * @var int $value Default is 5.
	 */
	define( 'LEARNDASH_QUIZ_RESUME_COOKIE_SEND_TIMER_MIN', 5 );
}

if ( ! defined( 'LEARNDASH_QUIZ_RESUME_COOKIE_SEND_TIMER_DEFAULT' ) ) {
	/**
	 * Define LearnDash LMS - Set the default second for sending quiz resume data to server.
	 *
	 * @since 3.6.1
	 *
	 * @var int $value Default is 5.
	 */
	define( 'LEARNDASH_QUIZ_RESUME_COOKIE_SEND_TIMER_DEFAULT', 20 );
}

if ( ! defined( 'LEARNDASH_QUIZ_ANSWER_MESSAGE_HTML_TYPE' ) ) {
	/**
	 * Define LearnDash LMS - Set the Quiz answer message wrapper
	 * HTML element type.
	 *
	 * @since 3.5.0
	 *
	 * @var string $value Default is 'div'.
	 */
	define( 'LEARNDASH_QUIZ_ANSWER_MESSAGE_HTML_TYPE', 'div' );
}


if ( ! defined( 'LEARNDASH_QUIZ_EXPORT_LEGACY' ) ) {
	/**
	 * Define LearnDash LMS - Use the legacy WPProQuiz import/export logic
	 * using unserialize/serialize instead of newer json_decode/json_encode.
	 *
	 * @since 3.2.0
	 *
	 * @var bool $value Default is false.
	 */
	define( 'LEARNDASH_QUIZ_EXPORT_LEGACY', false );
}

if ( ! defined( 'LEARNDASH_QUIZ_PREREQUISITE_ALT' ) ) {
	/**
	 * Define LearnDash LMS - Controls the Quiz Prerequisite
	 * handling.
	 *
	 * If `true` the user must pass the prerequisite
	 * quizzes. If `false` the user must have only taken
	 * the prerequisite quizzes but not required to pass
	 * them.
	 *
	 * @since 2.5.7
	 *
	 * @var bool $value Default is false.
	 */
	define( 'LEARNDASH_QUIZ_PREREQUISITE_ALT', true );
}


if ( ! defined( 'LEARNDASH_ADMIN_POPUP_STYLE' ) ) {
	/**
	 * Define LearnDash LMS - Set the popup method used for items like the
	 * TinyMCE popup used for shortcodes.
	 *
	 * @since 3.0.7
	 *
	 * @var string $value {
	 *    Only one of the following values.
	 *    @type string 'jQuery-dialog' Default.
	 *    @type string 'thickbox'      Legacy thickbox popup.
	 * }
	 */
	define( 'LEARNDASH_ADMIN_POPUP_STYLE', 'jQuery-dialog' );
}

if ( ! defined( 'LEARNDASH_USE_WP_SAFE_REDIRECT' ) ) {
	/**
	 * Define LearnDash LMS - Controls handling of redirects.
	 *
	 * @since 3.3.0.2
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Use the WP function `wp_safe_redirect`. Default.
	 *    @type bool false Use the WP function `wp_redirect`.
	 * }
	 */
	define( 'LEARNDASH_USE_WP_SAFE_REDIRECT', true );
}

if ( ! defined( 'LEARNDASH_DISABLE_TEMPLATE_CONTENT_OUTSIDE_LOOP' ) ) {
	/**
	 * Define LearnDash LMS - Controls filtering of 'the_content' outside of the 'loop'.
	 *
	 * @since 3.2.3
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  If called outside the WP loop, content will not be processed. Default.
	 *    @type bool false Content will be processed.
	 * }
	 */
	define( 'LEARNDASH_DISABLE_TEMPLATE_CONTENT_OUTSIDE_LOOP', true );
}

if ( ! defined( 'LEARNDASH_TEMPLATE_CONTENT_METHOD' ) ) {
	/**
	 * Define LearnDash LMS - Controls the method the template content is rendered.
	 * Supported by LD30 theme only.
	 *
	 * @since 4.0.0
	 *
	 * @var string $value {
	 *    Only one of the following values.
	 *    @type string 'template'  Content will be rendered via the template. This is the legacy/default method.
	 *    @type string 'shortcode' Content will be rendered via shortcodes.
	 * }
	 */
	define( 'LEARNDASH_TEMPLATE_CONTENT_METHOD', 'shortcode' );
}

if ( ! defined( 'LEARNDASH_GROUP_ENROLLED_COURSE_FROM_USER_REGISTRATION' ) ) {
	/**
	 * Define LearnDash LMS - Control the determination of the user's Group enrollment time.
	 *
	 * @since 3.2.0
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Use the user's registration for the Group enrollment time, if newer. Default.
	 *    @type bool false
	 * }
	 */
	define( 'LEARNDASH_GROUP_ENROLLED_COURSE_FROM_USER_REGISTRATION', true );
}

if ( ! defined( 'LEARNDASH_SELECT2_LIB' ) ) {
	/**
	 * Define LearnDash LMS - Enable use of the Select2 jQuery library.
	 *
	 * The Select2 library is used on post type listings and within admin setting
	 * used by LearnDash.
	 *
	 * @since 3.0.0
	 *
	 * @var bool $value Default is true.
	 */
	define( 'LEARNDASH_SELECT2_LIB', true );
}

if ( ! defined( 'LEARNDASH_SELECT2_LIB_AJAX_FETCH' ) ) {
	/**
	 * Define LearnDash LMS - Enable fetch logic as part of the Select2 library.
	 *
	 * Possible value:
	 * true (bool) Will enable callbacks to the server via AJAX to load selector
	 * items. This can improve performance. Default.
	 *
	 * The `LEARNDASH_SELECT2_LIB` define must be true.
	 *
	 * @since 3.2.3
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Will enable callbacks to the server via AJAX to load selector. Default.
	 *    @type bool false
	 * }
	 */
	define( 'LEARNDASH_SELECT2_LIB_AJAX_FETCH', true );
}

if ( ! defined( 'LEARNDASH_SETTINGS_METABOXES_LEGACY' ) ) {
	/**
	 * Define LearnDash LMS - Enable legacy Post Type Settings Metaboxes.
	 *
	 * @since 3.0.0
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Will use metabox containers when showing the settings outside of the post type editor. Default is true. Must be set to true.
	 *    @type bool false Not supported.
	 * }
	 */
	define( 'LEARNDASH_SETTINGS_METABOXES_LEGACY', true );
}

if ( ! defined( 'LEARNDASH_SETTINGS_METABOXES_LEGACY_QUIZ' ) ) {
	/**
	 * Define LearnDash LMS - Enable legacy WPProQuiz Post Type Settings Metaboxes.
	 *
	 * @since 3.0.0
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Will show the legacy WPProQuiz linear listing of settings.
	 *    @type bool false Will display Quiz Post settings using newer metabox containers. Default.
	 * }
	 */
	define( 'LEARNDASH_SETTINGS_METABOXES_LEGACY_QUIZ', false );
}

if ( ! defined( 'LEARNDASH_SETTINGS_HEADER_PANEL' ) ) {
	/**
	 * Define LearnDash LMS - Enable the new (3.0.0) Header Panel.
	 *
	 * @since 3.0.0
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Will show the LearnDash header panel on related admin pages. Default is true. Must be set to true.
	 *    @type bool false Not supported.
	 * }
	 */
	define( 'LEARNDASH_SETTINGS_HEADER_PANEL', true );
}

if ( ! defined( 'LEARNDASH_SHOW_MARK_INCOMPLETE' ) ) {
	/**
	 * Define LearnDash LMS - Enable the Mark Incomplete button on course steps. Beta.
	 *
	 * @since 3.1.4
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Will display a button on completed course steps allowing the user. BETA.
	 *    @type bool false Default.
	 * }
	 */
	define( 'LEARNDASH_SHOW_MARK_INCOMPLETE', false );
}

if ( ! defined( 'LEARNDASH_FILTER_SEARCH' ) ) {
	/**
	 * Define LearnDash LMS - Enable search filter logic.
	 *
	 * @since 3.2.0
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Will enable some logic to hook into the WP search processing.
	 *                     The logic can help filter display items to only show lessons, topics, etc.
	 *                     the user has access to. Default.
	 *    @type bool false
	 * }
	 */
	define( 'LEARNDASH_FILTER_SEARCH', true );
}

if ( ! defined( 'LEARNDASH_LMS_DATABASE_PREFIX_SUB' ) ) {
	/**
	 * Define LearnDash LMS - Set the default database prefix.
	 *
	 * This prefix is appended to the WP table prefix.
	 *
	 * @since 3.1.0
	 *
	 * @var string $value Default is 'learndash_'.
	 */
	define( 'LEARNDASH_LMS_DATABASE_PREFIX_SUB', 'learndash_' );
}

if ( ! defined( 'LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT' ) ) {
	/**
	 * Define LearnDash LMS - Set the default WPProQuiz database prefix.
	 *
	 * This prefix is appended to the WP table prefix.
	 *
	 * @since 3.1.0
	 *
	 * @var string $value Default is 'wp_'.
	 */
	define( 'LEARNDASH_PROQUIZ_DATABASE_PREFIX_SUB_DEFAULT', 'wp_' );
}

if ( ! defined( 'LEARNDASH_UPDATES_ENABLED' ) ) {
	/**
	 * Define LearnDash LMS - Enable support to check for updates for Core and Add-ons.
	 *
	 * @since 3.1.8
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Will enable calls to support.learndash.com and bitbucket.org to check for updates. Default.
	 *    @type bool false Will disable outbound server calls.
	 * }
	 */
	define( 'LEARNDASH_UPDATES_ENABLED', true );
}

if ( ! defined( 'LEARNDASH_UPDATE_HTTP_METHOD' ) ) {
	/**
	 * Define LearnDash LMS - Configure the HTTP method use to connect to the support/license server.
	 *
	 * @since 3.6.0.3
	 *
	 * @var string $value {
	 *    Only one of the following values.
	 *    @type string 'post' Use HTTP POST (wp_remote_post) to connect to the server. Default.
	 *    @type string 'get'  Use HTTP GET (wp_remote_get) to connect to the server. Default.
	 * }
	 */
	define( 'LEARNDASH_UPDATE_HTTP_METHOD', 'get' );
}

if ( ! defined( 'LEARNDASH_PLUGIN_LICENSE_INTERVAL' ) ) {
	/**
	 * Define LearnDash LMS - Configure the interval for support license check.
	 *
	 * @since 3.6.0.3
	 *
	 * @var int $value number of minutes between license checks. Default is 3600 minutes (60 minutes).
	 */
	define( 'LEARNDASH_PLUGIN_LICENSE_INTERVAL', 3600 );
}

if ( ! defined( 'LEARNDASH_PLUGIN_LICENSE_OPTIONS_AUTOLOAD' ) ) {
	/**
	 * Define LearnDash LMS - Configure the autoload options for licensing.
	 *
	 * @since 4.3.0
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Will enable autoload options.
	 *    @type bool false Will disable autoload options. Default.
	 * }
	 */
	define( 'LEARNDASH_PLUGIN_LICENSE_OPTIONS_AUTOLOAD', false );
}


if ( ! defined( 'LEARNDASH_PLUGIN_INFO_INTERVAL' ) ) {
	/**
	 * Define LearnDash LMS - Configure the interval for support information check.
	 *
	 * @since 3.6.0.3
	 *
	 * @var int $value number of minutes between information checks. Default is 600 minutes (10 minutes).
	 */
	define( 'LEARNDASH_PLUGIN_INFO_INTERVAL', 600 );
}

if ( ! defined( 'LEARNDASH_ADDONS_UPDATER' ) ) {
	$learndash_define_addons_updater_value = true;
	if ( defined( 'LEARNDASH_UPDATES_ENABLED' ) ) {
		$learndash_define_addons_updater_value = (bool) LEARNDASH_UPDATES_ENABLED;
	}

	/**
	 * Define LearnDash LMS - Enable support for Add-ons.
	 *
	 * @since 2.5.5
	 *
	 * @var bool $value {
	 *    Only one of the following values.
	 *    @type bool true  Will enable new menu items and install/update of related Add-ons. Default.
	 *    @type bool false
	 * }
	 */
	define( 'LEARNDASH_ADDONS_UPDATER', $learndash_define_addons_updater_value );
}
