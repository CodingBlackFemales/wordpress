<?php
/**
 * Resolves a Google Drive URL to a file ID and a source kind.
 *
 * Bulk migration takes URLs from a spreadsheet rather than from the Drive
 * picker, so the plugin has to make sense of whatever an author pasted in.
 * Measurement of the real curriculum sheet (P8.1) showed that roughly a sixth
 * of rows point at things that cannot become lesson content — Google Forms
 * most of all — and that those share the exact same `/d/FILE_ID/` shape as
 * Slides and Docs.
 *
 * So this parser keys on the **path segment**, not on finding something that
 * looks like an ID. A Form is recognised as a Form and rejected with a reason,
 * rather than yielding an ID that fails opaquely at download time.
 *
 * @class   Bulk\DriveUrl
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Bulk;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * DriveUrl class.
 *
 * `parse()` returns a result array in every case — callers branch on `kind`
 * rather than on null, so an unsupported source can carry its own explanation
 * into the pre-flight report.
 */
final class DriveUrl {

	/** Kinds that can be imported. */
	const KIND_SLIDES = 'slides';
	const KIND_DOC    = 'doc';
	const KIND_FILE   = 'file';

	/** Kinds that are recognised but cannot become lesson content. */
	const KIND_FORM        = 'form';
	const KIND_SHEET       = 'sheet';
	const KIND_FOLDER      = 'folder';
	const KIND_NOT_DRIVE   = 'not_drive';
	const KIND_EMPTY       = 'empty';
	const KIND_UNPARSEABLE = 'unparseable';

	/**
	 * Google Docs editor path segments, mapped to a kind.
	 *
	 * Every one of these is `docs.google.com/<segment>/d/FILE_ID`, which is why
	 * the segment is the only thing that distinguishes them.
	 */
	const DOCS_SEGMENTS = array(
		'presentation' => self::KIND_SLIDES,
		'document'     => self::KIND_DOC,
		'forms'        => self::KIND_FORM,
		'spreadsheets' => self::KIND_SHEET,
	);

	/** Hosts that can carry a Drive file reference. */
	const DRIVE_HOSTS = array( 'docs.google.com', 'drive.google.com' );

	/**
	 * A bare Drive file ID.
	 *
	 * Drive IDs are base64url-ish and at least 20 characters in practice; the
	 * lower bound stops ordinary words being mistaken for an ID.
	 */
	const BARE_ID_PATTERN = '/^[A-Za-z0-9_-]{20,}$/';


	/**
	 * Parse a source reference from a CSV row.
	 *
	 * @param  string $value Raw cell value: a URL, a bare file ID, or anything else.
	 * @return array{kind: string, file_id: string, importable: bool, reason: string}
	 */
	public static function parse( string $value ): array {
		$value = trim( $value );

		if ( $value === '' ) {
			return self::result( self::KIND_EMPTY, '' );
		}

		if ( preg_match( self::BARE_ID_PATTERN, $value ) ) {
			return self::result( self::KIND_FILE, $value );
		}

		$parts = self::split_url( $value );
		if ( $parts === null ) {
			return self::result( self::KIND_UNPARSEABLE, '' );
		}

		if ( ! in_array( $parts['host'], self::DRIVE_HOSTS, true ) ) {
			return self::result( self::KIND_NOT_DRIVE, '' );
		}

		return self::from_path( $parts['segments'], $parts['query'] );
	}


	/**
	 * Whether a parsed reference can be imported.
	 *
	 * @param  array $parsed Result of parse().
	 * @return bool
	 */
	public static function is_importable( array $parsed ): bool {
		return ! empty( $parsed['importable'] );
	}


	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Break a URL into the host, path segments and query needed to classify it.
	 *
	 * The scheme is not validated beyond being http or https: the real material
	 * contains at least one `http://` link, and rejecting it would lose a row
	 * for no benefit.
	 *
	 * @param  string $url Candidate URL.
	 * @return array{host: string, segments: array<string>, query: array<string, string>}|null
	 */
	private static function split_url( string $url ): ?array {
		if ( ! preg_match( '#^https?://#i', $url ) ) {
			return null;
		}

		$parsed = wp_parse_url( $url );
		if ( ! is_array( $parsed ) || empty( $parsed['host'] ) ) {
			return null;
		}

		$query = array();
		if ( ! empty( $parsed['query'] ) ) {
			parse_str( $parsed['query'], $query );
		}

		return array(
			'host'     => strtolower( $parsed['host'] ),
			'segments' => self::segments( (string) ( $parsed['path'] ?? '' ) ),
			'query'    => $query,
		);
	}


	/**
	 * Split a URL path into its non-empty segments.
	 *
	 * @param  string $path URL path.
	 * @return array<string>
	 */
	private static function segments( string $path ): array {
		return array_values( array_filter( explode( '/', $path ), static fn( string $s ): bool => $s !== '' ) );
	}


