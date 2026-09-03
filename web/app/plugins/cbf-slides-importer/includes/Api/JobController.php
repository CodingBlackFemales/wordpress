<?php
/**
 * REST endpoints for import job lifecycle.
 *
 * POST /jobs              — queue a new import job
 * GET  /jobs              — list jobs for current user
 * GET  /jobs/{id}         — get one job (status polling)
 * POST /jobs/{id}/cancel  — cancel a pending job
 *
 * @class   Api\JobController
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Api;

use CodingBlackFemales\SlidesImporter\Import\JobRunner;
use CodingBlackFemales\SlidesImporter\Api\PreviewController;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * JobController class.
 */
final class JobController {

	/**
	 * Register routes.
	 *
	 * @param string $namespace REST namespace.
	 */
	public static function register_routes( string $namespace ): void {
		register_rest_route(
			$namespace,
			'/jobs',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'index' ),
					'permission_callback' => array( AuthController::class, 'require_auth' ),
					'args'                => array(
						'page'     => array(
							'type' => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'per_page' => array(
							'type' => 'integer',
							'default' => 20,
							'minimum' => 1,
							'maximum' => 100,
						),
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'create' ),
					'permission_callback' => array( AuthController::class, 'require_auth' ),
					'args'                => array(
						'drive_file_id' => array(
							'type' => 'string',
							'required' => true,
						),
						'deck_name'     => array(
							'type' => 'string',
							'required' => false,
							'default' => '',
						),
						'config_id'     => array(
							'type' => 'integer',
							'required' => false,
							'default' => null,
						),
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/jobs/(?P<id>[\d]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'show' ),
				'permission_callback' => array( AuthController::class, 'require_auth' ),
			)
		);

		register_rest_route(
			$namespace,
			'/jobs/(?P<id>[\d]+)/cancel',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'cancel' ),
				'permission_callback' => array( AuthController::class, 'require_auth' ),
			)
		);

		register_rest_route(
			$namespace,
			'/jobs/(?P<id>[\d]+)/slides',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'slides' ),
				'permission_callback' => array( AuthController::class, 'require_auth' ),
			)
		);

		register_rest_route(
			$namespace,
			'/jobs/(?P<id>[\d]+)/import',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'trigger_import' ),
				'permission_callback' => array( AuthController::class, 'require_auth' ),
				'args'                => array(
					'mode'       => array(
						'type'    => 'string',
						'enum'    => array( 'lesson-only', 'topic' ),
						'default' => null,
					),
					'course_id'  => array(
						'type'    => 'integer',
						'default' => null,
					),
					'lesson_id'  => array(
						'type'    => 'integer',
						'default' => null,
					),
					'post_title'      => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'default'           => null,
					),
					'slide_overrides' => array(
						'type'    => 'object',
						'default' => null,
					),
					'overwrite'       => array(
						'type'    => 'boolean',
						'default' => null,
					),
					'force'           => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);
	}


	/** GET /jobs */
	public static function index( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;
		$table    = $wpdb->prefix . 'cbf_slide_import_jobs';
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );
		$offset   = ( $page - 1 ) * $per_page;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE blog_id = %d AND user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				get_current_blog_id(),
				get_current_user_id(),
				$per_page,
				$offset
			),
			ARRAY_A
		);

		return new WP_REST_Response( $rows ? $rows : array(), 200 );
	}


	/** POST /jobs — create a new job and schedule the background task. */
	public static function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		global $wpdb;
		$table = $wpdb->prefix . 'cbf_slide_import_jobs';

		$drive_file_id = sanitize_text_field( (string) $request->get_param( 'drive_file_id' ) );
		$deck_name     = sanitize_text_field( (string) $request->get_param( 'deck_name' ) );
		$config_id     = $request->get_param( 'config_id' ) ? absint( $request->get_param( 'config_id' ) ) : null;

		$data = array(
			'blog_id'       => get_current_blog_id(),
			'user_id'       => get_current_user_id(),
			'drive_file_id' => $drive_file_id,
			'deck_name'     => $deck_name,
			'config_id'     => $config_id,
			'status'        => 'pending',
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert( $table, $data, array( '%d', '%d', '%s', '%s', '%d', '%s' ) );
		if ( $result === false ) {
			return new WP_Error( 'cbf_si_db_error', $wpdb->last_error, array( 'status' => 500 ) );
		}

		$job_id = $wpdb->insert_id;

		// Schedule the background job (fires as soon as WP-Cron next runs).
		wp_schedule_single_event(
			time(),
			JobRunner::CRON_HOOK,
			array(
				array(
					'job_id' => $job_id,
					'blog_id' => get_current_blog_id(),
				),
			)
		);

		return new WP_REST_Response( array_merge( $data, array( 'id' => $job_id ) ), 201 );
	}


	/** GET /jobs/{id} */
	public static function show( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$row = self::find_row( (int) $request->get_param( 'id' ) );
		return is_wp_error( $row ) ? $row : new WP_REST_Response( $row, 200 );
	}


	/** POST /jobs/{id}/cancel — only pending jobs can be cancelled. */
	public static function cancel( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		global $wpdb;
		$table = $wpdb->prefix . 'cbf_slide_import_jobs';

		$row = self::find_row( (int) $request->get_param( 'id' ) );
		if ( is_wp_error( $row ) ) {
			return $row;
		}

		if ( $row['status'] !== 'pending' ) {
			return new WP_Error(
				'cbf_si_cannot_cancel',
				__( 'Only pending jobs can be cancelled.', 'cbf-slides-importer' ),
				array( 'status' => 409 )
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array(
				'status' => 'failed',
				'error_message' => 'Cancelled by user.',
			),
			array(
				'id' => (int) $request->get_param( 'id' ),
				'user_id' => get_current_user_id(),
				'blog_id' => get_current_blog_id(),
			),
			array( '%s', '%s' ),
			array( '%d', '%d', '%d' )
		);

		return new WP_REST_Response( array( 'cancelled' => true ), 200 );
	}


	/**
	 * GET /jobs/{id}/slides — return per-slide metadata for the slide-map UI.
	 *
	 * Returns the serialisable slide metadata captured at parse time together
	 * with any per-slide type overrides already stored in the job config so the
	 * UI can pre-populate the override dropdowns on re-open.
	 */
	public static function slides( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$row = self::find_row( (int) $request->get_param( 'id' ) );
		if ( is_wp_error( $row ) ) {
			return $row;
		}

		$summary     = self::decode_summary( $row );
		$slides_meta = $summary['slides_meta'] ?? array();
		$config      = self::extract_config( $summary );
		$overrides   = self::decode_stored_overrides( $config );

		foreach ( $slides_meta as &$slide ) {
			$num               = (int) ( $slide['slide_number'] ?? 0 );
			$slide['override'] = $overrides[ $num ] ?? null;
		}
		unset( $slide );

		return new WP_REST_Response( array( 'slides' => $slides_meta ), 200 );
	}


	/**
	 * POST /jobs/{id}/import — trigger the LearnDash import phase for a parsed job.
	 *
	 * The job must already be in 'parsed' status (download + parse completed).
	 * Optional body params `mode`, `course_id`, and `post_title` override the
	 * stored config so editors can configure the import in the UI without a
	 * separate config save step.
	 *
	 * Returns 409 when a prior `done` job for the same deck + config hash is found,
	 * unless `force=true` is supplied to bypass the check.
	 */
	public static function trigger_import( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		global $wpdb;

		$job_id = (int) $request->get_param( 'id' );
		$row    = self::find_row( $job_id );
		if ( is_wp_error( $row ) ) {
			return $row;
		}

		// Persist any UI-supplied overrides before the background job runs.
		self::save_overrides_for_job( $row, $request );

		// Check for a prior completed import of the same deck + config.
		if ( ! $request->get_param( 'force' ) ) {
			$table    = $wpdb->prefix . 'cbf_slide_import_jobs';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$fresh    = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $job_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				ARRAY_A
			);
			$conflict = self::find_prior_import( $fresh );
			if ( $conflict ) {
				return $conflict;
			}
		}

		// Re-schedule background job to proceed to import phase.
		wp_schedule_single_event(
			time(),
			JobRunner::CRON_HOOK,
			array(
				array(
					'job_id'  => $job_id,
					'blog_id' => (int) $row['blog_id'],
					'phase'   => 'import',
				),
			)
		);

		return new WP_REST_Response( array( 'scheduled' => true ), 202 );
	}


	/**
	 * Check for a prior completed import of the same deck + configuration.
	 *
	 * Computes a config hash from the freshly saved row, stores it, then
	 * queries for other `done` jobs on the same Drive file ID whose stored
	 * hash matches.  Returns a 409 WP_Error when a match is found, null
	 * when clear.
	 *
	 * @param array $row Job DB row with freshly saved config in result_summary.
	 * @return WP_Error|null
	 */
	private static function find_prior_import( array $row ): ?WP_Error {
		$summary     = self::decode_summary( $row );
		$config      = self::extract_config( $summary );
		$config_hash = self::compute_config_hash( $row['drive_file_id'], $config );

		self::store_config_hash( (int) $row['id'], $summary, $config_hash );

		return self::query_prior_import( $row, $config_hash );
	}


	/**
	 * Persist a config hash into a job's result_summary.
	 *
	 * @param int    $job_id      Job ID.
	 * @param array  $summary     Decoded result_summary (will be updated in-place).
	 * @param string $config_hash SHA-256 hex hash to store.
	 */
	private static function store_config_hash( int $job_id, array $summary, string $config_hash ): void {
		global $wpdb;
		$summary['config_hash'] = $config_hash;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->prefix . 'cbf_slide_import_jobs',
			array( 'result_summary' => wp_json_encode( $summary ) ),
			array( 'id' => $job_id ),
			array( '%s' ),
			array( '%d' )
		);
	}


	/**
	 * Query for a prior completed import with the same file + config hash.
	 *
	 * @param array  $row         Current job row.
	 * @param string $config_hash Hash to match against prior done jobs.
	 * @return WP_Error|null
	 */
	private static function query_prior_import( array $row, string $config_hash ): ?WP_Error {
		global $wpdb;
		$table = $wpdb->prefix . 'cbf_slide_import_jobs';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$prior_jobs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, result_summary, updated_at FROM {$table} WHERE drive_file_id = %s AND status = 'done' AND id != %d AND blog_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$row['drive_file_id'],
				(int) $row['id'],
				(int) $row['blog_id']
			),
			ARRAY_A
		);

		foreach ( (array) $prior_jobs as $prior ) {
			$prior_hash = self::decode_summary( $prior )['config_hash'] ?? null;
			if ( $prior_hash === $config_hash ) {
				return new WP_Error(
					'cbf_si_already_imported',
					__( 'This deck was already imported with the same configuration.', 'cbf-slides-importer' ),
					array(
						'status'         => 409,
						'prior_job_id'   => (int) $prior['id'],
						'prior_job_date' => $prior['updated_at'] ?? '',
					)
				);
			}
		}

		return null;
	}


	/**
	 * Compute a stable SHA-256 hash of the key import-config fields.
	 *
	 * The hash covers the deck source and content-structure settings (mode,
	 * course, slide overrides) so the same deck imported with the same slide
	 * map can be detected on a subsequent trigger.  Post title and overwrite
	 * are deliberately excluded — they are post-metadata settings, not
	 * content-structure settings.
	 *
	 * @param string $drive_file_id Google Drive file ID.
	 * @param array  $config        Stored config from result_summary.
	 * @return string Hex SHA-256 hash.
	 */
	private static function compute_config_hash( string $drive_file_id, array $config ): string {
		$mode      = $config['mode'] ?? 'lesson-only';
		$course_id = (int) ( $config['course_id'] ?? 0 );

		// Decode and sort slide overrides for a stable key order.
		$overrides = array();
		if ( ! empty( $config['slide_overrides'] ) ) {
			$decoded = json_decode( $config['slide_overrides'], true );
			if ( is_array( $decoded ) ) {
				ksort( $decoded );
				$overrides = $decoded;
			}
		}

		$payload = implode(
			'|',
			array(
				$drive_file_id,
				$mode,
				(string) $course_id,
				wp_json_encode( $overrides ),
			)
		);

		return hash( 'sha256', $payload );
	}


	/**
	 * Persist UI-supplied import config overrides into the job's result_summary.
	 *
	 * Public so PreviewController can call it before generating a refresh preview
	 * without duplicating the sanitisation and persistence logic.
	 *
	 * @param array           $row     Job DB row.
	 * @param WP_REST_Request $request Incoming REST request.
	 */
	public static function save_overrides_for_job( array $row, WP_REST_Request $request ): void {
		global $wpdb;

		$mode            = $request->get_param( 'mode' );
		$course_id       = $request->get_param( 'course_id' );
		$lesson_id       = $request->get_param( 'lesson_id' );
		$post_title      = $request->get_param( 'post_title' );
		$slide_overrides = $request->get_param( 'slide_overrides' );
		$overwrite       = $request->get_param( 'overwrite' );

		if ( $mode === null && $course_id === null && $lesson_id === null
			&& $post_title === null && $slide_overrides === null && $overwrite === null ) {
			return;
		}

		$summary           = self::decode_summary( $row );
		$config            = self::extract_config( $summary );
		$config            = self::apply_request_config( $config, $mode, $course_id, $lesson_id, $post_title, $overwrite );
		$config            = self::apply_slide_overrides( $config, $slide_overrides );
		$summary['config'] = $config;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->prefix . 'cbf_slide_import_jobs',
			array( 'result_summary' => wp_json_encode( $summary ) ),
			array( 'id' => (int) $row['id'] ),
			array( '%s' ),
			array( '%d' )
		);

		// Bust the preview transient so the next GET /preview re-renders with
		// the updated config (mode, slide_overrides, etc.).
		PreviewController::bust( (int) $row['id'], get_current_user_id() );
	}


	/**
	 * Apply scalar config overrides from a REST request to a config array.
	 *
	 * @param array       $config     Existing config.
	 * @param string|null $mode       Import mode.
	 * @param mixed       $course_id  Course ID.
	 * @param mixed       $lesson_id  Lesson ID (for topic mode).
	 * @param string|null $post_title Post title.
	 * @param bool|null   $overwrite  Overwrite flag.
	 * @return array Updated config.
	 */
	private static function apply_request_config(
		array $config,
		?string $mode,
		$course_id,
		$lesson_id,
		?string $post_title,
		?bool $overwrite
	): array {
		if ( $mode !== null ) {
			$config['mode'] = sanitize_text_field( $mode );
		}
		if ( $course_id !== null ) {
			$config['course_id'] = absint( $course_id );
		}
		if ( $lesson_id !== null ) {
			$config['lesson_id'] = absint( $lesson_id );
		}
		if ( $post_title !== null ) {
			$config['post_title'] = sanitize_text_field( $post_title );
		}
		if ( $overwrite !== null ) {
			$config['overwrite'] = (bool) $overwrite;
		}
		return $config;
	}


	/**
	 * Sanitise and apply slide overrides to a config array.
	 *
	 * @param array      $config          Existing config.
	 * @param array|null $slide_overrides Raw slide overrides from the request.
	 * @return array Updated config.
	 */
	private static function apply_slide_overrides( array $config, ?array $slide_overrides ): array {
		if ( $slide_overrides === null || ! is_array( $slide_overrides ) ) {
			return $config;
		}
		$allowed   = array( 'cover', 'body', 'heading', 'hidden', 'section' );
		$sanitised = array();
		foreach ( $slide_overrides as $slide_number => $type ) {
			$slide_number = absint( $slide_number );
			$type         = sanitize_key( (string) $type );
			if ( $slide_number > 0 && in_array( $type, $allowed, true ) ) {
				$sanitised[ $slide_number ] = $type;
			}
		}
		$config['slide_overrides'] = wp_json_encode( $sanitised );
		return $config;
	}


	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Find a job owned by the current user on the current site.
	 *
	 * @param  int $id Job ID.
	 * @return array|WP_Error
	 */
	private static function find_row( int $id ): array|WP_Error {
		global $wpdb;
		$table = $wpdb->prefix . 'cbf_slide_import_jobs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d AND user_id = %d AND blog_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$id,
				get_current_user_id(),
				get_current_blog_id()
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return new WP_Error( 'cbf_si_not_found', __( 'Job not found.', 'cbf-slides-importer' ), array( 'status' => 404 ) );
		}

		return $row;
	}


	// ── Summary / config helpers ──────────────────────────────────────────────

	/**
	 * JSON-decode a job row's result_summary, returning a safe array.
	 *
	 * @param array $row Job DB row.
	 * @return array
	 */
	private static function decode_summary( array $row ): array {
		$decoded = json_decode( $row['result_summary'] ?? '{}', true );
		return is_array( $decoded ) ? $decoded : array();
	}


	/**
	 * Extract the config sub-array from a decoded summary, returning [] when absent.
	 *
	 * @param array $summary Decoded result_summary.
	 * @return array
	 */
	private static function extract_config( array $summary ): array {
		$config = $summary['config'] ?? null;
		return is_array( $config ) ? $config : array();
	}


	/**
	 * Decode slide_overrides from a stored config into an int-keyed array.
	 *
	 * @param array $config Stored config.
	 * @return array<int, string>
	 */
	private static function decode_stored_overrides( array $config ): array {
		if ( empty( $config['slide_overrides'] ) ) {
			return array();
		}
		$decoded = json_decode( $config['slide_overrides'], true );
		if ( ! is_array( $decoded ) ) {
			return array();
		}
		$result = array();
		foreach ( $decoded as $slide_number => $type ) {
			$result[ (int) $slide_number ] = (string) $type;
		}
		return $result;
	}
}
