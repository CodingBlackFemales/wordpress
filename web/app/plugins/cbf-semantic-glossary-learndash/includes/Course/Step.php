<?php
/**
 * One lesson or topic of a course.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Course;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Step.
 *
 * Holds no WordPress objects, so the course glossary's logic can be exercised
 * without LearnDash. Steps reads these from LearnDash.
 */
final class Step {

	/**
	 * Why a step is locked: not locked.
	 */
	const OPEN = '';

	/**
	 * Why a step is locked: the learner cannot reach it yet (enrolment, drip or
	 * linear progression; LearnDash does not say which).
	 */
	const LOCKED = 'locked';

	/**
	 * Why a step is locked: it has a drip schedule. Used by the editor preview,
	 * which has no learner to ask about.
	 */
	const DRIP = 'drip';

	/**
	 * Constructor.
	 *
	 * @param int    $id    Post ID.
	 * @param string $title Title, used as-is (no numbering).
	 * @param string $url   Permalink.
	 * @param string $lock  Why the step is locked: OPEN, LOCKED or DRIP.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $title,
		public readonly string $url,
		public readonly string $lock = self::OPEN
	) {}


	/**
	 * Whether its terms are listed.
	 */
	public function is_open(): bool {
		return $this->lock === self::OPEN;
	}
}
