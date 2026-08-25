<?php
/**
 * Google OAuth2 client wrapper.
 *
 * Wraps google/apiclient's Google_Client with plugin-specific behaviour:
 * - Reads the encrypted client secret from wp_options.
 * - Stores and retrieves encrypted per-user tokens in wp_usermeta.
 * - Provides a get_access_token() helper that refreshes automatically.
 *
 * @class   Google\OAuthClient
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Google;

use CodingBlackFemales\SlidesImporter\Crypto;
use CodingBlackFemales\SlidesImporter\Install;
use CodingBlackFemales\SlidesImporter\Utils;
use Google\Client as GoogleClient;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OAuthClient class.
 */
final class OAuthClient {

	/**
	 * Build and configure a Google_Client instance for the current request.
	 *
	 * Returns WP_Error if the client secret is not stored or cannot be decrypted.
	 *
	 * @return GoogleClient|WP_Error
	 */
	public static function make(): GoogleClient|WP_Error {
		$enc_secret = get_option( Install::CLIENT_SECRET_OPTION, '' );
		if ( empty( $enc_secret ) ) {
			return new WP_Error(
				'cbf_si_no_client_secret',
				__( 'Google OAuth client secret is not configured. An administrator must add it in the plugin settings.', 'cbf-slides-importer' )
			);
		}

		$secret_json = Crypto::decrypt( (string) $enc_secret );
		if ( is_wp_error( $secret_json ) ) {
			return $secret_json;
		}

		$client = new GoogleClient();
		$client->setAuthConfig( json_decode( $secret_json, true ) );
		$client->setScopes( array( \Google\Service\Drive::DRIVE_READONLY ) );
		$client->setAccessType( 'offline' );
		$client->setPrompt( 'consent' );
		$client->setRedirectUri( rest_url( 'cbf-si/v1/auth/callback' ) );

		return $client;
	}


	/**
	 * Generate the Google OAuth consent URL and store a state nonce.
	 *
	 * The state is stored as a short-lived transient tied to the current user
	 * to prevent CSRF on the callback.
	 *
	 * @param  int  $user_id WP user ID.
	 * @return string|WP_Error Consent URL, or WP_Error on failure.
	 */
	public static function create_auth_url( int $user_id ): string|WP_Error {
		$client = self::make();
		if ( is_wp_error( $client ) ) {
			return $client;
		}

		$nonce = wp_generate_password( 32, false );
		set_transient( 'cbf_si_oauth_state_' . $user_id, $nonce, 10 * MINUTE_IN_SECONDS );

		// Encode user_id into the state so the callback can identify the user
		// without relying on the WP session cookie (which is unavailable in the
		// REST API context without a matching X-WP-Nonce header).
		// Format: "{user_id}:{nonce}" — the nonce is the CSRF token.
		$client->setState( $user_id . ':' . $nonce );

		return $client->createAuthUrl();
	}


	/**
	 * Exchange an authorisation code for tokens and store them for the user.
	 *
	 * @param  string $code    OAuth authorisation code from Google.
	 * @param  int    $user_id WP user ID.
	 * @return true|WP_Error
	 */
	public static function exchange_code( string $code, int $user_id ): true|WP_Error {
		$client = self::make();
		if ( is_wp_error( $client ) ) {
			return $client;
		}

		$token = $client->fetchAccessTokenWithAuthCode( $code );

		if ( isset( $token['error'] ) ) {
			return new WP_Error( 'cbf_si_oauth_error', (string) $token['error_description'] ?? $token['error'] );
		}

		return self::store_token( $user_id, $token );
	}


