<?php
/**
 * Utility methods.
 *
 * @class   Utils
 * @version 1.0.0
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Utils class.
 */
final class Utils {

	/**
	 * What type of request is this?
	 *
	 * @param string $type admin|ajax|cron|frontend|cli.
	 */
	public static function is_request( string $type ): bool {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' ) && DOING_AJAX;
			case 'cron':
				return defined( 'DOING_CRON' ) && DOING_CRON;
			case 'frontend':
				return ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) )
					&& ( ! defined( 'DOING_CRON' ) || ! DOING_CRON );
			case 'cli':
				return defined( 'WP_CLI' ) && WP_CLI;
		}
		return false;
	}


	/**
	 * Get the plugin URL (no trailing slash).
	 */
	public static function plugin_url(): string {
		return untrailingslashit( plugins_url( '/', PLUGIN_FILE ) );
	}


	/**
	 * Get the plugin filesystem path (no trailing slash).
	 */
	public static function plugin_path(): string {
		return untrailingslashit( plugin_dir_path( PLUGIN_FILE ) );
	}
}
