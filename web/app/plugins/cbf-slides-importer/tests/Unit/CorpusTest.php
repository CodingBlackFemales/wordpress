<?php
/**
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SlidesImporter\Document\BlockRenderer;
use CodingBlackFemales\SlidesImporter\Document\ParserFactory;
use CodingBlackFemales\SlidesImporter\Document\SlideClassifier;
use CodingBlackFemales\SlidesImporter\Tests\Support\Helper\Fixtures;
use CodingBlackFemales\SlidesImporter\Tests\UnitTester;

/**
 * Parses every real document in a nominated directory.
 *
 * The committed fixtures are small and synthetic; they prove specific
 * behaviours but say nothing about the messy decks editors actually import.
 * Real CBF decks are too large to commit, so this test is skipped unless
 * CBF_SI_FIXTURE_DIR names a directory of them:
 *
 *   CBF_SI_FIXTURE_DIR=/path/to/decks composer test
 *
 * It asserts only what must hold for any input — no errors, no PHP warnings,
 * renderable output — because the content itself is unknown to the test. That
 * makes it a regression net for library upgrades and parser changes.
 */
final class CorpusTest extends Unit {

	/** @var UnitTester */
	protected $tester;

	/**
	 * Every supported document in the nominated directory parses and renders.
	 */
	public function testCorpusParsesWithoutErrorsOrWarnings(): void {
		$corpus = $this->tester->corpus();

		if ( $corpus === array() ) {
			$this->markTestSkipped(
				sprintf(
					'No corpus found. Put documents in %s, or point %s at a directory — as an environment variable or in the plugin\'s .env file.',
					Fixtures::CORPUS_DEFAULT_DIR,
					Fixtures::CORPUS_ENV
				)
			);
		}

		foreach ( $corpus as $path ) {
			$this->assertDocumentImports( $path );
		}
	}

	/**
	 * Parse, classify and render one document, failing on any diagnostic.
	 *
	 * @param string $path Absolute path to a document.
	 */
	private function assertDocumentImports( string $path ): void {
		$name    = basename( $path );
		$notices = array();

		set_error_handler(
			static function ( int $number, string $message ) use ( &$notices ): bool {
				$notices[] = $message;
				return true;
			}
		);

		try {
			$parsed = ParserFactory::parse( $path, $this->tester->imageDir() );
		} finally {
			restore_error_handler();
		}

		$this->assertFalse(
			is_wp_error( $parsed ),
			$name . ': ' . ( is_wp_error( $parsed ) ? $parsed->get_error_message() : '' )
		);
		$this->assertSame( array(), $notices, $name . ' raised PHP diagnostics while parsing' );

		$this->assertNotEmpty( $parsed['slides'], $name . ' produced no units' );
		$this->assertSame( ParserFactory::detect_format( $path ), $parsed['source_format'], $name );

		$rendered = BlockRenderer::render( SlideClassifier::classify( $parsed, '' ) );

		$this->assertIsString( $rendered['lesson_html'], $name . ' did not render' );
		$this->assertStringNotContainsString(
			'<script',
			$rendered['lesson_html'],
			$name . ': document text must be escaped'
		);
	}
}
