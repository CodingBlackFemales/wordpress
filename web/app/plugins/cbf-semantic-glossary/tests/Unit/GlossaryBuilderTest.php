<?php
/**
 * @package CodingBlackFemales/SemanticGlossary
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SemanticGlossary\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SemanticGlossary\Entry\Entry;
use CodingBlackFemales\SemanticGlossary\Render\GlossaryBuilder;
use CodingBlackFemales\SemanticGlossary\Render\GlossaryItem;
use CodingBlackFemales\SemanticGlossary\Tests\Support\Entries;

/**
 * Covers glossary ordering and A–Z grouping.
 */
final class GlossaryBuilderTest extends Unit {

	public function testSortsByDisplayedTermSoAbbreviationsLead(): void {
		$items = GlossaryBuilder::items( array( Entries::vcs(), Entries::branch(), Entries::repository(), Entries::gitignore() ), array() );

		$this->assertSame(
			array( '.gitignore', 'Branch', 'repo', 'VCS' ),
			array_map( fn ( GlossaryItem $item ) => $item->entry->display(), $items )
		);
	}

	public function testDropsUnpublishedEntries(): void {
		$items = GlossaryBuilder::items( array( Entries::commit(), Entries::branch() ), array() );

		$this->assertSame( array( 3 ), array_map( fn ( GlossaryItem $item ) => $item->entry->id, $items ) );
	}

	public function testRecordsWhichEntriesAreMarkedInline(): void {
		$items = GlossaryBuilder::items( array( Entries::branch(), Entries::vcs() ), array( 1 ) );

		$this->assertSame( array( false, true ), array_map( fn ( GlossaryItem $item ) => $item->has_inline, $items ) );
	}

	public function testSortsNaturallyAndIgnoringCase(): void {
		$make  = fn ( int $id, string $term ) => new Entry(
			$id,
			's' . $id,
			array(
				array(
					'term' => $term,
					'abbr' => '',
				),
			)
		);
		$items = GlossaryBuilder::items( array( $make( 1, 'Step 10' ), $make( 2, 'step 2' ), $make( 3, 'Émoji' ), $make( 4, 'Echo' ) ), array() );

		$this->assertSame(
			array( 'Echo', 'Émoji', 'step 2', 'Step 10' ),
			array_map( fn ( GlossaryItem $item ) => $item->entry->term(), $items )
		);
	}

	/**
	 * Index letters come from the displayed term.
	 *
	 * @dataProvider letters
	 *
	 * @param string $display Displayed term.
	 * @param string $letter  Expected group.
	 */
	public function testIndexLetter( string $display, string $letter ): void {
		$this->assertSame( $letter, GlossaryBuilder::letter( $display ) );
	}

	/**
	 * Displayed terms and their index groups.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function letters(): array {
		return array(
			'upper case'       => array( 'VCS', 'V' ),
			'lower case'       => array( 'repo', 'R' ),
			'accented'         => array( 'Éclair', 'E' ),
			'leading dot'      => array( '.gitignore', GlossaryItem::OTHER ),
			'digit'            => array( '2FA', GlossaryItem::OTHER ),
			'leading space'    => array( '  branch', 'B' ),
			'non-Latin letter' => array( 'λ calculus', GlossaryItem::OTHER ),
		);
	}

	public function testLetterAnchors(): void {
		$this->assertSame( 'glossary-v', ( new GlossaryItem( Entries::vcs(), 'V', true ) )->letter_anchor() );
		$this->assertSame( 'glossary-other', ( new GlossaryItem( Entries::gitignore(), GlossaryItem::OTHER, true ) )->letter_anchor() );
	}
}
