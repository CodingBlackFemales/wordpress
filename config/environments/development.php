<?php
/**
 * Configuration overrides for WP_ENV === 'development'
 */

use Roots\WPConfig\Config;
use function Env\env;

Config::define( 'SAVEQUERIES', true );
Config::define( 'WP_DEBUG', ! ( defined( 'WP_CLI' ) && WP_CLI ) );
Config::define( 'WP_DEBUG_DISPLAY', env( 'WP_DEBUG_DISPLAY' ) ?? true );
Config::define( 'WP_DEBUG_LOG', env( 'WP_DEBUG_LOG' ) ?? true );
Config::define( 'WP_DISABLE_FATAL_ERROR_HANDLER', true );
Config::define( 'SCRIPT_DEBUG', true );
Config::define( 'DISALLOW_INDEXING', true );

ini_set( 'display_errors', '1' );

// Enable plugin and theme updates and installation from the admin
Config::define( 'DISALLOW_FILE_MODS', false );

/**
 * Defines custom DB_HOST value when run outside container
 */
if ( defined( 'WP_CLI' ) && WP_CLI && ! env( 'LANDO' ) ) {
	Config::define( 'DB_HOST', env( 'DB_HOST_EXTERNAL' ) ?? Config::get( 'DB_HOST' ) );
}
