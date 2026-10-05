<?php
/**
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SlidesImporter\Bulk\BatchPlanner;
use CodingBlackFemales\SlidesImporter\Bulk\CsvParser;
use CodingBlackFemales\SlidesImporter\Bulk\ParentResolver;

/**
 * Covers resolving topic and quiz parents named by title.
 */
final class ParentResolverTest extends Unit {

	/**
	 * A planned row.
	 *
	 * @param  int    $line   CSV line.
	 * @param  string $type   Row type.
	 * @param  string $title  Row title.
	 * @param  string $parent Parent title, for topics and quizzes.
	 * @param  string $status 'ready' or 'error'.
	 * @return array
	 */
	private function row( int $line, string $type, string $title, string $parent = '', string $status = 'ready' ): array {
		return array(
			'line'         => $line,
			'type'         => $type,
			'title'        => $title,
			'session_id'   => 0,
			'parent_title' => $parent,
			'status'       => $status,
			'errors'       => $status === 'ready' ? array() : array( 'Bad row.' ),
		);
	}

	/** An existing session in the course. */
	private function session( int $id, string $title ): array {
		return array(
			'id'    => $id,
			'title' => $title,
			'type'  => BatchPlanner::POST_TYPE_SESSION,
		);
	}

	public function testParentCreatedEarlierInTheFile(): void {
		$rows = ParentResolver::resolve(
			array(
				$this->row( 2, CsvParser::TYPE_SESSION, 'Introduction to Git' ),
				$this->row( 3, CsvParser::TYPE_TOPIC, 'Git exercises', 'introduction to git' ),
			),
			array()
		);

		$this->assertSame( 'ready', $rows[1]['status'] );
		$this->assertSame(
			array(
				'id'    => 0,
				'line'  => 2,
				'type'  => BatchPlanner::POST_TYPE_SESSION,
				'title' => 'Introduction to Git',
			),
			$rows[1]['parent']
		);
	}

	public function testParentAlreadyInTheCourse(): void {
		$rows = ParentResolver::resolve(
			array( $this->row( 2, CsvParser::TYPE_TOPIC, 'Git exercises', 'Introduction to Git' ) ),
			array( $this->session( 412, 'Introduction to Git' ) )
		);

		$this->assertSame( 412, $rows[0]['parent']['id'] );
		$this->assertSame( 0, $rows[0]['parent']['line'] );
	}

	/** The row is what the importer will match by title, so it is the authority. */
	public function testEarlierRowWinsOverExistingContent(): void {
		$rows = ParentResolver::resolve(
			array(
				$this->row( 2, CsvParser::TYPE_SESSION, 'Introduction to Git' ),
				$this->row( 3, CsvParser::TYPE_TOPIC, 'Git exercises', 'Introduction to Git' ),
			),
			array( $this->session( 412, 'Introduction to Git' ) )
		);

		$this->assertSame( 2, $rows[1]['parent']['line'] );
	}

	/** Stored titles may be entity-encoded where the spreadsheet is not. */
	public function testTitlesMatchAcrossEntityEncoding(): void {
		$rows = ParentResolver::resolve(
			array( $this->row( 2, CsvParser::TYPE_TOPIC, 'Exercises', 'Git & GitHub' ) ),
			array( $this->session( 7, 'Git &amp; GitHub' ) )
		);

		$this->assertSame( 7, $rows[0]['parent']['id'] );
	}

	public function testQuizMayBelongToATopic(): void {
		$rows = ParentResolver::resolve(
			array(
				$this->row( 2, CsvParser::TYPE_SESSION, 'dbt' ),
				$this->row( 3, CsvParser::TYPE_TOPIC, 'dbt: Lab 1', 'dbt' ),
				$this->row( 4, CsvParser::TYPE_QUIZ, 'dbt: Skills check', 'dbt: Lab 1' ),
			),
			array()
		);

		$this->assertSame( BatchPlanner::POST_TYPE_TOPIC, $rows[2]['parent']['type'] );
		$this->assertSame( 3, $rows[2]['parent']['line'] );
	}

