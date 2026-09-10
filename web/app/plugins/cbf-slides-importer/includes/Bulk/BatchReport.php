<?php
/**
 * Per-row outcomes for a bulk migration.
 *
 * Written incrementally as jobs finish, so the report is useful while a batch
 * is still running — which matters when a 120-row migration takes the better
 * part of an hour.
 *
 * @class   Bulk\BatchReport
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Bulk;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BatchReport class.
 *
 * Outcomes are deliberately few and unambiguous: an editor scanning 120 rows
 * needs to know which ones need their attention, not a taxonomy.
 */
final class BatchReport {

	/** Row never queued — the CSV or the pre-flight check rejected it. */
	const OUTCOME_REJECTED = 'rejected';

	/** Content was created. */
	const OUTCOME_CREATED = 'created';

	/** An existing post was updated, because Overwrite was on. */
	const OUTCOME_UPDATED = 'updated';

	/**
	 * An existing post was added to this course rather than duplicated.
	 *
	 * With shared course steps enabled a lesson can belong to several courses,
	 * so a title already in use elsewhere is content to reuse, not a collision
	 * to refuse. Distinct from `updated`, which rewrites what is there.
	 */
	const OUTCOME_REUSED = 'reused';

	/** Nothing done: already imported, or a matching post exists and Overwrite is off. */
	const OUTCOME_SKIPPED = 'skipped';

	/** The import was attempted and failed. */
	const OUTCOME_FAILED = 'failed';

	/** Queued but not yet reached. */
	const OUTCOME_PENDING = 'pending';

	/** Every outcome, in the order the summary presents them. */
	const OUTCOMES = array(
		self::OUTCOME_CREATED,
		self::OUTCOME_UPDATED,
		self::OUTCOME_REUSED,
		self::OUTCOME_SKIPPED,
		self::OUTCOME_FAILED,
		self::OUTCOME_REJECTED,
		self::OUTCOME_PENDING,
	);


	/**
	 * Build the initial report from a plan.
	 *
	 * Rows rejected at pre-flight are recorded now, with their reasons, so the
	 * report is complete from the outset rather than only explaining the rows
	 * that ran.
	 *
	 * @param  array $planned PlannedRow arrays from BatchPlanner.
	 * @return array<array<string, mixed>> Report entries keyed by row order.
	 */
	public static function from_plan( array $planned ): array {
		$entries = array();

		foreach ( $planned as $row ) {
			$ready = ( $row['status'] ?? '' ) === 'ready';

			$entries[] = array(
				'line'       => (int) $row['line'],
				'title'      => (string) $row['title'],
				'type'       => (string) $row['type'],
				'heading'    => (string) $row['heading'],
				'session_id' => (int) $row['session_id'],
				'outcome'    => $ready ? self::OUTCOME_PENDING : self::OUTCOME_REJECTED,
				'detail'     => $ready ? '' : implode( ' ', $row['errors'] ),
				'notices'    => array_values( $row['notices'] ),
				'post_id'    => 0,
				'job_id'     => 0,
			);
		}

		return $entries;
	}


	/**
	 * Record a row's outcome.
	 *
	 * @param  array  $entries Report entries.
	 * @param  int    $line    CSV line the row came from.
	 * @param  string $outcome One of the OUTCOME_* constants.
	 * @param  array  $extra   Any of: detail (string), post_id (int), job_id (int).
	 * @return array Updated entries.
	 */
	public static function record( array $entries, int $line, string $outcome, array $extra = array() ): array {
		foreach ( $entries as $index => $entry ) {
			if ( (int) $entry['line'] !== $line ) {
				continue;
			}

			$entries[ $index ]['outcome'] = $outcome;

			foreach ( array( 'detail', 'post_id', 'job_id' ) as $key ) {
				if ( isset( $extra[ $key ] ) ) {
					$entries[ $index ][ $key ] = $extra[ $key ];
				}
			}

			break;
		}

		return $entries;
	}


	/**
	 * Count entries by outcome.
	 *
	 * @param  array $entries Report entries.
	 * @return array<string, int> Counts keyed by outcome, plus `total`.
	 */
	public static function summarise( array $entries ): array {
		$counts = array_fill_keys( self::OUTCOMES, 0 );

		foreach ( $entries as $entry ) {
			$outcome = (string) $entry['outcome'];
			$counts[ $outcome ] = ( $counts[ $outcome ] ?? 0 ) + 1;
		}

		$counts['total'] = count( $entries );

		return $counts;
	}


	/**
	 * Whether every row has reached a final state.
	 *
	 * @param  array $entries Report entries.
	 * @return bool
	 */
	public static function is_complete( array $entries ): bool {
		foreach ( $entries as $entry ) {
			if ( $entry['outcome'] === self::OUTCOME_PENDING ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Whether any row needs the editor's attention.
	 *
	 * @param  array $entries Report entries.
	 * @return bool
	 */
	public static function has_problems( array $entries ): bool {
		foreach ( $entries as $entry ) {
			if ( in_array( $entry['outcome'], array( self::OUTCOME_FAILED, self::OUTCOME_REJECTED ), true ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Render the report as CSV.
	 *
	 * A leading apostrophe is added to any value a spreadsheet would otherwise
	 * evaluate as a formula — report text is built partly from CSV input and
	 * Drive file names, and is downloaded straight back into Excel.
	 *
	 * @param  array $entries Report entries.
	 * @return string
	 */
	public static function to_csv( array $entries ): string {
		$handle = fopen( 'php://temp', 'r+' );

		if ( $handle === false ) {
			return '';
		}

		fputcsv( $handle, array( 'line', 'title', 'type', 'heading', 'session_id', 'outcome', 'post_id', 'detail' ), ',', '"', '\\' );

		foreach ( $entries as $entry ) {
			fputcsv(
				$handle,
				array_map(
					array( __CLASS__, 'defuse' ),
					array(
						$entry['line'],
						$entry['title'],
						$entry['type'],
						$entry['heading'],
						$entry['session_id'] > 0 ? $entry['session_id'] : '',
						$entry['outcome'],
						$entry['post_id'] > 0 ? $entry['post_id'] : '',
						$entry['detail'],
					)
				),
				',',
				'"',
				'\\'
			);
		}

		rewind( $handle );
		$csv = (string) stream_get_contents( $handle );
		fclose( $handle );

		return $csv;
	}


	/**
	 * Stop a spreadsheet treating a value as a formula.
	 *
	 * @param  mixed $value Cell value.
	 * @return string
	 */
	private static function defuse( $value ): string {
		$value = (string) $value;

		return preg_match( '/^[=+\-@\t\r]/', $value ) ? "'" . $value : $value;
	}
}
