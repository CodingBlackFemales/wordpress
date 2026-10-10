<?php
/**
 * Course-level checks on the first-mention rule.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Course;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CourseAudit.
 *
 * Core's audit checks one post at a time. Across a course, an entry should be
 * marked where a learner first meets it: if an earlier lesson already uses the
 * term in prose without marking it, the glossary says it was introduced later
 * than it really was.
 */
final class CourseAudit {

	/**
	 * Finding: an entry is marked in a later step than one that already uses it unmarked.
	 */
	const INTRODUCED_LATE = 'introduced-late';

	/**
	 * Finding: a step references entries but has no Glossary block.
	 */
	const NO_GLOSSARY = 'no-glossary';

	/**
	 * Finding: a lesson or topic with references belongs to no course.
	 */
	const NO_COURSE = 'no-course';


	/**
	 * Entries used unmarked before the step that introduces them.
	 *
	 * @param int[]                          $step_ids  Steps, in course order.
	 * @param array<int, int>                $first_use Introducing step ID, by entry ID.
	 * @param array<int, array<int, string>> $unmarked  Unmarked text, by entry ID, by step ID.
	 * @return array<int, array{step_id:int, entry_id:int, text:string, introduced_in:int}>
	 */
	public static function introduced_late( array $step_ids, array $first_use, array $unmarked ): array {
		$position = array_flip( $step_ids );
		$findings = array();
		$reported = array();

		foreach ( $step_ids as $step_id ) {
			foreach ( $unmarked[ $step_id ] ?? array() as $entry_id => $text ) {
				$introduced_in = (int) ( $first_use[ $entry_id ] ?? 0 );

				if ( isset( $reported[ $entry_id ] ) || ! self::is_later( $position, $introduced_in, $step_id ) ) {
					continue;
				}

				$reported[ $entry_id ] = true;
				$findings[]            = array(
					'step_id'       => $step_id,
					'entry_id'      => $entry_id,
					'text'          => $text,
					'introduced_in' => $introduced_in,
				);
			}
		}

		return $findings;
	}


	/**
	 * Whether one step comes after another in the course.
	 *
	 * @param array<int, int> $position Position, by step ID.
	 * @param int             $later    Step that should come later.
	 * @param int             $earlier  Step that should come earlier.
	 */
	private static function is_later( array $position, int $later, int $earlier ): bool {
		return isset( $position[ $later ], $position[ $earlier ] ) && $position[ $later ] > $position[ $earlier ];
	}
}
