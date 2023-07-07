<?php
/**
 * Course Step Interface.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

namespace LearnDash\Core\Models\Interfaces;

use LearnDash\Core\Models\Course;

/**
 * Interface for models that are associated with a course and can be part of a course.
 */
interface Course_Step {
	/**
	 * Returns a course of the step or null if the step is not associated with a course.
	 *
	 * @since 4.6.0
	 *
	 * @return Course|null
	 */
	public function get_course(): ?Course;
}
