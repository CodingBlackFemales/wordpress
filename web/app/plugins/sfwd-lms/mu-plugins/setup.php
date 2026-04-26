<?php
/**
 * Must use plugins.
 *
 * @package LearnDash
 */

/**
 * Copy our Multisite support file(s) to the /wp-content/mu-plugins directory.
 */
if ( is_multisite() ) {
	$wpmu_plugin_dir = ( defined( 'WPMU_PLUGIN_DIR' ) && defined( 'WPMU_PLUGIN_URL' ) ) ? WPMU_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
	if ( is_writable( $wpmu_plugin_dir ) ) {
		$dest_file = trailingslashit( $wpmu_plugin_dir ) . 'learndash-multisite.php'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		if ( ! file_exists( $dest_file ) ) {
			$source_file = trailingslashit( LEARNDASH_LMS_PLUGIN_DIR ) . 'mu-plugins/learndash-multisite.php'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
			if ( file_exists( $source_file ) ) {
				copy( $source_file, $dest_file );
			}
		}
	}
}
