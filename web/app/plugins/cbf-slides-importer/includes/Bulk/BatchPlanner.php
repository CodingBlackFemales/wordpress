<?php
/**
 * Resolves parsed CSV rows against a course, without writing anything.
 *
 * This is the pre-flight pass: it turns "row 14 says topic, session 412, this
 * Drive URL" into either a concrete action or a stated reason why not. Nothing
 * it does is destructive, so an editor can upload a file, read the report, fix
 * the spreadsheet and upload again at no cost.
 *
 * It exists because roughly a sixth of the real migration rows point at things
 * that cannot become lesson content — Google Forms, repositories, blank cells —
 * and finding that out mid-import, after some content already exists, would be
 * far worse than finding out beforehand.
 *
 * @class   Bulk\BatchPlanner
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Bulk;

use CodingBlackFemales\SlidesImporter\Document\ParserFactory;
use CodingBlackFemales\SlidesImporter\Google\DriveClient;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BatchPlanner class.
 *
 * Produces `PlannedRow` arrays — a ParsedRow plus:
 * {
 *   status:     'ready' | 'error',
 *   errors:     string[],
 *   notices:    string[],
 *   file_id:    string,
 *   mime_type:  string,
 *   format:     string,   // pptx | pdf | docx
 *   post_type:  string,   // sfwd-lessons | sfwd-topic
 * }
 */
final class BatchPlanner {

	/** LearnDash post types the importer creates. */
	const POST_TYPE_SESSION = 'sfwd-lessons';
	const POST_TYPE_TOPIC   = 'sfwd-topic';


	/**
	 * Resolve every parsed row against the target course.
	 *
	 * @param  array $rows      ParsedRow arrays from CsvParser.
	 * @param  int   $course_id Target course post ID.
	 * @param  int   $user_id   User whose Drive credentials resolve the files.
	 * @return array{rows: array, headings: string[], counts: array<string, int>}
	 */
	public static function plan( array $rows, int $course_id, int $user_id ): array {
		$sessions = self::course_sessions( $course_id );
		$sections = SectionHeadings::read( $course_id );

		$planned  = array();
		$headings = array();

		foreach ( $rows as $row ) {
			$planned_row = self::plan_row( $row, $course_id, $user_id, $sessions, $sections );
			$planned[]   = $planned_row;

			if ( $planned_row['status'] === 'ready' && $planned_row['heading'] !== '' ) {
				$headings[ strtolower( $planned_row['heading'] ) ] = $planned_row['heading'];
			}
		}

		return array(
			'rows'     => $planned,
			'headings' => array_values( $headings ),
			'counts'   => self::counts( $planned ),
		);
	}


	/**
	 * Summarise a planned set by status.
	 *
	 * @param  array $rows PlannedRow arrays.
	 * @return array{ready: int, error: int, total: int}
	 */
	public static function counts( array $rows ): array {
		$ready = 0;

		foreach ( $rows as $row ) {
			if ( ( $row['status'] ?? '' ) === 'ready' ) {
				++$ready;
			}
		}

		return array(
			'ready' => $ready,
			'error' => count( $rows ) - $ready,
			'total' => count( $rows ),
		);
	}


	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Resolve one row.
	 *
	 * @param  array $row       ParsedRow.
	 * @param  int   $course_id Target course.
	 * @param  int   $user_id   Drive credential owner.
	 * @param  array $sessions  Lesson IDs in the course, keyed by ID.
	 * @param  array $sections  The course's existing headings.
	 * @return array PlannedRow.
	 */
	private static function plan_row( array $row, int $course_id, int $user_id, array $sessions, array $sections ): array {
		$planned = array_merge(
			$row,
			array(
				'status'    => 'error',
				'file_id'   => $row['source']['file_id'] ?? '',
				'mime_type' => '',
				'format'    => '',
				'post_type' => $row['type'] === CsvParser::TYPE_TOPIC ? self::POST_TYPE_TOPIC : self::POST_TYPE_SESSION,
			)
		);

		// Rows the CSV parser already rejected need no further work.
		if ( $planned['errors'] !== array() ) {
			return $planned;
		}

		self::check_parent_session( $planned, $sessions, $course_id );
		self::note_existing_heading( $planned, $sections );
		self::resolve_source( $planned, $user_id );

		$planned['status'] = $planned['errors'] === array() ? 'ready' : 'error';

		return $planned;
	}


