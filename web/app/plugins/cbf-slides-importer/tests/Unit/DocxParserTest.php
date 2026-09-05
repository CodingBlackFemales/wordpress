<?php
/**
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SlidesImporter\Docx\Parser;
use CodingBlackFemales\SlidesImporter\Document\Ir;
use CodingBlackFemales\SlidesImporter\Document\ParserFactory;
use CodingBlackFemales\SlidesImporter\Tests\UnitTester;

/**
 * Covers DOCX parsing against tests/_data/bin/document.docx.
 *
 * The fixture has a title, two Heading 2 parts, a Heading 3, bulleted and
 * numbered lists backed by real numbering definitions, a table with a bold
 * header row, an inline image, and text containing XML entities.
 */
final class DocxParserTest extends Unit {

	/** @var UnitTester */
	protected $tester;

	/** @var array Parsed document. */
	private $deck;

	/**
	 * Parse the fixture once per test.
	 */
	protected function _before(): void {
		$this->deck = Parser::parse( $this->tester->fixture( 'document.docx' ), $this->tester->imageDir() );
		$this->assertFalse( is_wp_error( $this->deck ), 'Fixture document should parse' );
	}

	/**
	 * Find a section by its 1-based number.
	 *
	 * @param int $number Section number.
	 */
	private function section( int $number ): array {
		return $this->deck['slides'][ $number - 1 ];
	}

	/**
	 * All paragraphs in a section, excluding table blocks.
	 *
	 * @param int $number Section number.
	 * @return array Paragraph arrays.
	 */
	private function paragraphs( int $number ): array {
		$paragraphs = array();

		foreach ( $this->section( $number )['content'] as $block ) {
			foreach ( $block['paragraphs'] ?? array() as $paragraph ) {
				$paragraphs[] = $paragraph;
			}
		}

		return $paragraphs;
	}

	/** A document has sections rather than slides, and no page geometry. */
	public function testDocumentMetadata(): void {
		$this->assertSame( ParserFactory::FORMAT_DOCX, $this->deck['source_format'] );
		$this->assertSame( 'section', $this->deck['unit_label'] );
		$this->assertSame( 0, $this->deck['slide_width_px'] );
		$this->assertSame( 0, $this->deck['slide_height_px'] );
	}

	/**
	 * Splitting descends past a lone top-level heading.
	 *
	 * The document's only Heading 1 is its title, so splitting on it would
	 * collapse the whole file into one section; Heading 2 is used instead.
	 */
	public function testSectioningSplitsOnTheRepeatedHeadingDepth(): void {
		$this->assertCount( 3, $this->deck['slides'] );
		$this->assertSame( 'Fixture Guide', $this->section( 1 )['title'] );
		$this->assertSame( 'Part One', $this->section( 2 )['title'] );
		$this->assertSame( 'Part Two', $this->section( 3 )['title'] );
	}

	/** A leading heading with no body of its own is a title page. */
	public function testLeadingTitleSectionIsMarkedAsCover(): void {
		$this->assertTrue( $this->section( 1 )['is_cover'] );
		$this->assertSame( array(), $this->section( 1 )['content'] );

		$this->assertFalse( $this->section( 2 )['is_cover'] );
	}

	/** The heading's Word style travels through as the layout name. */
	public function testHeadingStyleBecomesLayoutName(): void {
		$this->assertSame( 'Heading1', $this->section( 1 )['layout_name'] );
		$this->assertSame( 'Heading2', $this->section( 2 )['layout_name'] );
	}

	/** Word has no hidden-section concept. */
	public function testNothingIsHidden(): void {
		foreach ( $this->deck['slides'] as $slide ) {
			$this->assertFalse( $slide['is_hidden'] );
		}
	}

	/**
	 * Headings nest relative to the split depth.
	 *
	 * A section's own heading is rendered as the H2 that opens it, so the next
	 * depth down has to start at H3 rather than skipping a level.
	 */
	public function testNestedHeadingLevelIsRelativeToTheSplitDepth(): void {
		$headings = array_values(
			array_filter( $this->paragraphs( 2 ), static fn( array $p ): bool => $p['kind'] === Ir::KIND_HEADING )
		);

		$this->assertCount( 1, $headings );
		$this->assertSame( 'Nested Detail', Ir::plain_text( $headings[0] ) );
		$this->assertSame( 3, $headings[0]['level'] );
	}

