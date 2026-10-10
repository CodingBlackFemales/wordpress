<?php
/**
 * The lesson filter's markup.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Render;

use CodingBlackFemales\SemanticGlossaryLearnDash\Course\Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LessonFilter.
 *
 * A multi-select combobox (https://uxpatterns.dev/patterns/forms/multi-select-input).
 * The server renders the label, the empty control, the full option list and
 * the status line; the front-end script adds the chips and behaviour and
 * filters the entries already on the page, by their `data-lesson`.
 *
 * Its options are the lessons and topics the learner can open that introduce
 * at least one entry, in course order. Locked lessons never appear in it.
 *
 * It is `hidden` until the script runs, since it does nothing without it.
 * The editor preview shows it as a static picture of its initial state.
 */
final class LessonFilter {

	/**
	 * The filter, or an empty string when it is not shown.
	 *
	 * @param View $view The glossary.
	 */
	public static function render( View $view ): string {
		if ( ! $view->shows_filter() ) {
			return '';
		}

		$id    = $view->id . '-filter';
		$steps = $view->terms->filter_steps();
		$total = array_sum( $view->terms->counts() );

		return sprintf(
			'<div class="course-glossary-filter" data-course-glossary-filter%1$s>'
				. '<label class="course-glossary-filter__label" id="%2$s-label" for="%2$s-input">%3$s</label>'
				. '<div class="course-glossary-filter__control" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-controls="%2$s-listbox" aria-labelledby="%2$s-label">'
				. '<ul class="course-glossary-filter__chips" aria-label="%4$s"></ul>'
				. '<input class="course-glossary-filter__input" id="%2$s-input" type="text" autocomplete="off" aria-autocomplete="list" aria-controls="%2$s-listbox" placeholder="%5$s">'
				. '</div>'
				. '<ul class="course-glossary-filter__listbox" id="%2$s-listbox" role="listbox" aria-multiselectable="true" aria-labelledby="%2$s-label" hidden>%6$s</ul>'
				. '<p class="course-glossary-filter__status" aria-live="polite">%7$s</p>'
				. '</div>',
			$view->editor ? '' : ' hidden',
			esc_attr( $id ),
			esc_html__( 'Filter by lesson', 'cbf-semantic-glossary-learndash' ),
			esc_attr__( 'Selected lessons', 'cbf-semantic-glossary-learndash' ),
			esc_attr__( 'Search lessons', 'cbf-semantic-glossary-learndash' ),
			self::select_all( $id, count( $steps ) ) . implode( '', array_map( fn ( Step $step ) => self::option( $id, $step, $view->terms->counts()[ $step->id ] ), $steps ) ),
			esc_html( self::status( count( $steps ), $total ) )
		);
	}


	/**
	 * The status line: "{n} lessons selected · {m} terms".
	 *
	 * The front-end script builds the same text from the same strings.
	 *
	 * @param int $lessons Lessons selected.
	 * @param int $terms   Entries shown.
	 */
	public static function status( int $lessons, int $terms ): string {
		return sprintf(
			/* translators: 1: "N lessons selected", 2: "N terms" */
			__( '%1$s · %2$s', 'cbf-semantic-glossary-learndash' ),
			/* translators: %d: number of lessons */
			sprintf( _n( '%d lesson selected', '%d lessons selected', $lessons, 'cbf-semantic-glossary-learndash' ), $lessons ),
			/* translators: %d: number of terms */
			sprintf( _n( '%d term', '%d terms', $terms, 'cbf-semantic-glossary-learndash' ), $terms )
		);
	}


	/**
	 * The "Select all (N)" option.
	 *
	 * @param string $id    ID prefix.
	 * @param int    $count Number of lessons.
	 */
	private static function select_all( string $id, int $count ): string {
		return sprintf(
			'<li class="course-glossary-filter__option course-glossary-filter__option--all" id="%1$s-all" role="option" aria-selected="true" data-value="all">'
				. '<span class="course-glossary-filter__check" aria-hidden="true"></span>'
				. '<span class="course-glossary-filter__text">%2$s <span class="course-glossary-filter__count">(%3$d)</span></span>'
				. '</li>',
			esc_attr( $id ),
			esc_html__( 'Select all', 'cbf-semantic-glossary-learndash' ),
			$count
		);
	}


	/**
	 * One lesson's option.
	 *
	 * @param string $id    ID prefix.
	 * @param Step   $step  Lesson or topic.
	 * @param int    $count Entries it introduces.
	 */
	private static function option( string $id, Step $step, int $count ): string {
		return sprintf(
			'<li class="course-glossary-filter__option" id="%1$s-%2$d" role="option" aria-selected="true" data-value="%2$d" data-count="%3$d">'
				. '<span class="course-glossary-filter__check" aria-hidden="true"></span>'
				. '<span class="course-glossary-filter__text">%4$s</span>'
				. '<span class="course-glossary-filter__count">%5$s</span>'
				. '</li>',
			esc_attr( $id ),
			$step->id,
			$count,
			esc_html( $step->title ),
			/* translators: %d: number of terms */
			esc_html( sprintf( _n( '%d term', '%d terms', $count, 'cbf-semantic-glossary-learndash' ), $count ) )
		);
	}
}
