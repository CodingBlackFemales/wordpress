<?php
/**
 * Background job runner — WP-Cron handler.
 *
 * Fired by wp_schedule_single_event() from JobController::create().
 * Orchestrates: download → parse → store preview → (on import trigger) import.
 *
 * The source file may be a PowerPoint deck, a PDF or a Word document; the phase
 * logic is identical for all three, with Document\ParserFactory choosing the
 * parser from the file's extension.
 *
 * @class   Import\JobRunner
 * @version 1.1.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Import;

use CodingBlackFemales\SlidesImporter\Api\PreviewController;
use CodingBlackFemales\SlidesImporter\Document\ParserFactory;
use CodingBlackFemales\SlidesImporter\Document\SlideClassifier;
use CodingBlackFemales\SlidesImporter\Google\DriveClient;
use CodingBlackFemales\SlidesImporter\Import\PreviewRenderer;
use CodingBlackFemales\SlidesImporter\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * JobRunner class.
 *
 * Each cron event carries a payload array:
 *   { job_id: int, blog_id: int, phase?: 'import' }
 *
 * Phase 'import' is triggered by the user via POST /jobs/{id}/import after
 * reviewing the preview. All other phases (download, parse) run automatically.
 */
final class JobRunner {

	/** WP-Cron event hook name. */
	const CRON_HOOK = 'cbf_si_process_job';

	/**
	 * Register the cron hook.
	 */
	public static function hooks(): void {
		add_action( self::CRON_HOOK, array( __CLASS__, 'run' ) );
	}


	/**
	 * Main cron callback.
	 *
	 * @param array $payload { job_id: int, blog_id: int, phase?: string }
	 */
	public static function run( array $payload ): void {
		$job_id  = isset( $payload['job_id'] ) ? (int) $payload['job_id'] : 0;
		$blog_id = isset( $payload['blog_id'] ) ? (int) $payload['blog_id'] : get_current_blog_id();
		$phase   = $payload['phase'] ?? 'download';

		if ( ! $job_id ) {
			Utils::log( 'JobRunner called with no job_id.' );
			return;
		}

		// Ensure we are on the correct blog in multisite.
		if ( function_exists( 'switch_to_blog' ) && $blog_id !== get_current_blog_id() ) {
			switch_to_blog( $blog_id );
			$switched = true;
		}

		try {
			$job = self::get_job( $job_id, $blog_id );
			if ( ! $job ) {
				Utils::log( 'Job not found.', array( 'job_id' => $job_id ) );
				return;
			}

			// Already in a terminal state — do nothing.
			if ( in_array( $job['status'], array( 'done', 'failed' ), true ) ) {
				return;
			}

			self::dispatch_phase( $job, $phase );
		} catch ( \Throwable $e ) {
			Utils::log(
				'JobRunner uncaught exception.',
				array(
					'job_id' => $job_id,
					'msg' => $e->getMessage(),
				)
			);
			self::set_failed( $job_id, $e->getMessage() );
		} finally {
			if ( ! empty( $switched ) ) {
				restore_current_blog();
			}
		}
	}


	// ── Phases ────────────────────────────────────────────────────────────────

	/**
	 * Dispatch to the correct phase handler.
	 *
	 * Extracted from run() so that adding a third phase ('parse') does not
	 * push that method's cyclomatic complexity over the project limit.
	 *
	 * @param array  $job   Job DB row.
	 * @param string $phase Phase name from the cron payload.
	 */
	private static function dispatch_phase( array $job, string $phase ): void {
		if ( $phase === 'import' ) {
			self::run_import_phase( $job );
		} elseif ( $phase === 'parse' ) {
			self::run_parse_phase( $job );
		} else {
			self::run_download_and_parse_phase( $job );
		}
	}


