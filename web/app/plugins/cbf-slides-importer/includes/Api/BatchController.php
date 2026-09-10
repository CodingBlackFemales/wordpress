<?php
/**
 * REST endpoints for bulk CSV migration.
 *
 * POST /batches              — upload a CSV, validate it, return a pre-flight plan
 * GET  /batches              — list the current user's batches
 * GET  /batches/{id}         — one batch, with its plan and live report
 * POST /batches/{id}/run     — confirm the plan and begin importing
 * GET  /batches/{id}/report  — the report, as JSON or a CSV download
 * POST /batches/{id}/cancel  — stop a running batch
 *
 * Uploading validates but never writes: an editor can upload, read the report,
 * correct the spreadsheet and upload again without consequence. Only `run`
 * creates anything.
 *
 * @class   Api\BatchController
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Api;

use CodingBlackFemales\SlidesImporter\Bulk\BatchPlanner;
use CodingBlackFemales\SlidesImporter\Bulk\BatchReport;
use CodingBlackFemales\SlidesImporter\Bulk\BatchRepository;
use CodingBlackFemales\SlidesImporter\Bulk\BatchRunner;
use CodingBlackFemales\SlidesImporter\Bulk\CsvParser;
use CodingBlackFemales\SlidesImporter\Utils;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BatchController class.
 */
final class BatchController {

	/** Largest CSV accepted, in bytes. */
	const MAX_CSV_BYTES = 2097152;


