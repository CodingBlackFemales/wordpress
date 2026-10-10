<?php
/**
 * Render a course glossary.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Render;

use CodingBlackFemales\SemanticGlossary\Entry\Definition;
use CodingBlackFemales\SemanticGlossary\Entry\Entry;
use CodingBlackFemales\SemanticGlossary\Render\GlossaryBuilder;
use CodingBlackFemales\SemanticGlossary\Render\GlossaryItem;
use CodingBlackFemales\SemanticGlossary\Render\GlossaryRenderer;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\CourseTerms;
use CodingBlackFemales\SemanticGlossaryLearnDash\Course\Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CourseGlossary.
 *
 * Core renders the glossary; this decorates it through core's filters, so the
 * course glossary keeps core's markup, index and styling:
 *
 *     <section class="glossary-section" aria-labelledby="…">
 *       <h2 …>Glossary</h2>
 *       <p class="course-glossary-intro">Terms from the lessons you've unlocked so far…</p>
 *       <div class="course-glossary-filter" …>…</div>          (lesson filter)
 *       <nav class="glossary-index" …>…</nav>                    (core's A–Z index)
 *       <dl class="glossary">
 *         <div class="glossary-entry" id="glossary-b" data-lesson="123" data-letter="B">
 *           <dt><dfn id="dfn-branch">Branch</dfn> <a href="/lessons/how-does-git-work/#ref-branch" class="glossary-backlink" …>↩</a></dt>
 *           <dd>A named line of development within a repository.</dd>
 *           <dd class="glossary-source">Introduced in <a href="/lessons/how-does-git-work/">How Does Git Work?</a></dd>
 *         </div>
 *       </dl>
 *       <p class="course-glossary-locked">9 more terms are introduced in lessons you haven't unlocked yet… <a …>Continue to Branching</a></p>
 *     </section>
 *
 * Every filter checks for a View in the render context, so post glossaries
 * on the same page are left alone.
 */
final class CourseGlossary {

	/**
	 * Render-context key holding the View.
	 */
	const CONTEXT = 'course_glossary';

	/**
	 * Padlock for the editor's locked-lesson badge.
	 */
	const LOCK_ICON = '<svg class="course-glossary-placeholder__icon" width="12" height="12" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17 10h-1V7a4 4 0 0 0-8 0v3H7a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1v-9a1 1 0 0 0-1-1Zm-7.5-3a2.5 2.5 0 0 1 5 0v3h-5V7Z"/></svg>';