	/**
	 * Confirm a topic's parent session exists and belongs to this course.
	 *
	 * A topic pointing at a session in another course would import somewhere the
	 * editor is not looking, which is worse than refusing the row.
	 *
	 * @param array $row       PlannedRow, updated in place.
	 * @param array $sessions  Lesson IDs in the course, keyed by ID.
	 * @param int   $course_id Target course.
	 */
	private static function check_parent_session( array &$row, array $sessions, int $course_id ): void {
		if ( $row['type'] !== CsvParser::TYPE_TOPIC ) {
			return;
		}

		$session_id = (int) $row['session_id'];

		if ( isset( $sessions[ $session_id ] ) ) {
			return;
		}

		$post = get_post( $session_id );

		if ( ! $post || $post->post_type !== self::POST_TYPE_SESSION ) {
			$row['errors'][] = sprintf(
				/* translators: %d: post ID from the session_id column */
				__( 'No session with ID %d exists.', 'cbf-slides-importer' ),
				$session_id
			);
			return;
		}

		$row['errors'][] = sprintf(
			/* translators: 1: session title, 2: post ID, 3: course ID */
			__( '"%1$s" (ID %2$d) is not part of the selected course (ID %3$d).', 'cbf-slides-importer' ),
			$post->post_title,
			$session_id,
			$course_id
		);
	}


	/**
	 * Note whether a session's heading already exists or will be created.
	 *
	 * Not an error either way — the editor simply benefits from knowing which
	 * headings the import is about to add to their course.
	 *
	 * @param array $row      PlannedRow, updated in place.
	 * @param array $sections The course's existing headings.
	 */
	private static function note_existing_heading( array &$row, array $sections ): void {
		if ( $row['type'] !== CsvParser::TYPE_SESSION || $row['heading'] === '' ) {
			return;
		}

		if ( SectionHeadings::find( $sections, $row['heading'] ) === null ) {
			$row['notices'][] = sprintf(
				/* translators: %s: section heading title */
				__( 'Section heading "%s" does not exist yet and will be created.', 'cbf-slides-importer' ),
				$row['heading']
			);
		}
	}


	/**
	 * Ask Drive what the file actually is.
	 *
	 * The URL says which editor a file belongs to but not, for a stored binary,
	 * whether it is a deck, a document or something the importer cannot read.
	 * This is also the only point at which a permissions problem surfaces before
	 * content starts being created.
	 *
	 * @param array $row     PlannedRow, updated in place.
	 * @param int   $user_id Drive credential owner.
	 */
	private static function resolve_source( array &$row, int $user_id ): void {
		if ( $row['file_id'] === '' ) {
			return;
		}

		$meta = DriveClient::describe( $row['file_id'], $user_id );

		if ( is_wp_error( $meta ) ) {
			$row['errors'][] = $meta->get_error_message();
			return;
		}

		$row['mime_type'] = (string) $meta['mime_type'];
		$format           = ParserFactory::format_for_mime( $row['mime_type'] );

		if ( $format === null ) {
			$row['errors'][] = sprintf(
				/* translators: 1: file name in Drive, 2: comma-separated list of supported extensions */
				__( '"%1$s" is not a document the importer can read. Supported formats: %2$s.', 'cbf-slides-importer' ),
				$meta['name'],
				ParserFactory::extension_list()
			);
			return;
		}

		$row['format'] = $format;

		if ( $meta['name'] !== '' ) {
			$row['notices'][] = sprintf(
				/* translators: %s: file name in Drive */
				__( 'Source: %s', 'cbf-slides-importer' ),
				$meta['name']
			);
		}
	}


	/**
	 * The course's lesson IDs, keyed for membership lookups.
	 *
	 * @param  int $course_id Course post ID.
	 * @return array<int, bool>
	 */
	private static function course_sessions( int $course_id ): array {
		if ( ! function_exists( 'learndash_course_get_steps_by_type' ) ) {
			return array();
		}

		$steps    = learndash_course_get_steps_by_type( $course_id, self::POST_TYPE_SESSION );
		$sessions = array();

		foreach ( (array) $steps as $step_id ) {
			$sessions[ (int) $step_id ] = true;
		}

		return $sessions;
	}
}
