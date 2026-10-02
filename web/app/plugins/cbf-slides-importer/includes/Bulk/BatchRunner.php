<?php
/**
 * Drives a bulk migration: queues rows, advances the batch, closes it out.
 *
 * Rows run **one at a time**. Each job's completion schedules the next, and
 * nothing runs in parallel. That is slower than it could be and deliberately
 * so — a lesson's section membership is decided by its position in the course's
 * lesson list, so concurrent completions would interleave orderings and land
 * content under the wrong headings. Sequential execution also keeps Drive
 * inside its rate limits and bounds peak memory to a single document parse.
 *
 * Section headings are created once, here, before the first job runs: they all
 * live in one post-meta value, so per-job creation would lose headings to
 * concurrent writes.
 *
 * @class   Bulk\BatchRunner
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Bulk;

use CodingBlackFemales\SlidesImporter\Import\JobRunner;
use CodingBlackFemales\SlidesImporter\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BatchRunner class.
 */
final class BatchRunner {

	/**
	 * Seconds to leave between rows.
	 *
	 * Enough to keep a long batch from monopolising WP-Cron, small enough that
	 * it adds only a couple of minutes across a 120-row migration.
	 */
	const ROW_DELAY = 2;


	/**
	 * Begin a confirmed batch.
	 *
	 * Creates the section headings the plan needs, then queues the first row.
	 *
	 * @param  int $batch_id Batch ID.
	 * @return bool Whether anything was queued.
	 */
	public static function start( int $batch_id ): bool {
		$batch = BatchRepository::find( $batch_id );

		if ( ! $batch || $batch['status'] !== BatchRepository::STATUS_AWAITING ) {
			return false;
		}

		$plan   = BatchRepository::decode( $batch, 'plan' );
		$report = BatchReport::from_plan( $plan['rows'] ?? array() );

		$headings = SectionHeadings::ensure( (int) $batch['course_id'], $plan['headings'] ?? array() );

		BatchRepository::update(
			$batch_id,
			array(
				'status' => BatchRepository::STATUS_RUNNING,
				'plan'   => array_merge( $plan, array( 'heading_ids' => $headings ) ),
				'report' => $report,
			)
		);

		Utils::log(
			'Bulk batch started.',
			array(
				'batch_id' => $batch_id,
				'rows'     => count( $report ),
			)
		);

		return self::advance( $batch_id );
	}


	/**
	 * Queue the next unstarted row, or finish the batch.
	 *
	 * Called after every job in the batch reaches a terminal state, which is
	 * what makes execution sequential without a lock.
	 *
	 * @param  int $batch_id Batch ID.
	 * @return bool Whether a row was queued.
	 */
	public static function advance( int $batch_id ): bool {
		$batch = BatchRepository::find( $batch_id );

		if ( ! $batch || $batch['status'] !== BatchRepository::STATUS_RUNNING ) {
			return false;
		}

		$plan   = BatchRepository::decode( $batch, 'plan' );
		$report = BatchRepository::decode( $batch, 'report' );
		$next   = self::next_row( $plan['rows'] ?? array(), $report );

		if ( $next === null ) {
			self::finish( $batch, $report );
			return false;
		}

		$parent = self::parent_post( $next, $report, (int) $batch['course_id'] );

		if ( is_string( $parent ) ) {
			$report = BatchReport::record( $report, (int) $next['line'], BatchReport::OUTCOME_FAILED, array( 'detail' => $parent ) );
			BatchRepository::update( $batch_id, array( 'report' => $report ) );

			return self::advance( $batch_id );
		}

		$job_id = self::queue_row( $batch, $next, $parent );

		if ( $job_id === 0 ) {
			$report = BatchReport::record(
				$report,
				(int) $next['line'],
				BatchReport::OUTCOME_FAILED,
				array( 'detail' => __( 'The import job could not be created.', 'cbf-slides-importer' ) )
			);
			BatchRepository::update( $batch_id, array( 'report' => $report ) );

			return self::advance( $batch_id );
		}

		BatchRepository::update(
			$batch_id,
			array( 'report' => BatchReport::record( $report, (int) $next['line'], BatchReport::OUTCOME_PENDING, array( 'job_id' => $job_id ) ) )
		);

		return true;
	}


