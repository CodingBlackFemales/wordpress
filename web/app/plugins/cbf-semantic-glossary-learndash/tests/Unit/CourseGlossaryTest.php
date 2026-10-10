<?php
/**
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Tests\Unit;

use Codeception\Test\Unit;
use CodingBlackFemales\SemanticGlossary\Render\GlossaryBuilder;
use CodingBlackFemales\SemanticGlossary\Render\GlossaryRenderer;
use CodingBlackFemales\SemanticGlossary\Render\Options;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\CourseTerms;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\Step;
use CodingBlackFemales\SemanticGlossaryLearnDash\Render\CourseGlossary;
use CodingBlackFemales\SemanticGlossaryLearnDash\Render\CourseOptions;
use CodingBlackFemales\SemanticGlossaryLearnDash\Tests\Support\Course;

/**
 * Covers the course glossary markup from TECH-900.
 */
final class CourseGlossaryTest extends Unit {

	/**
	 * Register the decorating filters.
	 */
	protected function _before(): void {
		CourseGlossary::hooks();
	}

	/**
	 * Lessons 1 and 2 open with an entry each; lesson 3 dripped with two more.
	 *
	 * @param string $lock How lesson 3 is locked.
	 */
	private function terms( string $lock = Step::LOCKED ): CourseTerms {
		$course = array(
			Course::term( 10, 'Commit', 1 ),
			Course::term( 11, 'Branch', 2 ),
			Course::term( 12, 'Merge', 3 ),
			Course::term( 13, 'Rebase', 3 ),
		);

		return new CourseTerms( array( Course::step( 1 ), Course::step( 2 ), Course::step( 3, $lock ) ), array_slice( $course, 0, 2 ), $course );
	}

