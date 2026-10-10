<?php
/**
 * @package CodingBlackFemales/SemanticGlossary
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SemanticGlossary\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SemanticGlossary\Entry\Entry;
use CodingBlackFemales\SemanticGlossary\Render\GlossaryBuilder;
use CodingBlackFemales\SemanticGlossary\Render\GlossaryRenderer;
use CodingBlackFemales\SemanticGlossary\Render\Options;
use CodingBlackFemales\SemanticGlossary\Tests\Support\Entries;

/**
 * Covers the glossary markup from TECH-899.
 */
final class GlossaryRendererTest extends Unit {

	/**
	 * Render entries with every entry marked inline.
	 *
	 * @param Entry[] $entries Entries.
	 * @param Options $options Options.
	 */
	private function render( array $entries, ?Options $options = null ): string {
		$items = GlossaryBuilder::items( $entries, array_map( fn ( Entry $e ) => $e->id, $entries ) );
		return GlossaryRenderer::render( $items, $options ?? new Options( 'Glossary', 3 ) );
	}

	public function testEntryMarkupMatchesTheSpec(): void {
		$html = $this->render( array( Entries::vcs() ) );

		$this->assertStringContainsString(
			'<div class="glossary-entry">'
			. '<dt><dfn id="dfn-version-control-system"><abbr title="Version control system">VCS</abbr></dfn> (Version control system) '
			. '<a href="#ref-version-control-system" class="glossary-backlink" aria-label="Back to where VCS is used">' . GlossaryRenderer::BACK_LINK . '</a></dt>'
			. '<dt class="glossary-alt"><abbr title="Source code management">SCM</abbr> (Source code management)</dt>'
			. '<dt class="glossary-alt">Revision control</dt>'
			. '<dd>A tool to keep track of changes made to software over time.</dd>'
			. '</div>',
			$html
		);
	}

	public function testSectionIsLabelledByItsHeadingAtTheConfiguredLevel(): void {
		$html = $this->render( array( Entries::branch() ), new Options( 'Key terms', 4, true, true, true, 'gh' ) );

		$this->assertStringStartsWith(
			'<section class="glossary-section" aria-labelledby="gh"><h4 id="gh" class="glossary-heading">Key terms</h4><dl class="glossary">',
			$html
		);
	}

	public function testTermWithoutAbbreviationHasNoExpansion(): void {
		$items = GlossaryBuilder::items( array( Entries::branch() ), array() );

		$this->assertStringContainsString( '<dt><dfn id="dfn-branch">Branch</dfn></dt>', GlossaryRenderer::render( $items, new Options( 'G' ) ) );
	}

	public function testAlternativesCanBeHidden(): void {
		$html = $this->render( array( Entries::vcs() ), new Options( 'Glossary', 2, false ) );

		$this->assertStringNotContainsString( 'glossary-alt', $html );
	}

	public function testBackLinkNeedsTheOptionAndAnInlineReference(): void {
		$items = GlossaryBuilder::items( array( Entries::branch(), Entries::vcs() ), array( 3 ) );

		$with    = GlossaryRenderer::render( $items, new Options( 'G' ) );
		$without = GlossaryRenderer::render( $items, ( new Options( 'G' ) )->without_back_links() );

		$this->assertStringContainsString( 'href="#ref-branch"', $with );
		$this->assertStringNotContainsString( 'href="#ref-version-control-system"', $with );
		$this->assertStringNotContainsString( 'glossary-backlink', $without );
	}

	public function testDefinitionIsSanitised(): void {
		$entry = new Entry(
			9,
			'x',
			array(
				array(
					'term' => 'X',
					'abbr' => '',
				),
			),
			'Safe <script>alert(1)</script><div>block</div>'
		);

		$html = $this->render( array( $entry ) );

		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringNotContainsString( '<div>block', $html );
	}

	public function testTermsAreEscaped(): void {
		$entry = new Entry(
			9,
			'x',
			array(
				array(
					'term' => 'A <b> & "C"',
					'abbr' => '',
				),
			)
		);

		$this->assertStringContainsString( 'A &lt;b&gt; &amp; &quot;C&quot;', $this->render( array( $entry ) ) );
	}

