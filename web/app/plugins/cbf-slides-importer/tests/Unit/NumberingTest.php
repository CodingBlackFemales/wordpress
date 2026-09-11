<?php
/**
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SlidesImporter\Docx\Numbering;
use CodingBlackFemales\SlidesImporter\Tests\UnitTester;

/**
 * Covers resolution of Word list numbering.
 *
 * PhpWord records which numbering definition a list item belongs to but not
 * whether that definition renders as a bullet or a counter, so the answer is
 * read out of word/numbering.xml directly.
 */
final class NumberingTest extends Unit {

	/** @var UnitTester */
	protected $tester;

	/** A map covering both list kinds and two indent levels. */
	private const MAP = array(
		1 => array(
			0 => 'bullet',
			1 => 'bullet',
		),
		2 => array(
			0 => 'decimal',
			1 => 'lowerLetter',
		),
	);

	/** Bulleted definitions are unordered. */
	public function testBulletFormatIsUnordered(): void {
		$this->assertFalse( Numbering::is_ordered( self::MAP, 1, 0 ) );
		$this->assertFalse( Numbering::is_ordered( self::MAP, 1, 1 ) );
	}

	/** Any counting format is ordered, not just decimal. */
	public function testCountingFormatsAreOrdered(): void {
		$this->assertTrue( Numbering::is_ordered( self::MAP, 2, 0 ) );
		$this->assertTrue( Numbering::is_ordered( self::MAP, 2, 1 ) );
	}

	/** A level the definition does not declare falls back to level zero. */
	public function testUnknownLevelFallsBackToTheFirst(): void {
		$this->assertTrue( Numbering::is_ordered( self::MAP, 2, 7 ) );
		$this->assertFalse( Numbering::is_ordered( self::MAP, 1, 7 ) );
	}

	/**
	 * Anything unresolvable is treated as unordered.
	 *
	 * @dataProvider unresolvable
	 *
	 * @param array    $map    Numbering map.
	 * @param int|null $num_id Numbering id.
	 */
	public function testUnresolvableIsUnordered( array $map, ?int $num_id ): void {
		$this->assertFalse( Numbering::is_ordered( $map, $num_id, 0 ) );
	}

	/** @return array<string, array{array, int|null}> */
	public static function unresolvable(): array {
		return array(
			'unknown id' => array( self::MAP, 99 ),
			'null id'    => array( self::MAP, null ),
			'empty map'  => array( array(), 2 ),
		);
	}

	/** Definitions are read out of the package and keyed by numbering id. */
	public function testReadResolvesDefinitionsFromTheDocument(): void {
		$map = Numbering::read( $this->tester->fixture( 'document.docx' ) );

		$this->assertArrayHasKey( 1, $map );
		$this->assertArrayHasKey( 2, $map );
		$this->assertSame( 'bullet', $map[1][0] );
		$this->assertSame( 'decimal', $map[2][0] );
	}

	/** The map read from a real document drives the ordered decision. */
	public function testReadMapDrivesOrdering(): void {
		$map = Numbering::read( $this->tester->fixture( 'document.docx' ) );

		$this->assertFalse( Numbering::is_ordered( $map, 1, 0 ) );
		$this->assertTrue( Numbering::is_ordered( $map, 2, 0 ) );
	}

	/** A document with no lists carries no numbering part; that is not an error. */
	public function testDocumentWithoutListsYieldsAnEmptyMap(): void {
		$path = $this->tester->imageDir() . '/no-lists.docx';
		$zip  = new \ZipArchive();
		$zip->open( $path, \ZipArchive::CREATE );
		$zip->addFromString( 'word/document.xml', '<w:document/>' );
		$zip->close();

		$this->assertSame( array(), Numbering::read( $path ) );
	}

	/** An unreadable file yields an empty map rather than an error. */
	public function testUnreadableFileYieldsAnEmptyMap(): void {
		$this->assertSame( array(), Numbering::read( '/tmp/does-not-exist.docx' ) );
	}

	/** Malformed numbering XML is survivable. */
	public function testMalformedNumberingXmlYieldsAnEmptyMap(): void {
		$path = $this->tester->imageDir() . '/bad-numbering.docx';
		$zip  = new \ZipArchive();
		$zip->open( $path, \ZipArchive::CREATE );
		$zip->addFromString( 'word/numbering.xml', '<w:numbering><unclosed>' );
		$zip->close();

		$this->assertSame( array(), Numbering::read( $path ) );
	}
}
