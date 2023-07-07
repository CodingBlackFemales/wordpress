<?php
/**
 * This class provides the easy way to operate a topic.
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
use LearnDash\Core\Models\Traits\Has_Lesson;
use LearnDash\Core\Models\Traits\Has_Quizzes;
use LearnDash\Core\Models\Traits\Supports_Timer;
use WP_User;

/**
 * Topic model class.
 *
 * @since 4.6.0
 */
class Topic extends Post implements Interfaces\Course_Step {
	use Has_Course {
		get_course as get_course_from_trait;
	}
	use Has_Lesson {
		get_lesson as get_lesson_from_trait;
	}
	use Has_Quizzes {
		get_quizzes as get_quizzes_from_trait;
		get_quizzes_number as get_quizzes_number_from_trait;
	}
	use Supports_Timer {
		get_time_limit_in_seconds as get_time_limit_in_seconds_from_trait;
		get_time_limit_formatted as get_time_limit_formatted_from_trait;
	}
	use Traits\Has_Materials;
	use Traits\Supports_Video_Progression;

	/**
	 * Returns allowed post types.
	 *
	 * @since 4.6.0
	 *
	 * @return string[]
	 */
	public static function get_allowed_post_types(): array {
		return array(
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC ),
		);
	}

	/**
	 * Returns a course of the topic or null if the topic is not associated with a course.
	 *
	 * @since 4.6.0
	 *
	 * @return Course|null
	 */
	public function get_course(): ?Course {
		/**
		 * Filters a topic course.
		 *
		 * @since 4.6.0
		 *
		 * @param Course|null $course Course model.
		 * @param Topic       $topic  Topic model.
		 *
		 * @return Course|null Topic course model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_topic_course',
			$this->get_course_from_trait(),
			$this
		);
	}

	/**
	 * Returns a lesson of the topic or null if the topic is not associated with a lesson.
	 *
	 * @since 4.6.0
	 *
	 * @return Lesson|null
	 */
	public function get_lesson(): ?Lesson {
		/**
		 * Filters a topic lesson.
		 *
		 * @since 4.6.0
		 *
		 * @param Lesson|null $lesson Lesson model.
		 * @param Topic       $topic  Topic model.
		 *
		 * @return Lesson|null Topic lesson model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_topic_lesson',
			$this->get_lesson_from_trait(),
			$this
		);
	}

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
		/**
		 * Filters topic quizzes.
		 *
		 * @since 4.6.0
		 *
		 * @param Quiz[] $quizzes Quizzes.
		 * @param Topic  $topic   Topic model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_topic_quizzes',
			$this->get_quizzes_from_trait( $limit, $offset ),
			$this
		);
	}

	/**
	 * Returns the total number of related quizzes.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function get_quizzes_number(): int {
		/**
		 * Filters topic quizzes number.
		 *
		 * @since 4.6.0
		 *
		 * @param int   $number Number of quizzes.
		 * @param Topic $topic  Topic model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_topic_quizzes_number',
			$this->get_quizzes_number_from_trait(),
			$this
		);
	}

	/**
	 * Gets the topic time limit in seconds.
	 *
	 * @since 4.6.0
	 *
	 * @return int The number of seconds.
	 */
	public function get_time_limit_in_seconds(): int {
		/**
		 * Filters the topic time limit in seconds.
		 *
		 * @since 4.6.0
		 *
		 * @param int   $time_limit_in_seconds The topic time limit in seconds.
		 * @param Topic $topic                 The topic model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_topic_time_limit',
			$this->get_time_limit_in_seconds_from_trait(),
			$this
		);
	}

	/**
	 * Gets the topic time limit as a H:M:S string.
	 *
	 * @since 4.6.0
	 *
	 * @return string
	 */
	public function get_time_limit_formatted(): string {
		/**
		 * Filters the topic time limit.
		 *
		 * @since 4.6.0
		 *
		 * @param string $time_limit            The topic time limit as a H:M:S string.
		 * @param int    $time_limit_in_seconds The topic time limit in seconds.
		 * @param Topic  $topic                 The topic model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_topic_time_limit_formatted',
			$this->get_time_limit_formatted_from_trait(),
			$this->get_time_limit_in_seconds(),
			$this
		);
	}

	/**
	 * Returns the progress percentage for a user.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_User $user User.
	 *
	 * @return int
	 */
	public function get_progress_percentage( WP_User $user ): int {
		// TODO: Memoize, tests, hook.

		// TODO: implement it.
		return 0;
	}
}