	/**
	 * Phase 1a: Fetch the source file from Drive then parse it.
	 *
	 * Used for jobs created via the Google Drive picker. Handles the download
	 * step then delegates to run_parse_and_store() for the shared parse pipeline.
	 *
	 * @param array $job Job DB row.
	 */
	private static function run_download_and_parse_phase( array $job ): void {
		$job_id  = (int) $job['id'];
		$user_id = (int) $job['user_id'];

		self::update_status( $job_id, 'downloading' );
		Utils::log(
			'Downloading source file.',
			array(
				'job_id'  => $job_id,
				'file_id' => $job['drive_file_id'],
			)
		);

		$tmp_dir = Utils::tmp_dir();
		if ( is_wp_error( $tmp_dir ) ) {
			self::set_failed( $job_id, $tmp_dir->get_error_message() );
			return;
		}

		$job_tmp_dir = trailingslashit( $tmp_dir ) . 'job_' . $job_id;
		wp_mkdir_p( $job_tmp_dir );

		$summary     = self::decode_summary( $job );
		$source_mime = (string) ( $summary['source_mime'] ?? '' );

		$source_path = DriveClient::fetch_source( $job['drive_file_id'], $source_mime, $user_id, $job_tmp_dir );
		if ( is_wp_error( $source_path ) ) {
			self::set_failed( $job_id, $source_path->get_error_message() );
			Utils::rmdir_recursive( $job_tmp_dir );
			return;
		}

		$img_dir = trailingslashit( $job_tmp_dir ) . 'images';
		wp_mkdir_p( $img_dir );

		self::run_parse_and_store( $job, $source_path, $img_dir, $job_tmp_dir );
	}


	/**
	 * Phase 1b: Parse a locally-uploaded file (no download needed).
	 *
	 * Used for jobs created via the local-file upload endpoint. The source path
	 * is read from result_summary — stored there by the upload endpoint before
	 * the cron was scheduled — and parsing is handed off to run_parse_and_store().
	 *
	 * @param array $job Job DB row.
	 */
	private static function run_parse_phase( array $job ): void {
		$job_id      = (int) $job['id'];
		$summary     = self::decode_summary( $job );
		$source_path = self::source_path( $summary );
		$job_tmp_dir = $summary['job_tmp_dir'] ?? '';

		if ( empty( $source_path ) || ! file_exists( $source_path ) ) {
			self::set_failed( $job_id, 'Uploaded file not found on disk.' );
			return;
		}

		Utils::log( 'Parsing uploaded file.', array( 'job_id' => $job_id ) );

		$img_dir = trailingslashit( $job_tmp_dir ) . 'images';
		wp_mkdir_p( $img_dir );

		self::run_parse_and_store( $job, $source_path, $img_dir, $job_tmp_dir );
	}


	/**
	 * Shared parse + preview + result-summary pipeline.
	 *
	 * Called by both run_download_and_parse_phase() (Drive jobs) and
	 * run_parse_phase() (local-upload jobs). Parses the file at $source_path,
	 * renders a preview, and persists the result_summary for the import phase.
	 *
	 * @param array  $job         Job DB row.
	 * @param string $source_path Absolute path to the source document.
	 * @param string $img_dir     Directory to extract images into.
	 * @param string $job_tmp_dir Root temp directory for this job.
	 */
	private static function run_parse_and_store( array $job, string $source_path, string $img_dir, string $job_tmp_dir ): void {
		$job_id  = (int) $job['id'];
		$user_id = (int) $job['user_id'];

		self::update_status( $job_id, 'parsing' );
		Utils::log(
			'Parsing source document.',
			array(
				'job_id' => $job_id,
				'format' => ParserFactory::detect_format( $source_path ),
			)
		);

		$parsed = ParserFactory::parse( $source_path, $img_dir );
		if ( is_wp_error( $parsed ) ) {
			self::set_failed( $job_id, $parsed->get_error_message() );
			Utils::rmdir_recursive( $job_tmp_dir );
			return;
		}

		$config          = self::load_config( $job );
		$heading_regex   = $config['heading_layout_regex'] ?? '';
		$slide_overrides = isset( $config['slide_overrides'] ) ? json_decode( $config['slide_overrides'], true ) : array();
		$mode            = $config['mode'] ?? 'lesson-only';

		$classified = SlideClassifier::classify( $parsed, $heading_regex, (array) $slide_overrides );

		// Only the fields the slide-map UI needs are kept, so the stored summary
		// stays small even for a long deck.
		$slides_meta = array_map(
			static function ( array $slide ): array {
				return array(
					'index'        => $slide['index'],
					'slide_number' => $slide['slide_number'],
					'title'        => $slide['title'],
					'layout_name'  => $slide['layout_name'],
					'is_hidden'    => $slide['is_hidden'],
					'is_cover'     => $slide['is_cover'],
					'slide_type'   => $slide['slide_type'],
				);
			},
			$classified['slides']
		);

		// Use PreviewRenderer so the same render pipeline is shared with the
		// on-demand refresh in PreviewController::refresh().
		$preview_summary = array(
			'source_path' => $source_path,
			'img_dir'     => $img_dir,
			'config'      => $config,
		);
		$rendered = PreviewRenderer::render_from_summary( $preview_summary );
		if ( ! is_wp_error( $rendered ) ) {
			PreviewController::store( $job_id, $user_id, $rendered );
		}

		// Note: $classified is NOT stored. Re-parsing at import time costs a few
		// seconds and keeps the stored summary to metadata the UI actually reads.
		$summary = self::decode_summary( $job );
		self::update_result_summary(
			$job_id,
			array_merge(
				$summary,
				array(
					'source_path'   => $source_path,
					'source_format' => $parsed['source_format'] ?? '',
					'unit_label'    => $parsed['unit_label'] ?? 'slide',
					'img_dir'       => $img_dir,
					'job_tmp_dir'   => $job_tmp_dir,
					'mode'          => $mode,
					'config'        => $config,
					'slides_meta'   => $slides_meta,
				)
			)
		);

		self::update_status( $job_id, 'parsed' );
		Utils::log( 'Parse complete. Awaiting user import trigger.', array( 'job_id' => $job_id ) );
	}


