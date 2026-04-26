<?php
/**
 * URL Safe Base64 encoding.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Utilities;

use InvalidArgumentException;

/**
 * Encode and decode values in Base64 that is URI safe.
 *
 * @since 5.0.0
 */
class Base64_Url {
	/**
	 * Encode a string to a URL safe base64 string.
	 *
	 * @since 5.0.0
	 *
	 * @param string $data The data to encode.
	 * @param bool   $pad If true, keep the "=" padding at end of the encoded string.
	 *
	 * @return string The encoded data.
	 */
	public function encode( string $data, bool $pad = false ): string {
		$encoded = strtr( base64_encode( $data ), '+/', '-_' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- This is a valid use of base64 encoding.

		return true === $pad ? $encoded : rtrim( $encoded, '=' );
	}

	/**
	 * Decode a base64 string into its original value.
	 *
	 * @since 5.0.0
	 *
	 * @param string $encoded The data to decode.
	 *
	 * @throws InvalidArgumentException If we fail to decode the data.
	 *
	 * @return string The decoded data.
	 */
	public function decode( string $encoded ): string {
		$decoded = base64_decode( strtr( $encoded, '-_', '+/' ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- This is a valid use of base64 decoding.

		if ( false === $decoded ) {
			throw new InvalidArgumentException( 'Invalid data provided' );
		}

		return $decoded;
	}
}
