<?php
/**
 * LearnDash File class.
 *
 * @since 4.18.1.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Utilities;

/**
 * A helper class to provide easier ways to interact with files and file paths.
 *
 * @since 4.18.1.1
 */
class File {
	/**
	 * Gets the shared base path between two given file paths.
	 *
	 * @since 4.18.1.1
	 *
	 * @param string $path_a File path A.
	 * @param string $path_b File path B.
	 *
	 * @return string
	 */
	public static function get_shared_path( string $path_a, string $path_b ): string {
		$path_a = rtrim( wp_normalize_path( $path_a ), '/' );
		$path_b = rtrim( wp_normalize_path( $path_b ), '/' );

		if ( $path_a === $path_b ) {
			return trailingslashit( $path_a );
		}

		// It is expected that there may be an empty string at the start of the exploded array depending on input.
		$parts_a = explode( '/', $path_a );
		$parts_b = explode( '/', $path_b );

		$shared = [];
		foreach ( $parts_a as $i => $part ) {
			if (
				isset( $parts_b[ $i ] )
				&& $parts_a[ $i ] === $parts_b[ $i ]
			) {
				$shared[] = $part;
			} else {
				break;
			}
		}

		if ( empty( array_filter( $shared ) ) ) {
			return '';
		}

		return trailingslashit( implode( '/', $shared ) );
	}

	/**
	 * Gets the relative path from one file path to another.
	 *
	 * @since 4.18.1.1
	 *
	 * @param string $from File path to start from.
	 * @param string $to   File path to end at.
	 *
	 * @return string
	 */
	public static function get_relative_path( string $from, string $to ): string {
		// Sanitize out any trailing slashes from the paths. These will be added back as appropriate.
		$from = rtrim( wp_normalize_path( $from ), '/' );
		$to   = rtrim( wp_normalize_path( $to ), '/' );

		// Sanitize out any leading './' from the paths.
		$from = Cast::to_string( preg_replace( '/^(?:\.\/)?/', '', $from ) );
		$to   = Cast::to_string( preg_replace( '/^(?:\.\/)?/', '', $to ) );

		$shared_path = rtrim( self::get_shared_path( $from, $to ), '/' );

		/**
		 * If there's no shared path, assume the "To" path is already relative to the "From" path.
		 *
		 * Additionally, this could also occur if the "To" path is an absolute path
		 * with no shared path with the "From" path.
		 */
		if ( empty( $shared_path ) ) {
			// If the paths are the same, return an empty string.
			if ( $from === $to ) {
				return '';
			}

			// Account for dot files and files with extensions.
			if ( is_file( $to ) ) {
				return $to;
			}

			return trailingslashit( $to );
		}

		$from_without_shared = trim( str_replace( $shared_path, '', $from ), '/' );
		$to_without_shared   = trim( str_replace( $shared_path, '', $to ), '/' );

		$result = str_repeat(
			'../',
			substr_count( $from_without_shared, '/' )
		) . $to_without_shared;

		// Ensure we don't return '/'.
		if ( empty( $result ) ) {
			return '';
		}

		if ( is_file( $result ) ) {
			return $result;
		}

		return trailingslashit( $result );
	}
}