	/**
	 * Phase 2: Import parsed content into LearnDash.
	 *
	 * The classified deck is not carried across from the parse phase, so the
	 * source document is re-read from the stored path and re-classified with
	 * whatever config the editor settled on in the preview UI.
	 *
	 * @param array $job Job DB row.
	 */
	private static function run_import_phase( array $job ): void {
		$job_id  = (int) $job['id'];
		$summary = self::decode_summary( $job );

		self::update_status( $job_id, 'importing' );

		$parsed = self::reparse_for_import( $job_id, $summary );
		if ( $parsed === null ) {
			return;
		}

		$classified = self::classify_from_summary( $parsed, $summary );

		// Make deck_name available to the importer for the default lesson title.
		$summary['deck_name'] = $job['deck_name'] ?? '';

		$importer = new LearnDashImporter();
		$result   = $importer->import( $classified, $summary );

		if ( is_wp_error( $result ) ) {
			self::set_failed( $job_id, $result->get_error_message() );
		} else {
			self::record_import_result( $job_id, $summary, $result );
		}

		// Clean up temp files after import.
		if ( ! empty( $summary['job_tmp_dir'] ) ) {
			Utils::rmdir_recursive( $summary['job_tmp_dir'] );
		}
	}


	/**
	 * Re-read the source document for the import phase.
	 *
	 * Marks the job failed and returns null when the file is gone or unreadable,
	 * so the caller only has one outcome to check.
	 *
	 * @param  int   $job_id  Job ID.
	 * @param  array $summary Decoded result_summary.
	 * @return array|null ParsedDeck, or null when the job has been failed.
	 */
	private static function reparse_for_import( int $job_id, array $summary ): ?array {
		$source_path = self::source_path( $summary );

		if ( empty( $source_path ) || ! file_exists( $source_path ) ) {
			self::set_failed( $job_id, 'Source file not found. Please re-run the job from scratch.' );
			return null;
		}

		$parsed = ParserFactory::parse( $source_path, $summary['img_dir'] ?? '' );
		if ( is_wp_error( $parsed ) ) {
			self::set_failed( $job_id, $parsed->get_error_message() );
			return null;
		}

		return $parsed;
	}


	/**
	 * Persist the outcome of a successful import.
	 *
	 * Skipped IDs are written back into result_summary so the UI can explain
	 * that an existing post was found and that Overwrite would update it,
	 * rather than reporting a bare "0 created".
	 *
	 * @param int   $job_id  Job ID.
	 * @param array $summary Decoded result_summary.
	 * @param array $result  Importer result.
	 */
	private static function record_import_result( int $job_id, array $summary, array $result ): void {
		$post_ids    = $result['created_post_ids'] ?? array();
		$skipped_ids = $result['skipped_post_ids'] ?? array();

		self::update_status( $job_id, 'done' );
		self::update_created_posts( $job_id, $post_ids );

		if ( ! empty( $skipped_ids ) ) {
			$summary['skipped_post_ids'] = array_values( array_map( 'intval', $skipped_ids ) );
			self::update_result_summary( $job_id, $summary );
		}

		Utils::log(
			'Import complete.',
			array(
				'job_id'  => $job_id,
				'posts'   => count( $post_ids ),
				'skipped' => count( $skipped_ids ),
			)
		);
	}


