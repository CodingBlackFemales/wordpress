<?php
/**
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SlidesImporter\Document\Ir;
use CodingBlackFemales\SlidesImporter\Document\ParserFactory;
use CodingBlackFemales\SlidesImporter\Pdf\Parser;
use CodingBlackFemales\SlidesImporter\Tests\UnitTester;

/**
 * Covers PDF parsing against tests/_data/bin/slides.pdf.
 *
 * The fixture is a 720x405pt deck whose glyphs are positioned individually with
 * word gaps expressed as spacing rather than drawn space characters — the case
 * that forces the extractor to reconstruct structure rather than read it.
 */
final class PdfParserTest extends Unit {

	/** @var UnitTester */
	protected $tester;

	/** @var array Parsed deck. */
	private $deck;

	/**
	 * Parse the fixture once per test.
	 */
	protected function _before(): void {
		$this->deck = Parser::parse( $this->tester->fixture( 'slides.pdf' ), $this->tester->imageDir() );
		$this->assertFalse( is_wp_error( $this->deck ), 'Fixture PDF should parse' );
	}

	/**
	 * All paragraphs on a page, flattened.
	 *
	 * @param int $number Page number.
	 * @return array Paragraph arrays.
	 */
	private function paragraphs( int $number ): array {
		$paragraphs = array();

		foreach ( $this->deck['slides'][ $number - 1 ]['content'] as $block ) {
			foreach ( $block['paragraphs'] ?? array() as $paragraph ) {
				$paragraphs[] = $paragraph;
			}
		}

		return $paragraphs;
	}

	/**
	 * All text on a page.
	 *
	 * @param int $number Page number.
	 */
	private function text( int $number ): string {
		return Ir::plain_text_all( $this->paragraphs( $number ) );
	}

	/** Pages are reported in pixels, with the format's own unit noun. */
	public function testDeckMetadata(): void {
		$this->assertSame( ParserFactory::FORMAT_PDF, $this->deck['source_format'] );
		$this->assertSame( 'page', $this->deck['unit_label'] );
		$this->assertCount( 2, $this->deck['slides'] );

		// 720x405pt at 96 DPI.
		$this->assertSame( 960, $this->deck['slide_width_px'] );
		$this->assertSame( 540, $this->deck['slide_height_px'] );
	}

	/** Page one is the cover; PDFs have no hidden pages. */
	public function testCoverAndHiddenFlags(): void {
		$this->assertTrue( $this->deck['slides'][0]['is_cover'] );
		$this->assertFalse( $this->deck['slides'][1]['is_cover'] );

		foreach ( $this->deck['slides'] as $slide ) {
			$this->assertFalse( $slide['is_hidden'] );
		}
	}

	/** PDFs carry no layout names, so type auto-detection never fires. */
	public function testLayoutNameIsEmpty(): void {
		foreach ( $this->deck['slides'] as $slide ) {
			$this->assertSame( '', $slide['layout_name'] );
		}
	}

	/**
	 * Word spacing is rebuilt from glyph positions.
	 *
	 * The fixture draws no space characters at all; every space in the output
	 * was inferred from the gaps between glyphs.
	 */
	public function testWordSpacingIsReconstructed(): void {
		$this->assertStringContainsString( 'A separate paragraph.', $this->text( 2 ) );
		$this->assertStringContainsString( 'Fixture Slides', $this->text( 1 ) );
	}

	/** The largest text near the top of a page becomes its title. */
	public function testTitleIsTheLargestTextAtTheTop(): void {
		$this->assertSame( 'Reconstructed Page', $this->deck['slides'][1]['title'] );
	}

	/** The title is not repeated in the page body. */
	public function testTitleIsNotDuplicatedInContent(): void {
		$this->assertStringNotContainsString( 'Reconstructed Page', $this->text( 2 ) );
	}

	/**
	 * A line that runs to the block's right edge continues into the next.
	 *
	 * Without this, every visual line would import as its own paragraph.
	 */
	public function testWrappedLinesAreRejoined(): void {
		$this->assertStringContainsString(
			'wraps onto the following line and must be rejoined.',
			$this->text( 2 )
		);
	}

	/** A leading bullet glyph is recognised and stripped. */
	public function testBulletIsDetectedAndMarkerRemoved(): void {
		$bullets = array_values(
			array_filter( $this->paragraphs( 2 ), static fn( array $p ): bool => $p['kind'] === Ir::KIND_BULLET )
		);

		$this->assertCount( 1, $bullets );
		$this->assertSame( 'A bullet item', Ir::plain_text( $bullets[0] ) );
	}

	/**
	 * A monospaced line becomes code, because the page's body font is not
	 * monospaced and so the font name carries information.
	 */
	public function testMonospacedLineBecomesCode(): void {
		$code = array_values(
			array_filter( $this->paragraphs( 2 ), static fn( array $p ): bool => $p['kind'] === Ir::KIND_CODE )
		);

		$this->assertCount( 1, $code );
		$this->assertSame( 'composer install', Ir::plain_text( $code[0] ) );
	}

	/** Text in the footer band is page furniture and is dropped. */
	public function testFooterTextIsExcluded(): void {
		$this->assertStringNotContainsString( 'Copyright', $this->text( 2 ) );
	}

	/** The content image is extracted; the footer-band one is not. */
	public function testFooterImagesAreExcluded(): void {
		$this->assertCount( 1, $this->deck['slides'][1]['images'] );
		$this->assertSame( array( 'slide_002_img_01.jpg' ), $this->tester->extractedImages() );
	}

	/** Image bytes are written out with the extension their format implies. */
	public function testExtractedImageIsAUsableJpeg(): void {
		$image = $this->deck['slides'][1]['images'][0];

		$this->assertSame( 'jpg', $image['ext'] );
		$this->assertFileExists( $image['path'] );
		$this->assertSame( "\xFF\xD8\xFF", substr( (string) file_get_contents( $image['path'] ), 0, 3 ) );
	}

	/** A missing file is reported rather than throwing. */
	public function testMissingFileReturnsError(): void {
		$result = Parser::parse( '/tmp/does-not-exist.pdf', $this->tester->imageDir() );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'cbf_si_pdf_missing', $result->get_error_code() );
	}

	/** A file that is not a PDF fails with an error, not an exception. */
	public function testMalformedFileReturnsError(): void {
		$path = $this->tester->imageDir() . '/broken.pdf';
		file_put_contents( $path, "%PDF-1.4\nnot actually a pdf\n" );

		$result = Parser::parse( $path, $this->tester->imageDir() );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'cbf_si_parse_error', $result->get_error_code() );
	}

	/** The parse result holds no PHP objects. */
	public function testParsedDeckContainsNoObjects(): void {
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