	/**
	 * Record a finished job's outcome and move the batch on.
	 *
	 * @param int    $batch_id Batch ID.
	 * @param int    $line     CSV line the job came from.
	 * @param string $outcome  A BatchReport::OUTCOME_* value.
	 * @param array  $extra    Any of: detail, post_id.
	 */
	public static function complete_row( int $batch_id, int $line, string $outcome, array $extra = array() ): void {
		$batch = BatchRepository::find( $batch_id );

		if ( ! $batch ) {
			return;
		}

		$report = BatchRepository::decode( $batch, 'report' );

		// A row can reach a terminal state by more than one route — a failing
		// import both marks the job failed and returns a WP_Error — so recording
		// is idempotent. Advancing twice would put two rows in flight at once
		// and undo the ordering guarantee the whole design rests on.
		if ( ! self::is_pending( $report, $line ) ) {
			return;
		}

		$report = BatchReport::record( $report, $line, $outcome, $extra );

		BatchRepository::update( $batch_id, array( 'report' => $report ) );

		// Position what exists so far before starting the next row. Doing this
		// once at the end left the course builder scrambled for the length of a
		// run — headings created against an empty course sit at indices 0..n,
		// so every lesson lands under the wrong one until the pass runs — and a
		// batch that was cancelled or abandoned never ran it at all. Rows are
		// sequential, so there is no interleaving to guard against; the pass is
		// idempotent, and re-running it per row simply keeps the course correct
		// at every point a person might look at it.
		SectionHeadings::place_lessons( (int) $batch['course_id'], self::placements( $batch, $report ) );

		self::advance( $batch_id );
	}


	/**
	 * Whether a row is still awaiting its outcome.
	 *
	 * @param  array $report Report entries.
	 * @param  int   $line   CSV line.
	 * @return bool
	 */
	private static function is_pending( array $report, int $line ): bool {
		foreach ( $report as $entry ) {
			if ( (int) $entry['line'] === $line ) {
				return $entry['outcome'] === BatchReport::OUTCOME_PENDING;
			}
		}

		return false;
	}


	/**
	 * Stop a batch without touching what it has already created.
	 *
	 * @param  int $batch_id Batch ID.
	 * @return bool Whether the batch was cancellable.
	 */
	public static function cancel( int $batch_id ): bool {
		$batch = BatchRepository::find( $batch_id );

		if ( ! $batch || in_array( $batch['status'], BatchRepository::TERMINAL, true ) ) {
			return false;
		}

		$report = BatchRepository::decode( $batch, 'report' );

		foreach ( $report as $index => $entry ) {
			if ( $entry['outcome'] === BatchReport::OUTCOME_PENDING ) {
				$report[ $index ]['outcome'] = BatchReport::OUTCOME_SKIPPED;
				$report[ $index ]['detail']  = __( 'Cancelled before this row ran.', 'cbf-slides-importer' );
			}
		}

		BatchRepository::update(
			$batch_id,
			array(
				'status' => BatchRepository::STATUS_CANCELLED,
				'report' => $report,
			)
		);

		// Leave the course tidy. Rows are placed as they finish, but a row that
		// was mid-flight when the cancel landed may still report afterwards.
		SectionHeadings::place_lessons( (int) $batch['course_id'], self::placements( $batch, $report ) );

		Utils::log( 'Bulk batch cancelled.', array( 'batch_id' => $batch_id ) );

		return true;
	}


	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * The first planned row that has not been attempted.
	 *
	 * @param  array $rows   PlannedRow arrays.
	 * @param  array $report Report entries.
	 * @return array|null
	 */
	private static function next_row( array $rows, array $report ): ?array {
		$pending = array();

		foreach ( $report as $entry ) {
			if ( $entry['outcome'] === BatchReport::OUTCOME_PENDING && (int) $entry['job_id'] === 0 ) {
				$pending[ (int) $entry['line'] ] = true;
			}
		}

		foreach ( $rows as $row ) {
			if ( isset( $pending[ (int) $row['line'] ] ) ) {
				return $row;
			}
		}

		return null;
	}


	/**
	 * The import mode a row runs in.
	 *
	 * @param  array $row PlannedRow.
	 * @return string
	 */
	private static function mode_for( array $row ): string {
		return match ( $row['type'] ) {
			CsvParser::TYPE_TOPIC => 'topic',
			CsvParser::TYPE_QUIZ  => JobRunner::QUIZ_MODE,
			default               => 'lesson-only',
		};
	}


