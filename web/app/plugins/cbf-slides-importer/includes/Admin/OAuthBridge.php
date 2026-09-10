<?php
/**
 * WP admin_post handler for the Google OAuth begin flow.
 *
 * The REST endpoint /auth/begin exists for the JS SPA (Phase 3+), but until
 * the React bundle is built the admin page renders a plain HTML link. A plain
 * <a href> to a REST endpoint never carries the X-WP-Nonce header that WP
 * cookie-auth requires, so the REST route returns 401.
 *
 * This class registers an admin-post action that:
 *  1. Verifies a standard wp_nonce (safe for browser GETs from admin pages).
 *  2. Builds the Google consent URL (storing the state transient).
 *  3. Redirects the browser to Google.
 *
 * The callback continues to use the REST endpoint (/auth/callback) because
 * Google's redirect will carry the WordPress session cookie (the user stays
 * logged in). No X-WP-Nonce is needed there — the OAuth state parameter is
 * the CSRF protection.
 *
 * @class   Admin\OAuthBridge
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Admin;

use CodingBlackFemales\SlidesImporter\Google\OAuthClient;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OAuthBridge class.
 */
final class OAuthBridge {

	const ACTION     = 'cbf_si_auth_begin';
	const NONCE_NAME = 'cbf_si_auth_begin_nonce';

	const REVOKE_ACTION     = 'cbf_si_auth_revoke';
	const REVOKE_NONCE_NAME = 'cbf_si_auth_revoke_nonce';

	/**
	 * Register the admin_post hook.
	 */
	public static function hooks(): void {
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_begin' ) );
		add_action( 'admin_post_' . self::REVOKE_ACTION, array( __CLASS__, 'handle_revoke' ) );
	}


	/**
	 * Build the auth URL for use in a nonce-protected admin link.
	 *
	 * Called by ImporterPage to render the "Connect Google Drive" button href.
	 *
	 * @return string Admin-post URL with action and nonce baked in.
	 */
	public static function begin_url(): string {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::ACTION ),
			self::ACTION,
			self::NONCE_NAME
		);
	}


	/**
	 * Build the disconnect URL for use in a nonce-protected admin link.
	 *
	 * Deliberately an admin-post link rather than a REST call: disconnecting has
	 * to work from the page itself, without depending on the importer's script
	 * having loaded.
	 *
	 * @return string
	 */
	public static function revoke_url(): string {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::REVOKE_ACTION ),
			self::REVOKE_ACTION,
			self::REVOKE_NONCE_NAME
		);
	}


	/**
	 * admin_post handler — verify nonce, revoke the token, return to the page.
	 */
	public static function handle_revoke(): void {
		if ( ! current_user_can( 'cbf_slides_import' ) ) {
			wp_die( esc_html__( 'You do not have permission to disconnect Google Drive.', 'cbf-slides-importer' ), 403 );
		}

		check_admin_referer( self::REVOKE_ACTION, self::REVOKE_NONCE_NAME );

		OAuthClient::revoke_token( get_current_user_id() );

		wp_safe_redirect( admin_url( 'admin.php?page=' . ImporterPage::PAGE_SLUG . '&google=disconnected' ) );
		exit;
	}


	/**
	 * admin_post handler — verify nonce, build Google auth URL, redirect.
	 *
	 * Registered for authenticated users only (admin_post_ prefix requires login).
	 */
	public static function handle_begin(): void {
		if ( ! current_user_can( 'cbf_slides_import' ) ) {
			wp_die( esc_html__( 'You do not have permission to connect Google Drive.', 'cbf-slides-importer' ), 403 );
		}

		check_admin_referer( self::ACTION, self::NONCE_NAME );

		$auth_url = OAuthClient::create_auth_url( get_current_user_id() );

		if ( is_wp_error( $auth_url ) ) {
			wp_die( esc_html( $auth_url->get_error_message() ), 500 );
		}

		wp_redirect( $auth_url ); // External URL — wp_safe_redirect would block it.
		exit;
	}
}
