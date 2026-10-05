<?php
/**
 * Parses and validates a bulk migration CSV.
 *
 * The file is authored by hand in a spreadsheet and exported, so it is treated
 * as untrusted input throughout: every value is sanitised, the row count is
 * capped, and a bad row is reported rather than allowed to abort the parse.
 * The point is to tell an editor everything wrong with their file in one pass.
 *
 * Column semantics follow the resolved design questions:
 *  - `heading` is read on session rows only; on a topic row it is ignored and
 *    reported as a notice, because LearnDash sections group lessons, not topics.
 *  - `parent` is read on topic and quiz rows; on a session row it is ignored.
 *    It names the session a topic belongs under, or the session or topic a
 *    quiz belongs under, by **title** — so a parent created earlier in the
 *    same file can be referred to before it exists. A value made only of
 *    digits is still read as a post ID, and the column's old name,
 *    `session_id`, is accepted as an alias.
 *  - `type` accepts "session" and "lesson" interchangeably, plus "topic" and
 *    "quiz". A quiz row's url must be a Google Form, and no other type's may be.
 *
 * @class   Bulk\CsvParser
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Bulk;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CsvParser class.
 *
 * Produces `ParsedRow` arrays:
 * {
 *   line:       int,     // 1-based line in the file, header included
 *   type:       string,  // 'session' | 'topic' | 'quiz'
 *   title:      string,
 *   heading:      string,  // '' on topic and quiz rows
 *   session_id:   int,     // parent given as a post ID; 0 otherwise
 *   parent_title: string,  // parent given as a title; '' otherwise
 *   source:     array,   // DriveUrl::parse() result
 *   errors:     string[],
 *   notices:    string[]
 * }
 */
final class CsvParser {

	/** Post-type intents a row can express. */
	const TYPE_SESSION = 'session';
	const TYPE_TOPIC   = 'topic';
	const TYPE_QUIZ    = 'quiz';

	/** Accepted `type` values, mapped to the intent they express. */
	const TYPE_SYNONYMS = array(
		'session' => self::TYPE_SESSION,
		'lesson'  => self::TYPE_SESSION,
		'topic'   => self::TYPE_TOPIC,
		'quiz'    => self::TYPE_QUIZ,
	);

	/** Columns the parser reads. Anything else is ignored with a notice. */
	const COLUMNS = array( 'heading', 'parent', 'type', 'title', 'url' );

	/**
	 * Former column names, mapped to the column they now mean.
	 *
	 * `session_id` only ever held post IDs; `parent` reads those the same way,
	 * so files written for the old name keep working unchanged.
	 */
	const COLUMN_ALIASES = array( 'session_id' => 'parent' );

	/**
	 * Most rows a single CSV may contain.
	 *
	 * The real migration is around 120 rows; this is generous enough not to
	 * obstruct it while bounding what one upload can queue.
	 */
	const MAX_ROWS = 500;

	/** Longest accepted title, matching the `post_title` column. */
	const MAX_TITLE_LENGTH = 500;


	/**
	 * Parse CSV text into validated rows.
	 *
	 * @param  string $csv Raw file contents.
	 * @return array{rows: array, errors: string[], notices: string[]}
	 *         File-level errors mean nothing could be parsed; row-level problems
	 *         travel on the rows themselves.
	 */
	public static function parse( string $csv ): array {
		$lines = self::read( $csv );

		if ( $lines === array() ) {
			return self::failure( __( 'The file is empty.', 'cbf-slides-importer' ) );
		}

		$header = self::map_header( array_shift( $lines ) );

		if ( $header['missing'] !== array() ) {
			return self::failure(
				sprintf(
					/* translators: %s: comma-separated list of column names */
					__( 'The CSV is missing required columns: %s.', 'cbf-slides-importer' ),
					implode( ', ', $header['missing'] )
				)
			);
		}

		if ( count( $lines ) > self::MAX_ROWS ) {
			return self::failure(
				sprintf(
					/* translators: %d: maximum number of rows */
					__( 'The CSV has more rows than can be imported at once (limit: %d).', 'cbf-slides-importer' ),
					self::MAX_ROWS
				)
			);
		}

		$rows = self::build_rows( $lines, $header['index'] );

		if ( $rows === array() ) {
			return self::failure( __( 'The CSV contains no data rows.', 'cbf-slides-importer' ) );
		}

		return array(
			'rows'    => $rows,
			'errors'  => array(),
			'notices' => $header['notices'],
		);
	}


