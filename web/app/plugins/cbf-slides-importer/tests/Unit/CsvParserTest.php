<?php
/**
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SlidesImporter\Bulk\CsvParser;
use CodingBlackFemales\SlidesImporter\Bulk\DriveUrl;

/**
 * Covers bulk CSV parsing and row validation.
 */
final class CsvParserTest extends Unit {

	/** A plausible Drive file ID. */
	private const ID = '1H0p6qErPpdywFEfqM2LeoLsH8P-z9xjfp7cbKQpSlFw';

	/** A valid Slides URL. */
	private const URL = 'https://docs.google.com/presentation/d/' . self::ID . '/edit';

	/**
	 * Build CSV text from rows.
	 *
	 * @param  array  $rows   Rows, each an array of five values.
	 * @param  string $header Header line.
	 * @return string
	 */
	private function csv( array $rows, string $header = 'heading,session_id,type,title,url' ): string {
		$out = $header . "\n";
		foreach ( $rows as $row ) {
			$out .= implode( ',', array_map( static fn( $v ): string => '"' . str_replace( '"', '""', (string) $v ) . '"', $row ) ) . "\n";
		}
		return $out;
	}

	/** A session row is parsed with its heading. */
	public function testSessionRow(): void {
		$result = CsvParser::parse( $this->csv( array( array( 'Foundations', '', 'session', 'Introduction to Git', self::URL ) ) ) );

		$this->assertSame( array(), $result['errors'] );
		$this->assertCount( 1, $result['rows'] );

		$row = $result['rows'][0];
		$this->assertTrue( CsvParser::is_valid( $row ) );
		$this->assertSame( CsvParser::TYPE_SESSION, $row['type'] );
		$this->assertSame( 'Foundations', $row['heading'] );
		$this->assertSame( 'Introduction to Git', $row['title'] );
		$this->assertSame( 0, $row['session_id'] );
		$this->assertSame( self::ID, $row['source']['file_id'] );
		$this->assertSame( 2, $row['line'], 'Line numbers count the header' );
	}

	/** A topic row is parsed with its parent session. */
	public function testTopicRow(): void {
		$row = CsvParser::parse( $this->csv( array( array( '', '412', 'topic', 'Git Exercises', self::URL ) ) ) )['rows'][0];

		$this->assertTrue( CsvParser::is_valid( $row ) );
		$this->assertSame( CsvParser::TYPE_TOPIC, $row['type'] );
		$this->assertSame( 412, $row['session_id'] );
		$this->assertSame( '', $row['heading'] );
	}

	/**
	 * "lesson" is accepted wherever "session" is.
	 *
	 * @dataProvider typeSynonyms
	 *
	 * @param string $written  The value as written in the CSV.
	 * @param string $expected The intent it expresses.
	 */
	public function testTypeSynonyms( string $written, string $expected ): void {
		$row = CsvParser::parse( $this->csv( array( array( '', '', $written, 'Title', self::URL ) ) ) )['rows'][0];

		$this->assertSame( $expected, $row['type'] );
	}

	/** @return array<string, array{string, string}> */
	public static function typeSynonyms(): array {
		return array(
			'session'      => array( 'session', CsvParser::TYPE_SESSION ),
			'lesson'       => array( 'lesson', CsvParser::TYPE_SESSION ),
			'Session caps' => array( 'Session', CsvParser::TYPE_SESSION ),
			'LESSON caps'  => array( 'LESSON', CsvParser::TYPE_SESSION ),
			'padded'       => array( '  topic  ', CsvParser::TYPE_TOPIC ),
			'topic'        => array( 'topic', CsvParser::TYPE_TOPIC ),
		);
	}

	/**
	 * A heading on a topic row is ignored, not rejected.
	 *
	 * LearnDash sections group sessions, so a heading cannot apply to a topic —
	 * but a populated cell is an authoring slip, not a reason to lose the row.
	 */
	public function testHeadingOnTopicRowIsIgnoredWithANotice(): void {
		$row = CsvParser::parse( $this->csv( array( array( 'Foundations', '412', 'topic', 'Git Exercises', self::URL ) ) ) )['rows'][0];

		$this->assertTrue( CsvParser::is_valid( $row ) );
		$this->assertSame( '', $row['heading'] );
		$this->assertNotEmpty( $row['notices'] );
		$this->assertStringContainsString( 'sections group sessions', $row['notices'][0] );
	}

