<?php
/**
 * The entries a course glossary lists, and where each was introduced.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Course;

use CodingBlackFemales\SemanticGlossary\Entry\Entry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CourseTerms.
 *
 * Built from term arrays as `glossary_get_terms_for_posts()` returns them, so
 * it runs without a database:
 *
 * - the open terms come from the steps the learner can open, walked in course
 *   order, so each term's `post_id` is the first open step that uses it;
 * - the course terms come from every step, so anything among them that is not
 *   open is still to come.
 *
 * Locked terms are only ever counted and grouped here. Rendering them is the
 * editor preview's business; the front end never sees them.
 */
final class CourseTerms {

	/**
	 * Steps in course order, by ID.
	 *
	 * @var array<int, Step>
	 */
	private array $steps = array();

	/**
	 * Terms the learner can see, by entry ID.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $open = array();

	/**
	 * Terms still to come, by entry ID, in course order of first use.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $locked = array();


	/**
	 * Constructor.
	 *
	 * @param Step[]                           $steps        Lessons and topics, in course order.
	 * @param array<int, array<string, mixed>> $open_terms   Terms used in the open steps.
	 * @param array<int, array<string, mixed>> $course_terms Terms used anywhere in the course.
	 */
	public function __construct( array $steps, array $open_terms, array $course_terms ) {
		foreach ( $steps as $step ) {
			$this->steps[ $step->id ] = $step;
		}

		$this->open   = $this->keyed( array_filter( $open_terms, fn ( array $term ) => $this->is_open_step( (int) $term['post_id'] ) ) );
		$this->locked = $this->keyed(
			array_filter( $course_terms, fn ( array $term ) => ! isset( $this->open[ (int) $term['id'] ] ) && isset( $this->steps[ (int) $term['post_id'] ] ) )
		);
	}


	/**
	 * IDs of the steps whose terms are listed, in course order.
	 *
	 * @param Step[] $steps Steps.
	 * @return int[]
	 */
	public static function open_ids( array $steps ): array {
		return array_values( array_map( fn ( Step $step ) => $step->id, array_filter( $steps, fn ( Step $step ) => $step->is_open() ) ) );
	}


	/**
	 * Whether there is nothing to list and nothing to come.
	 */
	public function is_empty(): bool {
		return $this->open === array() && $this->locked === array();
	}


	/**
	 * The entries to list, in no particular order.
	 *
	 * @return Entry[]
	 */
	public function entries(): array {
		return array_values( array_map( array( self::class, 'entry' ), $this->open ) );
	}


	/**
	 * Entries whose introducing step marks them inline, so a back-link has somewhere to go.
	 *
	 * @return int[]
	 */
	public function inline_ids(): array {
		return array_keys( array_filter( $this->open, fn ( array $term ) => ! empty( $term['inline'] ) ) );
	}


	/**
	 * The step that introduces an entry: the first open step using it.
	 *
	 * @param int $entry_id Entry ID.
	 */
	public function source( int $entry_id ): ?Step {
		$step_id = isset( $this->open[ $entry_id ] ) ? (int) $this->open[ $entry_id ]['post_id'] : 0;

		return $this->steps[ $step_id ] ?? null;
	}


	/**
	 * How many listed entries each open step introduces, in course order.
	 *
	 * Steps that introduce nothing are left out.
	 *
	 * @return array<int, int> Count by step ID.
	 */
	public function counts(): array {
		$counts = array_fill_keys( array_keys( $this->steps ), 0 );

		foreach ( $this->open as $term ) {
			++$counts[ (int) $term['post_id'] ];
		}

		return array_filter( $counts );
	}


	/**
	 * Steps a learner can filter by: the open steps that introduce something.
	 *
	 * @return Step[]
	 */
	public function filter_steps(): array {
		return array_values( array_intersect_key( $this->steps, $this->counts() ) );
	}


	/**
	 * How many entries are still to come.
	 */
	public function locked_count(): int {
		return count( $this->locked );
	}


	/**
	 * Entries still to come, grouped by the step that introduces them, in course order.
	 *
	 * @return array<int, array{step: Step, entries: Entry[]}> By step ID.
	 */
	public function locked_by_step(): array {
		$groups = array();

		foreach ( $this->locked as $term ) {
			$step_id = (int) $term['post_id'];

			$groups[ $step_id ] ??= array(
				'step'    => $this->steps[ $step_id ],
				'entries' => array(),
			);

			$groups[ $step_id ]['entries'][] = self::entry( $term );
		}

		$ordered = array();
		foreach ( array_keys( $this->steps ) as $step_id ) {
			if ( isset( $groups[ $step_id ] ) ) {
				$ordered[ $step_id ] = $groups[ $step_id ];
			}
		}

		return $ordered;
	}


	/**
	 * The first locked step that introduces something: where the learner is headed.
	 */
	public function next_locked(): ?Step {
		$first = array_key_first( $this->locked_by_step() );

		return $first !== null ? $this->steps[ $first ] : null;
	}


	/**
	 * Whether a step is part of the course and open.
	 *
	 * @param int $step_id Step ID.
	 */
	private function is_open_step( int $step_id ): bool {
		return isset( $this->steps[ $step_id ] ) && $this->steps[ $step_id ]->is_open();
	}


	/**
	 * Terms by entry ID, keeping each entry's first.
	 *
	 * @param array<int, array<string, mixed>> $terms Terms.
	 * @return array<int, array<string, mixed>>
	 */
	private function keyed( array $terms ): array {
		$keyed = array();
		foreach ( $terms as $term ) {
			$keyed[ (int) $term['id'] ] ??= $term;
		}
		return $keyed;
	}


	/**
	 * An entry from its public array.
	 *
	 * @param array<string, mixed> $term Term, as glossary_get_terms_for_posts() returns it.
	 */
	private static function entry( array $term ): Entry {
		return new Entry( (int) $term['id'], (string) $term['slug'], (array) $term['forms'], (string) $term['definition'], (string) $term['status'] );
	}
}
