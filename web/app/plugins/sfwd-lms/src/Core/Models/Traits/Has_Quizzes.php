<?php
/**
 * Trait for models that can have children of type quiz.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models\Traits;

use LDLMS_Post_Types;
use LearnDash\Core\Models\Course;
use LearnDash\Core\Models\Quiz;
use LearnDash\Core\Models\Step;

/**
 * Trait for models that can have children of type quiz.
 *
 * @since 4.6.0
 */
trait Has_Quizzes {
	use Has_Steps;

	/**
	 * Returns quizzes that are a direct child of this model.
	 *
	 * @since 4.21.0
	 *
	 * @param int  $limit       Optional. Limit. Default 0.
	 * @param int  $offset      Optional. Offset. Default 0.
	 * @param bool $with_nested Optional. Whether to include nested quizzes. Default false.
	 *
	 * @return Quiz[]
	 */
	public function get_quizzes( int $limit = 0, int $offset = 0, bool $with_nested = false ): array {
		/**
		 * Quizzes
		 *
		 * @var Quiz[] $quizzes
		 */
		$quizzes = $this->get_steps(
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ),
			$limit,
			$offset,
			$with_nested
		);

		// As a Course can have Final Quizzes, we need to account for this properly.
		if ( ! $this instanceof Course ) {
			$course = $this->get_course();
		} else {
			$course = $this;
		}

		foreach ( $quizzes as $quiz ) {
			$quiz->set_course( $course ); // This is used to optimize subsequent calls to $quiz->get_course().
		}

		/**
		 * Filters direct child quizzes.
		 *
		 * @since 4.21.0
		 *
		 * @param Quiz[]      $quizzes Quizzes.
		 * @param int         $limit   Limit. Default 0.
		 * @param int         $offset  Offset. Default 0.
		 * @param Course|Step $model   Model with quizzes.
		 *
		 * @return Quiz[] Quizzes.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_quizzes",
			$quizzes,
			$limit,
			$offset,
			$this
		);
	}

	/**
	 * Returns the total number of quizzes, including those nested multiple levels deep.
	 *
	 * @since 4.21.0
	 *
	 * @return int
	 */
	public function get_quizzes_number(): int {
		/**
		 * Filters nested quizzes number.
		 *
		 * @since 4.21.0
		 *
		 * @param int         $number Number of quizzes.
		 * @param Course|Step $model  Model with quizzes.
		 *
		 * @return int Number of nested quizzes.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_quizzes_number",
			$this->get_steps_number(
				LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ )
			),
			$this
		);
	}
}
