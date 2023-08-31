<?php
/**
 * Your base production configuration goes in this file. Environment-specific
 * overrides go in their respective config/environments/{{WP_ENV}}.php file.
 *
 * A good default policy is to deviate from the production config as little as
 * possible. Try to define as much of your configuration in this file as you
 * can.
 *
 * phpcs:disable PHPCompatibility.Operators.NewOperators.t_coalesceFound, Universal.Operators.DisallowShortTernary.Found
 */

use Roots\WPConfig\Config;
use function Env\env;

/**
 * Directory containing all of the site's files
 *
 * @var string
 */
$root_dir = dirname( __DIR__ );

/**
 * Document Root
 *
 * @var string
 */
$webroot_dir = $root_dir . '/web';

/**
 * Use Dotenv to set required environment variables and load .env file in root
 * .env.local will override .env if it exists
 */
if ( file_exists( $root_dir . '/.env' ) ) {
	$env_files = file_exists( $root_dir . '/.env.local' )
		? array( '.env', '.env.local' )
		: array( '.env' );

	$dotenv = Dotenv\Dotenv::createUnsafeImmutable( $root_dir, $env_files, false );

	$dotenv->load();

	$dotenv->required( array( 'WP_HOME', 'WP_SITEURL' ) );
	if ( ! env( 'DATABASE_URL' ) ) {
		$dotenv->required( array( 'DB_NAME', 'DB_USER', 'DB_PASSWORD' ) );
	}
}

/**
 * Set up our global environment constant and load its config first
 * Default: production
 */
define( 'WP_ENV', env( 'WP_ENV' ) ?: 'production' );

/**
 * Infer WP_ENVIRONMENT_TYPE based on WP_ENV
 */
if ( ! env( 'WP_ENVIRONMENT_TYPE' ) && in_array( WP_ENV, array( 'production', 'staging', 'development' ) ) ) {
	Config::define( 'WP_ENVIRONMENT_TYPE', WP_ENV );
}

/**
 * URLs
 */
Config::define( 'WP_HOME', env( 'WP_HOME' ) );
Config::define( 'WP_SITEURL', env( 'WP_SITEURL' ) );

/**
 * Custom Content Directory
 */
Config::define( 'CONTENT_DIR', '/app' );
Config::define( 'WP_CONTENT_DIR', $webroot_dir . Config::get( 'CONTENT_DIR' ) );
Config::define( 'WP_CONTENT_URL', Config::get( 'WP_HOME' ) . Config::get( 'CONTENT_DIR' ) );

/**
 * DB settings
 */
