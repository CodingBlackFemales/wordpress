<?php
/**
 * AES-256-GCM encryption helpers for storing OAuth tokens and client secrets.
 *
 * The encryption key MUST be set in the environment as CBF_SI_ENCRYPTION_KEY
 * (32+ byte random string, never stored in the DB). Tokens and secrets are
 * stored as base64-encoded ciphertext in wp_usermeta / wp_options only.
 *
 * @class   Crypto
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Crypto class.
 *
 * All methods are static — this is a pure utility class with no instance state.
 *
 * Security notes:
 * - A fresh 12-byte nonce is generated per encryption call (prepended to ciphertext).
 * - GCM authentication tag (16 bytes) is appended automatically by openssl_encrypt.
 * - The stored value is: base64( nonce || ciphertext || tag ).
 * - If CBF_SI_ENCRYPTION_KEY is missing, encrypt() returns WP_Error and nothing
 *   is written. decrypt() returns WP_Error and the caller must surface a
 *   "re-authenticate" prompt to the user.
 */
final class Crypto {

	private const CIPHER   = 'aes-256-gcm';
	private const NONCE_LEN = 12;
	private const TAG_LEN   = 16;
	private const ENV_KEY   = 'CBF_SI_ENCRYPTION_KEY';

	/**
	 * Encrypt a plaintext string.
	 *
	 * @param  string $plaintext The value to encrypt.
	 * @return string|\WP_Error  Base64-encoded ciphertext, or WP_Error on failure.
	 */
	public static function encrypt( string $plaintext ): string|\WP_Error {
		$key = self::get_key();
		if ( is_wp_error( $key ) ) {
			return $key;
		}

		$nonce = random_bytes( self::NONCE_LEN );
		$tag   = '';

		$ciphertext = openssl_encrypt( $plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $nonce, $tag, '', self::TAG_LEN );

		if ( $ciphertext === false ) {
			return new \WP_Error( 'cbf_si_encrypt_failed', 'Encryption failed.' );
		}

		return base64_encode( $nonce . $ciphertext . $tag ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}


	/**
	 * Decrypt a value produced by self::encrypt().
	 *
	 * @param  string $encoded  The base64-encoded value from storage.
	 * @return string|\WP_Error The original plaintext, or WP_Error on failure.
	 */
	public static function decrypt( string $encoded ): string|\WP_Error {
		$key = self::get_key();
		if ( is_wp_error( $key ) ) {
			return $key;
		}

		$raw = base64_decode( $encoded, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( $raw === false ) {
			return new \WP_Error( 'cbf_si_decrypt_invalid', 'Stored value is not valid base64.' );
		}

		$min_len = self::NONCE_LEN + self::TAG_LEN + 1;
		if ( strlen( $raw ) < $min_len ) {
			return new \WP_Error( 'cbf_si_decrypt_short', 'Stored value is too short to be valid ciphertext.' );
		}

		$nonce      = substr( $raw, 0, self::NONCE_LEN );
		$tag        = substr( $raw, -self::TAG_LEN );
		$ciphertext = substr( $raw, self::NONCE_LEN, -self::TAG_LEN );

		$plaintext = openssl_decrypt( $ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $nonce, $tag );

		if ( $plaintext === false ) {
			return new \WP_Error( 'cbf_si_decrypt_failed', 'Decryption failed — key mismatch or tampered data.' );
		}

		return $plaintext;
	}


	/**
	 * Retrieve and validate the encryption key from the environment.
	 *
	 * @return string|\WP_Error 32-byte key, or WP_Error if not set / too short.
	 */
	private static function get_key(): string|\WP_Error {
		$raw = defined( self::ENV_KEY ) ? constant( self::ENV_KEY ) : getenv( self::ENV_KEY );

		if ( empty( $raw ) ) {
			return new \WP_Error(
				'cbf_si_no_key',
				sprintf( 'Environment variable %s is not set.', self::ENV_KEY )
			);
		}

		// Derive a fixed-length 32-byte key with SHA-256 so any passphrase works.
		return hash( 'sha256', $raw, true );
	}
}