	/**
	 * The post a topic or quiz row goes under, now that earlier rows have run.
	 *
	 * A parent that already existed was resolved to an ID at pre-flight. One
	 * created by an earlier row only has an ID once that row has finished, so
	 * it is read from the row's report entry here. A parent row that did not
	 * produce a post in this course leaves nothing to attach to, and the
	 * dependent row fails with the reason rather than importing unparented.
	 *
	 * @param  array $row       PlannedRow about to be queued.
	 * @param  array $report    Report entries.
	 * @param  int   $course_id Course the batch imports into.
	 * @return array|string { id, type }, empty for a session; or why the parent
	 *                      is unavailable.
	 */
	private static function parent_post( array $row, array $report, int $course_id ): array|string {
		if ( $row['type'] === CsvParser::TYPE_SESSION ) {
			return array();
		}

		$parent = $row['parent'] ?? null;

		// Batches planned before parents were named by title carry only an ID.
		if ( ! is_array( $parent ) ) {
			return array(
				'id'   => (int) $row['session_id'],
				'type' => (string) ( $row['parent_type'] ?? BatchPlanner::POST_TYPE_SESSION ),
			);
		}

		if ( (int) $parent['id'] > 0 ) {
			return $parent;
		}

		$entry = self::entry_for_line( $report, (int) $parent['line'] );
		$id    = (int) ( $entry['post_id'] ?? 0 );

		if ( $id > 0 && self::is_usable_parent( $entry, $id, (string) $parent['type'], $course_id ) ) {
			return array_merge( $parent, array( 'id' => $id ) );
		}

		return sprintf(
			/* translators: 1: parent title, 2: line number, 3: that row's outcome */
			__( 'The parent "%1$s" on line %2$d was not imported (%3$s), so there is nothing to attach this row to.', 'cbf-slides-importer' ),
			$parent['title'],
			$parent['line'],
			$entry['outcome'] ?? BatchReport::OUTCOME_FAILED
		);
	}


	/**
	 * Whether a finished parent row left a post this row can go under.
	 *
	 * Created, updated and reused posts are all in the course by the time the
	 * row reports. A skipped row also names a post — the one whose title it
	 * matched — but that is only the right parent if it is already part of this
	 * course; if it belongs to another course, attaching to it would put this
	 * row's content somewhere the editor is not looking.
	 *
	 * @param  array  $entry     The parent row's report entry.
	 * @param  int    $id        Post ID it reported.
	 * @param  string $type      Expected post type.
	 * @param  int    $course_id Course the batch imports into.
	 * @return bool
	 */
	private static function is_usable_parent( array $entry, int $id, string $type, int $course_id ): bool {
		$imported = array( BatchReport::OUTCOME_CREATED, BatchReport::OUTCOME_UPDATED, BatchReport::OUTCOME_REUSED );

		if ( in_array( $entry['outcome'], $imported, true ) ) {
			return true;
		}

		if ( $entry['outcome'] !== BatchReport::OUTCOME_SKIPPED || ! function_exists( 'learndash_course_get_steps_by_type' ) ) {
			return false;
		}

		return in_array( $id, array_map( 'intval', (array) learndash_course_get_steps_by_type( $course_id, $type ) ), true );
	}


	/**
	 * A report entry by CSV line.
	 *
	 * @param  array $report Report entries.
	 * @param  int   $line   CSV line.
	 * @return array|null
	 */
	private static function entry_for_line( array $report, int $line ): ?array {
		foreach ( $report as $entry ) {
			if ( (int) $entry['line'] === $line ) {
				return $entry;
			}
		}

		return null;
	}