	/** A session_id on a session row is ignored, not rejected. */
	public function testSessionIdOnSessionRowIsIgnoredWithANotice(): void {
		$row = CsvParser::parse( $this->csv( array( array( 'Foundations', '412', 'session', 'Intro', self::URL ) ) ) )['rows'][0];

		$this->assertTrue( CsvParser::is_valid( $row ) );
		$this->assertSame( 0, $row['session_id'] );
		$this->assertStringContainsString( 'do not use session_id', $row['notices'][0] );
	}

	/**
	 * Row-level problems are reported per row without aborting the parse.
	 *
	 * @dataProvider invalidRows
	 *
	 * @param array  $fields   The five column values.
	 * @param string $fragment Text the first error must contain.
	 */
	public function testInvalidRows( array $fields, string $fragment ): void {
		$row = CsvParser::parse( $this->csv( array( $fields ) ) )['rows'][0];

		$this->assertFalse( CsvParser::is_valid( $row ) );
		$this->assertStringContainsString( $fragment, implode( ' ', $row['errors'] ) );
	}

	/** @return array<string, array{array, string}> */
	public static function invalidRows(): array {
		return array(
			'unknown type'     => array( array( '', '', 'widget', 'Title', self::URL ), 'Unrecognised type' ),
			'missing type'     => array( array( '', '', '', 'Title', self::URL ), 'No type was given' ),
			'missing title'    => array( array( '', '', 'session', '', self::URL ), 'No title was given' ),
			'missing url'      => array( array( '', '', 'session', 'Title', '' ), 'No source link' ),
			'google form'      => array( array( '', '', 'session', 'Quiz', 'https://docs.google.com/forms/d/' . self::ID . '/edit' ), 'Google Form' ),
			'github repo'      => array( array( '', '', 'session', 'Repo', 'https://github.com/cbfacademy/x' ), 'not a Google Drive link' ),
			'topic no session' => array( array( '', '', 'topic', 'Orphan', self::URL ), 'need a session_id' ),
			'topic bad session' => array( array( '', 'abc', 'topic', 'Orphan', self::URL ), 'is not a post ID' ),
			'topic zero session' => array( array( '', '0', 'topic', 'Orphan', self::URL ), 'is not a post ID' ),
		);
	}

	/** One bad row does not stop the others being parsed. */
	public function testABadRowDoesNotAbortTheFile(): void {
		$result = CsvParser::parse(
			$this->csv(
				array(
					array( 'Foundations', '', 'session', 'Good One', self::URL ),
					array( '', '', 'widget', 'Bad One', self::URL ),
					array( 'Foundations', '', 'session', 'Good Two', self::URL ),
				)
			)
		);

		$this->assertCount( 3, $result['rows'] );
		$this->assertTrue( CsvParser::is_valid( $result['rows'][0] ) );
		$this->assertFalse( CsvParser::is_valid( $result['rows'][1] ) );
		$this->assertTrue( CsvParser::is_valid( $result['rows'][2] ) );
	}

	/** Header matching tolerates case, spaces and hyphens. */
	public function testHeaderMatchingIsForgiving(): void {
		$csv = $this->csv(
			array( array( 'Foundations', '', 'session', 'Title', self::URL ) ),
			'Heading, Session ID ,TYPE,Title,URL'
		);

		$result = CsvParser::parse( $csv );

		$this->assertSame( array(), $result['errors'] );
		$this->assertTrue( CsvParser::is_valid( $result['rows'][0] ) );
	}

	/** Unknown columns are ignored with a notice rather than rejected. */
	public function testUnknownColumnsAreIgnored(): void {
		$csv = "heading,session_id,type,title,url,week,reviewer\n"
			. '"Foundations","","session","Title","' . self::URL . '","1.0","Diego"' . "\n";

		$result = CsvParser::parse( $csv );

		$this->assertSame( array(), $result['errors'] );
		$this->assertTrue( CsvParser::is_valid( $result['rows'][0] ) );
		$this->assertCount( 2, $result['notices'] );
		$this->assertStringContainsString( 'week', implode( ' ', $result['notices'] ) );
	}

