<?php
/**
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\Steps;

/**
 * Covers choosing a group's default course.
 */
final class StepsTest extends Unit {

	public function testAGroupWithOneCourseDefaultsToIt(): void {
		$this->assertSame( 42, Steps::only( array( 42 ) ) );
	}

	public function testAGroupWithSeveralCoursesHasNoDefault(): void {
		$this->assertSame( 0, Steps::only( array( 42, 43 ) ) );
	}

	public function testAGroupWithNoCoursesHasNoDefault(): void {
		$this->assertSame( 0, Steps::only( array() ) );
	}

	public function testKeysDoNotMatter(): void {
		$this->assertSame( 42, Steps::only( array( 7 => 42 ) ) );
	}
}
