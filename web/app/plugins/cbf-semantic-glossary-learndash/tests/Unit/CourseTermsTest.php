<?php
/**
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SemanticGlossary\Entry\Entry;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\CourseTerms;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\Step;
use CodingBlackFemales\SemanticGlossaryLearnDash\Tests\Support\Course;

/**
 * Covers which entries a learner sees and where each was introduced.
 */
final class CourseTermsTest extends Unit {

	/**
	 * Lessons 1 and 2 open, 3 and 4 locked; four entries across them.
	 */
	private function terms(): CourseTerms {
		$steps = array( Course::step( 1 ), Course::step( 2 ), Course::step( 3, Step::LOCKED ), Course::step( 4, Step::LOCKED ) );

		$course = array(
			Course::term( 10, 'Commit', 1 ),
			Course::term( 11, 'Branch', 2 ),
			Course::term( 12, 'Merge', 3 ),
			Course::term( 13, 'Rebase', 4, false ),
		);

		return new CourseTerms( $steps, array_slice( $course, 0, 2 ), $course );
	}

	public function testOnlyOpenStepsAreQueried(): void {
		$steps = array( Course::step( 1 ), Course::step( 2, Step::LOCKED ), Course::step( 3 ) );

		$this->assertSame( array( 1, 3 ), CourseTerms::open_ids( $steps ) );
	}

	public function testListsOnlyEntriesFromOpenSteps(): void {
		$ids = array_map( fn ( Entry $entry ) => $entry->id, $this->terms()->entries() );

		$this->assertSame( array( 10, 11 ), $ids );
	}

	public function testSourceIsTheFirstOpenStepUsingTheEntry(): void {
		$this->assertSame( 2, $this->terms()->source( 11 )?->id );
		$this->assertNull( $this->terms()->source( 12 ), 'Locked entries have no source.' );
	}

	public function testCountsAndFilterCoverOpenStepsThatIntroduceSomething(): void {
		$steps = array( Course::step( 1 ), Course::step( 2 ), Course::step( 3 ) );
		$terms = array( Course::term( 10, 'Commit', 1 ), Course::term( 11, 'Branch', 3 ), Course::term( 12, 'Merge', 3 ) );
		$model = new CourseTerms( $steps, $terms, $terms );

		$this->assertSame(
			array(
				1 => 1,
				3 => 2,
			),
			$model->counts()
		);
		$this->assertSame( array( 1, 3 ), array_map( fn ( Step $step ) => $step->id, $model->filter_steps() ) );
	}

	public function testLockedEntriesAreCountedAndGroupedByIntroducingStep(): void {
		$terms  = $this->terms();
		$groups = $terms->locked_by_step();

		$this->assertSame( 2, $terms->locked_count() );
		$this->assertSame( array( 3, 4 ), array_keys( $groups ) );
		$this->assertSame( 'Merge', $groups[3]['entries'][0]->term() );
		$this->assertSame( 3, $terms->next_locked()?->id );
	}

	public function testEntryUsedInALockedStepAndALaterOpenOneIsListed(): void {
		// Drip can lock lesson 1 while lesson 2 is open: the entry is introduced, for this learner, in lesson 2.
		$steps  = array( Course::step( 1, Step::LOCKED ), Course::step( 2 ) );
		$course = array( Course::term( 10, 'Commit', 1 ) );
		$open   = array( Course::term( 10, 'Commit', 2 ) );
		$model  = new CourseTerms( $steps, $open, $course );

		$this->assertSame( 2, $model->source( 10 )?->id );
		$this->assertSame( 0, $model->locked_count() );
		$this->assertNull( $model->next_locked() );
	}

	public function testInlineIdsComeFromTheIntroducingStep(): void {
		$steps = array( Course::step( 1 ), Course::step( 2 ) );
		$terms = array( Course::term( 10, 'Commit', 1 ), Course::term( 11, 'Branch', 2, false ) );

		$this->assertSame( array( 10 ), ( new CourseTerms( $steps, $terms, $terms ) )->inline_ids() );
	}

	public function testTermsFromStepsOutsideTheCourseAreIgnored(): void {
		$model = new CourseTerms( array( Course::step( 1 ) ), array( Course::term( 10, 'Commit', 99 ) ), array( Course::term( 10, 'Commit', 99 ) ) );

		$this->assertTrue( $model->is_empty() );
	}
}
