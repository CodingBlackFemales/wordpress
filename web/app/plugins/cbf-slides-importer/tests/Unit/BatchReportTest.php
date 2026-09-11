<?php
/**
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SlidesImporter\Bulk\BatchReport;
use CodingBlackFemales\SlidesImporter\Bulk\CsvParser;

/**
 * Covers per-row outcome tracking for a bulk migration.
 */
final class BatchReportTest extends Unit {

	/**
	 * A planned row.
	 *
	 * @param  int    $line   CSV line.
	 * @param  string $status 'ready' or 'error'.
	 * @param  array  $extra  Field overrides.
	 * @return array
	 */
	private function planned( int $line, string $status = 'ready', array $extra = array() ): array {
		return array_merge(
			array(
				'line'       => $line,
				'title'      => 'Row ' . $line,
				'type'       => CsvParser::TYPE_SESSION,
				'heading'    => 'Foundations',
				'session_id' => 0,
				'status'     => $status,
				'errors'     => $status === 'ready' ? array() : array( 'Something is wrong.' ),
				'notices'    => array(),
			),
			$extra
		);
	}

	/** Ready rows start pending; rejected rows carry their reason immediately. */
	public function testFromPlan(): void {
		$entries = BatchReport::from_plan(
			array(
				$this->planned( 2 ),
				$this->planned( 3, 'error' ),
			)
		);

		$this->assertCount( 2, $entries );
		$this->assertSame( BatchReport::OUTCOME_PENDING, $entries[0]['outcome'] );
		$this->assertSame( '', $entries[0]['detail'] );
		$this->assertSame( BatchReport::OUTCOME_REJECTED, $entries[1]['outcome'] );
		$this->assertSame( 'Something is wrong.', $entries[1]['detail'] );
	}

	/** Recording an outcome updates only the row it names. */
	public function testRecordTargetsOneRow(): void {
		$entries = BatchReport::from_plan( array( $this->planned( 2 ), $this->planned( 3 ) ) );

		$entries = BatchReport::record( $entries, 3, BatchReport::OUTCOME_CREATED, array( 'post_id' => 99 ) );

		$this->assertSame( BatchReport::OUTCOME_PENDING, $entries[0]['outcome'] );
		$this->assertSame( BatchReport::OUTCOME_CREATED, $entries[1]['outcome'] );
		$this->assertSame( 99, $entries[1]['post_id'] );
	}

	/** Recording against an unknown line changes nothing. */
	public function testRecordIgnoresUnknownLines(): void {
		$entries = BatchReport::from_plan( array( $this->planned( 2 ) ) );

		$this->assertSame( $entries, BatchReport::record( $entries, 99, BatchReport::OUTCOME_FAILED ) );
	}

	/** Counts cover every outcome, including those with no rows. */
	public function testSummarise(): void {
		$entries = BatchReport::from_plan(
			array( $this->planned( 2 ), $this->planned( 3 ), $this->planned( 4, 'error' ) )
		);
		$entries = BatchReport::record( $entries, 2, BatchReport::OUTCOME_CREATED );
		$entries = BatchReport::record( $entries, 3, BatchReport::OUTCOME_FAILED );

		$summary = BatchReport::summarise( $entries );

		$this->assertSame( 1, $summary[ BatchReport::OUTCOME_CREATED ] );
		$this->assertSame( 1, $summary[ BatchReport::OUTCOME_FAILED ] );
		$this->assertSame( 1, $summary[ BatchReport::OUTCOME_REJECTED ] );
		$this->assertSame( 0, $summary[ BatchReport::OUTCOME_SKIPPED ] );
		$this->assertSame( 3, $summary['total'] );
	}

	/** A batch is complete once no row is still waiting. */
	public function testIsComplete(): void {
		$entries = BatchReport::from_plan( array( $this->planned( 2 ), $this->planned( 3, 'error' ) ) );

		$this->assertFalse( BatchReport::is_complete( $entries ), 'Row 2 has not run' );

		$entries = BatchReport::record( $entries, 2, BatchReport::OUTCOME_CREATED );

		$this->assertTrue( BatchReport::is_complete( $entries ) );
	}