	/**
	 * File-level failures.
	 *
	 * @dataProvider brokenFiles
	 *
	 * @param string $csv      File contents.
	 * @param string $fragment Text the error must contain.
	 */
	public function testFileLevelFailures( string $csv, string $fragment ): void {
		$result = CsvParser::parse( $csv );

		$this->assertSame( array(), $result['rows'] );
		$this->assertNotEmpty( $result['errors'] );
		$this->assertStringContainsString( $fragment, $result['errors'][0] );
	}

	/** @return array<string, array{string, string}> */
	public static function brokenFiles(): array {
		return array(
			'empty file'       => array( '', 'file is empty' ),
			'header only'      => array( "heading,session_id,type,title,url\n", 'no data rows' ),
			'missing columns'  => array( "title,url\n\"A\",\"B\"\n", 'missing required columns' ),
			'too many rows'    => array(
				"heading,session_id,type,title,url\n" . str_repeat( '"","","session","T","' . self::URL . "\"\n", CsvParser::MAX_ROWS + 1 ),
				'more rows than can be imported',
			),
		);
	}

	/** A byte-order mark from a spreadsheet export does not break the header. */
	public function testByteOrderMarkIsStripped(): void {
		$csv = "\xEF\xBB\xBF" . $this->csv( array( array( 'Foundations', '', 'session', 'Title', self::URL ) ) );

		$result = CsvParser::parse( $csv );

		$this->assertSame( array(), $result['errors'] );
		$this->assertSame( 'Foundations', $result['rows'][0]['heading'] );
	}

	/** Quoted values containing commas and newlines survive intact. */
	public function testQuotedValuesWithSeparators(): void {
		$csv = "heading,session_id,type,title,url\n"
			. '"Data, Modelling","","session","Title with, comma","' . self::URL . '"' . "\n";

		$row = CsvParser::parse( $csv )['rows'][0];

		$this->assertSame( 'Data, Modelling', $row['heading'] );
		$this->assertSame( 'Title with, comma', $row['title'] );
	}

	/** Markup in a title is stripped rather than carried into a post title. */
	public function testTitlesAreSanitised(): void {
		$row = CsvParser::parse(
			$this->csv( array( array( '', '', 'session', '<script>alert(1)</script>Real Title', self::URL ) ) )
		)['rows'][0];

		$this->assertStringNotContainsString( '<script>', $row['title'] );
		$this->assertStringContainsString( 'Real Title', $row['title'] );
	}

	/** An over-long title is shortened with a notice rather than rejected. */
	public function testOverlongTitleIsTruncated(): void {
		$row = CsvParser::parse(
			$this->csv( array( array( '', '', 'session', str_repeat( 'a', CsvParser::MAX_TITLE_LENGTH + 50 ), self::URL ) ) )
		)['rows'][0];

		$this->assertTrue( CsvParser::is_valid( $row ) );
		$this->assertSame( CsvParser::MAX_TITLE_LENGTH, mb_strlen( $row['title'] ) );
		$this->assertStringContainsString( 'shortened', $row['notices'][0] );
	}

	/** Wholly blank lines are skipped rather than reported as broken rows. */
	public function testBlankLinesAreSkipped(): void {
		$csv = "heading,session_id,type,title,url\n"
			. '"Foundations","","session","Title","' . self::URL . '"' . "\n"
			. ",,,,\n"
			. "\n"
			. '"Foundations","","session","Second","' . self::URL . '"' . "\n";

		$result = CsvParser::parse( $csv );

		$this->assertCount( 2, $result['rows'] );
	}

	/** Line numbers refer to the file, so an editor can find the row. */
	public function testLineNumbersMatchTheFile(): void {
		$result = CsvParser::parse(
			$this->csv(
				array(
					array( '', '', 'session', 'One', self::URL ),
					array( '', '', 'session', 'Two', self::URL ),
				)
			)
		);

		$this->assertSame( 2, $result['rows'][0]['line'] );
		$this->assertSame( 3, $result['rows'][1]['line'] );
	}

	/** Each row carries its resolved source, ready for the planner. */
	public function testRowCarriesItsResolvedSource(): void {
		$row = CsvParser::parse( $this->csv( array( array( '', '', 'session', 'Title', self::URL ) ) ) )['rows'][0];

		$this->assertSame( DriveUrl::KIND_SLIDES, $row['source']['kind'] );
		$this->assertTrue( $row['source']['importable'] );
	}
}
