<?php
/**
 * @package CodingBlackFemales/SemanticGlossary
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SemanticGlossary\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SemanticGlossary\Audit\Auditor;
use CodingBlackFemales\SemanticGlossary\Audit\TermMatcher;
use CodingBlackFemales\SemanticGlossary\Entry\Entry;
use CodingBlackFemales\SemanticGlossary\Tests\Support\Entries;

/**
 * Covers the audit: duplicates, dead references and unmarked terms.
 */
final class AuditTest extends Unit {

	public function testReportsDuplicatesDeadAndUnmarkedTerms(): void {
		$html = '<p>' . Entries::ref( 2, 'repository' ) . ' and ' . Entries::ref( 2, 'repo' ) . '. '
			. Entries::ref( 99, 'gone' ) . ' Each branch has a .gitignore.</p>';

		$findings = Auditor::audit( $html, Entries::standard() );

		$this->assertSame(
			array(
				array(
					'type'     => Auditor::DUPLICATE,
					'entry_id' => 2,
					'text'     => 'repo',
					'position' => 1,
				),
				array(
					'type'     => Auditor::DEAD,
					'entry_id' => 99,
					'text'     => 'gone',
					'position' => 2,
				),
				array(
					'type'     => Auditor::UNMARKED,
					'entry_id' => 3,
					'text'     => 'branch',
					'position' => null,
				),
				array(
					'type'     => Auditor::UNMARKED,
					'entry_id' => 5,
					'text'     => '.gitignore',
					'position' => null,
				),
			),
			$findings
		);
	}

	public function testIgnoredEntriesAreNotSuggested(): void {
		$findings = Auditor::audit( '<p>A branch.</p>', Entries::standard(), array( 3 ) );

		$this->assertSame( array(), $findings );
	}

	public function testUnpublishedEntryReferencesAreNotDead(): void {
		$findings = Auditor::audit( Entries::ref( 4, 'commit' ) . ' ' . Entries::ref( 4, 'commits' ), Entries::standard() );

		$this->assertSame( array( Auditor::UNPUBLISHED, Auditor::DUPLICATE ), array_column( $findings, 'type' ) );
	}

	public function testFixLeavesUnpublishedReferencesAlone(): void {
		$html = Entries::ref( 4, 'commit' ) . ' ' . Entries::ref( 4, 'commits' );

		$result = Auditor::fix( $html, Entries::standard() );

		$this->assertSame( Entries::ref( 4, 'commit' ) . ' commits', $result['html'] );
		$this->assertSame( 1, $result['fixed'] );
		$this->assertFalse( Auditor::has_fixable( Auditor::audit( $result['html'], Entries::standard() ) ) );
	}

	public function testFixUnwrapsLaterDuplicatesAndDeadReferencesOnly(): void {
		$html = Entries::ref( 2, 'repository' ) . ' ' . Entries::ref( 2, 'repo' ) . ' ' . Entries::ref( 99, 'gone' ) . ' branch';

		$result = Auditor::fix( $html, Entries::standard() );

		$this->assertSame( Entries::ref( 2, 'repository' ) . ' repo gone branch', $result['html'] );
		$this->assertSame( 2, $result['fixed'] );
		$this->assertFalse( Auditor::has_fixable( Auditor::audit( $result['html'], Entries::standard() ) ) );
	}

	public function testMatcherRulesForTerms(): void {
		$entries = array( Entries::branch() );

		$this->assertSame( 'Branches', TermMatcher::find( 'Branches are cheap.', $entries )[0]['text'] );
		$this->assertSame( array(), TermMatcher::find( 'Rebranching is not a word.', $entries ) );
		$this->assertSame( array(), TermMatcher::find( '<pre>branch</pre><!-- branch -->', $entries ) );
		$this->assertSame( array(), TermMatcher::find( '<a href="/branch">link</a>', $entries ) );
	}

	public function testMatcherRulesForAbbreviations(): void {
		$it  = new Entry(
			7,
			'it',
			array(
				array(
					'term' => 'Information technology',
					'abbr' => 'IT',
				),
			)
		);
		$vcs = Entries::vcs();

		$this->assertSame( array(), TermMatcher::find( 'Is it here?', array( $it ) ) );
		$this->assertSame( 'IT', TermMatcher::find( 'Ask IT.', array( $it ) )[0]['text'] );
		$this->assertSame( 'Repo', TermMatcher::find( 'Repo first.', array( Entries::repository() ) )[0]['text'] );
		$this->assertSame( 'version   control system', TermMatcher::find( 'A version   control system and SCM.', array( $vcs ) )[0]['text'] );
	}

	public function testMatcherSkipsTextAlreadyInsideReferences(): void {
		$html = Entries::ref( 9, 'remote repository' ) . ' only.';

		$this->assertSame( array(), TermMatcher::find( $html, array( Entries::repository() ) ) );
	}

	public function testMatcherReportsEarliestOccurrenceAcrossForms(): void {
		$found = TermMatcher::find( 'First SCM, later version control system.', array( Entries::vcs() ) );

		$this->assertSame( 'SCM', $found[0]['text'] );
	}

	public function testMatcherSeparatesBlocks(): void {
		$this->assertSame( array(), TermMatcher::find( '<p>re</p><p>po</p>', array( Entries::repository() ) ) );
	}
}