	/**
	 * Only a failure counts as needing attention.
	 *
	 * A batch that finished with one is reported as completed-with-errors
	 * rather than done, so a partial outcome is never read as a clean run.
	 */
	public function testHasProblems(): void {
		$clean = BatchReport::record( BatchReport::from_plan( array( $this->planned( 2 ) ) ), 2, BatchReport::OUTCOME_CREATED );
		$this->assertFalse( BatchReport::has_problems( $clean ) );

		$failed = BatchReport::record( BatchReport::from_plan( array( $this->planned( 2 ) ) ), 2, BatchReport::OUTCOME_FAILED );
		$this->assertTrue( BatchReport::has_problems( $failed ) );
	}

	/**
	 * A rejected row is not a problem with the run.
	 *
	 * Pre-flight identified it and the editor saw it before pressing the
	 * button, so a batch that did exactly what it said it would has not gone
	 * wrong. Reporting a known, accepted exclusion as a problem teaches people
	 * to ignore the final status.
	 */
	public function testRejectedIsNotAProblem(): void {
		$entries = BatchReport::from_plan( array( $this->planned( 2, 'error' ) ) );

		$this->assertSame( BatchReport::OUTCOME_REJECTED, $entries[0]['outcome'] );
		$this->assertFalse( BatchReport::has_problems( $entries ) );
		$this->assertTrue( BatchReport::is_complete( $entries ) );
	}

	/** A reused row is not a problem — the content was shared, not duplicated. */
	public function testReusedIsNotAProblem(): void {
		$entries = BatchReport::record( BatchReport::from_plan( array( $this->planned( 2 ) ) ), 2, BatchReport::OUTCOME_REUSED );

		$this->assertSame( BatchReport::OUTCOME_REUSED, $entries[0]['outcome'] );
		$this->assertFalse( BatchReport::has_problems( $entries ) );
		$this->assertTrue( BatchReport::is_complete( $entries ) );
	}

	/** A skipped row is not a problem — it means the content already existed. */
	public function testSkippedIsNotAProblem(): void {
		$entries = BatchReport::record( BatchReport::from_plan( array( $this->planned( 2 ) ) ), 2, BatchReport::OUTCOME_SKIPPED );

		$this->assertFalse( BatchReport::has_problems( $entries ) );
		$this->assertTrue( BatchReport::is_complete( $entries ) );
	}

	/** The CSV export carries a header and one line per row. */
	public function testCsvExport(): void {
		$entries = BatchReport::record(
			BatchReport::from_plan( array( $this->planned( 2 ), $this->planned( 3, 'error' ) ) ),
			2,
			BatchReport::OUTCOME_CREATED,
			array( 'post_id' => 4242 )
		);

		$csv   = BatchReport::to_csv( $entries );
		$lines = array_values( array_filter( explode( "\n", $csv ) ) );

		$this->assertStringStartsWith( 'line,title,type,heading,session_id,outcome,post_id,detail', $csv );
		$this->assertCount( 3, $lines );
		$this->assertStringContainsString( '4242', $lines[1] );
		$this->assertStringContainsString( 'Something is wrong.', $lines[2] );
	}

	/**
	 * Values a spreadsheet would evaluate are defused.
	 *
	 * Report text is built from CSV input and Drive file names and is
	 * downloaded straight back into a spreadsheet, so a title beginning with
	 * `=` must not become a formula.
	 */
	public function testCsvExportNeutralisesFormulas(): void {
		$entries = BatchReport::from_plan(
			array( $this->planned( 2, 'ready', array( 'title' => '=HYPERLINK("http://evil","x")' ) ) )
		);

		$csv = BatchReport::to_csv( $entries );

		$this->assertStringContainsString( "'=HYPERLINK", $csv );
		$this->assertStringNotContainsString( ',=HYPERLINK', $csv );
	}

	/** Zero IDs render as blanks rather than a misleading "0". */
	public function testCsvExportBlanksZeroIds(): void {
		$csv = BatchReport::to_csv( BatchReport::from_plan( array( $this->planned( 2 ) ) ) );

		$this->assertStringNotContainsString( ',0,', $csv );
	}
}