	/**
	 * Validate every data line.
	 *
	 * @param  array $lines Field arrays, header already removed.
	 * @param  array $index Column name to position map.
	 * @return array ParsedRow arrays.
	 */
	private static function build_rows( array $lines, array $index ): array {
		$rows = array();

		foreach ( $lines as $offset => $fields ) {
			// +2: the header is line 1, and $offset counts from zero.
			$row = self::build_row( $fields, $index, $offset + 2 );

			if ( $row !== null ) {
				$rows[] = $row;
			}
		}

		return $rows;
	}


	/**
	 * Whether a parsed row can be queued as a job.
	 *
	 * @param  array $row ParsedRow.
	 * @return bool
	 */
	public static function is_valid( array $row ): bool {
		return $row['errors'] === array();
	}


	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Split CSV text into field arrays, discarding wholly blank lines.
	 *
	 * A UTF-8 BOM is stripped: spreadsheet exports routinely carry one, and it
	 * would otherwise corrupt the first column name.
	 *
	 * @param  string $csv Raw file contents.
	 * @return array<array<string>>
	 */
	private static function read( string $csv ): array {
		$csv    = (string) preg_replace( '/^\xEF\xBB\xBF/', '', $csv );
		$handle = fopen( 'php://temp', 'r+' );

		if ( $handle === false ) {
			return array();
		}

		fwrite( $handle, $csv );
		rewind( $handle );

		$lines = array();
		while ( ( $fields = fgetcsv( $handle, 0, ',', '"', '\\' ) ) !== false ) {
			$row = self::normalise_fields( $fields );
			if ( $row !== null ) {
				$lines[] = $row;
			}
		}

		fclose( $handle );

		return $lines;
	}


	/**
	 * Coerce one raw fgetcsv() result, discarding wholly blank lines.
	 *
	 * @param  array $fields Raw fields.
	 * @return array<string>|null
	 */
	private static function normalise_fields( array $fields ): ?array {
		if ( $fields === array( null ) ) {
			return null;
		}

		$row = array_map( static fn( $f ): string => (string) $f, $fields );

		return implode( '', $row ) === '' ? null : $row;
	}


	/**
	 * Map header names to column positions.
	 *
	 * Matching is case-insensitive and tolerant of spaces in place of
	 * underscores, since the header is typed by hand in a spreadsheet.
	 *
	 * @param  array $header Header fields.
	 * @return array{index: array<string, int>, missing: string[], notices: string[]}
	 */
	private static function map_header( array $header ): array {
		$index   = array();
		$notices = array();

		foreach ( $header as $position => $name ) {
			$key = strtolower( trim( $name ) );
			$key = (string) preg_replace( '/[\s-]+/', '_', $key );
			$key = self::COLUMN_ALIASES[ $key ] ?? $key;

			if ( in_array( $key, self::COLUMNS, true ) ) {
				// A file carrying both `parent` and its old alias: the first wins,
				// and the author is told which one was read.
				if ( isset( $index[ $key ] ) ) {
					$notices[] = sprintf(
						/* translators: %s: column name */
						__( 'Column "%s" duplicates an earlier column and was ignored.', 'cbf-slides-importer' ),
						$name
					);
					continue;
				}

				$index[ $key ] = $position;
				continue;
			}

			if ( $key !== '' ) {
				$notices[] = sprintf(
					/* translators: %s: column name */
					__( 'Column "%s" is not used by the importer and was ignored.', 'cbf-slides-importer' ),
					$name
				);
			}
		}

		return array(
			'index'   => $index,
			'missing' => array_values( array_diff( self::COLUMNS, array_keys( $index ) ) ),
			'notices' => $notices,
		);
	}


