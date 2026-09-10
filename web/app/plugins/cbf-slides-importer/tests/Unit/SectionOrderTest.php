<?php
/**
 * @package CodingBlackFemales/SlidesImporter
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SlidesImporter\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SlidesImporter\Bulk\SectionHeadings;

/**
 * Covers the `order` value LearnDash reads section membership from.
 *
 * These tests exist because an end-to-end check could not catch the bug they
 * guard against: the code that read the grouping back used the same mistaken
 * rule as the code that wrote it, so a course with 28 lessons in the wrong
 * section verified as correct. The fix is to test against a replica of what
 * LearnDash actually does, rather than against our own reading of it.
 */
final class SectionOrderTest extends Unit {

	/**
	 * Reproduce LDLMS_Model_Course_Steps::steps_grouped_sections().
	 *
	 *     $lessons = array_keys( $steps['sfwd-lessons'] );
	 *     foreach ( $sections_array as $section ) {
	 *         array_splice( $lessons, (int) $section->order, 0, array( $section ) );
	 *     }
	 *
	 * @param  int   $lesson_count How many lessons the course holds.
	 * @param  array $sections     [ title => order ], in the order LearnDash reads them.
	 * @return array<string, int>  Lessons rendered under each heading.
	 */
	private function render( int $lesson_count, array $sections ): array {
		$lessons = range( 1, $lesson_count );

		foreach ( $sections as $title => $order ) {
			array_splice( $lessons, $order, 0, array( array( 'heading' => $title ) ) );
		}

		$counts  = array();
		$current = null;

		foreach ( $lessons as $item ) {
			if ( is_array( $item ) ) {
				$current            = $item['heading'];
				$counts[ $current ] = 0;
				continue;
			}

			if ( $current !== null ) {
				++$counts[ $current ];
			}
		}

		return $counts;
	}

	/**
	 * Orders produced by section_order() group the lessons as intended.
	 *
	 * The section sizes here are the ones from the first full migration, which
	 * is where the drift was found.
	 */
	public function testOrdersProduceTheIntendedGrouping(): void {
		$intended = array(
			'Course Onboarding'           => 4,
			'Foundations'                 => 19,
			'Analytics Fundamentals'      => 8,
			'SQL & BigQuery'              => 6,
			'Data Modelling'              => 16,
			'Analytics Engineering (dbt)' => 30,
			'Analytics Delivery'          => 19,
			'Professional Skills'         => 7,
		);

		$orders  = array();
		$lessons = 0;
		$index   = 0;

		foreach ( $intended as $title => $size ) {
			$orders[ $title ] = SectionHeadings::section_order( $lessons, $index );
			$lessons         += $size;
			++$index;
		}

		$this->assertSame( $intended, $this->render( array_sum( $intended ), $orders ) );
	}

	/**
	 * Counting lessons alone drifts each heading one place per heading before it.
	 *
	 * This is the regression guard: it asserts the old rule is wrong, so
	 * reverting section_order() to `count( $sequence )` fails the suite.
	 */
	public function testCountingLessonsAloneMisplacesContent(): void {
		$intended = array(
			'One'   => 4,
			'Two'   => 3,
			'Three' => 3,
		);

		$naive   = array();
		$lessons = 0;

		foreach ( $intended as $title => $size ) {
			$naive[ $title ] = $lessons;
			$lessons        += $size;
		}

		$rendered = $this->render( array_sum( $intended ), $naive );

		$this->assertNotSame( $intended, $rendered );
		$this->assertSame( 3, $rendered['One'] );
		$this->assertSame( 2, $rendered['Two'] );
		$this->assertSame( 5, $rendered['Three'] );
	}

	/** A heading with no lessons keeps its place and stays empty. */
	public function testEmptyHeadingsKeepTheirPlace(): void {
		$intended = array(
			'First'  => 2,
			'Empty'  => 0,
			'Second' => 3,
		);

		$orders  = array();
		$lessons = 0;
		$index   = 0;

		foreach ( $intended as $title => $size ) {
			$orders[ $title ] = SectionHeadings::section_order( $lessons, $index );
			$lessons         += $size;
			++$index;
		}

		$this->assertSame( $intended, $this->render( array_sum( $intended ), $orders ) );
	}

	/** The first heading always sits at the very top. */
	public function testFirstHeadingIsAtZero(): void {
		$this->assertSame( 0, SectionHeadings::section_order( 0, 0 ) );
	}
}
