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
	 * Google's OAuth token endpoint.
	 *
	 * Used when the stored client secret does not name one of its own.
	 */
	const TOKEN_URL = 'https://oauth2.googleapis.com/token';

	/** Google's token revocation endpoint. */
	const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';

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
		// `select_account` is what makes it possible to connect a different
		// account: with `consent` alone Google re-uses whichever account the
		// browser is already signed into, so someone who authorised the wrong
		// one has no way to choose another. `consent` stays because it is what
		// guarantees a refresh token comes back.
		$client->setPrompt( 'select_account consent' );
		$client->setRedirectUri( self::redirect_uri() );

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
		$token = self::request_token(
			array(
				'grant_type'   => 'authorization_code',
				'code'         => $code,
				'redirect_uri' => self::redirect_uri(),
			)
		);

		if ( is_wp_error( $token ) ) {
			return $token;
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
			$token = self::refresh_token( $token, $user_id );

			if ( is_wp_error( $token ) ) {
				return $token;
			}
		}

		return $token['access_token'];
	}


	/**
	 * Exchange a refresh token for a fresh access token and store the result.
	 *
	 * @param  array $token   The expired token.
	 * @param  int   $user_id WP user ID to store the new token against.
	 * @return array|WP_Error The refreshed token.
	 */
	private static function refresh_token( array $token, int $user_id ): array|WP_Error {
		if ( empty( $token['refresh_token'] ) ) {
			return new WP_Error(
				'cbf_si_token_expired',
				__( 'Google access token expired and no refresh token available. Please re-authorise.', 'cbf-slides-importer' )
			);
		}

		$new_token = self::request_token(
			array(
				'grant_type'    => 'refresh_token',
				'refresh_token' => $token['refresh_token'],
			)
		);

		if ( is_wp_error( $new_token ) ) {
			// Google's own wording here is terse — "Bad Request" for a revoked
			// grant — so it is kept for the log but paired with the one thing
			// the person reading it can actually do.
			return new WP_Error(
				'cbf_si_refresh_error',
				sprintf(
					/* translators: %s: reason reported by Google */
					__( 'Your Google sign-in could not be renewed (%s). Reconnect your Google account and try again.', 'cbf-slides-importer' ),
					$new_token->get_error_message()
				)
			);
		}

		// Google only returns a refresh token on first authorisation, so carry
		// the existing one forward or the next refresh has nothing to use.
		if ( empty( $new_token['refresh_token'] ) ) {
			$new_token['refresh_token'] = $token['refresh_token'];
		}

		$stored = self::store_token( $user_id, $new_token );

		return is_wp_error( $stored ) ? $stored : $new_token;
	}


	/**
	 * Ask Google's token endpoint for a token.
	 *
	 * Deliberately not `Google\Client::fetchAccessTokenWith*()`. google/apiclient
	 * 2.x builds its transport on Guzzle 6 or 7, and Bedrock's root vendor ships
	 * Guzzle 8, which wins the autoloader race against the copy bundled here — so
	 * any call through the client raises "Could not find supported version of
	 * Guzzle" before reaching the network. The Drive paths already route around
	 * it; this one had no fallback, which meant a token could never be refreshed
	 * and a re-authorisation could never complete. Nothing noticed because both
	 * only happen once an access token passes an hour old.
	 *
	 * @param  array $grant The grant-specific parameters.
	 * @return array|WP_Error The token, or an editor-facing error.
	 */
	private static function request_token( array $grant ): array|WP_Error {
		$cred = self::credentials();

		if ( is_wp_error( $cred ) ) {
			return $cred;
		}

		$response = wp_remote_post(
			self::token_url( $cred ),
			array(
				'timeout' => 30,
				'body'    => array_merge(
					array(
						'client_id'     => $cred['client_id'],
						'client_secret' => $cred['client_secret'],
					),
					$grant
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'cbf_si_oauth_unreachable',
				__( 'Google could not be reached to complete sign-in. Try again in a moment.', 'cbf-slides-importer' )
			);
		}

		return self::decode_token( (string) wp_remote_retrieve_body( $response ) );
	}


	/**
	 * Read a token out of the endpoint's response.
	 *
	 * `created` is stamped here because the client works out expiry from it, and
	 * a token without one is treated as expired on every single check.
	 *
	 * @param  string $body Raw response body.
	 * @return array|WP_Error
	 */
	private static function decode_token( string $body ): array|WP_Error {
		$token = json_decode( $body, true );

		if ( ! is_array( $token ) ) {
			return new WP_Error(
				'cbf_si_oauth_error',
				__( 'Google returned a sign-in response this plugin could not read.', 'cbf-slides-importer' )
			);
		}

		if ( isset( $token['error'] ) ) {
			return new WP_Error( 'cbf_si_oauth_error', (string) ( $token['error_description'] ?? $token['error'] ) );
		}

		$token['created'] = time();

		return $token;
	}


	/**
	 * The client credentials from the stored secret.
	 *
	 * Google issues the file as either a "web" or an "installed" credential and
	 * both are accepted, matching what the settings screen validates.
	 *
	 * @return array|WP_Error
	 */
	private static function credentials(): array|WP_Error {
		$config = self::auth_config();

		if ( is_wp_error( $config ) ) {
			return $config;
		}

		$cred = (array) ( $config['web'] ?? $config['installed'] ?? array() );

		if ( empty( $cred['client_id'] ) || empty( $cred['client_secret'] ) ) {
			return new WP_Error(
				'cbf_si_bad_client_secret',
				__( 'The stored Google client secret is missing its client ID or secret. Re-save it in the plugin settings.', 'cbf-slides-importer' )
			);
		}

		return $cred;
	}


	/**
	 * Decrypt and decode the stored client secret.
	 *
	 * @return array|WP_Error
	 */
	private static function auth_config(): array|WP_Error {
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

		$decoded = json_decode( (string) $secret_json, true );

		return is_array( $decoded ) ? $decoded : new WP_Error(
			'cbf_si_bad_client_secret',
			__( 'The stored Google client secret could not be read. Re-save it in the plugin settings.', 'cbf-slides-importer' )
		);
	}


	/**
	 * The token endpoint to use, preferring the one the credentials name.
	 *
	 * @param  array $cred Client credentials.
	 * @return string
	 */
	private static function token_url( array $cred ): string {
		return empty( $cred['token_uri'] ) ? self::TOKEN_URL : (string) $cred['token_uri'];
	}


	/**
	 * Where Google sends the browser back to after consent.
	 *
	 * Google requires the value sent with the code exchange to match the one
	 * the consent URL carried exactly, so both read it from here.
	 *
	 * @return string
	 */
	private static function redirect_uri(): string {
		return rest_url( 'cbf-si/v1/auth/callback' );
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
		self::revoke_at_google( $user_id );

		delete_user_meta( $user_id, Install::USER_TOKEN_META );
		Utils::log( 'OAuth token revoked.', array( 'user_id' => $user_id ) );
	}


	/**
	 * Withdraw the grant at Google, not just the copy stored here.
	 *
	 * Deleting the stored token only makes this site forget the account; the
	 * grant itself lives on in the account's third-party access list until it
	 * is revoked, so a "disconnect" that skips this leaves access standing.
	 *
	 * Revoking the refresh token withdraws the whole grant. Failure is logged
	 * and otherwise ignored: the local token is deleted either way, because
	 * leaving someone connected to an account they are trying to disconnect
	 * from is the worse outcome, and an already-invalid grant fails here too.
	 *
	 * @param int $user_id WP user ID.
	 */
	private static function revoke_at_google( int $user_id ): void {
		$token = self::retrieve_token( $user_id );

		if ( is_wp_error( $token ) ) {
			return;
		}

		$revocable = self::revocable_token( $token );

		if ( $revocable === '' ) {
			return;
		}

		$response = wp_remote_post(
			self::REVOKE_URL,
			array(
				'timeout' => 15,
				'body'    => array( 'token' => $revocable ),
			)
		);

		$status = self::status_of( $response );

		if ( $status !== 200 ) {
			Utils::log(
				'Google did not confirm the token revocation.',
				array(
					'user_id' => $user_id,
					'status'  => $status,
				)
			);
		}
	}


	/**
	 * The token to send to the revocation endpoint.
	 *
	 * Revoking the refresh token withdraws the whole grant; the access token is
	 * a fallback for a record that never carried one.
	 *
	 * @param  array $token Stored token.
	 * @return string
	 */
	private static function revocable_token( array $token ): string {
		return (string) ( $token['refresh_token'] ?? $token['access_token'] ?? '' );
	}


	/**
	 * The HTTP status of a response, or 0 when the request never landed.
	 *
	 * @param  array|WP_Error $response Result of a wp_remote_* call.
	 * @return int
	 */
	private static function status_of( $response ): int {
		return is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
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