	/**
	 * Entities are decoded once, on the way in.
	 *
	 * PhpWord returns run text exactly as it appeared in the XML, so without
	 * decoding here the renderer would escape it a second time and readers
	 * would see the raw entity.
	 */
	public function testXmlEntitiesAreDecoded(): void {
		$text = Ir::plain_text_all( $this->paragraphs( 2 ) );

		$this->assertStringContainsString( 'R&D', $text );
		$this->assertStringContainsString( '"quotes"', $text );
		$this->assertStringNotContainsString( '&amp;', $text );
		$this->assertStringNotContainsString( '&quot;', $text );
	}

	/** List items are bullets, and their numbering decides the list type. */
	public function testListTypesComeFromTheNumberingDefinitions(): void {
		$bullets = array_values(
			array_filter( $this->paragraphs( 2 ), static fn( array $p ): bool => $p['kind'] === Ir::KIND_BULLET )
		);

		$this->assertCount( 4, $bullets );

		$by_text = array();
		foreach ( $bullets as $bullet ) {
			$by_text[ Ir::plain_text( $bullet ) ] = $bullet['ordered'];
		}

		$this->assertFalse( $by_text['First bullet'], 'numFmt bullet is unordered' );
		$this->assertFalse( $by_text['Second bullet'] );
		$this->assertTrue( $by_text['Step one'], 'numFmt decimal is ordered' );
		$this->assertTrue( $by_text['Step two'] );
	}

	/** A Word table becomes a table content block. */
	public function testTableIsPreserved(): void {
		$tables = array_values(
			array_filter(
				$this->section( 2 )['content'],
				static fn( array $block ): bool => ( $block['type'] ?? '' ) === 'table'
			)
		);

		$this->assertCount( 1, $tables, 'The table must survive the empty-paragraph filter' );
		$this->assertCount( 2, $tables[0]['rows'] );
		$this->assertTrue( $tables[0]['header'], 'An all-bold first row is a header row' );
		$this->assertSame( 'Column A', Ir::plain_text_all( $tables[0]['rows'][0][0] ) );
		$this->assertSame( 'b1', Ir::plain_text_all( $tables[0]['rows'][1][1] ) );
	}

	/** An inline image is extracted to disk. */
	public function testInlineImageIsExtracted(): void {
		$images = $this->section( 3 )['images'];

		$this->assertCount( 1, $images );
		$this->assertSame( 'png', $images[0]['ext'] );
		$this->assertFileExists( $images[0]['path'] );
		$this->assertSame( array( 'slide_003_img_01.png' ), $this->tester->extractedImages() );
	}

	/** A missing file is reported rather than throwing. */
	public function testMissingFileReturnsError(): void {
		$result = Parser::parse( '/tmp/does-not-exist.docx', $this->tester->imageDir() );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'cbf_si_docx_missing', $result->get_error_code() );
	}

	/** A file that is not a document fails with an error, not an exception. */
	public function testMalformedFileReturnsError(): void {
		$path = $this->tester->imageDir() . '/broken.docx';
		file_put_contents( $path, "PK\x03\x04 not really a package" );

		$result = Parser::parse( $path, $this->tester->imageDir() );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'cbf_si_parse_error', $result->get_error_code() );
	}

	/** A document with no readable content is reported rather than imported empty. */
	public function testEmptyDocumentReturnsError(): void {
		$path = $this->tester->imageDir() . '/empty.docx';
		file_put_contents( $path, '' );

		$result = Parser::parse( $path, $this->tester->imageDir() );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertContains(
			$result->get_error_code(),
			array( 'cbf_si_docx_empty', 'cbf_si_parse_error' )
		);
	}

	/** The parse result holds no PHP objects. */
	public function testParsedDocumentContainsNoObjects(): void {
		$objects = array();

		array_walk_recursive(
			$this->deck,
			static function ( $value ) use ( &$objects ): void {
				if ( is_object( $value ) || is_resource( $value ) ) {
					$objects[] = $value;
				}
			}
		);

		$this->assertSame( array(), $objects );
	}
}
