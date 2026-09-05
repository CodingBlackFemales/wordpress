<?php
/**
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SlidesImporter\Import\JobRunner;
use CodingBlackFemales\SlidesImporter\Import\PreviewRenderer;
use CodingBlackFemales\SlidesImporter\Tests\UnitTester;

/**
 * Covers the parse-to-HTML pipeline the preview and import phases share.
 */
final class PreviewRendererTest extends Unit {

	/** @var UnitTester */
	protected $tester;

	/**
	 * A job summary pointing at a fixture.
	 *
	 * @param string $fixture File name under tests/_data/bin.
	 * @param array  $config  Import configuration.
	 * @return array Result summary.
	 */
	private function summary( string $fixture, array $config = array() ): array {
		return array(
			'source_path' => $this->tester->fixture( $fixture ),
			'img_dir'     => $this->tester->imageDir(),
			'config'      => array_merge( array( 'mode' => 'lesson-only' ), $config ),
		);
	}

	/**
	 * Every format renders through the same pipeline.
	 *
	 * @dataProvider fixtures
	 *
	 * @param string $fixture File name under tests/_data/bin.
	 * @param string $expected Text that must appear in the rendered HTML.
	 */
	public function testEachFormatRenders( string $fixture, string $expected ): void {
		$rendered = PreviewRenderer::render_from_summary( $this->summary( $fixture ) );

		$this->assertFalse( is_wp_error( $rendered ) );
		$this->assertArrayHasKey( 'lesson_html', $rendered );
		$this->assertStringContainsString( $expected, $rendered['lesson_html'] );
		$this->assertStringContainsString( '<!-- wp:', $rendered['lesson_html'] );
	}

	/** @return array<string, array{string, string}> */
	public static function fixtures(): array {
		return array(
			'pptx' => array( 'deck.pptx', 'Two Columns' ),
			'pdf'  => array( 'slides.pdf', 'Reconstructed Page' ),
			'docx' => array( 'document.docx', 'Part One' ),
		);
	}

	/** Cover content never reaches the rendered output. */
	public function testCoverContentIsExcluded(): void {
		$rendered = PreviewRenderer::render_from_summary( $this->summary( 'deck.pptx' ) );

		$this->assertStringNotContainsString( 'Fixture Deck', $rendered['lesson_html'] );
	}

	/** Hidden units never reach the rendered output. */
	public function testHiddenContentIsExcluded(): void {
		$rendered = PreviewRenderer::render_from_summary( $this->summary( 'deck.pptx' ) );

		$this->assertStringNotContainsString( 'Must not be imported', $rendered['lesson_html'] );
	}

	/** Per-unit overrides from the stored config are applied. */
	public function testSlideOverridesAreApplied(): void {
		$rendered = PreviewRenderer::render_from_summary(
			$this->summary( 'deck.pptx', array( 'slide_overrides' => json_encode( array( 2 => 'hidden' ) ) ) )
		);

		$this->assertStringNotContainsString( 'Left body text.', $rendered['lesson_html'] );
		$this->assertStringContainsString( 'First item', $rendered['lesson_html'], 'Other units are unaffected' );
	}

	/** Malformed stored overrides are ignored rather than fatal. */
	public function testMalformedOverridesAreIgnored(): void {
		$rendered = PreviewRenderer::render_from_summary(
			$this->summary( 'deck.pptx', array( 'slide_overrides' => 'not json' ) )
		);

		$this->assertFalse( is_wp_error( $rendered ) );
		$this->assertStringContainsString( 'Left body text.', $rendered['lesson_html'] );
	}

	/**
	 * Preview images resolve to absolute URLs.
	 *
	 * The import path emits relative `media/` paths for the media rewriter;
	 * the preview has to give the browser something it can actually load.
	 */
	public function testPreviewImagesUseAbsoluteUrls(): void {
		$upload  = wp_upload_dir();
		$img_dir = $upload['basedir'] . '/cbf-slides-tmp/job_1/images';
		if ( ! is_dir( $img_dir ) ) {
			mkdir( $img_dir, 0777, true );
		}

		$rendered = PreviewRenderer::render_from_summary(
			array(
				'source_path' => $this->tester->fixture( 'deck.pptx' ),
				'img_dir'     => $img_dir,
				'config'      => array( 'mode' => 'lesson-only' ),
			)
		);

		$this->assertStringContainsString( 'src="' . $upload['baseurl'] . '/cbf-slides-tmp/job_1/images/', $rendered['lesson_html'] );
		$this->assertStringNotContainsString( 'src="media/', $rendered['lesson_html'] );
	}

	/**
	 * Jobs queued before multi-format support still resolve.
	 *
	 * Their summaries carry the old pptx_path key.
	 */
	public function testLegacyPptxPathKeyIsHonoured(): void {
		$rendered = PreviewRenderer::render_from_summary(
			array(
				'pptx_path' => $this->tester->fixture( 'deck.pptx' ),
				'img_dir'   => $this->tester->imageDir(),
				'config'    => array( 'mode' => 'lesson-only' ),
			)
		);

		$this->assertFalse( is_wp_error( $rendered ) );
		$this->assertStringContainsString( 'Two Columns', $rendered['lesson_html'] );
	}

	/** A cleaned-up source file produces a clear message, not a crash. */
	public function testRemovedSourceFileIsReported(): void {
		$rendered = PreviewRenderer::render_from_summary(
			array(
				'source_path' => '/tmp/already-cleaned-up.pptx',
				'img_dir'     => $this->tester->imageDir(),
			)
		);

		$this->assertTrue( is_wp_error( $rendered ) );
		$this->assertSame( 'cbf_si_preview_unavailable', $rendered->get_error_code() );
	}

	/** An empty summary is reported the same way. */
	public function testEmptySummaryIsReported(): void {
		$rendered = PreviewRenderer::render_from_summary( array() );

		$this->assertTrue( is_wp_error( $rendered ) );
		$this->assertSame( 'cbf_si_preview_unavailable', $rendered->get_error_code() );
	}

	/**
	 * The stored source path is read from either key, preferring the current one.
	 *
	 * @dataProvider summaries
	 *
	 * @param array  $summary  Stored result summary.
	 * @param string $expected Resolved path.
	 */
	public function testSourcePathResolution( array $summary, string $expected ): void {
		$this->assertSame( $expected, JobRunner::source_path( $summary ) );
	}

	/** @return array<string, array{array, string}> */
	public static function summaries(): array {
		return array(
			'current key'  => array( array( 'source_path' => '/a/b.pdf' ), '/a/b.pdf' ),
			'legacy key'   => array( array( 'pptx_path' => '/a/b.pptx' ), '/a/b.pptx' ),
			'both present' => array(
				array(
					'source_path' => '/new.pdf',
					'pptx_path'   => '/old.pptx',
				),
				'/new.pdf',
			),
			'neither'      => array( array(), '' ),
		);
	}
}
