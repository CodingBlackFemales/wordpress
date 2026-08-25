<?php
/**
 * Background job runner — WP-Cron handler.
 *
 * Fired by wp_schedule_single_event() from JobController::create().
 * Orchestrates: download → parse → store preview → (on import trigger) import.
 *
 * @class   Import\JobRunner
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Import;

use CodingBlackFemales\SlidesImporter\Api\PreviewController;
use CodingBlackFemales\SlidesImporter\Google\DriveClient;
use CodingBlackFemales\SlidesImporter\Pptx\Parser;
use CodingBlackFemales\SlidesImporter\Pptx\SlideClassifier;
use CodingBlackFemales\SlidesImporter\Pptx\BlockRenderer;
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

			if ( $phase === 'import' ) {
				self::run_import_phase( $job );
			} else {
				self::run_download_and_parse_phase( $job );
			}
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
	 * Phase 1: Download PPTX from Drive, parse it, store preview.
	 *
	 * @param array $job Job DB row.
	 */
	private static function run_download_and_parse_phase( array $job ): void {
		$job_id  = (int) $job['id'];
		$user_id = (int) $job['user_id'];

		// ── Download ──────────────────────────────────────────────────────────
		self::update_status( $job_id, 'downloading' );
		Utils::log(
			'Downloading PPTX.',
			array(
				'job_id' => $job_id,
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

		$pptx_path = DriveClient::export_pptx( $job['drive_file_id'], $user_id, $job_tmp_dir );
		if ( is_wp_error( $pptx_path ) ) {
			self::set_failed( $job_id, $pptx_path->get_error_message() );
			Utils::rmdir_recursive( $job_tmp_dir );
			return;
		}

		// ── Parse ─────────────────────────────────────────────────────────────
		self::update_status( $job_id, 'parsing' );
		Utils::log( 'Parsing PPTX.', array( 'job_id' => $job_id ) );

		$img_dir = trailingslashit( $job_tmp_dir ) . 'images';
		wp_mkdir_p( $img_dir );

		$parsed = Parser::parse( $pptx_path, $img_dir );
		if ( is_wp_error( $parsed ) ) {
			self::set_failed( $job_id, $parsed->get_error_message() );
			Utils::rmdir_recursive( $job_tmp_dir );
			return;
		}

		// ── Load config and classify ──────────────────────────────────────────
		$config          = self::load_config( $job );
		$heading_regex   = $config['heading_layout_regex'] ?? '';
		$slide_overrides = isset( $config['slide_overrides'] ) ? json_decode( $config['slide_overrides'], true ) : array();
		$mode            = $config['mode'] ?? 'lesson-only';

		$classified = SlideClassifier::classify( $parsed, $heading_regex, (array) $slide_overrides );

		// ── Render preview ────────────────────────────────────────────────────
		$rendered = BlockRenderer::render( $classified, $mode );
		PreviewController::store( $job_id, $user_id, $rendered );

		// ── Store tmp paths in result_summary for import phase ────────────────
		// Note: $classified is NOT stored here — PhpPresentation shape objects
		// cannot survive JSON serialisation. The import phase re-parses from disk.
		self::update_result_summary(
			$job_id,
			array(
				'pptx_path'   => $pptx_path,
				'img_dir'     => $img_dir,
				'job_tmp_dir' => $job_tmp_dir,
				'mode'        => $mode,
				'config'      => $config,
			)
		);

		// Status 'parsed' — waiting for user to confirm and trigger import.
		self::update_status( $job_id, 'parsed' );
		Utils::log( 'Parse complete. Awaiting user import trigger.', array( 'job_id' => $job_id ) );
	}


	/**
	 * Phase 2: Import parsed content into LearnDash.
	 *
	 * PHP objects (PhpPresentation shape instances) cannot survive JSON
	 * serialisation, so we re-parse the PPTX from the stored path rather than
	 * relying on the `classified` value that was JSON-encoded at parse time.
	 *
	 * @param array $job Job DB row.
	 */
	private static function run_import_phase( array $job ): void {
		$job_id  = (int) $job['id'];
		$summary = json_decode( $job['result_summary'], true );

		self::update_status( $job_id, 'importing' );

		$pptx_path = $summary['pptx_path'] ?? '';
		if ( empty( $pptx_path ) || ! file_exists( $pptx_path ) ) {
			self::set_failed( $job_id, 'PPTX file not found. Please re-run the job from scratch.' );
			return;
		}

		// Re-parse: shape objects are not JSON-serialisable, so we rebuild from disk.
		$img_dir = $summary['img_dir'] ?? '';
		$parsed  = Parser::parse( $pptx_path, $img_dir );
		if ( is_wp_error( $parsed ) ) {
			self::set_failed( $job_id, $parsed->get_error_message() );
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
			self::update_status( $job_id, 'done' );
			$post_ids = $result['created_post_ids'] ?? array();
			self::update_created_posts( $job_id, $post_ids );
			Utils::log(
				'Import complete.',
				array(
					'job_id' => $job_id,
					'posts'  => count( $post_ids ),
				)
			);
		}

		// Clean up temp files after import.
		if ( ! empty( $summary['job_tmp_dir'] ) ) {
			Utils::rmdir_recursive( $summary['job_tmp_dir'] );
		}
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
