<?php
/**
 * This class provides the easy way to operate arrays.
 *
 * @since 4.5.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Arr' ) ) {
	/**
	 * Array manipulation class.
	 *
	 * @since 4.5.0
	 */
	class Learndash_Arr {
		/**
		 * Returns an array with specified keys only.
		 *
		 * @since 4.5.0
		 *
		 * @param array<int|string,mixed> $array Array.
		 * @param array<int|string>       $keys  Keys.
		 *
		 * @return array<int|string,mixed>
		 */
		public static function only( array $array, array $keys ): array {
			return array_intersect_key( $array, array_flip( $keys ) );
		}

		/**
		 * Returns an array with specified keys removed.
		 *
		 * @since 4.5.0
		 *
		 * @param array<int|string,mixed>|ArrayAccess|ArrayObject $array Array.
		 * @param array<int|string>                               $keys  Keys.
		 *
		 * @return array<int|string,mixed>
		 */
		public static function except( $array, array $keys ): array {
			return static::forget( $array, $keys );
		}

		/**
		 * Deletes a value from array by the passed key(s).
		 *
		 * @since 4.5.0
		 *
		 * @param array<int|string,mixed>|ArrayAccess|ArrayObject $array Array.
		 * @param array<int|string>                               $keys  Keys.
		 *
		 * @return array<int|string,mixed>
		 */
		public static function forget( $array, array $keys ): array {
			if ( count( $keys ) === 0 ) {
				return (array) $array;
			}

			foreach ( $keys as $key ) {
				// If the exact key exists in the top-level, remove it.
				if ( static::exists( $array, $key ) ) {
					unset( $array[ $key ] );

					continue;
				}

				// Check if the key is using the dot-notation.
				if ( false === mb_strpos( (string) $key, '.' ) ) {
					continue;
				}

				// If we are dealing with dot-notation, recursively handle it.
				$parts = explode( '.', (string) $key );
				$key   = array_shift( $parts );

				if ( static::exists( $array, $key ) && static::accessible( $array[ $key ] ) ) {
					$array[ $key ] = static::forget(
						$array[ $key ],
						array( implode( '.', $parts ) )
					);

					if ( count( $array[ $key ] ) === 0 ) {
						unset( $array[ $key ] );
					}
				}
			}

			return (array) $array;
		}

		/**
		 * Returns a value from array by the passed key or the default value if not found.
		 *
		 * @since 4.5.0
		 *
		 * @param array<int|string,mixed>|ArrayAccess|ArrayObject $array   Array.
		 * @param string|int                                      $key     Key.
		 * @param mixed                                           $default Default value. Default null.
		 *
		 * @return mixed
		 */
		public static function get( $array, $key, $default = null ) {
			if ( ! static::accessible( $array ) ) {
				return $default;
			}

			if ( static::exists( $array, $key ) ) {
				return $array[ $key ];
			}

			$key = (string) $key;

			if ( strpos( $key, '.' ) === false ) {
				return $array[ $key ] ?? $default;
			}

			foreach ( explode( '.', $key ) as $segment ) {
				if ( static::accessible( $array ) && static::exists( $array, $segment ) ) {
					$array = $array[ $segment ];
				} else {
					return $default;
				}
			}

			return $array;
		}

		/**
		 * Returns true if the given value is array accessible, false otherwise.
		 *
		 * @since 4.5.0
		 *
		 * @param mixed $value Value.
		 *
		 * @return bool
		 */
		public static function accessible( $value ): bool {
			return is_array( $value ) || $value instanceof ArrayAccess;
		}

		/**
		 * Returns true if the given key exists in the array, false otherwise.
		 *
		 * @since 4.5.0
		 *
		 * @param array<int|string,mixed>|ArrayAccess|ArrayObject $array Array.
		 * @param int|string                                      $key   Key.
		 *
		 * @return bool
		 */
		public static function exists( $array, $key ): bool {
			if ( $array instanceof ArrayAccess ) {
				return $array->offsetExists( $key );
			}

			return array_key_exists( $key, $array );
		}
	}
}