	/**
	 * Block options with overrides.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	private function options( array $attributes = array() ): CourseOptions {
		return CourseOptions::from_block( $attributes, 'h' );
	}

	public function testEntryCarriesItsLessonAndIntroducedInLine(): void {
		$html = CourseGlossary::render( $this->terms(), $this->options() );

		$this->assertStringContainsString(
			'<div class="glossary-entry" data-lesson="2" data-letter="B">'
			. '<dt><dfn id="dfn-branch">Branch</dfn> <a href="https://academy.test/lessons/lesson-2/#ref-branch" class="glossary-backlink" aria-label="Back to where Branch is used">' . GlossaryRenderer::BACK_LINK . '</a></dt>'
			. '<dd>Definition of Branch.</dd>'
			. '<dd class="glossary-source">Introduced in <a href="https://academy.test/lessons/lesson-2/">Lesson 2</a></dd>'
			. '</div>',
			$html
		);
	}

	public function testIntroducedInLineAndBackLinksCanBeTurnedOff(): void {
		$html = CourseGlossary::render(
			$this->terms(),
			$this->options(
				array(
					'showSource' => false,
					'backLinks' => false,
				)
			)
		);

		$this->assertStringNotContainsString( 'glossary-source', $html );
		$this->assertStringNotContainsString( 'glossary-backlink', $html );
		$this->assertStringContainsString( 'data-lesson="1"', $html );
	}

	public function testLockedEntriesNeverReachTheFrontEnd(): void {
		$html = CourseGlossary::render( $this->terms(), $this->options() );

		$this->assertStringNotContainsString( 'Merge', $html );
		$this->assertStringNotContainsString( 'Rebase', $html );
		$this->assertStringNotContainsString( 'Lesson 3</span>', $html, 'Locked lessons are not offered in the filter.' );
	}

	public function testLockedNoticeCountsWhatIsToComeAndLinksOn(): void {
		$html = CourseGlossary::render( $this->terms(), $this->options() );

		$this->assertStringContainsString(
			'<p class="course-glossary-locked">2 more terms are introduced in lessons you haven&#039;t unlocked yet. They&#039;ll appear here as those lessons unlock. '
			. '<a href="https://academy.test/lessons/lesson-3/">Continue to Lesson 3</a></p>',
			$html
		);
	}

	public function testIntroAndFilterSitBetweenHeadingAndList(): void {
		$html = CourseGlossary::render( $this->terms(), $this->options() );

		$heading = strpos( $html, '</h2>' );
		$intro   = strpos( $html, 'course-glossary-intro' );
		$filter  = strpos( $html, 'data-course-glossary-filter hidden' );
		$list    = strpos( $html, '<dl class="glossary">' );

		$this->assertTrue( $heading < $intro && $intro < $filter && $filter < $list );
		$this->assertStringContainsString( 'role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-controls="course-glossary-', $html );
		$this->assertStringContainsString( 'role="listbox" aria-multiselectable="true"', $html );
		$this->assertStringContainsString( 'Select all <span class="course-glossary-filter__count">(2)</span>', $html );
		$this->assertStringContainsString( '<p class="course-glossary-filter__status" aria-live="polite">2 lessons selected · 2 terms</p>', $html );
	}

	public function testFilterIsLeftOutWithOnlyOneLessonToPick(): void {
		$terms = array( Course::term( 10, 'Commit', 1 ) );
		$html  = CourseGlossary::render( new CourseTerms( array( Course::step( 1 ) ), $terms, $terms ), $this->options() );

		$this->assertStringNotContainsString( 'course-glossary-filter', $html );
	}

	public function testEditorPreviewShowsLockedLessonsAsPlaceholders(): void {
		$html = CourseGlossary::render( $this->terms( Step::DRIP ), $this->options(), true );

		$this->assertStringContainsString( 'class="course-glossary-placeholder"', $html );
		$this->assertStringContainsString( 'Drip</span> <span class="course-glossary-placeholder__title">Lesson 3</span> <span class="course-glossary-placeholder__count">· 2 terms</span>', $html );
		$this->assertStringContainsString( 'Merge, Rebase', $html );
		$this->assertStringContainsString( 'data-course-glossary-filter>', $html, 'The editor shows the filter statically.' );
	}

	public function testEditorPreviewCanLeaveLockedLessonsOut(): void {
		$html = CourseGlossary::render( $this->terms( Step::DRIP ), $this->options( array( 'previewLocked' => false ) ), true );

		$this->assertStringNotContainsString( 'course-glossary-placeholder', $html );
	}

	public function testLearnerWithNothingOpenGetsTheHeadingAndNotice(): void {
		$course = array( Course::term( 10, 'Commit', 1 ) );
		$html   = CourseGlossary::render( new CourseTerms( array( Course::step( 1, Step::LOCKED ) ), array(), $course ), $this->options() );

		$this->assertSame(
			'<section class="glossary-section" aria-labelledby="' . $this->heading_id( $html ) . '"><h2 id="' . $this->heading_id( $html ) . '" class="glossary-heading">Glossary</h2>'
			. '<p class="course-glossary-locked">1 term is introduced in this course&#039;s lessons. It will appear here when its lesson unlocks. '
			. '<a href="https://academy.test/lessons/lesson-1/">Continue to Lesson 1</a></p></section>',
			$html
		);
	}

	public function testNothingIsRenderedForACourseWithoutReferences(): void {
		$this->assertSame( '', CourseGlossary::render( new CourseTerms( array( Course::step( 1 ) ), array(), array() ), $this->options() ) );
	}

	public function testPostGlossariesAreLeftAlone(): void {
		$terms = $this->terms();
		$items = GlossaryBuilder::items( $terms->entries(), $terms->inline_ids() );
		$html  = GlossaryRenderer::render( $items, new Options( 'Glossary' ) );

		$this->assertStringNotContainsString( 'data-lesson', $html );
		$this->assertStringContainsString( 'href="#ref-branch"', $html );
	}

	public function testEachGlossaryOnAPageHasItsOwnIds(): void {
		$first  = CourseGlossary::render( $this->terms(), $this->options() );
		$second = CourseGlossary::render( $this->terms(), $this->options() );

		$this->assertNotSame( $this->heading_id( $first ), $this->heading_id( $second ) );
	}

	public function testShortcodeAttributesMapOntoOptions(): void {
		$options = CourseOptions::from_shortcode(
			array(
				'course' => '5943',
				'filter' => 'no',
				'source' => 'false',
				'index'  => '0',
				'level'  => '3',
			),
			'h'
		);

		$this->assertSame( 5943, $options->course_id );
		$this->assertFalse( $options->show_filter );
		$this->assertFalse( $options->show_source );
		$this->assertFalse( $options->glossary->show_index );
		$this->assertSame( 3, $options->glossary->level );
		$this->assertSame( 0, CourseOptions::from_shortcode( array( 'course' => 'current' ), 'h' )->course_id );
	}

	/**
	 * The heading ID a rendered glossary uses.
	 *
	 * @param string $html Glossary markup.
	 */
	private function heading_id( string $html ): string {
		return preg_match( '/aria-labelledby="([^"]+)"/', $html, $match ) ? $match[1] : '';
	}
}