	/** LearnDash nests topics under sessions only. */
	public function testTopicCannotBelongToATopic(): void {
		$rows = ParentResolver::resolve(
			array(
				$this->row( 2, CsvParser::TYPE_SESSION, 'dbt' ),
				$this->row( 3, CsvParser::TYPE_TOPIC, 'dbt: Lab 1', 'dbt' ),
				$this->row( 4, CsvParser::TYPE_TOPIC, 'dbt: Lab 2', 'dbt: Lab 1' ),
			),
			array()
		);

		$this->assertSame( 'error', $rows[2]['status'] );
		$this->assertStringContainsString( 'No session titled "dbt: Lab 1"', $rows[2]['errors'][0] );
	}

	/** Rows import in order, so a parent below its child is not there yet. */
	public function testParentLaterInTheFile(): void {
		$rows = ParentResolver::resolve(
			array(
				$this->row( 2, CsvParser::TYPE_TOPIC, 'Git exercises', 'Introduction to Git' ),
				$this->row( 3, CsvParser::TYPE_SESSION, 'Introduction to Git' ),
			),
			array()
		);

		$this->assertSame( 'error', $rows[0]['status'] );
		$this->assertStringContainsString( 'on line 3, after this row', $rows[0]['errors'][0] );
	}

	public function testRejectedParentRejectsTheChild(): void {
		$rows = ParentResolver::resolve(
			array(
				$this->row( 2, CsvParser::TYPE_SESSION, 'Introduction to Git', '', 'error' ),
				$this->row( 3, CsvParser::TYPE_TOPIC, 'Git exercises', 'Introduction to Git' ),
				$this->row( 4, CsvParser::TYPE_QUIZ, 'Git: Skills check', 'Git exercises' ),
			),
			array()
		);

		$this->assertSame( 'error', $rows[1]['status'] );
		$this->assertStringContainsString( 'on line 2 cannot be imported', $rows[1]['errors'][0] );
		// The rejection carries down the chain.
		$this->assertSame( 'error', $rows[2]['status'] );
		$this->assertStringContainsString( 'on line 3 cannot be imported', $rows[2]['errors'][0] );
	}

	public function testAmbiguousExistingTitle(): void {
		$rows = ParentResolver::resolve(
			array( $this->row( 2, CsvParser::TYPE_TOPIC, 'Exercises', 'Introduction' ) ),
			array( $this->session( 10, 'Introduction' ), $this->session( 11, 'Introduction' ) )
		);

		$this->assertStringContainsString( 'IDs 10, 11', $rows[0]['errors'][0] );
	}

	public function testAmbiguousEarlierRows(): void {
		$rows = ParentResolver::resolve(
			array(
				$this->row( 2, CsvParser::TYPE_SESSION, 'Introduction' ),
				$this->row( 3, CsvParser::TYPE_SESSION, 'Introduction' ),
				$this->row( 4, CsvParser::TYPE_TOPIC, 'Exercises', 'Introduction' ),
			),
			array()
		);

		$this->assertStringContainsString( 'lines 2, 3', $rows[2]['errors'][0] );
	}

	public function testUnknownParent(): void {
		$rows = ParentResolver::resolve(
			array( $this->row( 2, CsvParser::TYPE_QUIZ, 'Skills check', 'Nowhere' ) ),
			array()
		);

		$this->assertStringContainsString( 'No session or topic titled "Nowhere"', $rows[0]['errors'][0] );
	}

	/** Rows that named their parent by ID are left to BatchPlanner. */
	public function testIdParentsAreUntouched(): void {
		$row               = $this->row( 2, CsvParser::TYPE_TOPIC, 'Exercises' );
		$row['session_id'] = 412;

		$this->assertSame( array( $row ), ParentResolver::resolve( array( $row ), array() ) );
	}
}