	/**
	 * Create and schedule the job for one row.
	 *
	 * The job carries its own config, so from here on it is an ordinary import
	 * job — the only difference is `batch_id`, which tells JobRunner to import
	 * without waiting for a human to press the button.
	 *
	 * @param  array $batch  Batch row.
	 * @param  array $row    PlannedRow.
	 * @param  array $parent { id, type } of the post a topic or quiz goes under;
	 *                       empty for a session.
	 * @return int Job ID, or 0 on failure.
	 */
	private static function queue_row( array $batch, array $row, array $parent ): int {
		global $wpdb;

		$plan        = BatchRepository::decode( $batch, 'plan' );
		$heading_ids = $plan['heading_ids'] ?? array();
		$heading_key = strtolower( (string) $row['heading'] );

		$config = array(
			'mode'       => self::mode_for( $row ),
			'course_id'  => (int) $batch['course_id'],
			'lesson_id'  => (int) ( $parent['id'] ?? 0 ),
			'post_title' => (string) $row['title'],
			'overwrite'  => ! empty( $batch['overwrite'] ),
		);

		// A quiz's parent may be a topic; the importer wants that as topic_id.
		if ( $row['type'] === CsvParser::TYPE_QUIZ && ( $parent['type'] ?? '' ) === BatchPlanner::POST_TYPE_TOPIC ) {
			$config['topic_id'] = (int) $parent['id'];
		}

		$summary = array(
			'source_mime' => (string) $row['mime_type'],
			'batch'       => array(
				'batch_id'   => (int) $batch['id'],
				'line'       => (int) $row['line'],
				'section_id' => (int) ( $heading_ids[ $heading_key ] ?? 0 ),
			),
			'config'      => $config,
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			$wpdb->prefix . 'cbf_slide_import_jobs',
			array(
				'blog_id'        => (int) $batch['blog_id'],
				'user_id'        => (int) $batch['user_id'],
				'drive_file_id'  => (string) $row['file_id'],
				'deck_name'      => (string) $row['title'],
				'status'         => 'pending',
				'result_summary' => wp_json_encode( $summary ),
				'batch_id'       => (int) $batch['id'],
				'batch_row'      => (int) $row['line'],
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d' )
		);

		if ( $inserted === false ) {
			Utils::log(
				'Could not queue a bulk row.',
				array(
					'batch_id' => (int) $batch['id'],
					'line'     => (int) $row['line'],
				)
			);
			return 0;
		}

		$job_id = (int) $wpdb->insert_id;

		wp_schedule_single_event(
			time() + self::ROW_DELAY,
			JobRunner::CRON_HOOK,
			array(
				array(
					'job_id'  => $job_id,
					'blog_id' => (int) $batch['blog_id'],
				),
			)
		);

		return $job_id;
	}


	/**
	 * Close out a batch and place its lessons under their headings.
	 *
	 * Ordering happens here, once, rather than per job — see the class comment.
	 *
	 * @param array $batch  Batch row.
	 * @param array $report Report entries.
	 */
	private static function finish( array $batch, array $report ): void {
		$batch_id = (int) $batch['id'];

		// complete_row() has already placed every row as it finished; this final
		// pass costs one more write and covers a batch whose last row never
		// reported, so the course is never left half-ordered.
		SectionHeadings::place_lessons( (int) $batch['course_id'], self::placements( $batch, $report ) );

		$status = BatchReport::has_problems( $report )
			? BatchRepository::STATUS_WITH_ERRORS
			: BatchRepository::STATUS_DONE;

		BatchRepository::update( $batch_id, array( 'status' => $status ) );

		Utils::log(
			'Bulk batch finished.',
			array_merge(
				array(
					'batch_id' => $batch_id,
					'status'   => $status,
				),
				BatchReport::summarise( $report )
			)
		);
	}


	/**
	 * Map each section heading to the lessons this batch created under it.
	 *
	 * @param  array $batch  Batch row.
	 * @param  array $report Report entries.
	 * @return array<int, int[]> Lesson IDs keyed by section ID.
	 */
	private static function placements( array $batch, array $report ): array {
		$plan       = BatchRepository::decode( $batch, 'plan' );
		$headings   = $plan['heading_ids'] ?? array();
		$placements = array();

		foreach ( $report as $entry ) {
			// A reused row belongs in the course exactly as a created one does:
			// with shared steps, placing it is what attaches it.
			$created = in_array(
				$entry['outcome'],
				array( BatchReport::OUTCOME_CREATED, BatchReport::OUTCOME_UPDATED, BatchReport::OUTCOME_REUSED ),
				true
			);

			if ( ! $created || $entry['type'] !== CsvParser::TYPE_SESSION || (int) $entry['post_id'] === 0 ) {
				continue;
			}

			$section_id = (int) ( $headings[ strtolower( (string) $entry['heading'] ) ] ?? 0 );

			if ( $section_id !== 0 ) {
				$placements[ $section_id ][] = (int) $entry['post_id'];
			}
		}

		return $placements;
	}
}
