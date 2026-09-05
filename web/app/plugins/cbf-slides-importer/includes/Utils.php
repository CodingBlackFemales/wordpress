<?php
/**
 * Utility methods.
 *
 * @class   Utils
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter;

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
	 * @param string $type admin|ajax|cron|frontend|cli
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


	/**
	 * Get the Ajax URL.
	 */
	public static function ajax_url(): string {
		return admin_url( 'admin-ajax.php', 'relative' );
	}


	/**
	 * Return the absolute path to the plugin's temporary upload directory.
	 *
	 * The directory is created on first call. All source-file downloads and extracted
	 * images are written here and cleaned up after each import job completes.
	 *
	 * Path: {wp_upload_dir}/cbf-slides-tmp/
	 *
	 * @return string|WP_Error Absolute path or WP_Error if the directory cannot be created.
	 */
	public static function tmp_dir(): string|\WP_Error {
		$upload = wp_upload_dir();
		if ( $upload['error'] ) {
			return new \WP_Error( 'cbf_si_upload_dir', $upload['error'] );
		}
		$dir = trailingslashit( $upload['basedir'] ) . 'cbf-slides-tmp';
		if ( ! wp_mkdir_p( $dir ) ) {
			return new \WP_Error( 'cbf_si_mkdir', sprintf( 'Could not create directory: %s', esc_html( $dir ) ) );
		}
		// Prevent directory listing.
		$index = $dir . '/index.php';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, '<?php // Silence is golden.' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
		return $dir;
	}


	/**
	 * Recursively delete a directory and its contents.
	 *
	 * Used to clean up per-job temp directories after import completes.
	 *
	 * @param string $dir Absolute path to the directory.
	 */
	public static function rmdir_recursive( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$items = array_diff( (array) scandir( $dir ), array( '.', '..' ) );
		foreach ( $items as $item ) {
			$path = $dir . '/' . $item;
			is_dir( $path ) ? self::rmdir_recursive( $path ) : wp_delete_file( $path );
		}
		rmdir( $dir );
	}


	/**
	 * Prefix a log message with [CBF-SI] and a context suffix, then send to
	 * error_log(). Never logs token values or raw file paths.
	 *
	 * @param string $message  Human-readable description.
	 * @param array  $context  Optional key=>value pairs appended to the message.
	 *                         Keys `token`, `secret`, `key` are automatically redacted.
	 */
	public static function log( string $message, array $context = array() ): void {
		if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) && ! ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) ) {
			return;
		}
		$redacted_keys = array( 'token', 'secret', 'key', 'password', 'access_token', 'refresh_token' );
		foreach ( $redacted_keys as $k ) {
			if ( isset( $context[ $k ] ) ) {
				$context[ $k ] = '[REDACTED]';
			}
		}
		$ctx_str = empty( $context ) ? '' : ' ' . wp_json_encode( $context );
		error_log( '[CBF-SI] ' . $message . $ctx_str ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}
