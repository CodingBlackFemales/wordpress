<?php
/**
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SlidesImporter\Document\BlockRenderer;
use CodingBlackFemales\SlidesImporter\Document\Ir;

/**
 * Covers the IR-to-Gutenberg rendering that every format shares.
 */
final class BlockRendererTest extends Unit {

	/**
	 * Render a single body unit built from the given content blocks.
	 *
	 * @param array  $blocks ContentBlock arrays.
	 * @param string $title  Unit title.
	 * @param array  $images Image metadata.
	 * @return string Block HTML.
	 */
	private function render( array $blocks, string $title = '', array $images = array() ): string {
		$deck = array(
			'slides' => array(
				array(
					'slide_type' => 'body',
					'title'      => $title,
					'content'    => $blocks,
					'images'     => $images,
				),
			),
		);

		return BlockRenderer::render( $deck )['lesson_html'];
	}

	/** A unit title becomes the H2 that opens its content. */
	public function testTitleBecomesHeadingTwo(): void {
		$html = $this->render( array(), 'Session Outline' );

		$this->assertStringContainsString( '<!-- wp:heading {"level":2} -->', $html );
		$this->assertStringContainsString( '<h2 class="wp-block-heading">Session Outline</h2>', $html );
	}

	/** Cover and hidden units contribute nothing. */
	public function testCoverAndHiddenUnitsAreSkipped(): void {
		$deck = array(
			'slides' => array(
				array(
					'slide_type' => 'cover',
					'title'      => 'Cover',
					'content'    => array( Ir::linear( array( Ir::text_para( Ir::KIND_PARAGRAPH, 'Cover body' ) ) ) ),
					'images'     => array(),
				),
				array(
					'slide_type' => 'hidden',
					'title'      => 'Hidden',
					'content'    => array( Ir::linear( array( Ir::text_para( Ir::KIND_PARAGRAPH, 'Hidden body' ) ) ) ),
					'images'     => array(),
				),
			),
		);

		$this->assertSame( '', BlockRenderer::render( $deck )['lesson_html'] );
	}

	/**
	 * Consecutive units sharing a title emit one heading.
	 *
	 * Google Slides progressive-reveal exports produce runs of identically
	 * titled slides, which would otherwise repeat the heading.
	 */
	public function testRepeatedTitlesEmitOneHeading(): void {
		$unit = static function ( string $title, string $body ): array {
			return array(
				'slide_type' => 'body',
				'title'      => $title,
				'content'    => array( Ir::linear( array( Ir::text_para( Ir::KIND_PARAGRAPH, $body ) ) ) ),
				'images'     => array(),
			);
		};

		$html = BlockRenderer::render(
			array(
				'slides' => array(
					$unit( 'Same Title', 'first' ),
					$unit( 'Same Title', 'second' ),
					$unit( 'Other Title', 'third' ),
				),
			)
		)['lesson_html'];

		$this->assertSame( 1, substr_count( $html, '>Same Title<' ) );
		$this->assertSame( 1, substr_count( $html, '>Other Title<' ) );
		$this->assertStringContainsString( '<p>second</p>', $html );
	}

	/** Body text becomes a paragraph block. */
	public function testParagraphBlock(): void {
		$html = $this->render( array( Ir::linear( array( Ir::text_para( Ir::KIND_PARAGRAPH, 'Body text.' ) ) ) ) );

		$this->assertStringContainsString( "<!-- wp:paragraph -->\n<p>Body text.</p>\n<!-- /wp:paragraph -->", $html );
	}

	/** Consecutive bullets collapse into a single unordered list. */
	public function testConsecutiveBulletsFormOneList(): void {
		$html = $this->render(
			array(
				Ir::linear(
					array(
						Ir::text_para( Ir::KIND_BULLET, 'One' ),
						Ir::text_para( Ir::KIND_BULLET, 'Two' ),
						Ir::text_para( Ir::KIND_BULLET, 'Three' ),
					)
				),
			)
		);

		$this->assertSame( 1, substr_count( $html, '<!-- wp:list -->' ) );
		$this->assertSame( 3, substr_count( $html, '<!-- wp:list-item -->' ) );
		$this->assertStringContainsString( '<ul class="wp-block-list">', $html );
	}

	/** Ordered bullets render as an ol and carry the block attribute. */
	public function testOrderedListBlock(): void {
		$html = $this->render(
			array(
				Ir::linear(
					array(
						Ir::text_para( Ir::KIND_BULLET, 'Step one', array( 'ordered' => true ) ),
						Ir::text_para( Ir::KIND_BULLET, 'Step two', array( 'ordered' => true ) ),
					)
				),
			)
		);

		$this->assertStringContainsString( '<!-- wp:list {"ordered":true} -->', $html );
		$this->assertStringContainsString( '<ol class="wp-block-list">', $html );
	}

