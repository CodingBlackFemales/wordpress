<?php
/**
 * Configuration overrides for WP_ENV === 'staging'
 */

use Roots\WPConfig\Config;

/**
 * You should try to keep staging as close to production as possible. However,
 * should you need to, you can always override production configuration values
 * with `Config::define`.
 *
 * Example: `Config::define('WP_DEBUG', true);`
 * Example: `Config::define('DISALLOW_FILE_MODS', false);`
 */

Config::define( 'DISALLOW_INDEXING', true );
Config::define( 'SUBDOMAIN_INSTALL', false );
Config::define( 'WP_DEBUG', ( env( 'WP_DEBUG' ) ?? false ) && ! ( defined( 'WP_CLI' ) && WP_CLI ) );
Config::define( 'WP_DEBUG_DISPLAY', env( 'WP_DEBUG_DISPLAY' ) ?? false );
Config::define( 'WP_DEBUG_LOG', env( 'WP_DEBUG_LOG' ) ?? false );
Config::define( 'WP_DISABLE_FATAL_ERROR_HANDLER', false );
