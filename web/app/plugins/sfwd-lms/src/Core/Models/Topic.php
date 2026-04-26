<?php
/**
 * This class provides the easy way to operate a topic.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models;

use LDLMS_Post_Types;
use LearnDash\Core\Utilities\Cast;
use Learndash_Course_Video;

/**
 * Topic model class.
 *
 * @since 4.6.0
 */
class Topic extends Step {
	use Traits\Has_Quizzes;
	use Traits\Has_Materials;
	use Traits\Has_Assignments;
	use Traits\Has_Steps;
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
	 * Returns the related lesson of the topic or null if the topic is not associated with a lesson.
	 *
	 * @since 4.24.0
	 *
	 * @return Lesson|null
	 */
	public function get_lesson(): ?Lesson {
		$cached_lesson = $this->getAttribute( LDLMS_Post_Types::LESSON, false );

		if (
			$cached_lesson instanceof Lesson
			|| is_null( $cached_lesson )
		) {
			return $cached_lesson;
		}

		$lesson = Lesson::find(
			Cast::to_int(
				learndash_get_lesson_id( $this->get_id() )
			)
		);

		/**
		 * Filters a topic's lesson.
		 *
		 * @since 4.24.0
		 *
		 * @param Lesson|null $lesson Lesson model.
		 * @param Topic       $topic  Topic model.
		 *
		 * @return Lesson|null Lesson model or null if not found.
		 */
		$lesson = apply_filters(
			'learndash_model_topic_lesson',
			$lesson,
			$this
		);

		$this->set_lesson( $lesson );

		return $lesson;
	}

	/**
	 * Sets the related lesson of the topic.
	 *
	 * @since 4.24.0
	 *
	 * @param Lesson|null $lesson Lesson model or null.
	 *
	 * @return void
	 */
	public function set_lesson( ?Lesson $lesson ): void {
		$this->setAttribute( LDLMS_Post_Types::LESSON, $lesson );
	}

	/**
	 * Returns the topic content.
	 *
	 * @since 4.24.0
	 *
	 * @param bool $raw Whether to return raw content or not. Default is false.
	 *
	 * @return string
	 */
	public function get_content( bool $raw = false ): string {
		$content = parent::get_content( false );

		// Add the topic video logic.

		if (
			defined( 'LEARNDASH_LESSON_VIDEO' )
			&& true === LEARNDASH_LESSON_VIDEO
		) {
			$content = Learndash_Course_Video::get_instance()->add_video_to_content(
				$content,
				$this->get_post(),
				$this->get_settings()
			);
		}

		if ( ! $raw ) {
			return apply_filters( 'the_content', $content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- It's a WordPress core filter.
		}

		return $content;
	}
}