	public function testNothingToListRendersNothing(): void {
		$this->assertSame( '', GlossaryRenderer::render( array(), new Options() ) );
	}

	public function testIndexIsOmittedBelowTheMinimum(): void {
		$this->assertStringNotContainsString( 'glossary-index', $this->render( $this->entries( 7 ) ) );
	}

	public function testIndexListsOnlyLettersWithEntriesAndTargetsTheFirstOfEach(): void {
		$entries   = $this->entries( 8 );
		$entries[] = Entries::gitignore();

		$html = $this->render( $entries );

		$this->assertStringContainsString(
			'<nav class="glossary-index" aria-label="Glossary index"><ol>'
			. '<li><a href="#glossary-other">#</a></li>'
			. '<li><a href="#glossary-a">A</a></li>'
			. '<li><a href="#glossary-b">B</a></li>'
			. '</ol></nav>',
			$html
		);
		$this->assertSame( 1, substr_count( $html, 'id="glossary-a"' ) );
		$this->assertStringContainsString( '<div class="glossary-entry" id="glossary-other"><dt><dfn id="dfn-gitignore">', $html );
	}

	public function testIndexMinimumComesFromTheOptions(): void {
		$options = new Options( 'G', 2, true, true, true, 'gh', 2 );

		$this->assertStringContainsString( 'glossary-index', $this->render( $this->entries( 2 ), $options ) );
		$this->assertStringNotContainsString( 'glossary-index', $this->render( $this->entries( 1 ), $options ) );
	}

	public function testIndexCanBeSwitchedOff(): void {
		$this->assertStringNotContainsString( 'glossary-index', $this->render( $this->entries( 10 ), new Options( 'G', 2, true, true, false ) ) );
	}

	public function testOutputIsFilterableAtEachLevel(): void {
		add_filter( 'glossary_entry_html', fn ( $html, $entry ) => str_replace( '</dd>', ' [' . $entry->slug . ']</dd>', $html ) );
		add_filter( 'glossary_list_html', fn ( $html ) => $html . '<!--list-->' );
		add_filter( 'glossary_html', fn ( $html, $items, $context ) => $html . '<!--' . $context['options']->level . '-->' );

		$html = $this->render( array( Entries::branch() ) );

		$this->assertStringContainsString( ' [branch]</dd>', $html );
		$this->assertStringContainsString( '</dl><!--list--></section><!--3-->', $html );
	}

	public function testContentCanBeInsertedAroundTheList(): void {
		add_filter( 'glossary_html_before_index', fn () => '<p class="intro">Intro</p>' );
		add_filter( 'glossary_html_after_list', fn () => '<p class="outro">Outro</p>' );

		$html = $this->render( array( Entries::branch() ) );

		$this->assertStringContainsString( '</h3><p class="intro">Intro</p><dl class="glossary">', $html );
		$this->assertStringContainsString( '</dl><p class="outro">Outro</p></section>', $html );
	}

	public function testBackLinkTargetIsFilterable(): void {
		add_filter( 'glossary_back_link_href', fn ( $href, $entry ) => 'https://example.com/lesson/' . $href );

		$this->assertStringContainsString(
			'<a href="https://example.com/lesson/#ref-branch" class="glossary-backlink"',
			$this->render( array( Entries::branch() ) )
		);
	}

	/**
	 * Entries named "Alpha 1", "Alpha 2", … with half under B.
	 *
	 * @param int $count How many.
	 * @return Entry[]
	 */
	private function entries( int $count ): array {
		$entries = array();
		for ( $i = 1; $i <= $count; $i++ ) {
			$term      = ( $i % 2 ? 'Alpha ' : 'Beta ' ) . $i;
			$entries[] = new Entry(
				100 + $i,
				'e' . $i,
				array(
					array(
						'term' => $term,
						'abbr' => '',
					),
				)
			);
		}
		return $entries;
	}
}
