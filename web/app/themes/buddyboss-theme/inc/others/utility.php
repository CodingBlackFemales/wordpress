<?php



if ( ! function_exists( 'buddyboss_unique_id' ) ) {
	/**
	 * Get unique ID.
	 *
	 * This is a PHP implementation of Underscore's uniqueId method. A static variable
	 * contains an integer that is incremented with each call. This number is returned
	 * with the optional prefix. As such the returned value is not universally unique,
	 * but it is unique across the life of the PHP process.
	 *
	 * @param string $prefix Prefix for the returned ID.
	 *
	 * @return string Unique ID.
	 *
	 * @staticvar int $id_counter
	 */
	function buddyboss_unique_id( $prefix = '' ) {
		static $id_counter = 0;

		return $prefix . (string) ++ $id_counter;
	}
}