	/**
	 * Glossaries rendered so far in this request, to keep their IDs unique.
	 *
	 * @var int
	 */
	private static $count = 0;


	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_filter( 'glossary_html_before_index', array( __CLASS__, 'before_index' ), 10, 3 );
		add_filter( 'glossary_html_after_list', array( __CLASS__, 'after_list' ), 10, 3 );
		add_filter( 'glossary_entry_html', array( __CLASS__, 'entry_html' ), 10, 3 );
		add_filter( 'glossary_back_link_href', array( __CLASS__, 'back_link_href' ), 10, 3 );
	}


	/**
	 * Render a course glossary; an empty string when the course references nothing.
	 *
	 * When the learner can see nothing yet but more is to come, the heading and
	 * the locked-content notice are still shown, so the page explains itself.
	 *
	 * @param CourseTerms   $terms   What to list.
	 * @param CourseOptions $options Presentation.
	 * @param bool          $editor  Whether this is the block editor's preview.
	 */
	public static function render( CourseTerms $terms, CourseOptions $options, bool $editor = false ): string {
		if ( $terms->is_empty() ) {
			return '';
		}

		++self::$count;
		$id      = 'course-glossary-' . self::$count;
		$options = $options->with_heading_id( $id . '-heading' );
		$view    = new View( $terms, $options, $id, $editor );
		$items   = GlossaryBuilder::items( $terms->entries(), $terms->inline_ids() );
		$context = array( self::CONTEXT => $view );

		if ( $items === array() ) {
			return self::section_without_entries( $view, $context );
		}

		return GlossaryRenderer::render( $items, $options->glossary, $context );
	}


	/**
	 * Introduction and lesson filter, between the heading and the index.
	 *
	 * @param string               $html    Markup so far.
	 * @param GlossaryItem[]       $items   Items.
	 * @param array<string, mixed> $context Render context.
	 */
	public static function before_index( $html, $items, $context ): string {
		$view = self::view( $context );

		if ( $view === null ) {
			return (string) $html;
		}

		return $html . self::intro( $view ) . LessonFilter::render( $view );
	}


	/**
	 * Locked-lesson placeholders (editor only) and the locked-content notice, after the list.
	 *
	 * @param string               $html    Markup so far.
	 * @param GlossaryItem[]       $items   Items.
	 * @param array<string, mixed> $context Render context.
	 */
	public static function after_list( $html, $items, $context ): string {
		$view = self::view( $context );

		if ( $view === null ) {
			return (string) $html;
		}

		return $html . self::empty_selection() . self::placeholders( $view ) . self::locked_notice( $view );
	}


	/**
	 * Mark an entry with its lesson and index letter, and add its "Introduced in" line.
	 *
	 * @param string               $html    The entry's `<div>`.
	 * @param Entry                $entry   The entry.
	 * @param array<string, mixed> $context Render context, including 'item'.
	 */
	public static function entry_html( $html, $entry, $context ): string {
		$view = self::view( $context );
		$step = $view?->terms->source( $entry->id );

		if ( $step === null || ! ( $context['item'] ?? null ) instanceof GlossaryItem ) {
			return (string) $html;
		}

		$html = (string) preg_replace(
			'/^<div class="glossary-entry"/',
			sprintf( '<div class="glossary-entry" data-lesson="%d" data-letter="%s"', $step->id, esc_attr( $context['item']->letter ) ),
			(string) $html,
			1
		);

		if ( ! $view->options->show_source ) {
			return $html;
		}

		$close = strrpos( $html, '</div>' );

		return $close === false ? $html : substr( $html, 0, $close ) . self::source( $step ) . substr( $html, $close );
	}


	/**
	 * Point the back-link at the first reference in the lesson that introduces the entry.
	 *
	 * @param string               $href    Default target: the reference on this page.
	 * @param Entry                $entry   The entry.
	 * @param array<string, mixed> $context Render context.
	 */
	public static function back_link_href( $href, $entry, $context ): string {
		$step = self::view( $context )?->terms->source( $entry->id );

		return $step === null ? (string) $href : $step->url . '#' . $entry->ref_anchor();
	}


	/**
	 * The View in a render context, if this is a course glossary.
	 *
	 * @param mixed $context Render context.
	 */
	private static function view( $context ): ?View {
		$view = is_array( $context ) ? ( $context[ self::CONTEXT ] ?? null ) : null;

		return $view instanceof View ? $view : null;
	}


	/**
	 * The introduction.
	 *
	 * @param View $view The glossary.
	 */
	private static function intro( View $view ): string {
		$text = $view->terms->locked_count() > 0
			? __( "Terms from the lessons you've unlocked so far. New terms appear here as you progress.", 'cbf-semantic-glossary-learndash' )
			: __( "Terms from this course's lessons.", 'cbf-semantic-glossary-learndash' );

		return '<p class="course-glossary-intro">' . esc_html( $text ) . '</p>';
	}


	/**
	 * "Introduced in {lesson}".
	 *
	 * @param Step $step The lesson that introduces the entry.
	 */
	private static function source( Step $step ): string {
		return sprintf(
			'<dd class="glossary-source">%s</dd>',
			sprintf(
				/* translators: %s: linked lesson title */
				esc_html__( 'Introduced in %s', 'cbf-semantic-glossary-learndash' ),
				'<a href="' . esc_url( $step->url ) . '">' . esc_html( $step->title ) . '</a>'
			)
		);
	}


	/**
	 * Shown by the lesson filter when nothing is selected.
	 */
	private static function empty_selection(): string {
		return '<p class="course-glossary-empty" hidden>' . esc_html__( 'Select a lesson to see its terms.', 'cbf-semantic-glossary-learndash' ) . '</p>';
	}


	/**
	 * Why the list is incomplete, and where to go next.
	 *
	 * @param View $view The glossary.
	 */
	private static function locked_notice( View $view ): string {
		$count = $view->terms->locked_count();
		$next  = $view->terms->next_locked();

		if ( $count === 0 ) {
			return '';
		}

		$text = $view->terms->entries() === array()
			/* translators: %d: number of terms */
			? _n( "%d term is introduced in this course's lessons. It will appear here when its lesson unlocks.", "%d terms are introduced in this course's lessons. They'll appear here as those lessons unlock.", $count, 'cbf-semantic-glossary-learndash' )
			/* translators: %d: number of terms */
			: _n( "%d more term is introduced in a lesson you haven't unlocked yet. It will appear here when that lesson unlocks.", "%d more terms are introduced in lessons you haven't unlocked yet. They'll appear here as those lessons unlock.", $count, 'cbf-semantic-glossary-learndash' );

		$link = $next === null ? '' : sprintf(
			' <a href="%s">%s</a>',
			esc_url( $next->url ),
			/* translators: %s: lesson title */
			esc_html( sprintf( __( 'Continue to %s', 'cbf-semantic-glossary-learndash' ), $next->title ) )
		);

		return '<p class="course-glossary-locked">' . esc_html( sprintf( $text, $count ) ) . $link . '</p>';
	}


	/**
	 * Locked lessons as collapsed placeholders, so editors can check the full list.
	 *
	 * Editor preview only: locked entries never reach front-end markup.
	 *
	 * @param View $view The glossary.
	 */
	private static function placeholders( View $view ): string {
		if ( ! $view->shows_placeholders() ) {
			return '';
		}

		$html = '';
		foreach ( $view->terms->locked_by_step() as $group ) {
			$html .= self::placeholder( $group['step'], $group['entries'] );
		}

		return $html === '' ? '' : '<div class="course-glossary-placeholders">' . $html . '</div>';
	}


	/**
	 * One locked lesson's placeholder: badge, title, term count, and the terms on one line.
	 *
	 * @param Step    $step    The lesson.
	 * @param Entry[] $entries Entries it introduces.
	 */
	private static function placeholder( Step $step, array $entries ): string {
		$badge = $step->lock === Step::DRIP ? __( 'Drip', 'cbf-semantic-glossary-learndash' ) : __( 'Locked', 'cbf-semantic-glossary-learndash' );
		$names = array_map( fn ( Entry $entry ) => $entry->display(), $entries );

		return sprintf(
			'<div class="course-glossary-placeholder"><p class="course-glossary-placeholder__summary"><span class="course-glossary-placeholder__badge">%1$s%2$s</span> <span class="course-glossary-placeholder__title">%3$s</span> <span class="course-glossary-placeholder__count">· %4$s</span></p><p class="course-glossary-placeholder__note">%5$s</p><p class="course-glossary-placeholder__terms">%6$s</p></div>',
			self::LOCK_ICON,
			esc_html( $badge ),
			esc_html( $step->title ),
			/* translators: %d: number of terms */
			esc_html( sprintf( _n( '%d term', '%d terms', count( $entries ), 'cbf-semantic-glossary-learndash' ), count( $entries ) ) ),
			esc_html__( 'Hidden from learners until this lesson unlocks. Shown here so you can check the full list.', 'cbf-semantic-glossary-learndash' ),
			esc_html( implode( ', ', $names ) )
		);
	}


	/**
	 * The section when the learner can see no entries yet: heading and notice.
	 *
	 * @param View                 $view    The glossary.
	 * @param array<string, mixed> $context Render context.
	 */
	private static function section_without_entries( View $view, array $context ): string {
		$options = $view->options->glossary;
		$html    = sprintf(
			'<section class="glossary-section" aria-labelledby="%1$s"><h%2$d id="%1$s" class="glossary-heading">%3$s</h%2$d>%4$s</section>',
			esc_attr( $options->heading_id ),
			$options->level,
			wp_kses( $options->heading, Definition::ALLOWED_HTML ),
			self::placeholders( $view ) . self::locked_notice( $view )
		);

		/** This filter is documented in cbf-semantic-glossary/includes/Render/GlossaryRenderer.php */
		return (string) apply_filters( 'glossary_html', $html, array(), array_merge( $context, array( 'options' => $options ) ) );
	}
}