	/** A change of list type starts a new list rather than mixing them. */
	public function testOrderedAndUnorderedListsDoNotMerge(): void {
		$html = $this->render(
			array(
				Ir::linear(
					array(
						Ir::text_para( Ir::KIND_BULLET, 'Bullet' ),
						Ir::text_para( Ir::KIND_BULLET, 'Number', array( 'ordered' => true ) ),
					)
				),
			)
		);

		$this->assertStringContainsString( '<ul class="wp-block-list">', $html );
		$this->assertStringContainsString( '<ol class="wp-block-list">', $html );
	}

	/** Consecutive code paragraphs become one code block, newline-separated. */
	public function testConsecutiveCodeLinesFormOneBlock(): void {
		$html = $this->render(
			array(
				Ir::linear(
					array(
						Ir::text_para( Ir::KIND_CODE, 'composer install' ),
						Ir::text_para( Ir::KIND_CODE, 'composer test' ),
					)
				),
			)
		);

		$this->assertSame( 1, substr_count( $html, '<!-- wp:code -->' ) );
		$this->assertStringContainsString( "composer install\ncomposer test", $html );
	}

	/**
	 * A heading level set by the parser wins; otherwise it is derived from the
	 * font size, mirroring the python-pptx pipeline's 36pt threshold.
	 */
	public function testHeadingLevels(): void {
		$explicit = $this->render( array( Ir::linear( array( Ir::text_para( Ir::KIND_HEADING, 'Explicit', array( 'level' => 3 ) ) ) ) ) );
		$this->assertStringContainsString( '<h3 class="wp-block-heading">Explicit</h3>', $explicit );

		$large = $this->render( array( Ir::linear( array( Ir::text_para( Ir::KIND_HEADING, 'Large', array( 'size' => 40 ) ) ) ) ) );
		$this->assertStringContainsString( '<h3 class="wp-block-heading">Large</h3>', $large );

		$small = $this->render( array( Ir::linear( array( Ir::text_para( Ir::KIND_HEADING, 'Small', array( 'size' => 24 ) ) ) ) ) );
		$this->assertStringContainsString( '<h4 class="wp-block-heading">Small</h4>', $small );
	}

	/** Heading levels are clamped into the range Gutenberg accepts here. */
	public function testHeadingLevelsAreClamped(): void {
		$shallow = $this->render( array( Ir::linear( array( Ir::text_para( Ir::KIND_HEADING, 'Shallow', array( 'level' => 1 ) ) ) ) ) );
		$this->assertStringContainsString( '<h2 ', $shallow );

		$deep = $this->render( array( Ir::linear( array( Ir::text_para( Ir::KIND_HEADING, 'Deep', array( 'level' => 9 ) ) ) ) ) );
		$this->assertStringContainsString( '<h6 ', $deep );
	}

	/** Run formatting nests with code innermost. */
	public function testRunFormatting(): void {
		$para = Ir::para(
			Ir::KIND_PARAGRAPH,
			array(
				Ir::run( 'plain ' ),
				Ir::run( 'bold', array( 'bold' => true ) ),
				Ir::run( ' ' ),
				Ir::run( 'italic', array( 'italic' => true ) ),
				Ir::run( ' ' ),
				Ir::run( 'code', array( 'mono' => true ) ),
				Ir::run( ' ' ),
				Ir::run( 'under', array( 'underline' => true ) ),
				Ir::run( ' ' ),
				Ir::run( 'struck', array( 'strike' => true ) ),
			)
		);

		$html = $this->render( array( Ir::linear( array( $para ) ) ) );

		$this->assertStringContainsString( '<strong>bold</strong>', $html );
		$this->assertStringContainsString( '<em>italic</em>', $html );
		$this->assertStringContainsString( '<code>code</code>', $html );
		$this->assertStringContainsString( '<u>under</u>', $html );
		$this->assertStringContainsString( '<s>struck</s>', $html );
	}

	/** Combined emphasis wraps outwards from the code tag. */
	public function testCombinedRunFormattingNests(): void {
		$para = Ir::para(
			Ir::KIND_PARAGRAPH,
			array(
				Ir::run(
					'x',
					array(
						'mono' => true,
						'bold' => true,
						'italic' => true,
					)
				),
			)
		);

		$this->assertStringContainsString( '<em><strong><code>x</code></strong></em>', $this->render( array( Ir::linear( array( $para ) ) ) ) );
	}

	/** A run's link becomes an anchor. */
	public function testLinkedRun(): void {
		$para = Ir::para(
			Ir::KIND_PARAGRAPH,
			array( Ir::run( 'the docs', array( 'link' => 'https://example.com/docs' ) ) )
		);

		$this->assertStringContainsString(
			'<a href="https://example.com/docs">the docs</a>',
			$this->render( array( Ir::linear( array( $para ) ) ) )
		);
	}

	/** A link with a scheme WordPress would reject is dropped, keeping the text. */
	public function testUnsafeLinkIsDropped(): void {
		$para = Ir::para(
			Ir::KIND_PARAGRAPH,
			array( Ir::run( 'click', array( 'link' => 'javascript:alert(1)' ) ) )
		);
		$html = $this->render( array( Ir::linear( array( $para ) ) ) );

		$this->assertStringNotContainsString( '<a ', $html );
		$this->assertStringContainsString( 'click', $html );
	}

