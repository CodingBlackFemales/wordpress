<?php
/**
 * REST endpoints for Google OAuth2 flow.
 *
 * GET  /auth/begin    — redirect user to Google consent screen
 * GET  /auth/callback — receive code, exchange for tokens, store encrypted
 * POST /auth/revoke   — delete current user's stored token
 * GET  /auth/status   — return auth state for current user
 *
 * @class   Api\AuthController
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Api;

use CodingBlackFemales\SlidesImporter\Google\OAuthClient;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AuthController class.
 */
final class AuthController {

	/**
	 * Register routes.
	 *
	 * @param string $namespace REST namespace.
	 */
	public static function register_routes( string $namespace ): void {
		register_rest_route(
			$namespace,
			'/auth/begin',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'begin' ),
				'permission_callback' => array( __CLASS__, 'require_auth' ),
			)
		);

		register_rest_route(
			$namespace,
			'/auth/callback',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'callback' ),
				// Google's browser redirect carries the WP session cookie but
				// never an X-WP-Nonce header, so standard cookie auth fails.
				// We allow the route through and check is_user_logged_in() +
				// the OAuth state nonce inside the callback method itself.
				'permission_callback' => '__return_true',
				'args'                => array(
					'code'  => array(
						'type'     => 'string',
						'required' => true,
					),
					'state' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/auth/revoke',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'revoke' ),
				'permission_callback' => array( __CLASS__, 'require_auth' ),
			)
		);

		register_rest_route(
			$namespace,
			'/auth/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'status' ),
				'permission_callback' => array( __CLASS__, 'require_auth' ),
			)
		);
	}


	/**
	 * Permission: user must be logged in and have cbf_slides_import capability.
	 */
	public static function require_auth(): bool|WP_Error {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'rest_not_logged_in', __( 'You must be logged in.', 'cbf-slides-importer' ), array( 'status' => 401 ) );
		}
		if ( ! current_user_can( 'cbf_slides_import' ) ) {
			return new WP_Error( 'rest_forbidden', __( 'You do not have permission.', 'cbf-slides-importer' ), array( 'status' => 403 ) );
		}
		return true;
	}


	/**
	 * GET /auth/begin — build the Google OAuth consent URL and redirect.
	 *
	 * Uses OAuthClient::create_auth_url() (not GoogleClient directly) so that
	 * the state nonce transient is stored before the user is sent to Google.
	 */
	public static function begin( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$auth_url = OAuthClient::create_auth_url( get_current_user_id() );
		if ( is_wp_error( $auth_url ) ) {
			return $auth_url;
		}

		// Return the URL; the React UI opens it in a popup or redirect.
		return new WP_REST_Response( array( 'auth_url' => $auth_url ), 200 );
	}


	/**
	 * GET /auth/callback — exchange the authorisation code for tokens and store.
	 *
	 * After success, redirect the user to the importer admin page.
	 */
	public static function callback( WP_REST_Request $request ): void {
		// permission_callback is __return_true so Google's redirect is not blocked
		// by the missing X-WP-Nonce header. Perform auth checks manually here.
		if ( ! is_user_logged_in() || ! current_user_can( 'cbf_slides_import' ) ) {
			wp_die( esc_html__( 'You must be logged in to connect Google Drive.', 'cbf-slides-importer' ), 401 );
		}

		$code  = sanitize_text_field( $request->get_param( 'code' ) );
		$state = sanitize_text_field( $request->get_param( 'state' ) );

		// Validate state nonce (stored transiently during begin).
		$stored_state = get_transient( 'cbf_si_oauth_state_' . get_current_user_id() );
		if ( ! hash_equals( (string) $stored_state, $state ) ) {
			wp_die( esc_html__( 'OAuth state mismatch — possible CSRF attack.', 'cbf-slides-importer' ), 403 );
		}
		delete_transient( 'cbf_si_oauth_state_' . get_current_user_id() );

		$result = OAuthClient::exchange_code( $code, get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ), 500 );
		}

		// Redirect back to the importer admin page.
		wp_safe_redirect( admin_url( 'admin.php?page=cbf-slides-importer&oauth=success' ) );
		exit;
	}


	/**
	 * POST /auth/revoke — delete the current user's stored OAuth token.
	 */
	public static function revoke( WP_REST_Request $request ): WP_REST_Response {
		OAuthClient::revoke_token( get_current_user_id() );
		return new WP_REST_Response( array( 'revoked' => true ), 200 );
	}


	/**
	 * GET /auth/status — return whether the current user has a stored token.
	 */
	public static function status( WP_REST_Request $request ): WP_REST_Response {
		$user_id   = get_current_user_id();
		$has_token = OAuthClient::has_token( $user_id );
		return new WP_REST_Response(
			array(
				'authenticated' => $has_token,
				'user_id'       => $user_id,
			),
			200
		);
	}
}
