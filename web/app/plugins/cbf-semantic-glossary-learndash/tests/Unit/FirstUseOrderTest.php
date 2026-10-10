<?php
/**
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\FirstUseOrder;

/**
 * Covers re-sorting "First used in" into course order.
 */
final class FirstUseOrderTest extends Unit {

	public function testStepsOfACourseAreSortedIntoCourseOrder(): void {
		// Core's date order: lesson 30 was written first, but comes last in the course.
		$positions = array(
			30 => array( 5, 2 ),
			10 => array( 5, 0 ),
			20 => array( 5, 1 ),
		);

		$this->assertSame( array( 10, 20, 30 ), FirstUseOrder::sort( array( 30, 10, 20 ), $positions ) );
	}

	public function testPostsOutsideACourseKeepTheirPlace(): void {
		$positions = array(
			7  => null,
			30 => array( 5, 1 ),
			10 => array( 5, 0 ),
			8  => null,
		);

		$this->assertSame( array( 7, 10, 30, 8 ), FirstUseOrder::sort( array( 7, 30, 10, 8 ), $positions ) );
	}

	public function testEachCourseStaysTogetherWhereItFirstAppears(): void {
		$positions = array(
			40 => array( 6, 1 ),
			10 => array( 5, 3 ),
			41 => array( 6, 0 ),
			11 => array( 5, 1 ),
		);

		$this->assertSame( array( 41, 40, 11, 10 ), FirstUseOrder::sort( array( 40, 10, 41, 11 ), $positions ) );
	}
}
