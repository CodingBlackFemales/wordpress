<?php
/**
 * Class BB_SSO_PKCE.
 *
 * This class provides a simple interface for generating PKCE code verifiers and code challenges.
 * PKCE (Proof Key for Code Exchange) is a security feature to prevent interception attacks
 * during OAuth2 authorization flows.
 *
 * @link    https://auth0.com/docs/libraries/auth0-php          Project URL
 * @link    https://github.com/auth0/auth0-PHP                  GitHub Repo
 * @author  auth0 <https://auth0.com>
 * @license http://opensource.org/licenses/mit-license.php     MIT License
 *
 * Code adjustments for BuddyBoss Single Sign-On:
 * - Fix: throw simple exception.
 * - Fix: code structure.
 *
 * @since   2.6.30
 * @package BuddyBossPro/SSO/PKCE
 */

namespace BBSSO\PKCE;

use Exception;

/**
 * Class BB_SSO_PKCE.
 *
 * @since 2.6.30
 */
final class BB_SSO_PKCE {

	/**
	 * Generates a code challenge from the provided code verifier.
	 * The code challenge is a Base64 encoded string with URL and filename-safe characters.
	 * Trailing '=' characters are removed, and no line breaks, whitespace, or other additional characters are included.
	 *
	 * @since 2.6.30
	 *
	 * @param string $code_verifier The string used to generate the code challenge.
	 *
	 * @return string The generated code challenge.
	 *
	 * @see   https://auth0.com/docs/flows/concepts/auth-code-pkce
	 */
	public static function generate_code_challenge( $code_verifier ): string {
		// phpcs:ignore
		$encoded = base64_encode( hash( 'sha256', $code_verifier, true ) );

		return strtr( rtrim( $encoded, '=' ), '+/', '-_' );
	}

	/**
	 * Generates a random string between 43 and 128 characters, containing letters, numbers, and "-", ".", "_", "~",
	 * as defined in the RFC 7636 specification.
	 *
	 * @since 2.6.30
	 *
	 * @param int $length The length of the code verifier. Must be between 43 and 128 characters.
	 *
	 * @throws Exception If the length is less than 43 or more than 128 characters.
	 *
	 * @return string The generated code verifier.
	 * @see   https://tools.ietf.org/html/rfc7636
	 */
	public static function generate_code_verifier( $length = 43 ): string {
		if ( $length < 43 || $length > 128 ) {
			throw new Exception( __( 'Code verifier must be created with a minimum length of 43 characters and a maximum length of 128 characters!', 'buddyboss-pro' ) );
		}

		$string = '';

		// phpcs:ignore
		while ( ( $len = mb_strlen( $string ) ) < $length ) {
			$size = $length - $len;
			$size = $size >= 1 ? $size : 1;

			try {
				$bytes = random_bytes( $size );
			} catch ( Exception $e ) {
				$bytes = openssl_random_pseudo_bytes( $size );
			}

			$string .= mb_substr(
				str_replace(
					array( '/', '+', '=' ),
					'',
					base64_encode( $bytes ) // phpcs:ignore
				),
				0,
				$size
			);
		}

		return $string;
	}
}
