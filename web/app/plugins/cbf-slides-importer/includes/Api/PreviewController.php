<?php
/**
 * REST endpoint for slide import preview.
 *
 * GET /jobs/{id}/preview — return rendered Gutenberg block HTML for a parsed job.
 *
 * @class   Api\PreviewController
 * @version 1.0.1
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Api;

use CodingBlackFemales\SlidesImporter\Import\PreviewRenderer;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PreviewController class.
 *
 * Preview data is cached in a transient keyed by job + user so each user gets
 * a view reflecting the config they last saved.  The transient is busted
 * whenever the user saves import config overrides (mode, slide_overrides, etc.)
 * so that a subsequent GET /preview re-renders from the updated config.
 *
 * If the transient has expired or been busted and the source file is still on
 * disk, the endpoint re-renders on-demand.  If the source file has been cleaned up
 * (post-import), the endpoint returns 404.
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
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'show' ),
					'permission_callback' => array( AuthController::class, 'require_auth' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'refresh' ),
					'permission_callback' => array( AuthController::class, 'require_auth' ),
					'args'                => array(
						'mode'            => array(
							'type'    => 'string',
							'enum'    => array( 'lesson-only', 'topic' ),
							'default' => null,
						),
						'course_id'       => array(
							'type' => 'integer',
							'default' => null,
						),
						'post_title'      => array(
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
							'default'           => null,
						),
						'slide_overrides' => array(
							'type' => 'object',
							'default' => null,
						),
						'overwrite'       => array(
							'type' => 'boolean',
							'default' => null,
						),
					),
				),
			)
		);
	}


	/**
	 * GET /jobs/{id}/preview
	 *
	 * Returns rendered block HTML for a job's content.  Reads from the cached
	 * transient when available; otherwise re-renders on-demand from the stored file.
	 *
	 * Response shape:
	 *   { lesson_html: '<string>', topics: [] }
	 *
	 * Returns 404 when the source file is no longer on disk (cleaned up after import)
	 * or when the job does not belong to the current user.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function show( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$job_id  = (int) $request->get_param( 'id' );
		$user_id = get_current_user_id();

		// Return cached transient when available.
		$cached = get_transient( self::TRANSIENT_PREFIX . $job_id . '_' . $user_id );
		if ( $cached !== false ) {
			return new WP_REST_Response( array_merge( $cached, array( 'cached' => true ) ), 200 );
		}

		// Cache miss — look up job (ownership check) and render on-demand.
		$row = self::find_owned_row( $job_id, $user_id );
		if ( is_wp_error( $row ) ) {
			return $row;
		}

		$in_progress = self::check_in_progress( $row );
		if ( $in_progress !== null ) {
			return $in_progress;
		}

		return self::render_and_respond( $job_id, $user_id, $row );
	}


	/**
	 * POST /jobs/{id}/preview
	 *
	 * Saves any supplied config overrides (mode, slide_overrides, etc.) into
	 * the job's result_summary — identical to what POST /jobs/{id}/import does
	 * before scheduling the background task — then immediately re-generates the
	 * preview from the updated config and returns it.
	 *
	 * This is the entry point used by the "Preview content…" button so that the
	 * current slide-map override selections are reflected in the preview without
	 * the user having to explicitly save config first.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function refresh( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		global $wpdb;

		$job_id  = (int) $request->get_param( 'id' );
		$user_id = get_current_user_id();

		$row = self::find_owned_row( $job_id, $user_id );
		if ( is_wp_error( $row ) ) {
			return $row;
		}

		// Persist the submitted config overrides (same logic as trigger_import).
		JobController::save_overrides_for_job( $row, $request );

		// Re-read the row so the summary reflects the freshly saved config.
		$table = $wpdb->prefix . 'cbf_slide_import_jobs';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $job_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		$in_progress = self::check_in_progress( $row );
		if ( $in_progress !== null ) {
			return $in_progress;
		}

		return self::render_and_respond( $job_id, $user_id, $row );
	}


	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Fetch a job row, enforcing ownership.
	 *
	 * @param int $job_id  Job ID.
	 * @param int $user_id Authenticated user ID.
	 * @return array|WP_Error
	 */
	private static function find_owned_row( int $job_id, int $user_id ): array|WP_Error {
		global $wpdb;
		$table = $wpdb->prefix . 'cbf_slide_import_jobs';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d AND user_id = %d AND blog_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$job_id,
				$user_id,
				get_current_blog_id()
			),
			ARRAY_A
		);
		if ( ! $row ) {
			return new WP_Error( 'cbf_si_not_found', __( 'Job not found.', 'cbf-slides-importer' ), array( 'status' => 404 ) );
		}
		return $row;
	}


	/**
	 * Return a 202 retry response if the job is still processing, or null if ready.
	 *
	 * @param array|null $row Job row.
	 * @return WP_REST_Response|null
	 */
	private static function check_in_progress( ?array $row ): ?WP_REST_Response {
		if ( $row === null || in_array( $row['status'], array( 'pending', 'downloading', 'parsing' ), true ) ) {
			return new WP_REST_Response( array( 'retry_after' => 3 ), 202 );
		}
		return null;
	}


	/**
	 * Render preview HTML from a job row's summary and return the REST response.
	 *
	 * Caches the rendered result in a transient on success.
	 *
	 * @param int   $job_id  Job ID.
	 * @param int   $user_id Authenticated user ID.
	 * @param array $row     Job DB row.
	 * @return WP_REST_Response|WP_Error
	 */
	private static function render_and_respond( int $job_id, int $user_id, array $row ): WP_REST_Response|WP_Error {
		$summary  = json_decode( $row['result_summary'] ?? '{}', true );
		$summary  = is_array( $summary ) ? $summary : array();
		$rendered = PreviewRenderer::render_from_summary( $summary );

		if ( is_wp_error( $rendered ) ) {
			$status = $rendered->get_error_code() === 'cbf_si_preview_unavailable' ? 404 : 500;
			return new WP_Error( $rendered->get_error_code(), $rendered->get_error_message(), array( 'status' => $status ) );
		}

		self::store( $job_id, $user_id, $rendered );
		return new WP_REST_Response( $rendered, 200 );
	}


	/**
	 * Store preview data in a transient after the parse phase completes.
	 *
	 * Called by JobRunner — not a REST endpoint.
	 *
	 * @param int   $job_id  Job ID.
	 * @param int   $user_id User ID.
	 * @param array $data    Preview payload ({ lesson_html, topics }).
	 */
	public static function store( int $job_id, int $user_id, array $data ): void {
		set_transient( self::TRANSIENT_PREFIX . $job_id . '_' . $user_id, $data, self::TRANSIENT_TTL );
	}


	/**
	 * Invalidate the preview transient for a job/user pair.
	 *
	 * Called when the user saves import config overrides so the next GET
	 * /preview re-renders with the updated mode/slide_overrides/etc.
	 *
	 * @param int $job_id  Job ID.
	 * @param int $user_id User ID.
	 */
	public static function bust( int $job_id, int $user_id ): void {
		delete_transient( self::TRANSIENT_PREFIX . $job_id . '_' . $user_id );
	}
}
