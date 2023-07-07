<?php
/**
 * This class provides the easy way to operate an exam.
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

namespace LearnDash\Core\Models;

use LDLMS_Post_Types;
use LearnDash\Core\Models\Traits\Has_Course;

/**
 * Exam model class.
 *
 * @since 4.6.0
 */
class Exam extends Post {
	use Has_Course {
		get_course as get_course_from_trait;
	}

	/**
	 * Returns allowed post types.
	 *
	 * @since 4.6.0
	 *
	 * @return string[]
	 */
	public static function get_allowed_post_types(): array {
		return array(
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::EXAM ),
		);
	}

	/**
	 * Returns a course of the exam or null.
	 *
	 * @since 4.6.0
	 *
	 * @return Course|null
	 */
	public function get_course(): ?Course {
		/**
		 * Filters an exam course.
		 *
		 * @since 4.6.0
		 *
		 * @param Course|null $course Course model.
		 * @param Exam        $exam   Exam model.
		 *
		 * @return Course|null Lesson course model.
		 *
		 * @ignore
		 */
		return apply_filters( 'learndash_model_exam_course', $this->get_course_from_trait(), $this );
	}
}
