<?php
/**
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SlidesImporter\Document\Ir;
use CodingBlackFemales\SlidesImporter\Document\ParserFactory;
use CodingBlackFemales\SlidesImporter\Pptx\Parser;
use CodingBlackFemales\SlidesImporter\Tests\UnitTester;

/**
 * Covers PPTX parsing against tests/_data/bin/deck.pptx.
 *
 * The fixture is a 960x540 deck: a cover, a two-column slide, a slide mixing
 * bullets with code, a hidden slide, and a slide carrying one content image
 * plus one in the footer band.
 */
final class PptxParserTest extends Unit {

	/** @var UnitTester */
	protected $tester;

	/** @var array Parsed deck, shared across assertions in a single test. */
	private $deck;

	/**
	 * Parse the fixture once per test.
	 */
	protected function _before(): void {
		$this->deck = Parser::parse( $this->tester->fixture( 'deck.pptx' ), $this->tester->imageDir() );
		$this->assertFalse( is_wp_error( $this->deck ), 'Fixture deck should parse' );
	}

	/**
	 * Find a unit by its 1-based number.
	 *
	 * @param int $number Slide number.
	 * @return array ParsedSlide.
	 */
	private function slide( int $number ): array {
		return $this->deck['slides'][ $number - 1 ];
	}

	/**
	 * All text in a unit, flattened across blocks and columns.
	 *
	 * @param int $number Slide number.
	 */
	private function text( int $number ): string {
		$parts = array();

		foreach ( $this->slide( $number )['content'] as $block ) {
			if ( ( $block['type'] ?? '' ) === 'columns' ) {
				foreach ( $block['columns'] as $column ) {
					$parts[] = Ir::plain_text_all( $column );
				}
				continue;
			}
			$parts[] = Ir::plain_text_all( $block['paragraphs'] ?? array() );
		}

		return implode( "\n", $parts );
	}

	/** The deck reports its format, unit noun and pixel dimensions. */
	public function testDeckMetadata(): void {
		$this->assertSame( ParserFactory::FORMAT_PPTX, $this->deck['source_format'] );
		$this->assertSame( 'slide', $this->deck['unit_label'] );
		$this->assertSame( 960, $this->deck['slide_width_px'] );
		$this->assertSame( 540, $this->deck['slide_height_px'] );
		$this->assertCount( 5, $this->deck['slides'] );
	}

	/** Units are numbered from one and indexed from zero. */
	public function testUnitNumbering(): void {
		foreach ( $this->deck['slides'] as $index => $slide ) {
			$this->assertSame( $index, $slide['index'] );
			$this->assertSame( $index + 1, $slide['slide_number'] );
		}
	}

	/** Titles come from the title placeholder, not from position. */
	public function testTitlesComeFromPlaceholders(): void {
		$this->assertSame( 'Fixture Deck', $this->slide( 1 )['title'] );
		$this->assertSame( 'Two Columns', $this->slide( 2 )['title'] );
		$this->assertSame( 'With Images', $this->slide( 5 )['title'] );
	}

	/** Slide 1 is the cover; nothing else is. */
	public function testFirstSlideIsTheCover(): void {
		$this->assertTrue( $this->slide( 1 )['is_cover'] );

		foreach ( array( 2, 3, 4, 5 ) as $number ) {
			$this->assertFalse( $this->slide( $number )['is_cover'] );
		}
	}

	/** The show="0" attribute marks a slide hidden. */
	public function testHiddenSlideIsDetected(): void {
		$this->assertTrue( $this->slide( 4 )['is_hidden'] );
		$this->assertFalse( $this->slide( 2 )['is_hidden'] );
	}

	/** Side-by-side text boxes become a columns block. */
	public function testSideBySideShapesBecomeColumns(): void {
		$content = $this->slide( 2 )['content'];

		$this->assertCount( 1, $content );
		$this->assertSame( 'columns', $content[0]['type'] );
		$this->assertCount( 2, $content[0]['columns'] );
		$this->assertSame( 'Left body text.', Ir::plain_text_all( $content[0]['columns'][0] ) );
		$this->assertSame( 'Right body text.', Ir::plain_text_all( $content[0]['columns'][1] ) );
	}

	/** Column widths are reported as percentages of the slide width. */
	public function testColumnWidthsArePercentages(): void {
		$widths = $this->slide( 2 )['content'][0]['widths'];

		$this->assertCount( 2, $widths );
		foreach ( $widths as $width ) {
			$this->assertGreaterThan( 0, $width );
			$this->assertLessThanOrEqual( 100, $width );
		}
	}

