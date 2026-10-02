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
 *   format:     string,   // pptx | pdf | docx | gform
 *   post_type:  string,   // sfwd-lessons | sfwd-topic | sfwd-quiz
 *   parent:     array,    // topic and quiz rows: see ParentResolver
 * }
 */
final class BatchPlanner {

	/** LearnDash post types the importer creates. */
	const POST_TYPE_SESSION = 'sfwd-lessons';
	const POST_TYPE_TOPIC   = 'sfwd-topic';
	const POST_TYPE_QUIZ    = 'sfwd-quiz';


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
		$planned  = array();
		$headings = array();

		foreach ( $rows as $row ) {
			$planned[] = self::plan_row( $row, $course_id, $user_id, $sessions );
		}

		// Parents named by title can point at rows anywhere earlier in the file,
		// so they are resolved once every row's own checks are done.
		$planned = ParentResolver::resolve( $planned, self::course_parents( $course_id, $sessions ) );

		foreach ( $planned as $planned_row ) {
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
	 * @return array PlannedRow.
	 */
	private static function plan_row( array $row, int $course_id, int $user_id, array $sessions ): array {
		$planned = array_merge(
			$row,
			array(
				'status'    => 'error',
				'file_id'   => $row['source']['file_id'] ?? '',
				'mime_type' => '',
				'format'    => '',
				'post_type' => self::post_type_for( $row['type'] ),
			)
		);

		// Rows the CSV parser already rejected need no further work.
		if ( $planned['errors'] !== array() ) {
			return $planned;
		}

		// A parent given as a post ID is checked here; one given as a title is
		// left to ParentResolver, once the whole file has been planned.
		if ( $planned['session_id'] > 0 ) {
			if ( $planned['type'] === CsvParser::TYPE_QUIZ ) {
				self::check_quiz_parent( $planned, $sessions, $course_id );
			} else {
				self::check_parent_session( $planned, $sessions, $course_id );
			}
		}
		self::resolve_source( $planned, $user_id );

		$planned['status'] = $planned['errors'] === array() ? 'ready' : 'error';

		return $planned;
	}


	/**
	 * The LearnDash post type a row type creates.
	 *
	 * @param  string $type CsvParser row type.
	 * @return string
	 */
	private static function post_type_for( string $type ): string {
		return match ( $type ) {
			CsvParser::TYPE_TOPIC => self::POST_TYPE_TOPIC,
			CsvParser::TYPE_QUIZ  => self::POST_TYPE_QUIZ,
			default               => self::POST_TYPE_SESSION,
		};
	}


	/**
	 * Confirm a quiz's parent exists and belongs to this course.
	 *
	 * A quiz sits under a session or under one of a session's topics, so the
	 * parent column may name either.
	 *
	 * @param array $row       PlannedRow, updated in place.
	 * @param array $sessions  Lesson IDs in the course, keyed by ID.
	 * @param int   $course_id Target course.
	 */
	private static function check_quiz_parent( array &$row, array $sessions, int $course_id ): void {
		$parent_id = (int) $row['session_id'];
		$post      = get_post( $parent_id );

		if ( ! $post || ! in_array( $post->post_type, array( self::POST_TYPE_SESSION, self::POST_TYPE_TOPIC ), true ) ) {
			$row['errors'][] = sprintf(
				/* translators: %d: post ID from the parent column */
				__( 'No session or topic with ID %d exists.', 'cbf-slides-importer' ),
				$parent_id
			);
			return;
		}

		$in_course = $post->post_type === self::POST_TYPE_SESSION
			? isset( $sessions[ $parent_id ] )
			: self::topic_in_course( $parent_id, $course_id );

		if ( $in_course ) {
			$row['parent'] = self::existing_parent( $post );
		} else {
			$row['errors'][] = sprintf(
				/* translators: 1: parent title, 2: post ID, 3: course ID */
				__( '"%1$s" (ID %2$d) is not part of the selected course (ID %3$d).', 'cbf-slides-importer' ),
				$post->post_title,
				$parent_id,
				$course_id
			);
		}
	}


	/**
	 * Describe a parent that already exists, in ParentResolver's shape.
	 *
	 * @param  \WP_Post $post Session or topic.
	 * @return array{id: int, line: int, type: string, title: string}
	 */
	private static function existing_parent( \WP_Post $post ): array {
		return array(
			'id'    => (int) $post->ID,
			'line'  => 0,
			'type'  => $post->post_type,
			'title' => $post->post_title,
		);
	}


	/**
	 * The course's sessions and topics, for resolving parents by title.
	 *
	 * @param  int   $course_id Course post ID.
	 * @param  array $sessions  Lesson IDs in the course, keyed by ID.
	 * @return array<array{id: int, title: string, type: string}>
	 */
	private static function course_parents( int $course_id, array $sessions ): array {
		$ids = array_keys( $sessions );

		if ( function_exists( 'learndash_course_get_steps_by_type' ) ) {
			$ids = array_merge( $ids, array_map( 'intval', (array) learndash_course_get_steps_by_type( $course_id, self::POST_TYPE_TOPIC ) ) );
		}

		$parents = array();

		foreach ( $ids as $id ) {
			$post = get_post( (int) $id );

			if ( $post ) {
				$parents[] = array(
					'id'    => (int) $post->ID,
					'title' => $post->post_title,
					'type'  => $post->post_type,
				);
			}
		}

		return $parents;
	}


	/**
	 * Whether a topic is one of the course's steps.
	 *
	 * @param  int $topic_id  Topic post ID.
	 * @param  int $course_id Course post ID.
	 * @return bool
	 */
	private static function topic_in_course( int $topic_id, int $course_id ): bool {
		if ( ! function_exists( 'learndash_course_get_steps_by_type' ) ) {
			return false;
		}

		return in_array( $topic_id, array_map( 'intval', (array) learndash_course_get_steps_by_type( $course_id, self::POST_TYPE_TOPIC ) ), true );
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
		$post       = get_post( $session_id );

		if ( isset( $sessions[ $session_id ] ) && $post ) {
			$row['parent'] = self::existing_parent( $post );
			return;
		}

		if ( ! $post || $post->post_type !== self::POST_TYPE_SESSION ) {
			$row['errors'][] = sprintf(
				/* translators: %d: post ID from the parent column */
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

		$problem = self::format_problem( $row['type'], $format, (string) $meta['name'] );

		if ( $problem !== null ) {
			$row['errors'][] = $problem;
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
	 * Why a Drive file cannot be imported by this row, if it cannot.
	 *
	 * Whatever the URL claimed, the file must be the kind of thing the row's
	 * type will create: forms become quizzes and nothing else does.
	 *
	 * @param  string      $type   CsvParser row type.
	 * @param  string|null $format ParserFactory format, or null when unreadable.
	 * @param  string      $name   File name in Drive.
	 * @return string|null
	 */
	private static function format_problem( string $type, ?string $format, string $name ): ?string {
		if ( $format === null ) {
			return sprintf(
				/* translators: 1: file name in Drive, 2: comma-separated list of supported extensions */
				__( '"%1$s" is not a document the importer can read. Supported formats: %2$s.', 'cbf-slides-importer' ),
				$name,
				ParserFactory::extension_list()
			);
		}

		$is_form = $format === ParserFactory::FORMAT_GFORM;

		if ( $is_form === ( $type === CsvParser::TYPE_QUIZ ) ) {
			return null;
		}

		return $is_form
			? sprintf(
				/* translators: %s: file name in Drive */
				__( '"%s" is a Google Form. Set the row type to "quiz" to import it.', 'cbf-slides-importer' ),
				$name
			)
			: sprintf(
				/* translators: %s: file name in Drive */
				__( '"%s" is not a Google Form, so it cannot be imported as a quiz.', 'cbf-slides-importer' ),
				$name
			);
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
