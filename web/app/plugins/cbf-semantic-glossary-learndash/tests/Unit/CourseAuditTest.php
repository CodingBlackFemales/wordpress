<?php
/**
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\CourseAudit;

/**
 * Covers the course-level first-mention check.
 */
final class CourseAuditTest extends Unit {

	public function testUnmarkedUseBeforeTheIntroducingStepIsReported(): void {
		$findings = CourseAudit::introduced_late(
			array( 1, 2, 3 ),
			array( 10 => 3 ),
			array( 1 => array( 10 => 'commit' ) )
		);

		$this->assertSame(
			array(
				array(
					'step_id'       => 1,
					'entry_id'      => 10,
					'text'          => 'commit',
					'introduced_in' => 3,
				),
			),
			$findings
		);
	}

	public function testUnmarkedUseAfterTheIntroducingStepIsFine(): void {
		$this->assertSame( array(), CourseAudit::introduced_late( array( 1, 2 ), array( 10 => 1 ), array( 2 => array( 10 => 'commit' ) ) ) );
	}

	public function testEachEntryIsReportedOnceAtItsEarliestUnmarkedUse(): void {
		$findings = CourseAudit::introduced_late(
			array( 1, 2, 3 ),
			array( 10 => 3 ),
			array(
				2 => array( 10 => 'Commit' ),
				1 => array( 10 => 'commit' ),
			)
		);

		$this->assertCount( 1, $findings );
		$this->assertSame( 1, $findings[0]['step_id'] );
	}

	public function testEntriesNotInTheCourseAreIgnored(): void {
		$this->assertSame( array(), CourseAudit::introduced_late( array( 1, 2 ), array(), array( 1 => array( 10 => 'commit' ) ) ) );
	}
}
