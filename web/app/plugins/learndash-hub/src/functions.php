<?php

declare( strict_types=1 );

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