	/**
	 * Validate one data row.
	 *
	 * @param  array $fields Row fields.
	 * @param  array $index  Column name to position map.
	 * @param  int   $line   1-based line number in the file.
	 * @return array|null ParsedRow, or null when the row is entirely blank.
	 */
	private static function build_row( array $fields, array $index, int $line ): ?array {
		$raw_type = self::field( $fields, $index, 'type' );
		$title    = self::field( $fields, $index, 'title' );
		$url      = self::field( $fields, $index, 'url' );

		if ( $raw_type === '' && $title === '' && $url === '' ) {
			return null;
		}

		$type = self::TYPE_SYNONYMS[ strtolower( $raw_type ) ] ?? '';
		$row  = array(
			'line'       => $line,
			'type'       => $type,
			'title'      => self::clean_title( $title ),
			'heading'      => '',
			'session_id'   => 0,
			'parent_title' => '',
			'source'       => DriveUrl::parse( $url ),
			'errors'       => array(),
			'notices'      => array(),
		);

		self::validate_type( $row, $raw_type );
		self::validate_title( $row, $title );
		self::apply_placement(
			$row,
			self::field( $fields, $index, 'heading' ),
			self::field( $fields, $index, 'parent' )
		);

		self::validate_source( $row );

		return $row;
	}


	/**
	 * Check the row's source link suits its type.
	 *
	 * A quiz row must point at a Google Form and every other row must not: the
	 * two go through different importers, and a mismatch would otherwise fail
	 * in the background job rather than in the report.
	 *
	 * @param array $row ParsedRow, updated in place.
	 */
	private static function validate_source( array &$row ): void {
		$source = $row['source'];

		if ( $row['type'] !== self::TYPE_QUIZ ) {
			if ( ! DriveUrl::is_importable( $source ) ) {
				$row['errors'][] = $source['reason'];
			}
			return;
		}

		if ( $source['kind'] === DriveUrl::KIND_FORM && $source['file_id'] !== '' ) {
			return;
		}

		$row['errors'][] = $source['kind'] === DriveUrl::KIND_FORM || ! in_array( $source['kind'], array( DriveUrl::KIND_SLIDES, DriveUrl::KIND_DOC, DriveUrl::KIND_FILE ), true )
			? $source['reason']
			: __( 'Quiz rows need a link to a Google Form.', 'cbf-slides-importer' );
	}


	/**
	 * Read one column from a row.
	 *
	 * @param  array  $fields Row fields.
	 * @param  array  $index  Column name to position map.
	 * @param  string $column Column name.
	 * @return string Trimmed value, or '' when the column is absent.
	 */
	private static function field( array $fields, array $index, string $column ): string {
		$position = $index[ $column ] ?? null;

		return $position === null ? '' : trim( (string) ( $fields[ $position ] ?? '' ) );
	}


	/**
	 * Check the row's type against the accepted values.
	 *
	 * @param array  $row      ParsedRow, updated in place.
	 * @param string $raw_type The value as written.
	 */
	private static function validate_type( array &$row, string $raw_type ): void {
		if ( $row['type'] !== '' ) {
			return;
		}

		$row['errors'][] = $raw_type === ''
			? __( 'No type was given. Use "session", "topic" or "quiz".', 'cbf-slides-importer' )
			: sprintf(
				/* translators: %s: the value found in the type column */
				__( 'Unrecognised type "%s". Use "session" (or "lesson"), "topic" or "quiz".', 'cbf-slides-importer' ),
				$raw_type
			);
	}