	/**
	 * Get a valid (possibly refreshed) access token string for the user.
	 *
	 * Automatically refreshes if the stored token is expired.
	 *
	 * @param  int $user_id WP user ID.
	 * @return string|WP_Error Access token string, or WP_Error.
	 */
	public static function get_access_token( int $user_id ): string|WP_Error {
		$token = self::retrieve_token( $user_id );
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$client = self::make();
		if ( is_wp_error( $client ) ) {
			return $client;
		}

		$client->setAccessToken( $token );

		if ( $client->isAccessTokenExpired() ) {
			if ( empty( $token['refresh_token'] ) ) {
				return new WP_Error(
					'cbf_si_token_expired',
					__( 'Google access token expired and no refresh token available. Please re-authorise.', 'cbf-slides-importer' )
				);
			}

			$new_token = $client->fetchAccessTokenWithRefreshToken( $token['refresh_token'] );
			if ( isset( $new_token['error'] ) ) {
				return new WP_Error( 'cbf_si_refresh_error', (string) ( $new_token['error_description'] ?? $new_token['error'] ) );
			}

			// Merge refresh_token (Google only returns it on first auth).
			if ( empty( $new_token['refresh_token'] ) ) {
				$new_token['refresh_token'] = $token['refresh_token'];
			}

			$store_result = self::store_token( $user_id, $new_token );
			if ( is_wp_error( $store_result ) ) {
				return $store_result;
			}

			$token = $new_token;
		}

		return $token['access_token'];
	}


	/**
	 * Get a fully-configured Google_Client with the user's stored token set.
	 *
	 * Refreshes the token if necessary.
	 *
	 * @param  int $user_id WP user ID.
	 * @return GoogleClient|WP_Error
	 */
	public static function get_client_for_user( int $user_id ): GoogleClient|WP_Error {
		$token = self::retrieve_token( $user_id );
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$client = self::make();
		if ( is_wp_error( $client ) ) {
			return $client;
		}

		$client->setAccessToken( $token );

		if ( $client->isAccessTokenExpired() ) {
			// Trigger refresh via get_access_token which also re-stores.
			$access_token = self::get_access_token( $user_id );
			if ( is_wp_error( $access_token ) ) {
				return $access_token;
			}
		}

		return $client;
	}


	/**
	 * Delete the user's stored OAuth token (revoke local access).
	 *
	 * Does not call Google's revoke endpoint — just removes the stored data.
	 *
	 * @param int $user_id WP user ID.
	 */
	public static function revoke_token( int $user_id ): void {
		delete_user_meta( $user_id, Install::USER_TOKEN_META );
		Utils::log( 'OAuth token revoked.', array( 'user_id' => $user_id ) );
	}


	/**
	 * Return true if a token exists for the user.
	 *
	 * @param int $user_id WP user ID.
	 */
	public static function has_token( int $user_id ): bool {
		return ! empty( get_user_meta( $user_id, Install::USER_TOKEN_META, true ) );
	}


	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Encrypt and store a token array in wp_usermeta.
	 *
	 * @param int   $user_id WP user ID.
	 * @param array $token   Google token array.
	 * @return true|WP_Error
	 */
	private static function store_token( int $user_id, array $token ): true|WP_Error {
		$json      = wp_json_encode( $token );
		$encrypted = Crypto::encrypt( $json );
		if ( is_wp_error( $encrypted ) ) {
			return $encrypted;
		}
		update_user_meta( $user_id, Install::USER_TOKEN_META, $encrypted );
		return true;
	}


	/**
	 * Retrieve and decrypt the user's stored token array.
	 *
	 * @param  int $user_id WP user ID.
	 * @return array|WP_Error
	 */
	private static function retrieve_token( int $user_id ): array|WP_Error {
		$enc = get_user_meta( $user_id, Install::USER_TOKEN_META, true );
		if ( empty( $enc ) ) {
			return new WP_Error(
				'cbf_si_no_token',
				__( 'No Google token found. Please authorise Drive access.', 'cbf-slides-importer' )
			);
		}

		$json = Crypto::decrypt( (string) $enc );
		if ( is_wp_error( $json ) ) {
			return $json;
		}

		$token = json_decode( $json, true );
		if ( ! is_array( $token ) ) {
			return new WP_Error( 'cbf_si_token_invalid', 'Stored token is not valid JSON.' );
		}

		return $token;
	}
}
