<?php
/**
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SlidesImporter\Document\ParserFactory;

/**
 * Covers the format table that the REST layer, job runner and admin UI all
 * dispatch through.
 */
final class ParserFactoryTest extends Unit {

	/**
	 * Dispatch is by file extension, case-insensitively.
	 *
	 * @dataProvider paths
	 *
	 * @param string      $path     File path or name.
	 * @param string|null $expected Format key, or null when unsupported.
	 */
	public function testDetectFormat( string $path, ?string $expected ): void {
		$this->assertSame( $expected, ParserFactory::detect_format( $path ) );
	}

	/** @return array<string, array{string, string|null}> */
	public static function paths(): array {
		return array(
			'pptx'              => array( '/decks/session.pptx', 'pptx' ),
			'pdf'               => array( '/decks/lab.pdf', 'pdf' ),
			'docx'              => array( '/decks/sheet.docx', 'docx' ),
			'uppercase'         => array( '/decks/DECK.PPTX', 'pptx' ),
			'dots in name'      => array( '/decks/v1.2 final.docx', 'docx' ),
			'unsupported'       => array( '/decks/notes.txt', null ),
			'no extension'      => array( '/decks/README', null ),
			'extension in path' => array( '/pdf/archive/notes', null ),
		);
	}

	/**
	 * Both the binary formats and the Google editor types that export to them
	 * resolve, so a Drive pick can be routed before anything is downloaded.
	 *
	 * @dataProvider mimeTypes
	 *
	 * @param string      $mime     MIME type.
	 * @param string|null $expected Format key, or null when unrecognised.
	 */
	public function testFormatForMime( string $mime, ?string $expected ): void {
		$this->assertSame( $expected, ParserFactory::format_for_mime( $mime ) );
	}

	/** @return array<string, array{string, string|null}> */
	public static function mimeTypes(): array {
		return array(
			'google slides' => array( 'application/vnd.google-apps.presentation', 'pptx' ),
			'google docs'   => array( 'application/vnd.google-apps.document', 'docx' ),
			'binary pptx'   => array( 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'pptx' ),
			'binary docx'   => array( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx' ),
			'pdf'           => array( 'application/pdf', 'pdf' ),
			'mixed case'    => array( 'Application/PDF', 'pdf' ),
			'sheets'        => array( 'application/vnd.google-apps.spreadsheet', null ),
			'folder'        => array( 'application/vnd.google-apps.folder', null ),
			'empty'         => array( '', null ),
		);
	}

	/** Only Google editor files need converting; binaries are downloaded as-is. */
	public function testExportMimeOnlyAppliesToGoogleEditorFiles(): void {
		$this->assertSame(
			'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			ParserFactory::export_mime_for( 'application/vnd.google-apps.presentation' )
		);
		$this->assertSame(
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			ParserFactory::export_mime_for( 'application/vnd.google-apps.document' )
		);
		$this->assertNull( ParserFactory::export_mime_for( 'application/pdf' ) );
	}

	/** A direct export URL exists exactly where an export MIME type does. */
	public function testExportUrlTemplates(): void {
		$this->assertStringContainsString( 'presentation', ParserFactory::export_url_template( 'pptx' ) );
		$this->assertStringContainsString( 'document', ParserFactory::export_url_template( 'docx' ) );
		$this->assertSame( '', ParserFactory::export_url_template( 'pdf' ) );
	}

	/** The picker offers both editor types and all three binary types. */
	public function testPickerMimeTypes(): void {
		$types = ParserFactory::picker_mime_types();

		$this->assertContains( 'application/vnd.google-apps.presentation', $types );
		$this->assertContains( 'application/vnd.google-apps.document', $types );
		$this->assertContains( 'application/pdf', $types );
		$this->assertCount( 5, $types, 'Three binary types plus two Google editor types' );
	}

	/** The file input accepts every extension and its MIME type. */
	public function testAcceptAttribute(): void {
		$accept = ParserFactory::accept_attribute();

		foreach ( ParserFactory::extensions() as $extension ) {
			$this->assertStringContainsString( '.' . $extension, $accept );
		}
		foreach ( ParserFactory::mime_types() as $mime ) {
			$this->assertStringContainsString( $mime, $accept );
		}
	}

	/** Error messages list the supported extensions in a readable form. */
	public function testExtensionList(): void {
		$this->assertSame( '.pptx, .docx, .pdf', ParserFactory::extension_list() );
	}

	/**
	 * Each format names its own unit of content, so the UI does not call a Word
	 * section a slide.
	 */
	public function testUnitLabels(): void {
		$this->assertSame( 'slide', ParserFactory::unit_label( 'pptx' ) );
		$this->assertSame( 'page', ParserFactory::unit_label( 'pdf' ) );
		$this->assertSame( 'section', ParserFactory::unit_label( 'docx' ) );

		$this->assertSame( 'slides', ParserFactory::unit_label_plural( 'pptx' ) );
		$this->assertSame( 'pages', ParserFactory::unit_label_plural( 'pdf' ) );
		$this->assertSame( 'sections', ParserFactory::unit_label_plural( 'docx' ) );
	}

	/** An unknown format falls back to slide rather than erroring. */
	public function testUnitLabelFallback(): void {
		$this->assertSame( 'slide', ParserFactory::unit_label( 'xlsx' ) );
		$this->assertSame( 'slides', ParserFactory::unit_label_plural( 'xlsx' ) );
	}

	/** An unsupported file is refused before any parser is reached. */
	public function testParseRejectsUnsupportedExtension(): void {
		$result = ParserFactory::parse( '/tmp/notes.txt', sys_get_temp_dir() );

		$this->assertTrue( is_wp_error( $result ) );
		$this->assertSame( 'cbf_si_unsupported_format', $result->get_error_code() );
		$this->assertStringContainsString( '.pptx', $result->get_error_message() );
	}

	/** Every declared format points at a parser that actually exists. */
	public function testEveryFormatHasALoadableParser(): void {
		foreach ( ParserFactory::FORMATS as $format => $spec ) {
			$this->assertTrue(
				class_exists( $spec['parser'] ),
				"Parser for {$format} should be loadable"
			);
			$this->assertTrue(
				method_exists( $spec['parser'], 'parse' ),
				"Parser for {$format} should expose parse()"
			);
		}
	}
}
