<?php
/**
 * Trait for models that can have children of type quiz.
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

namespace LearnDash\Core\Models\Traits;

use LDLMS_Post_Types;
use LearnDash\Core\Models\Interfaces\Course_Step;
use LearnDash\Core\Models\Quiz;

/**
 * Trait for models that can have children of type quiz.
 *
 * @since 4.6.0
 */
trait Has_Quizzes {
	/**
	 * Returns related quizzes models.
	 *
	 * @since 4.6.0
	 *
	 * @param int $limit  Optional. Limit. Default is 0 which will be changed with LD settings.
	 * @param int $offset Optional. Offset. Default 0.
	 *
	 * @return Quiz[]
	 */
	public function get_quizzes( int $limit = 0, int $offset = 0 ): array {
		if ( $this instanceof Course_Step ) {
			$course = $this->get_course();

			if ( ! $course ) {
				return [];
			}

			$course_id = $course->get_id();
			$parent_id = $this->get_id();
		} else {
			$course_id = $this->get_id();
			$parent_id = 0;
		}

		return $this->memoize(
			function() use ( $course_id, $parent_id, $limit, $offset ): array {
				// TODO: This must be refactored to the direct call to the instance with disabling steps objects loading.
				$quiz_ids = learndash_course_get_children_of_step(
					$course_id,
					$parent_id,
					LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ )
				);

				// TODO: Lesson model has a similar logic, maybe refactor.

				// TODO: Here we need to remove those lessons that a user can't read. We need to refactor it, it can be an additional call to filter by statuses, but a loop is too inefficient.
				// It was done in the legacy code with the following method:
				// protected function can_user_read_step( $step_post_id = 0 ) {
				//
				// if ( ! empty( $lesson_ids ) ) {
				// foreach ( $lesson_ids as $lesson_id => $lesson_post ) {
				// if ( ! $this->can_user_read_step( $lesson_post->ID ) ) {
				// unset( $lessons[ $lesson_id ] );
				// }
				// }
				// }.

				$quiz_ids = array_slice( $quiz_ids, $offset, $limit > 0 ? $limit : null );

				return Quiz::find_many( $quiz_ids );
			}
		);
	}

	/**
	 * Returns the total number of related quizzes.
	 *
	 * @since 4.6.0
	 *
	 * @param bool $with_nested Optional. Whether to include nested quizzes. Default false.
	 *
	 * @return int
	 */
	public function get_quizzes_number( bool $with_nested = false ): int {
		if ( $this instanceof Course_Step ) {
			$course = $this->get_course();

			if ( ! $course ) {
				return 0;
			}

			$course_id = $course->get_id();
			$parent_id = $this->get_id();
		} else {
			$course_id = $this->get_id();
			$parent_id = 0;
		}

		return $this->memoize(
			function() use ( $course_id, $parent_id, $with_nested ): int {
				return count(
					// TODO: Inefficient, we need to refactor it to the direct call to the instance with disabling steps objects loading.
					learndash_course_get_children_of_step(
						$course_id,
						$parent_id,
						LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ),
						'ids',
						$with_nested
					)
				);
			}
		);
	}
}
