<?php

/**
 * Replaces faulty core wp_slash().
 *
 * Until WP 5.5 wp_slash() recursively added slashes not just to strings in array/objects, leading to errors.
 *
 * @param mixed $value What to add slashes to.
 *
 * @return mixed
 */
if ( ! function_exists( 'wdm_recursively_slash_strings' ) ) {
	function wdm_recursively_slash_strings( $value ) {
		return \map_deep( $value, 'wdm_addslashes_to_strings_only' );
	}
}

/**
 * Adds slashes only to strings.
 *
 * @param mixed $value Value to slash only if string.
 *
 * @return string|mixed
 */
if ( ! function_exists( 'wdm_addslashes_to_strings_only' ) ) {
	function wdm_addslashes_to_strings_only( $value ) {
		return \is_string( $value ) ? \addslashes( $value ) : $value;
	}
}