	/** The title placeholder is not repeated in the body content. */
	public function testTitleIsNotDuplicatedInContent(): void {
		$this->assertStringNotContainsString( 'Two Columns', $this->text( 2 ) );
	}

	/** Bulleted paragraphs are classified as bullets. */
	public function testBulletsAreClassified(): void {
		$kinds = array_column( $this->slide( 3 )['content'][0]['paragraphs'], 'kind' );

		$this->assertSame( Ir::KIND_BULLET, $kinds[0] );
		$this->assertSame( Ir::KIND_BULLET, $kinds[1] );
	}

	/** A wholly monospaced paragraph becomes a code block. */
	public function testFullyMonospacedParagraphIsCode(): void {
		$paragraphs = $this->slide( 3 )['content'][0]['paragraphs'];
		$code       = array_values(
			array_filter( $paragraphs, static fn( array $p ): bool => $p['kind'] === Ir::KIND_CODE )
		);

		$this->assertCount( 1, $code );
		$this->assertSame( 'composer install', Ir::plain_text( $code[0] ) );
	}

	/**
	 * A code font used for a few words inside prose stays an inline run.
	 *
	 * Treating any monospaced run as a code block turned whole paragraphs of
	 * body copy into <pre> blocks, which is what this guards against.
	 */
	public function testPartiallyMonospacedParagraphStaysProse(): void {
		$paragraphs = $this->slide( 3 )['content'][0]['paragraphs'];
		$mixed      = array_values(
			array_filter(
				$paragraphs,
				static fn( array $p ): bool => str_contains( Ir::plain_text( $p ), 'npm test' )
			)
		);

		$this->assertCount( 1, $mixed );
		$this->assertSame( Ir::KIND_PARAGRAPH, $mixed[0]['kind'] );
		$this->assertSame( 'Run npm test first.', Ir::plain_text( $mixed[0] ) );

		$mono = array_column( $mixed[0]['runs'], 'mono' );
		$this->assertContains( true, $mono, 'The code run is still marked monospaced' );
		$this->assertContains( false, $mono, 'The surrounding prose is not' );
	}

	/** Content images are extracted; footer-band images are not. */
	public function testFooterImagesAreExcluded(): void {
		$this->assertCount( 1, $this->slide( 5 )['images'] );
		$this->assertSame( array( 'slide_005_img_01.png' ), $this->tester->extractedImages() );
	}

	/** Extracted images are written to disk with usable metadata. */
	public function testExtractedImageIsWrittenToDisk(): void {
		$image = $this->slide( 5 )['images'][0];

		$this->assertFileExists( $image['path'] );
		$this->assertGreaterThan( 0, filesize( $image['path'] ) );
		$this->assertSame( 'png', $image['ext'] );
		$this->assertSame( 'slide_005_img_01.png', $image['filename'] );
	}

	/** A missing file is reported rather than throwing. */
	public function testMissingFileReturnsError(): void {
		$result = Parser::parse( '/tmp/does-not-exist.pptx', $this->tester->imageDir() );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'cbf_si_pptx_missing', $result->get_error_code() );
	}

	/** A file that is not a deck fails with an error, not an exception. */
	public function testMalformedFileReturnsError(): void {
		$path = $this->tester->imageDir() . '/broken.pptx';
		file_put_contents( $path, "PK\x03\x04 not really a package" );

		$result = Parser::parse( $path, $this->tester->imageDir() );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'cbf_si_parse_error', $result->get_error_code() );
	}

	/**
	 * The parsed deck holds no PHP objects.
	 *
	 * PhpPresentation shape instances used to be embedded in the parse result
	 * and silently became empty arrays when the job summary was JSON-encoded,
	 * producing imports with headings but no body text. The IR exists partly to
	 * make that unrepresentable.
	 */
	public function testParsedDeckContainsNoObjects(): void {
		$objects = array();

		array_walk_recursive(
			$this->deck,
			static function ( $value, $key ) use ( &$objects ): void {
				if ( is_object( $value ) || is_resource( $value ) ) {
					$objects[] = $key;
				}
			}
		);

		$this->assertSame( array(), $objects, 'Parsed deck should be plain arrays and scalars' );

		// Values survive encoding; whole floats come back as ints, hence loose comparison.
		$encoded = json_encode( $this->deck );
		$this->assertIsString( $encoded );
		$this->assertEquals( $this->deck, json_decode( $encoded, true ) );
	}
}
