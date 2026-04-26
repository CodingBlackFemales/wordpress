<?php
/**
 * Core utility functions
 *
 * @since 4.4.0
 *
 * @package LearnDash
 */

use LearnDash\Core\App;
use LearnDash\Core\Settings\Initialization;

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

/**
 * Check if the current site has been initialized.
 *
 * @since 4.16.0
 *
 * @return bool
 */
function learndash_is_initialized(): bool {
	try {
		$initialization = App::get( Initialization::class );
	} catch ( \Exception $e ) {
		// If this is called too early, lets consider this initialized to prevent breaking changes.
		return true;
	}

	if ( ! $initialization instanceof Initialization ) {
		return true;
	}

	return $initialization->is_initialized();
}
