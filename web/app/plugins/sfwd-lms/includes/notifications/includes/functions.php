<?php
/**
 * General functions.
 *
 * @package LearnDash\Notifications
 */

use LearnDash\Notifications\Logger;

/**
 * Don't include directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Log a variable in log file.
 *
 * @param mixed  $variable Any variable that needs to be debugged.
 * @param string $desc     Description of the log.
 * @return void
 */
function learndash_notifications_debug( $variable, $desc = '' ) {
	if ( is_bool( $variable ) ) {
		$variable = $variable ? 'true' : 'false';
	}

	$variable = print_r( $variable, true );

	Learndash_Logger::get_instance( 'notifications' )->info( "{$desc}: {$variable}" ); // @phpstan-ignore-line -- Can rely on this unless something goes horribly wrong.
}

/**
 * Log a message in log file.
 *
 * @param string $message Log message.
 *
 * @return void
 */
function learndash_notifications_log_action( $message ) {
	Learndash_Logger::get_instance( 'notifications' )->info( $message ); // @phpstan-ignore-line -- Can rely on this unless something goes horribly wrong.
}

/**
 * Parses a variable for logging.
 *
 * @param mixed $var Variable to parse.
 *
 * @return mixed
 */
function learndash_notifications_parse_variable( $var ) {
	if ( is_array( $var ) || is_object( $var ) ) {
		return json_encode( $var );
	} else {
		return $var;
	}
}
