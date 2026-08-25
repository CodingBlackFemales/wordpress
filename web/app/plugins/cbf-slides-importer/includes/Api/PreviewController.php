<?php
/**
 * REST endpoint for slide import preview.
 *
 * GET /jobs/{id}/preview — return rendered Gutenberg block HTML for a parsed job.
 *
 * @class   Api\PreviewController
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PreviewController class.
 *
 * Preview data is loaded from the transient set by JobRunner when the parse
 * phase completes. Transients have a short TTL (1 hour) to avoid caching
 * potentially sensitive slide content indefinitely.
 */
final class PreviewController {

	/** Transient key prefix for preview data. */
	const TRANSIENT_PREFIX = 'cbf_si_preview_';

	/** Transient TTL in seconds. */
	const TRANSIENT_TTL = HOUR_IN_SECONDS;

	/**
	 * Register routes.
	 *
	 * @param string $namespace REST namespace.
	 */
	public static function register_routes( string $namespace ): void {
		register_rest_route(
			$namespace,
			'/jobs/(?P<id>[\d]+)/preview',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'show' ),
				'permission_callback' => array( AuthController::class, 'require_auth' ),
			)
		);
	}


	/**
	 * GET /jobs/{id}/preview
	 *
	 * Returns an array of slides, each with rendered block HTML and metadata.
	 * The preview is keyed by job ID and scoped to the current user.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function show( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$job_id  = (int) $request->get_param( 'id' );
		$user_id = get_current_user_id();

		$data = get_transient( self::TRANSIENT_PREFIX . $job_id . '_' . $user_id );

		if ( $data === false ) {
			return new WP_Error(
				'cbf_si_preview_not_ready',
				__( 'Preview is not yet available. The job may still be processing, or the preview has expired.', 'cbf-slides-importer' ),
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response( $data, 200 );
	}


	/**
	 * Store preview data in a transient after the parse phase completes.
	 *
	 * Called by JobRunner — not a REST endpoint.
	 *
	 * @param int   $job_id  Job ID.
	 * @param int   $user_id User ID.
	 * @param array $data    Preview payload (slides array).
	 */
	public static function store( int $job_id, int $user_id, array $data ): void {
		set_transient( self::TRANSIENT_PREFIX . $job_id . '_' . $user_id, $data, self::TRANSIENT_TTL );
	}
}