	/** Document text is untrusted and is escaped on the way out. */
	public function testTextIsEscaped(): void {
		$html = $this->render(
			array( Ir::linear( array( Ir::text_para( Ir::KIND_PARAGRAPH, '<script>alert("x")</script> & more' ) ) ) ),
			'Title <b>with</b> markup'
		);

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
		$this->assertStringContainsString( '&amp; more', $html );
		$this->assertStringContainsString( 'Title &lt;b&gt;with&lt;/b&gt; markup', $html );
	}

	/** Side-by-side content becomes a columns block. */
	public function testColumnsBlock(): void {
		$html = $this->render(
			array(
				Ir::columns(
					array(
						array( Ir::text_para( Ir::KIND_PARAGRAPH, 'Left' ) ),
						array( Ir::text_para( Ir::KIND_PARAGRAPH, 'Right' ) ),
					),
					array( 50, 50 )
				),
			)
		);

		$this->assertSame( 1, substr_count( $html, '<!-- wp:columns -->' ) );
		$this->assertSame( 2, substr_count( $html, '<!-- wp:column -->' ) );
		$this->assertStringContainsString( '<p>Left</p>', $html );
		$this->assertStringContainsString( '<p>Right</p>', $html );
	}

	/** A table with a bold first row renders a thead. */
	public function testTableBlockWithHeader(): void {
		$html = $this->render(
			array(
				Ir::table(
					array(
						array(
							array( Ir::text_para( Ir::KIND_PARAGRAPH, 'A' ) ),
							array( Ir::text_para( Ir::KIND_PARAGRAPH, 'B' ) ),
						),
						array(
							array( Ir::text_para( Ir::KIND_PARAGRAPH, 'a1' ) ),
							array( Ir::text_para( Ir::KIND_PARAGRAPH, 'b1' ) ),
						),
					),
					true
				),
			)
		);

		$this->assertStringContainsString( '<!-- wp:table -->', $html );
		$this->assertStringContainsString( '<thead><tr><th>A</th><th>B</th></tr></thead>', $html );
		$this->assertStringContainsString( '<tbody><tr><td>a1</td><td>b1</td></tr></tbody>', $html );
	}

	/** Without a header row the table is all body. */
	public function testTableBlockWithoutHeader(): void {
		$html = $this->render(
			array(
				Ir::table(
					array(
						array(
							array( Ir::text_para( Ir::KIND_PARAGRAPH, 'a1' ) ),
							array( Ir::text_para( Ir::KIND_PARAGRAPH, 'b1' ) ),
						),
					),
					false
				),
			)
		);

		$this->assertStringNotContainsString( '<thead>', $html );
		$this->assertStringContainsString( '<td>a1</td>', $html );
	}

	/**
	 * At import time images use the relative media/ prefix that
	 * ELDBC_Media::rewrite_paths() scans for.
	 */
	public function testImageUsesMediaPrefixByDefault(): void {
		$html = $this->render( array(), '', array( array( 'filename' => 'slide_001_img_01.png' ) ) );

		$this->assertStringContainsString( '<img src="media/slide_001_img_01.png" alt=""/>', $html );
	}

	/** For preview a real base URL is substituted so the browser can load it. */
	public function testImageUsesBaseUrlWhenSupplied(): void {
		$deck = array(
			'slides' => array(
				array(
					'slide_type' => 'body',
					'title'      => '',
					'content'    => array(),
					'images'     => array( array( 'filename' => 'slide_001_img_01.png' ) ),
				),
			),
		);

		$html = BlockRenderer::render( $deck, 'lesson-only', true, 'https://example.test/uploads/job_1/images' )['lesson_html'];

		$this->assertStringContainsString( 'src="https://example.test/uploads/job_1/images/slide_001_img_01.png"', $html );
	}

	/** Blank paragraphs never reach the output. */
	public function testBlankParagraphsAreDropped(): void {
		$html = $this->render(
			array(
				Ir::linear(
					array(
						Ir::text_para( Ir::KIND_PARAGRAPH, '   ' ),
						Ir::text_para( Ir::KIND_PARAGRAPH, 'Real' ),
					)
				),
			)
		);

		$this->assertSame( 1, substr_count( $html, '<!-- wp:paragraph -->' ) );
	}

	/** Both import modes render the same HTML; only the post type differs. */
	public function testModesRenderIdenticalHtml(): void {
		$deck = array(
			'slides' => array(
				array(
					'slide_type' => 'body',
					'title'      => 'Unit',
					'content'    => array( Ir::linear( array( Ir::text_para( Ir::KIND_PARAGRAPH, 'Body' ) ) ) ),
					'images'     => array(),
				),
			),
		);

		$this->assertSame(
			BlockRenderer::render( $deck, 'lesson-only' )['lesson_html'],
			BlockRenderer::render( $deck, 'topic' )['lesson_html']
		);
	}
}
