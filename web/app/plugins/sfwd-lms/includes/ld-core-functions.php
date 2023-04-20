<?php
/**
 * Core utility functions
 *
 * @since 4.4.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if LearnDash cloud is enabled.
 *
 * @since 4.4.0
 *
 * @return bool
 */
function learndash_cloud_is_enabled(): bool {
	return defined( 'StellarWP\LearnDashCloud\PLUGIN_VERSION' );
}