	/**
	 * Check the row has a usable title.
	 *
	 * @param array  $row   ParsedRow, updated in place.
	 * @param string $title The value as written.
	 */
	private static function validate_title( array &$row, string $title ): void {
		if ( $row['title'] === '' ) {
			$row['errors'][] = __( 'No title was given.', 'cbf-slides-importer' );
			return;
		}

		if ( mb_strlen( $title ) > self::MAX_TITLE_LENGTH ) {
			$row['notices'][] = sprintf(
				/* translators: %d: maximum title length */
				__( 'The title was longer than %d characters and has been shortened.', 'cbf-slides-importer' ),
				self::MAX_TITLE_LENGTH
			);
		}
	}


	/**
	 * Apply the placement columns that are meaningful for this row's type.
	 *
	 * A session is placed under a heading; a topic or quiz is placed under a parent.
	 * The column that does not apply is ignored, and a populated one earns a
	 * notice so the author can see it had no effect.
	 *
	 * @param array  $row        ParsedRow, updated in place.
	 * @param string $heading    Heading column value.
	 * @param string $parent     Parent column value.
	 */
	private static function apply_placement( array &$row, string $heading, string $parent ): void {
		if ( $row['type'] === self::TYPE_SESSION ) {
			$row['heading'] = sanitize_text_field( $heading );

			if ( $parent !== '' ) {
				$row['notices'][] = __( 'Session rows do not use parent; the value was ignored.', 'cbf-slides-importer' );
			}
			return;
		}

		if ( $row['type'] !== self::TYPE_TOPIC && $row['type'] !== self::TYPE_QUIZ ) {
			return;
		}

		if ( $heading !== '' ) {
			$row['notices'][] = $row['type'] === self::TYPE_QUIZ
				? __( 'Quiz rows do not use heading — sections group sessions, not quizzes; the value was ignored.', 'cbf-slides-importer' )
				: __( 'Topic rows do not use heading — sections group sessions, not topics; the value was ignored.', 'cbf-slides-importer' );
		}

		self::parse_parent( $row, $parent );
	}


	/**
	 * Read a topic's or quiz's parent, recording any problem with it.
	 *
	 * Digits alone are a post ID, as the column always allowed. Anything else
	 * is a title, resolved by BatchPlanner against the course and against the
	 * rows earlier in this file.
	 *
	 * @param array  $row    ParsedRow, updated in place.
	 * @param string $parent The value as written.
	 */
	private static function parse_parent( array &$row, string $parent ): void {
		if ( $parent === '' ) {
			$row['errors'][] = $row['type'] === self::TYPE_QUIZ
				? __( 'Quiz rows need a parent naming the session (or topic) they belong to.', 'cbf-slides-importer' )
				: __( 'Topic rows need a parent naming the session they belong to.', 'cbf-slides-importer' );
			return;
		}

		if ( preg_match( '/^\d+$/', $parent ) ) {
			if ( (int) $parent < 1 ) {
				$row['errors'][] = sprintf(
					/* translators: %s: the value found in the parent column */
					__( 'parent "%s" is not a post ID.', 'cbf-slides-importer' ),
					$parent
				);
				return;
			}

			$row['session_id'] = (int) $parent;
			return;
		}

		$row['parent_title'] = self::clean_title( $parent );
	}


	/**
	 * Sanitise and length-cap a title.
	 *
	 * @param  string $title Raw title.
	 * @return string
	 */
	private static function clean_title( string $title ): string {
		return mb_substr( sanitize_text_field( $title ), 0, self::MAX_TITLE_LENGTH );
	}


	/**
	 * A file-level failure, with no usable rows.
	 *
	 * @param  string $message Explanation.
	 * @return array{rows: array, errors: string[], notices: string[]}
	 */
	private static function failure( string $message ): array {
		return array(
			'rows'    => array(),
			'errors'  => array( $message ),
			'notices' => array(),
		);
	}
}