	/**
	 * Register routes.
	 *
	 * @param string $namespace REST namespace.
	 */
	public static function register_routes( string $namespace ): void {
		$auth = array( AuthController::class, 'require_auth' );

		register_rest_route(
			$namespace,
			'/batches',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'index' ),
					'permission_callback' => $auth,
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'create' ),
					'permission_callback' => $auth,
					'args'                => array(
						'course_id' => array(
							'type'     => 'integer',
							'required' => true,
						),
						'overwrite' => array(
							'type'    => 'boolean',
							'default' => false,
						),
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/batches/(?P<id>[\d]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'show' ),
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			$namespace,
			'/batches/(?P<id>[\d]+)/run',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'run' ),
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			$namespace,
			'/batches/(?P<id>[\d]+)/report',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'report' ),
				'permission_callback' => $auth,
				'args'                => array(
					'format' => array(
						'type'    => 'string',
						'enum'    => array( 'json', 'csv' ),
						'default' => 'json',
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/batches/(?P<id>[\d]+)/cancel',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'cancel' ),
				'permission_callback' => $auth,
			)
		);
	}


	/** GET /batches */
	public static function index(): WP_REST_Response {
		return new WP_REST_Response( BatchRepository::list_owned(), 200 );
	}


	/**
	 * POST /batches — upload and validate a CSV.
	 *
	 * Nothing is written to LearnDash here. The response is the pre-flight
	 * report the editor confirms before any import runs.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$csv = self::read_upload( $request );
		if ( is_wp_error( $csv ) ) {
			return $csv;
		}

		$course_id = absint( $request->get_param( 'course_id' ) );
		$course    = get_post( $course_id );

		if ( ! $course || $course->post_type !== 'sfwd-courses' ) {
			return new WP_Error(
				'cbf_si_no_course',
				__( 'Choose a course to import into.', 'cbf-slides-importer' ),
				array( 'status' => 400 )
			);
		}

		$parsed = CsvParser::parse( $csv['contents'] );

		if ( $parsed['errors'] !== array() ) {
			return new WP_Error(
				'cbf_si_bad_csv',
				implode( ' ', $parsed['errors'] ),
				array( 'status' => 400 )
			);
		}

		$plan = BatchPlanner::plan( $parsed['rows'], $course_id, get_current_user_id() );

		$batch_id = BatchRepository::create(
			array(
				'course_id' => $course_id,
				'overwrite' => (bool) $request->get_param( 'overwrite' ),
				'csv_name'  => $csv['name'],
				'row_count' => count( $plan['rows'] ),
				'status'    => BatchRepository::STATUS_AWAITING,
			)
		);

		if ( $batch_id === 0 ) {
			return new WP_Error( 'cbf_si_db_error', __( 'The batch could not be saved.', 'cbf-slides-importer' ), array( 'status' => 500 ) );
		}

		BatchRepository::update(
			$batch_id,
			array(
				'plan'   => $plan,
				'report' => BatchReport::from_plan( $plan['rows'] ),
			)
		);

		Utils::log(
			'Bulk CSV validated.',
			array_merge( array( 'batch_id' => $batch_id ), $plan['counts'] )
		);

		return new WP_REST_Response( self::present( BatchRepository::find_owned( $batch_id ), $parsed['notices'] ), 201 );
	}


	/**
	 * GET /batches/{id}
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function show( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$batch = self::find( $request );

		return is_wp_error( $batch ) ? $batch : new WP_REST_Response( self::present( $batch ), 200 );
	}


	/**
	 * POST /batches/{id}/run — confirm the plan and start importing.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function run( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$batch = self::find( $request );
		if ( is_wp_error( $batch ) ) {
			return $batch;
		}

		if ( $batch['status'] !== BatchRepository::STATUS_AWAITING ) {
			return new WP_Error(
				'cbf_si_batch_not_ready',
				__( 'This batch has already been started.', 'cbf-slides-importer' ),
				array( 'status' => 409 )
			);
		}

		$plan = BatchRepository::decode( $batch, 'plan' );

		if ( (int) ( $plan['counts']['ready'] ?? 0 ) === 0 ) {
			return new WP_Error(
				'cbf_si_batch_empty',
				__( 'No rows in this CSV can be imported. Fix the problems listed in the report and upload it again.', 'cbf-slides-importer' ),
				array( 'status' => 409 )
			);
		}

		BatchRunner::start( (int) $batch['id'] );

		return new WP_REST_Response( self::present( BatchRepository::find_owned( (int) $batch['id'] ) ), 202 );
	}


	/**
	 * GET /batches/{id}/report
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function report( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$batch = self::find( $request );
		if ( is_wp_error( $batch ) ) {
			return $batch;
		}

		$entries = BatchRepository::decode( $batch, 'report' );

		if ( $request->get_param( 'format' ) === 'csv' ) {
			return self::csv_response( $batch, $entries );
		}

		return new WP_REST_Response(
			array(
				'batch_id' => (int) $batch['id'],
				'status'   => $batch['status'],
				'summary'  => BatchReport::summarise( $entries ),
				'complete' => BatchReport::is_complete( $entries ),
				'rows'     => $entries,
			),
			200
		);
	}


	/**
	 * POST /batches/{id}/cancel
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function cancel( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$batch = self::find( $request );
		if ( is_wp_error( $batch ) ) {
			return $batch;
		}

		if ( ! BatchRunner::cancel( (int) $batch['id'] ) ) {
			return new WP_Error(
				'cbf_si_batch_finished',
				__( 'This batch has already finished.', 'cbf-slides-importer' ),
				array( 'status' => 409 )
			);
		}

		return new WP_REST_Response( self::present( BatchRepository::find_owned( (int) $batch['id'] ) ), 200 );
	}


	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Read and check the uploaded CSV.
	 *
	 * @param  WP_REST_Request $request REST request.
	 * @return array{name: string, contents: string}|WP_Error
	 */
	private static function read_upload( WP_REST_Request $request ): array|WP_Error {
		$files = $request->get_file_params();

		if ( empty( $files['file'] ) ) {
			return new WP_Error( 'cbf_si_no_file', __( 'No CSV file was uploaded.', 'cbf-slides-importer' ), array( 'status' => 400 ) );
		}

		$file = $files['file'];

		if ( (int) $file['error'] !== UPLOAD_ERR_OK ) {
			return new WP_Error( 'cbf_si_upload_error', __( 'The CSV upload did not complete.', 'cbf-slides-importer' ), array( 'status' => 400 ) );
		}

		if ( (int) $file['size'] > self::MAX_CSV_BYTES ) {
			return new WP_Error(
				'cbf_si_file_too_large',
				sprintf(
					/* translators: %s: human-readable maximum size */
					__( 'The CSV is larger than %s.', 'cbf-slides-importer' ),
					size_format( self::MAX_CSV_BYTES )
				),
				array( 'status' => 400 )
			);
		}

		$extension = strtolower( pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) );

		if ( $extension !== 'csv' ) {
			return new WP_Error(
				'cbf_si_invalid_type',
				__( 'Upload a .csv file. Export it from your spreadsheet with File → Download → CSV.', 'cbf-slides-importer' ),
				array( 'status' => 400 )
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
		$contents = file_get_contents( $file['tmp_name'] );

		if ( $contents === false ) {
			return new WP_Error( 'cbf_si_unreadable', __( 'The CSV could not be read.', 'cbf-slides-importer' ), array( 'status' => 400 ) );
		}

		return array(
			'name'     => sanitize_text_field( (string) $file['name'] ),
			'contents' => $contents,
		);
	}


	/**
	 * Fetch the requested batch, enforcing ownership.
	 *
	 * @param  WP_REST_Request $request REST request.
	 * @return array|WP_Error
	 */
	private static function find( WP_REST_Request $request ): array|WP_Error {
		$batch = BatchRepository::find_owned( (int) $request->get_param( 'id' ) );

		if ( $batch === null ) {
			return new WP_Error( 'cbf_si_not_found', __( 'Batch not found.', 'cbf-slides-importer' ), array( 'status' => 404 ) );
		}

		return $batch;
	}


	/**
	 * Shape a batch row for the UI.
	 *
	 * The stored plan is not returned wholesale — it carries resolved Drive
	 * metadata the browser has no use for. The report is what the UI renders.
	 *
	 * @param  array|null $batch   Batch row.
	 * @param  array      $notices File-level notices from parsing.
	 * @return array
	 */
	private static function present( ?array $batch, array $notices = array() ): array {
		if ( $batch === null ) {
			return array();
		}

		$entries = BatchRepository::decode( $batch, 'report' );
		$plan    = BatchRepository::decode( $batch, 'plan' );

		return array(
			'id'         => (int) $batch['id'],
			'course_id'  => (int) $batch['course_id'],
			'overwrite'  => (bool) $batch['overwrite'],
			'csv_name'   => $batch['csv_name'],
			'row_count'  => (int) $batch['row_count'],
			'status'     => $batch['status'],
			'headings'   => $plan['headings'] ?? array(),
			'counts'     => $plan['counts'] ?? array(),
			'summary'    => BatchReport::summarise( $entries ),
			'complete'   => BatchReport::is_complete( $entries ),
			'notices'    => $notices,
			'rows'       => $entries,
			'created_at' => $batch['created_at'],
			'updated_at' => $batch['updated_at'],
		);
	}


	/**
	 * Send the report as a CSV download.
	 *
	 * @param  array $batch   Batch row.
	 * @param  array $entries Report entries.
	 * @return WP_REST_Response
	 */
	private static function csv_response( array $batch, array $entries ): WP_REST_Response {
		$filename = sanitize_file_name( 'import-report-batch-' . (int) $batch['id'] . '.csv' );
		$response = new WP_REST_Response( BatchReport::to_csv( $entries ), 200 );

		$response->header( 'Content-Type', 'text/csv; charset=utf-8' );
		$response->header( 'Content-Disposition', 'attachment; filename="' . $filename . '"' );

		return $response;
	}
}
