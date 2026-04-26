<?php

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Get the Plugin URL.
 *
 * @param string $path The relative path.
 *
 * @return string
 */
function hub_asset_url( string $path ): string {
	$base_url = plugin_dir_url( __DIR__ );

	return untrailingslashit( $base_url ) . $path;
}

/**
 * Get the Plugin path.
 *
 * @param string $path The relative path.
 *
 * @return string
 */
function hub_path( string $path ): string {
	$base_path = plugin_dir_path( __DIR__ );

	return $base_path . $path;
}

/**
 * Check if the LearnDash version is compatible with the plugin requirements.
 *
 * @param array  $plugin_data The plugin data containing requirements.
 * @param string $learndash_version The current version of LearnDash.
 *
 * @return bool|\WP_Error True if compatible, WP_Error if not compatible.
 */
function is_learndash_version_compatible( array $plugin_data, $learndash_version = '' ) {
	$require_ld = $plugin_data['requires_ld'] ?? '';

	if ( empty( $require_ld ) || empty( $learndash_version ) ) {
		// if the requires ld is empty, or the user install this plugin without learndash, we just process as now.
		return true;
	}

	$parts = explode( '.', $require_ld );
	if ( count( $parts ) === 2 ) {
		// this means the plugins not PHP standard.
		$parts[]    = '0';
		$require_ld = implode( '.', $parts );
	}

	if ( version_compare( $learndash_version, $require_ld, '<' ) ) {
		$error = sprintf(
		/* translators: 1: Current WordPress version, 2: Version required by the uploaded plugin. */
			__( 'Your LearnDash version is %1$s, however the uploaded plugin requires %2$s.', 'learndash' ),
			$learndash_version,
			$require_ld
		);

		return new \WP_Error( 'incompatible_learndash_required_version', $error );
	}

	// otherwise, just process as usual
	return true;
}
