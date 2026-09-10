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
 *  - `session_id` is read on topic rows only; on a session row it is ignored.
 *  - `type` accepts "session" and "lesson" interchangeably, plus "topic".
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
 *   type:       string,  // 'session' | 'topic'
 *   title:      string,
 *   heading:    string,  // '' on topic rows
 *   session_id: int,     // 0 on session rows
 *   source:     array,   // DriveUrl::parse() result
 *   errors:     string[],
 *   notices:    string[]
 * }
 */
final class CsvParser {

	/** Post-type intents a row can express. */
	const TYPE_SESSION = 'session';
	const TYPE_TOPIC   = 'topic';

	/** Accepted `type` values, mapped to the intent they express. */
	const TYPE_SYNONYMS = array(
		'session' => self::TYPE_SESSION,
		'lesson'  => self::TYPE_SESSION,
		'topic'   => self::TYPE_TOPIC,
	);

	/** Columns the parser reads. Anything else is ignored with a notice. */
	const COLUMNS = array( 'heading', 'session_id', 'type', 'title', 'url' );

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

			if ( in_array( $key, self::COLUMNS, true ) ) {
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
			'heading'    => '',
			'session_id' => 0,
			'source'     => DriveUrl::parse( $url ),
			'errors'     => array(),
			'notices'    => array(),
		);

		self::validate_type( $row, $raw_type );
		self::validate_title( $row, $title );
		self::apply_placement(
			$row,
			self::field( $fields, $index, 'heading' ),
			self::field( $fields, $index, 'session_id' )
		);

		if ( ! DriveUrl::is_importable( $row['source'] ) ) {
			$row['errors'][] = $row['source']['reason'];
		}

		return $row;
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
			? __( 'No type was given. Use "session" or "topic".', 'cbf-slides-importer' )
			: sprintf(
				/* translators: %s: the value found in the type column */
				__( 'Unrecognised type "%s". Use "session" (or "lesson") or "topic".', 'cbf-slides-importer' ),
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
	 * A session is placed under a heading; a topic is placed under a session.
	 * The column that does not apply is ignored, and a populated one earns a
	 * notice so the author can see it had no effect.
	 *
	 * @param array  $row        ParsedRow, updated in place.
	 * @param string $heading    Heading column value.
	 * @param string $session_id Session ID column value.
	 */
	private static function apply_placement( array &$row, string $heading, string $session_id ): void {
		if ( $row['type'] === self::TYPE_SESSION ) {
			$row['heading'] = sanitize_text_field( $heading );

			if ( $session_id !== '' ) {
				$row['notices'][] = __( 'Session rows do not use session_id; the value was ignored.', 'cbf-slides-importer' );
			}
			return;
		}

		if ( $row['type'] !== self::TYPE_TOPIC ) {
			return;
		}

		if ( $heading !== '' ) {
			$row['notices'][] = __( 'Topic rows do not use heading — sections group sessions, not topics; the value was ignored.', 'cbf-slides-importer' );
		}

		$row['session_id'] = self::parse_session_id( $row, $session_id );
	}


	/**
	 * Read a topic's parent session ID, recording any problem with it.
	 *
	 * @param  array  $row        ParsedRow, updated in place.
	 * @param  string $session_id The value as written.
	 * @return int Post ID, or 0 when unusable.
	 */
	private static function parse_session_id( array &$row, string $session_id ): int {
		if ( $session_id === '' ) {
			$row['errors'][] = __( 'Topic rows need a session_id naming the session they belong to.', 'cbf-slides-importer' );
			return 0;
		}

		if ( ! preg_match( '/^\d+$/', $session_id ) || (int) $session_id < 1 ) {
			$row['errors'][] = sprintf(
				/* translators: %s: the value found in the session_id column */
				__( 'session_id "%s" is not a post ID.', 'cbf-slides-importer' ),
				$session_id
			);
			return 0;
		}

		return (int) $session_id;
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
