<?php
/**
 * @package CodingBlackFemales/SemanticGlossary
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SemanticGlossary\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SemanticGlossary\Render\InlineRenderer;
use CodingBlackFemales\SemanticGlossary\Tests\Support\Entries;

/**
 * Covers the inline rendering rules from TECH-899.
 */
final class InlineRendererTest extends Unit {

	private function render( string $html ): string {
		return InlineRenderer::render( $html, Entries::standard() );
	}

	public function testTermRendersAsLinkToDefinition(): void {
		$this->assertSame(
			'<p><a href="#dfn-branch" id="ref-branch" class="glossary-ref">branch</a></p>',
			$this->render( '<p>' . Entries::ref( 3, 'branch' ) . '</p>' )
		);
	}

	public function testAbbreviationRendersAsAbbrExpandingToItsOwnRow(): void {
		$this->assertSame(
			'<a href="#dfn-version-control-system" id="ref-version-control-system" class="glossary-ref"><abbr title="Source code management">SCM</abbr></a>',
			$this->render( Entries::ref( 1, 'SCM' ) )
		);
	}

	public function testAbbreviationMatchIsCaseInsensitive(): void {
		$this->assertStringContainsString( '<abbr title="Repository">Repo</abbr>', $this->render( Entries::ref( 2, 'Repo' ) ) );
	}

	public function testInflectionRendersAsPlainLink(): void {
		$html = $this->render( Entries::ref( 3, 'branches' ) );

		$this->assertStringContainsString( '>branches</a>', $html );
		$this->assertStringNotContainsString( '<abbr', $html );
	}

	public function testOverrideForcesAbbrWithCanonicalExpansion(): void {
		$this->assertStringContainsString(
			'<abbr title="Version control system">version control</abbr>',
			$this->render( Entries::ref( 1, 'version control', true ) )
		);
	}

	public function testOnlyFirstMentionIsMarked(): void {
		$html = $this->render( '<p>' . Entries::ref( 2, 'repository' ) . ' then ' . Entries::ref( 2, '<em>repo</em>' ) . '</p>' );

		$this->assertSame(
			'<p><a href="#dfn-repository" id="ref-repository" class="glossary-ref">repository</a> then <em>repo</em></p>',
			$html
		);
	}

	public function testDeletedOrUnpublishedEntryRendersPlainText(): void {
		$this->assertSame(
			'<p>gone, draft</p>',
			$this->render( '<p>' . Entries::ref( 99, 'gone' ) . ', ' . Entries::ref( 4, 'draft' ) . '</p>' )
		);
	}

	public function testKeepsInnerMarkup(): void {
		$this->assertStringContainsString( '<code>.gitignore</code></a>', $this->render( Entries::ref( 5, '<code>.gitignore</code>' ) ) );
	}

	public function testHrefAndMarkupAreFilterable(): void {
		add_filter( 'glossary_reference_href', fn ( $href, $entry ) => 'https://example.com/glossary/#' . $entry->anchor() );
		add_filter( 'glossary_reference_html', fn ( $html ) => $html . '*' );

		$this->assertSame(
			'<a href="https://example.com/glossary/#dfn-branch" id="ref-branch" class="glossary-ref">branch</a>*',
			$this->render( Entries::ref( 3, 'branch' ) )
		);
	}

	public function testUnsafeFilteredHrefIsDropped(): void {
		add_filter( 'glossary_reference_href', fn () => 'javascript:alert(1)' );

		$this->assertStringContainsString( 'href=""', $this->render( Entries::ref( 3, 'branch' ) ) );
	}
}
