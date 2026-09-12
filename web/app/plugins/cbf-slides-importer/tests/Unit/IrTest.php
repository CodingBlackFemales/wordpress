<?php
/**
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SlidesImporter\Document\Ir;

/**
 * Covers the shared intermediate representation's constructors and the
 * heuristics every parser depends on.
 */
final class IrTest extends Unit {

	/**
	 * A bullet glyph is recognised even when drawn hard against the text.
	 *
	 * PDF exporters routinely place the bullet with no separating space, which
	 * is what this allows for.
	 */
	public function testGlyphBulletNeedsNoFollowingSpace(): void {
		$result = Ir::split_bullet_marker( '•unique: no duplicates' );

		$this->assertSame( 'unique: no duplicates', $result['text'] );
		$this->assertFalse( $result['ordered'] );
	}

	/** Whitespace after a glyph marker is stripped along with the marker. */
	public function testGlyphBulletStripsFollowingSpace(): void {
		$this->assertSame( 'spaced item', Ir::split_bullet_marker( '• spaced item' )['text'] );
	}

	/** Every glyph in the marker list is treated as a bullet. */
	public function testAllBulletGlyphsAreRecognised(): void {
		foreach ( Ir::BULLET_GLYPHS as $glyph ) {
			$this->assertSame(
				'item',
				Ir::split_bullet_marker( $glyph . ' item' )['text'],
				"Glyph {$glyph} should start a bullet"
			);
		}
	}

	/** A dash only starts a bullet when whitespace follows it. */
	public function testDashBulletRequiresFollowingSpace(): void {
		$this->assertSame( 'Subject-oriented', Ir::split_bullet_marker( '- Subject-oriented' )['text'] );
		$this->assertNull( Ir::split_bullet_marker( 'well-known behaviour' ) );
	}

	/**
	 * Lines that merely start with punctuation are not lists.
	 *
	 * @dataProvider nonBulletLines
	 *
	 * @param string $line Line that must not be treated as a bullet.
	 */
	public function testNonBulletLines( string $line ): void {
		$this->assertNull( Ir::split_bullet_marker( $line ) );
	}

	/** @return array<string, array{string}> */
	public static function nonBulletLines(): array {
		return array(
			'plain sentence' => array( 'Just a sentence.' ),
			'bare dash'      => array( '-' ),
			'bare glyph'     => array( '•' ),
			'empty'          => array( '' ),
			'whitespace'     => array( "  \t " ),
			'multiplication' => array( '3*4 equals 12' ),
			'numbered line'  => array( '1. Not treated as a list' ),
		);
	}

	/**
	 * Monospaced families are identified by name, since that is the only
	 * signal PDF and PPTX offer.
	 *
	 * @dataProvider fontNames
	 *
	 * @param string $font     Font family name.
	 * @param bool   $expected Whether it should read as monospaced.
	 */
	public function testMonoFontDetection( string $font, bool $expected ): void {
		$this->assertSame( $expected, Ir::is_mono_font( $font ) );
	}

	/** @return array<string, array{string, bool}> */
	public static function fontNames(): array {
		return array(
			'Consolas'             => array( 'Consolas', true ),
			'PDF subset prefix'    => array( 'QHIUOC+Consolas', true ),
			'Courier New'          => array( 'Courier New', true ),
			'Roboto Mono'          => array( 'Roboto Mono', true ),
			'Calibri'              => array( 'Calibri', false ),
			'Montserrat'           => array( 'Montserrat', false ),
			'subset proportional'  => array( 'ZPJYOD+Montserrat-Regular', false ),
			'empty'                => array( '', false ),
		);
	}

	/** PDF carries weight and slant only in the embedded font name. */
	public function testFontStyleFlagsComeFromTheName(): void {
		$bold = Ir::font_style_flags( 'IFABKG+Montserrat-Bold' );
		$this->assertTrue( $bold['bold'] );
		$this->assertFalse( $bold['italic'] );

		$italic = Ir::font_style_flags( 'ArialMT,BoldItalic' );
		$this->assertTrue( $italic['bold'] );
		$this->assertTrue( $italic['italic'] );

		$plain = Ir::font_style_flags( 'ZPJYOD+Montserrat-Regular' );
		$this->assertFalse( $plain['bold'] );
		$this->assertFalse( $plain['italic'] );
	}

	/** The six-letter subset tag PDF prepends carries no meaning. */
	public function testSubsetPrefixIsStripped(): void {
		$this->assertSame( 'Consolas', Ir::base_font_name( 'QHIUOC+Consolas' ) );
		$this->assertSame( 'Arial', Ir::base_font_name( 'Arial' ) );
	}

	/** Runs default to unstyled, and flags are normalised to booleans. */
	public function testRunDefaults(): void {
		$run = Ir::run( 'text' );

		$this->assertSame( 'text', $run['text'] );
		$this->assertSame( '', $run['link'] );
		foreach ( array( 'bold', 'italic', 'underline', 'strike', 'mono' ) as $flag ) {
			$this->assertFalse( $run[ $flag ], "{$flag} should default to false" );
		}
	}

	/** Paragraph attributes are optional and default sensibly. */
	public function testParagraphDefaults(): void {
		$para = Ir::para( Ir::KIND_PARAGRAPH, array( Ir::run( 'a' ) ) );

		$this->assertNull( $para['size'] );
		$this->assertSame( 0, $para['level'] );
		$this->assertFalse( $para['ordered'] );
	}

	/** Text spans every run in a paragraph, and every paragraph in a list. */
	public function testPlainTextConcatenation(): void {
		$para = Ir::para(
			Ir::KIND_PARAGRAPH,
			array( Ir::run( 'Hello ' ), Ir::run( 'world', array( 'bold' => true ) ) )
		);

		$this->assertSame( 'Hello world', Ir::plain_text( $para ) );
		$this->assertSame(
			"Hello world\nSecond",
			Ir::plain_text_all( array( $para, Ir::text_para( Ir::KIND_PARAGRAPH, 'Second' ) ) )
		);
	}

	/** Blank paragraphs are dropped and the list is re-indexed. */
	public function testDropEmptyRemovesWhitespaceOnlyParagraphs(): void {
		$kept = Ir::drop_empty(
			array(
				Ir::text_para( Ir::KIND_PARAGRAPH, 'keep' ),
				Ir::text_para( Ir::KIND_PARAGRAPH, "  \n " ),
				Ir::text_para( Ir::KIND_PARAGRAPH, '' ),
				Ir::text_para( Ir::KIND_PARAGRAPH, 'also keep' ),
			)
		);

		$this->assertCount( 2, $kept );
		$this->assertSame( array( 0, 1 ), array_keys( $kept ) );
		$this->assertSame( 'also keep', Ir::plain_text( $kept[1] ) );
	}

	/** Box geometry is rounded to whole pixels for the layout pass. */
	public function testBoxRoundsGeometry(): void {
		$box = Ir::box( 10.4, 20.6, 100.5, 50.49, array() );

		$this->assertSame( 10, $box['l'] );
		$this->assertSame( 21, $box['t'] );
		$this->assertSame( 101, $box['w'] );
		$this->assertSame( 50, $box['h'] );
	}
}
