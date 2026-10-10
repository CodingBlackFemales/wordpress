<?php
/**
 * @package CodingBlackFemales/SemanticGlossary
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SemanticGlossary\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SemanticGlossary\Reference\Scanner;
use CodingBlackFemales\SemanticGlossary\Tests\Support\Entries;

/**
 * Covers finding, replacing and unwrapping stored references.
 */
final class ScannerTest extends Unit {

	public function testFindsReferencesInDocumentOrder(): void {
		$html = '<p>A ' . Entries::ref( 2, 'repo' ) . ' and a ' . Entries::ref( 3, 'branch' ) . '.</p>'
			. '<p>Then ' . Entries::ref( 1, 'VCS', true ) . '.</p>';

		$refs = Scanner::scan( $html );

		$this->assertSame( array( 2, 3, 1 ), array_map( fn ( $r ) => $r->entry_id, $refs ) );
		$this->assertSame( array( 0, 1, 2 ), array_map( fn ( $r ) => $r->position, $refs ) );
		$this->assertSame( array( 'repo', 'branch', 'VCS' ), array_map( fn ( $r ) => $r->text, $refs ) );
		$this->assertFalse( $refs[0]->render_abbr );
		$this->assertTrue( $refs[2]->render_abbr );
	}

	public function testOffsetsCoverTheWholeSpan(): void {
		$span = Entries::ref( 3, 'branch' );
		$html = 'before ' . $span . ' after';

		$ref = Scanner::scan( $html )[0];

		$this->assertSame( $span, substr( $html, $ref->start, $ref->end - $ref->start ) );
	}

	public function testKeepsInnerMarkupAndNestedSpans(): void {
		$html = Entries::ref( 6, '<code>HEAD</code> <span class="x">file</span>' ) . '<span>other</span>';

		$ref = Scanner::scan( $html )[0];

		$this->assertSame( '<code>HEAD</code> <span class="x">file</span>', $ref->inner_html );
		$this->assertSame( 'HEAD file', $ref->text );
		$this->assertCount( 1, Scanner::scan( $html ) );
	}

	public function testDecodesEntitiesAndCollapsesWhitespaceInText(): void {
		$ref = Scanner::scan( Entries::ref( 1, " R&amp;D&nbsp;\n team " ) )[0];

		$this->assertSame( 'R&D team', $ref->text );
	}

	public function testAcceptsAnyAttributeOrderAndQuoting(): void {
		$html = "<span data-glossary-id='7' class='other glossary-ref'>x</span>";

		$this->assertSame( 7, Scanner::scan( $html )[0]->entry_id );
	}

	public function testIgnoresSpansWithoutTheClass(): void {
		$this->assertSame( array(), Scanner::scan( '<span data-glossary-id="1">x</span> glossary-reference' ) );
	}

	public function testMissingOrInvalidIdIsEntryZero(): void {
		$refs = Scanner::scan( '<span class="glossary-ref">a</span><span class="glossary-ref" data-glossary-id="x1">b</span>' );

		$this->assertSame( array( 0, 0 ), array_map( fn ( $r ) => $r->entry_id, $refs ) );
	}

	public function testEntryIdsAreUniqueInFirstAppearanceOrder(): void {
		$refs = Scanner::scan( Entries::ref( 3, 'a' ) . Entries::ref( 1, 'b' ) . Entries::ref( 3, 'c' ) . '<span class="glossary-ref">d</span>' );

		$this->assertSame( array( 3, 1 ), Scanner::entry_ids( $refs ) );
	}

	public function testReplaceUnwrapsAndSubstitutesOnlyTheGivenPositions(): void {
		$html = '<p>' . Entries::ref( 2, 'repo' ) . ', ' . Entries::ref( 3, '<em>branch</em>' ) . ', ' . Entries::ref( 1, 'VCS' ) . '</p>';
		$refs = Scanner::scan( $html );

		$result = Scanner::replace(
			$html,
			array(
				0 => '[repo]',
				1 => null,
			),
			$refs
		);

		$this->assertSame( '<p>[repo], <em>branch</em>, ' . Entries::ref( 1, 'VCS' ) . '</p>', $result );
	}

	public function testLeavesContentWithoutReferencesUntouched(): void {
		$html = "<p>Plain &amp; simple<br />\n<img src=x alt=''></p>";

		$this->assertSame( array(), Scanner::scan( $html ) );
		$this->assertSame( $html, Scanner::replace( $html, array(), array() ) );
	}
}