	/**
	 * Classify a Drive URL from its path segments.
	 *
	 * @param  array $segments Path segments, in order.
	 * @param  array $query    Query parameters.
	 * @return array Result array.
	 */
	private static function from_path( array $segments, array $query ): array {
		$first = $segments[0] ?? '';

		// docs.google.com/<editor>/d/FILE_ID — the editor decides the kind, and
		// is the only thing separating an importable Doc from a Form.
		if ( isset( self::DOCS_SEGMENTS[ $first ] ) ) {
			return self::result( self::DOCS_SEGMENTS[ $first ], self::id_after( $segments, 'd' ) );
		}

		// drive.google.com/file/d/FILE_ID — a stored binary of unknown type.
		if ( $first === 'file' ) {
			return self::result( self::KIND_FILE, self::id_after( $segments, 'd' ) );
		}

		return self::from_drive_path( $segments, $query );
	}


	/**
	 * Classify the drive.google.com paths that are not `/file/d/…`.
	 *
	 * @param  array $segments Path segments.
	 * @param  array $query    Query parameters.
	 * @return array Result array.
	 */
	private static function from_drive_path( array $segments, array $query ): array {
		// drive.google.com/open?id=… and /uc?id=…
		if ( in_array( $segments[0] ?? '', array( 'open', 'uc' ), true ) ) {
			return self::result( self::KIND_FILE, self::valid_id( (string) ( $query['id'] ?? '' ) ) );
		}

		// drive.google.com/drive/folders/… — a folder, not a file.
		if ( in_array( 'folders', $segments, true ) ) {
			return self::result( self::KIND_FOLDER, '' );
		}

		return self::result( self::KIND_UNPARSEABLE, '' );
	}


	/**
	 * Take the segment following a marker, when it looks like a file ID.
	 *
	 * @param  array  $segments Path segments.
	 * @param  string $marker   Segment preceding the ID, conventionally "d".
	 * @return string File ID, or '' when absent or malformed.
	 */
	private static function id_after( array $segments, string $marker ): string {
		$index = array_search( $marker, $segments, true );

		if ( $index === false || ! isset( $segments[ $index + 1 ] ) ) {
			return '';
		}

		return self::valid_id( $segments[ $index + 1 ] );
	}


	/**
	 * Return a candidate only if it is shaped like a Drive file ID.
	 *
	 * @param  string $candidate Candidate ID.
	 * @return string The ID, or '' when it is not one.
	 */
	private static function valid_id( string $candidate ): string {
		return preg_match( '/^[A-Za-z0-9_-]+$/', $candidate ) ? $candidate : '';
	}


	/**
	 * Build a result, deriving importability and the reason from the kind.
	 *
	 * @param  string $kind    One of the KIND_* constants.
	 * @param  string $file_id Extracted file ID, where there is one.
	 * @return array{kind: string, file_id: string, importable: bool, reason: string}
	 */
	private static function result( string $kind, string $file_id ): array {
		$importable = $file_id !== '' && in_array(
			$kind,
			array( self::KIND_SLIDES, self::KIND_DOC, self::KIND_FILE ),
			true
		);

		return array(
			'kind'       => $kind,
			'file_id'    => $file_id,
			'importable' => $importable,
			'reason'     => $importable ? '' : self::reason( $kind, $file_id ),
		);
	}


	/**
	 * A human explanation of why a reference cannot be imported.
	 *
	 * These are read by editors in the pre-flight report, so each names the
	 * thing that was found and what to do instead.
	 *
	 * @param  string $kind    Kind constant.
	 * @param  string $file_id Extracted file ID, where there is one.
	 * @return string
	 */
	private static function reason( string $kind, string $file_id ): string {
		$reasons = array(
			self::KIND_EMPTY     => __( 'No source link was given for this row.', 'cbf-slides-importer' ),
			self::KIND_FORM      => __( 'This is a Google Form. Quizzes are built in LearnDash and cannot be imported from Drive.', 'cbf-slides-importer' ),
			self::KIND_SHEET     => __( 'This is a Google Sheet. Spreadsheets cannot be imported as lesson content.', 'cbf-slides-importer' ),
			self::KIND_FOLDER    => __( 'This is a Drive folder, not a file. Link the individual document instead.', 'cbf-slides-importer' ),
			self::KIND_NOT_DRIVE => __( 'This is not a Google Drive link. Only files stored in Drive can be imported.', 'cbf-slides-importer' ),
		);

		if ( isset( $reasons[ $kind ] ) ) {
			return $reasons[ $kind ];
		}

		return $file_id === ''
			? __( 'No Drive file ID could be read from this link.', 'cbf-slides-importer' )
			: __( 'This Drive link does not point at a file.', 'cbf-slides-importer' );
	}
}
