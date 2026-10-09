<?php
/**
 * Import and export formats.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Entry;

use InvalidArgumentException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transfer.
 *
 * Converts entries to and from JSON and CSV, round-trippably. A record is
 * `{ slug, forms: [{term, abbr}, …], definition }`; the definition is HTML.
 *
 * CSV puts the canonical form in `term` and `abbr` and packs the alternatives
 * into one cell, `term:abbr|term`, so a spreadsheet stays one row per entry.
 */
final class Transfer {

	const JSON = 'json';
	const CSV  = 'csv';

	/**
	 * CSV columns, in order.
	 */
	const CSV_COLUMNS = array( 'slug', 'term', 'abbr', 'alternatives', 'definition' );


	/**
	 * Records for some entries.
	 *
	 * @param Entry[] $entries Entries.
	 * @return array<int, array{slug:string, forms:array<int, array{term:string,abbr:string}>, definition:string}>
	 */
	public static function records( array $entries ): array {
		return array_values(
			array_map(
				fn ( Entry $entry ): array => array(
					'slug'       => $entry->slug,
					'forms'      => $entry->forms,
					'definition' => $entry->definition,
				),
				$entries
			)
		);
	}


	/**
	 * Serialise records.
	 *
	 * @param array<int, array<string, mixed>> $records Records.
	 * @param string                           $format  JSON or CSV.
	 */
	public static function export( array $records, string $format ): string {
		if ( $format === self::CSV ) {
			return self::to_csv( $records );
		}

		return (string) json_encode( $records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
	}


	/**
	 * Parse an export back into records.
	 *
	 * Records without a term are rejected rather than skipped, so a bad file
	 * fails the import instead of half-applying.
	 *
	 * @param string $contents File contents.
	 * @param string $format   JSON or CSV.
	 * @return array<int, array{slug:string, forms:array<int, array{term:string,abbr:string}>, definition:string}>
	 * @throws InvalidArgumentException When the file cannot be parsed or a record is invalid.
	 */
	public static function import( string $contents, string $format ): array {
		$raw     = $format === self::CSV ? self::from_csv( $contents ) : self::from_json( $contents );
		$records = array();

		foreach ( $raw as $number => $item ) {
			$records[] = self::record( $item, $number + 1 );
		}

		return $records;
	}


	/**
	 * The format a file name implies.
	 *
	 * @param string $path File path.
	 */
	public static function format_for( string $path ): string {
		return strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) === self::CSV ? self::CSV : self::JSON;
	}


	/**
	 * Validate one record.
	 *
	 * @param mixed $item   Decoded record.
	 * @param int   $number 1-based record number, for errors.
	 * @return array{slug:string, forms:array<int, array{term:string,abbr:string}>, definition:string}
	 * @throws InvalidArgumentException When the record has no term.
	 */
	private static function record( $item, int $number ): array {
		$item  = array_merge(
			array(
				'slug'       => '',
				'forms'      => array(),
				'definition' => '',
			),
			is_array( $item ) ? $item : array()
		);
		$forms = Forms::normalise( (array) $item['forms'] );

		if ( $forms === array() ) {
			/* translators: %d: record number */
			throw new InvalidArgumentException( sprintf( __( 'Record %d has no term.', 'cbf-semantic-glossary' ), $number ) );
		}

		return array(
			'slug'       => trim( (string) $item['slug'] ),
			'forms'      => $forms,
			'definition' => (string) $item['definition'],
		);
	}


	/**
	 * Decode JSON records.
	 *
	 * @param string $contents JSON.
	 * @return array<int, mixed>
	 * @throws InvalidArgumentException When the JSON is not a list.
	 */
	private static function from_json( string $contents ): array {
		$decoded = json_decode( $contents, true );

		if ( ! is_array( $decoded ) || ! array_is_list( $decoded ) ) {
			throw new InvalidArgumentException( __( 'Expected a JSON array of entries.', 'cbf-semantic-glossary' ) );
		}

		return $decoded;
	}


	/**
	 * Decode CSV rows into records.
	 *
	 * @param string $contents CSV with a header row.
	 * @return array<int, array<string, mixed>>
	 * @throws InvalidArgumentException When the header lacks a term column.
	 */
	private static function from_csv( string $contents ): array {
		$rows   = self::csv_rows( $contents );
		$header = array_map( fn ( $cell ) => strtolower( trim( (string) $cell ) ), (array) array_shift( $rows ) );

		if ( ! in_array( 'term', $header, true ) ) {
			throw new InvalidArgumentException( __( 'The CSV needs a "term" column.', 'cbf-semantic-glossary' ) );
		}

		return array_map(
			fn ( array $row ): array => self::csv_record( array_combine( $header, array_pad( array_slice( $row, 0, count( $header ) ), count( $header ), '' ) ) ),
			$rows
		);
	}


	/**
	 * A record from one CSV row's cells.
	 *
	 * @param array<string, string> $cells Cells keyed by column.
	 * @return array<string, mixed>
	 */
	private static function csv_record( array $cells ): array {
		$cells = array_merge( array_fill_keys( self::CSV_COLUMNS, '' ), $cells );

		return array(
			'slug'       => $cells['slug'],
			'forms'      => array_merge(
				array(
					array(
						'term' => $cells['term'],
						'abbr' => $cells['abbr'],
					),
				),
				Forms::unpack( $cells['alternatives'] )
			),
			'definition' => $cells['definition'],
		);
	}


	/**
	 * Encode records as CSV.
	 *
	 * @param array<int, array<string, mixed>> $records Records.
	 */
	private static function to_csv( array $records ): string {
		$handle = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fputcsv( $handle, self::CSV_COLUMNS, ',', '"', '' );

		foreach ( $records as $record ) {
			$forms = $record['forms'];
			fputcsv(
				$handle,
				array( $record['slug'], $forms[0]['term'], $forms[0]['abbr'], Forms::pack( array_slice( $forms, 1 ) ), $record['definition'] ),
				',',
				'"',
				''
			);
		}

		rewind( $handle );
		$csv = (string) stream_get_contents( $handle );
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $csv;
	}


	/**
	 * Parse CSV text, skipping blank lines. Cells may span lines when quoted.
	 *
	 * @param string $contents CSV.
	 * @return array<int, array<int, string>>
	 */
	private static function csv_rows( string $contents ): array {
		$handle = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		fwrite( $handle, preg_replace( '/^\xEF\xBB\xBF/', '', $contents ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		rewind( $handle );

		$rows = array();
		while ( ( $row = fgetcsv( $handle, null, ',', '"', '' ) ) !== false ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			if ( $row !== array( null ) ) {
				$rows[] = array_map( 'strval', $row );
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $rows;
	}
}