	/**
	 * Re-classify a freshly parsed deck using the config stored in a job summary.
	 *
	 * Extracted to keep run_import_phase() within cyclomatic complexity limits.
	 *
	 * @param array $parsed  ParsedDeck from Parser::parse().
	 * @param array $summary Decoded result_summary from the DB row.
	 * @return array Classified deck.
	 */
	private static function classify_from_summary( array $parsed, array $summary ): array {
		$config          = $summary['config'] ?? array();
		$heading_regex   = $config['heading_layout_regex'] ?? '';
		$slide_overrides = array();

		if ( ! empty( $config['slide_overrides'] ) ) {
			$slide_overrides = (array) json_decode( $config['slide_overrides'], true );
		}

		return SlideClassifier::classify( $parsed, $heading_regex, $slide_overrides );
	}


	// ── Summary helpers ───────────────────────────────────────────────────────

	/**
	 * Read the source document's path out of a job summary.
	 *
	 * Falls back to the legacy `pptx_path` key so jobs queued before multi-format
	 * support was added still resolve after an upgrade.
	 *
	 * @param  array $summary Decoded result_summary.
	 * @return string Absolute path, or '' when the summary holds neither key.
	 */
	public static function source_path( array $summary ): string {
		return (string) ( $summary['source_path'] ?? $summary['pptx_path'] ?? '' );
	}


	/**
	 * JSON-decode a job row's result_summary into a safe array.
	 *
	 * @param  array $job Job DB row.
	 * @return array
	 */
	private static function decode_summary( array $job ): array {
		$decoded = json_decode( $job['result_summary'] ?? '{}', true );
		return is_array( $decoded ) ? $decoded : array();
	}


	// ── DB helpers ────────────────────────────────────────────────────────────

	/** Fetch a job row. */
	private static function get_job( int $job_id, int $blog_id ): ?array {
		global $wpdb;
		$table = $wpdb->prefix . 'cbf_slide_import_jobs';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND blog_id = %d", $job_id, $blog_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
	}


	/** Update a job's status column. */
	private static function update_status( int $job_id, string $status ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->prefix . 'cbf_slide_import_jobs',
			array( 'status' => $status ),
			array( 'id' => $job_id ),
			array( '%s' ),
			array( '%d' )
		);
	}


	/** Mark a job as failed with an error message. */
	private static function set_failed( int $job_id, string $message ): void {
		global $wpdb;
		// Redact any path information from the stored error message.
		$safe_message = preg_replace( '/\/[^\s]+/', '[path redacted]', $message );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->prefix . 'cbf_slide_import_jobs',
			array(
				'status' => 'failed',
				'error_message' => $safe_message,
			),
			array( 'id' => $job_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		Utils::log(
			'Job failed.',
			array(
				'job_id' => $job_id,
				'error' => $safe_message,
			)
		);
	}


	/** Store serialised result summary. */
	private static function update_result_summary( int $job_id, array $data ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->prefix . 'cbf_slide_import_jobs',
			array( 'result_summary' => wp_json_encode( $data ) ),
			array( 'id' => $job_id ),
			array( '%s' ),
			array( '%d' )
		);
	}


	/** Store created post IDs after successful import. */
	private static function update_created_posts( int $job_id, array $post_ids ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->prefix . 'cbf_slide_import_jobs',
			array( 'created_post_ids' => wp_json_encode( $post_ids ) ),
			array( 'id' => $job_id ),
			array( '%s' ),
			array( '%d' )
		);
	}


	/**
	 * Load config for a job, if a config_id is set.
	 *
	 * @param  array $job Job DB row.
	 * @return array Config row, or empty defaults.
	 */
	private static function load_config( array $job ): array {
		if ( empty( $job['config_id'] ) ) {
			return array();
		}
		global $wpdb;
		$table = $wpdb->prefix . 'cbf_slide_import_configs';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND blog_id = %d", $job['config_id'], $job['blog_id'] ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return $row ?: array();
	}
}