if ( env( 'DB_SSL' ) ) {
	Config::define( 'MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL );
}

Config::define( 'DB_NAME', env( 'DB_NAME' ) );
Config::define( 'DB_USER', env( 'DB_USER' ) );
Config::define( 'DB_PASSWORD', env( 'DB_PASSWORD' ) );
Config::define( 'DB_HOST', env( 'DB_HOST' ) ?: 'localhost' );
Config::define( 'DB_CHARSET', 'utf8mb4' );
Config::define( 'DB_COLLATE', '' );
$table_prefix = env( 'DB_PREFIX' ) ?: 'wp_';

if ( env( 'DATABASE_URL' ) ) {
	$dsn = (object) parse_url( env( 'DATABASE_URL' ) );

	Config::define( 'DB_NAME', substr( $dsn->path, 1 ) );
	Config::define( 'DB_USER', $dsn->user );
	Config::define( 'DB_PASSWORD', isset( $dsn->pass ) ? $dsn->pass : null );
	Config::define( 'DB_HOST', isset( $dsn->port ) ? "{$dsn->host}:{$dsn->port}" : $dsn->host );
}

/**
 * Authentication Unique Keys and Salts
 */
Config::define( 'AUTH_KEY', env( 'AUTH_KEY' ) );
Config::define( 'SECURE_AUTH_KEY', env( 'SECURE_AUTH_KEY' ) );
Config::define( 'LOGGED_IN_KEY', env( 'LOGGED_IN_KEY' ) );
Config::define( 'NONCE_KEY', env( 'NONCE_KEY' ) );
Config::define( 'AUTH_SALT', env( 'AUTH_SALT' ) );
Config::define( 'SECURE_AUTH_SALT', env( 'SECURE_AUTH_SALT' ) );
Config::define( 'LOGGED_IN_SALT', env( 'LOGGED_IN_SALT' ) );
Config::define( 'NONCE_SALT', env( 'NONCE_SALT' ) );

/**
 * Custom Settings
 */
Config::define( 'AUTOMATIC_UPDATER_DISABLED', true );
Config::define( 'DISABLE_WP_CRON', env( 'DISABLE_WP_CRON' ) ?: false );

// Disable the plugin and theme file editor in the admin
Config::define( 'DISALLOW_FILE_EDIT', true );

// Disable plugin and theme updates and installation from the admin
Config::define( 'DISALLOW_FILE_MODS', true );

// Limit the number of post revisions
Config::define( 'WP_POST_REVISIONS', env( 'WP_POST_REVISIONS' ) ?? true );

// Set PHP memory limit
Config::define( 'WP_MEMORY_LIMIT', env( 'WP_MEMORY_LIMIT' ) ?? '256M' );

/**
 * Airtable Settings
 */
Config::define( 'AIRTABLE_API_KEY', env( 'AIRTABLE_API_KEY' ) ?? '' );
Config::define( 'AIRTABLE_BATCH_SIZE', env( 'AIRTABLE_BATCH_SIZE' ) ?? 10 );
Config::define( 'AIRTABLE_REPORTS_BASE', env( 'AIRTABLE_REPORTS_BASE' ) ?? '' );
Config::define( 'AIRTABLE_REPORTS_TABLE', env( 'AIRTABLE_REPORTS_TABLE' ) ?? 'Skills Check Log' );

/**
 * CBF Settings
 */
Config::define( 'ENABLE_CBF_SCHEDULED_EXPORT', env( 'ENABLE_CBF_SCHEDULED_EXPORT' ) ?? WP_ENV === 'production' );
Config::define( 'CBF_AUTH_USER_ID', env( 'CBF_AUTH_USER_ID' ) ?? 1 );

/**
 * Debugging Settings
 */
Config::define( 'WP_DEBUG_DISPLAY', false );
Config::define( 'WP_DEBUG_LOG', false );
Config::define( 'SCRIPT_DEBUG', false );
ini_set( 'display_errors', '0' );
if ( env( 'EXCLUDED_ERROR_LEVELS' ) ) {
	Config::define( 'EXCLUDED_ERROR_LEVELS', explode( ',', env( 'EXCLUDED_ERROR_LEVELS' ) ) );
}

/**
 * Allow WordPress to detect HTTPS when used behind a reverse proxy or a load balancer
 * See https://codex.wordpress.org/Function_Reference/is_ssl#Notes
 */
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
	$_SERVER['HTTPS'] = 'on';
}

/**
 * Multisite Settings
 */
Config::define( 'WP_ALLOW_MULTISITE', true );
Config::define( 'MULTISITE', true );
Config::define( 'SUBDOMAIN_INSTALL', true );
Config::define( 'DOMAIN_CURRENT_SITE', $_SERVER['HTTP_HOST'] ?? env( 'DOMAIN_CURRENT_SITE' ) );
Config::define( 'PATH_CURRENT_SITE', env( 'PATH_CURRENT_SITE' ) ?? '/' );
Config::define( 'SITE_ID_CURRENT_SITE', env( 'SITE_ID_CURRENT_SITE' ) ?? 1 );
Config::define( 'BLOG_ID_CURRENT_SITE', env( 'BLOG_ID_CURRENT_SITE' ) ?? 1 );
Config::define( 'MAIN_SITE_ID', 1 );
Config::define( 'ACADEMY_SITE_ID', 2 );
Config::define( 'JOBS_SITE_ID', 3 );
Config::define( 'WP_DEFAULT_THEME', 'twentytwentythree' );
Config::define( 'COOKIE_DOMAIN', false );
Config::define( 'COOKIEPATH', '/' );
Config::define( 'COOKIEHASH', md5( env( 'DOMAIN_CURRENT_SITE' ) ) ); // notice absence of a '.' in front
if ( env( 'HEADLESS_MODE_CLIENT_URL' ) ) {
	Config::define( 'HEADLESS_MODE_CLIENT_URL', env( 'HEADLESS_MODE_CLIENT_URL' ) );
}
$base = '/';

$env_config = __DIR__ . '/environments/' . WP_ENV . '.php';

if ( file_exists( $env_config ) ) {
	require_once $env_config;
}

Config::apply();

/**
 * Bootstrap WordPress
 */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $webroot_dir . '/wp/' );
}
