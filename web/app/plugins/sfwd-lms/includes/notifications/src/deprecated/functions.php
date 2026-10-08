<?php
/**
 * Deprecated functions file.
 *
 * @package LearnDash\Notifications\Deprecated
 */

defined( 'ABSPATH' ) || exit;

/**
 * The main function for returning the plugin instance.
 *
 * @deprecated 5.2.0
 *
 * @since 5.2.0
 *
 * @return LearnDash_Notifications The one and only true instance.
 */
function learndash_notifications() {
	_deprecated_function( __FUNCTION__, '5.2.0' );

	return LearnDash_Notifications::instance();
}
