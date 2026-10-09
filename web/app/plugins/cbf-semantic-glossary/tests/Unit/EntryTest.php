<?php
/**
 * @package CodingBlackFemales/SemanticGlossary
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SemanticGlossary\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SemanticGlossary\Entry\Forms;
use CodingBlackFemales\SemanticGlossary\Tests\Support\Entries;

/**
 * Covers entries and their forms.
 */
final class EntryTest extends Unit {

	public function testCanonicalFormAndAnchors(): void {
		$entry = Entries::vcs();

		$this->assertSame( 'Version control system', $entry->term() );
		$this->assertSame( 'VCS', $entry->abbr() );
		$this->assertSame( 'VCS', $entry->display() );
		$this->assertSame( 'dfn-version-control-system', $entry->anchor() );
		$this->assertSame( 'ref-version-control-system', $entry->ref_anchor() );
		$this->assertSame( 'Branch', Entries::branch()->display() );
	}

	public function testMatchPrefersAbbreviationsAndIgnoresCase(): void {
		$entry = Entries::vcs();

		$this->assertSame(
			array(
				'form' => 1,
				'type' => 'abbr',
			),
			$entry->match( 'scm' )
		);
		$this->assertSame(
			array(
				'form' => 2,
				'type' => 'term',
			),
			$entry->match( ' revision CONTROL ' )
		);
		$this->assertNull( $entry->match( 'version control' ) );
	}

	public function testExpansionComesFromTheMatchedRow(): void {
		$entry = Entries::vcs();

		$this->assertSame( 'Source code management', $entry->expansion_for( 'SCM' ) );
		$this->assertSame( 'Version control system', $entry->expansion_for( 'VCS' ) );
		$this->assertSame( 'Version control system', $entry->expansion_for( 'version control' ) );
		$this->assertTrue( $entry->is_abbreviation( 'scm' ) );
		$this->assertFalse( $entry->is_abbreviation( 'Revision control' ) );
	}

	public function testNormaliseCleansDropsAndDeduplicates(): void {
		$forms = Forms::normalise(
			array(
				array(
					'term' => "  Version <b>control</b>\n system ",
					'abbr' => 'VCS',
				),
				'Source code management:SCM',
				array( 'abbr' => 'orphan' ),
				array(
					'term' => 'version control system',
					'abbr' => 'vcs',
				),
				42,
			)
		);

		$this->assertSame(
			array(
				array(
					'term' => 'Version control system',
					'abbr' => 'VCS',
				),
				array(
					'term' => 'Source code management',
					'abbr' => 'SCM',
				),
			),
			$forms
		);
	}

	public function testPairShorthandRoundTrips(): void {
		$this->assertSame(
			array(
				'term' => 'Ratio 16:9',
				'abbr' => 'R',
			),
			Forms::parse_pair( 'Ratio 16:9:R' )
		);
		$this->assertSame(
			array(
				'term' => 'Revision control',
				'abbr' => '',
			),
			Forms::parse_pair( 'Revision control' )
		);

		$forms = Entries::vcs()->alternatives();
		$this->assertSame( 'Source code management:SCM|Revision control', Forms::pack( $forms ) );
		$this->assertSame( $forms, Forms::unpack( Forms::pack( $forms ) ) );
		$this->assertSame( array(), Forms::unpack( '  ' ) );
	}

	public function testRemoveNeverDropsTheCanonicalForm(): void {
		$forms = Entries::vcs()->forms;

		$this->assertCount( 3, Forms::remove( $forms, 'version control system' ) );
		$this->assertSame( array( 'Version control system', 'Revision control' ), array_column( Forms::remove( $forms, 'source code MANAGEMENT' ), 'term' ) );
	}
}
