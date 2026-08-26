<?php
/**
 * Periodic cleanup: stale job reset and orphaned temp file purge.
 *
 * @class   Import\Janitor
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Import;

use CodingBlackFemales\SlidesImporter\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Janitor class.
 *
 * Runs on a scheduled hourly WP-Cron event (`cbf_si_cleanup`):
 *
 * 1. Stale job reset — any job that has been in-flight (downloading, parsing,
 *    or importing) for more than STALE_MINUTES without a status change is most
 *    likely stuck (e.g. PHP OOM, server crash). It is reset to `pending` with
 *    a note in error_message so the user can see what happened and the job
 *    can be retried by the next WP-Cron tick.
 *
 * 2. Orphaned temp file purge — per-job temp directories under cbf-slides-tmp/
 *    older than TMP_MAX_HOURS are deleted. This covers jobs whose normal
 *    cleanup call failed and jobs whose temp files were left after a crash.
 *
 * Relationships:
 *  - STALE_MINUTES (30) < TMP_MAX_HOURS * 60 (120): a job reset to `pending`
 *    at 30 min will be re-processed before its temp dir (created at job start)
 *    is purged at 2 h, so a re-run can still find its previous PPTX if needed.
 */
final class Janitor {

	/** WP-Cron event hook name. */
	const CLEANUP_HOOK = 'cbf_si_cleanup';

	/**
	 * Jobs idle in an in-flight status longer than this many minutes are
	 * considered stale and reset to `pending` for retry.
	 */
	const STALE_MINUTES = 30;

	/**
	 * Job temp directories older than this many hours are deleted.
	 */
	const TMP_MAX_HOURS = 2;

	/**
	 * Statuses that indicate a job is actively processing.
	 *
	 * A job stuck in one of these past STALE_MINUTES is eligible for a reset.
	 * `parsed` is intentionally excluded — it is a stable waiting state, not
	 * in-flight processing.
	 */
	const IN_FLIGHT_STATUSES = array( 'downloading', 'parsing', 'importing' );


	/**
	 * Register the WP-Cron hook.
	 */
	public static function hooks(): void {
		add_action( self::CLEANUP_HOOK, array( __CLASS__, 'run' ) );
	}


	/**
	 * Cron callback — runs all cleanup tasks in sequence.
	 */
	public static function run(): void {
		self::reset_stale_jobs();
		self::purge_orphaned_tmp_dirs();
	}


	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Reset any job stuck in an in-flight status for more than STALE_MINUTES.
	 *
	 * The job is returned to `pending` so WP-Cron retries it from the download
	 * phase on the next scheduled tick. A human-readable note is stored in
	 * `error_message` so users can see what happened without digging into logs.
	 */
	private static function reset_stale_jobs(): void {
		global $wpdb;
		$table  = $wpdb->prefix . 'cbf_slide_import_jobs';
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - self::STALE_MINUTES * MINUTE_IN_SECONDS );

		// Status values are hardcoded constants — not user input — so inlining
		// them in the IN() clause is safe. The table name is also not user input.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$stale_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table}
				 WHERE status IN ('downloading','parsing','importing')
				 AND updated_at < %s",
				$cutoff
			)
		);

		if ( empty( $stale_ids ) ) {
			return;
		}

		$note = sprintf(
			/* translators: %d: number of minutes */
			__( 'Job timed out after %d minutes with no progress and has been reset for retry.', 'cbf-slides-importer' ),
			self::STALE_MINUTES
		);

		foreach ( $stale_ids as $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table,
				array(
					'status'        => 'pending',
					'error_message' => $note,
				),
				array( 'id' => (int) $id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
			Utils::log( 'Stale job reset to pending.', array( 'job_id' => (int) $id ) );
		}
	}


	/**
	 * Delete per-job temp directories older than TMP_MAX_HOURS.
	 *
	 * Only `job_N` subdirectories inside cbf-slides-tmp/ are removed; the root
	 * directory and its index.php sentinel are never touched.
	 */
	private static function purge_orphaned_tmp_dirs(): void {
		$base = Utils::tmp_dir();
		if ( is_wp_error( $base ) ) {
			return;
		}

		$cutoff = time() - self::TMP_MAX_HOURS * HOUR_IN_SECONDS;
		$base   = trailingslashit( $base );

		foreach ( (array) scandir( $base ) as $entry ) {
			// Only process job_N subdirectories created by JobRunner.
			if ( strpos( $entry, 'job_' ) !== 0 ) {
				continue;
			}

			$dir = $base . $entry;
			if ( ! is_dir( $dir ) ) {
				continue;
			}

			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$mtime = @filemtime( $dir );
			if ( $mtime === false || $mtime >= $cutoff ) {
				continue;
			}

			Utils::rmdir_recursive( $dir );
			Utils::log( 'Purged stale tmp dir.', array( 'dir' => $entry ) );
		}
	}
}
