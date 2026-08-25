<?php
/**
 * REST API router — registers all plugin endpoints.
 *
 * Namespace: cbf-si/v1
 *
 * @class   Api\Router
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Router class.
 *
 * All route definitions live here so it is easy to see the full REST
 * surface at a glance. Permission callbacks are defined on each controller.
 */
final class Router {

	const NAMESPACE = 'cbf-si/v1';

	/**
	 * Register the rest_api_init hook.
	 */
	public static function hooks(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}


	/**
	 * Register all plugin REST routes.
	 */
	public static function register_routes(): void {
		// ── Auth ──────────────────────────────────────────────────────────────
		// GET  /auth/begin    — redirect user to Google OAuth consent screen
		// GET  /auth/callback — handle OAuth callback, store encrypted token
		// POST /auth/revoke   — delete the current user's stored token
		// GET  /auth/status   — check whether the current user is authenticated
		AuthController::register_routes( self::NAMESPACE );

		// ── Drive ─────────────────────────────────────────────────────────────
		// GET  /drive/picker-config  — return picker init data (token, folder ID)
		DriveController::register_routes( self::NAMESPACE );

		// ── Configs ───────────────────────────────────────────────────────────
		// GET    /configs           — list saved deck configs for current user
		// POST   /configs           — create a new deck config
		// GET    /configs/{id}      — get one config
		// PUT    /configs/{id}      — update a config
		// DELETE /configs/{id}      — delete a config
		ConfigController::register_routes( self::NAMESPACE );

		// ── Jobs ──────────────────────────────────────────────────────────────
		// POST /jobs              — queue a new import job
		// GET  /jobs              — list jobs for current user
		// GET  /jobs/{id}         — get one job (status polling)
		// POST /jobs/{id}/cancel  — cancel a pending job
		JobController::register_routes( self::NAMESPACE );

		// ── Preview ───────────────────────────────────────────────────────────
		// GET  /jobs/{id}/preview — return parsed HTML preview for a job
		PreviewController::register_routes( self::NAMESPACE );
	}
}
