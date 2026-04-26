<?php
/**
 * Trait for models that can retrieve a number of child topics.
 *
 * @since 4.24.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models\Traits;

use LDLMS_Post_Types;
use LearnDash\Core\Models\Course;
use LearnDash\Core\Models\Step;
use LearnDash\Core\Models\Topic;

/**
 * Trait for models that can retrieve a number of child topics.
 *
 * @since 4.24.0
 */
trait Has_Topics {
	use Has_Steps;

	/**
	 * Returns related topics.
	 *
	 * @since 4.24.0
	 *
	 * @param int  $limit       Optional. Limit. Default 0.
	 * @param int  $offset      Optional. Offset. Default 0.
	 * @param bool $with_nested Optional. Whether to include nested topics. Default true.
	 *
	 * @return Topic[]
	 */
	public function get_topics( int $limit = 0, int $offset = 0, bool $with_nested = true ): array {
		/**
		 * Topics
		 *
		 * @var Topic[] $topics
		 */
		$topics = $this->get_steps(
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC ),
			$limit,
			$offset,
			$with_nested
		);

		/**
		 * Filters course topics
		 *
		 * @since 4.24.0
		 *
		 * @param Topic[]     $topics Topics.
		 * @param int         $limit  Limit. Default 0.
		 * @param int         $offset Offset. Default 0.
		 * @param Course|Step $model  Model with topics.
		 *
		 * @return Topic[] Topics.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_topics",
			$topics,
			$limit,
			$offset,
			$this
		);
	}

	/**
	 * Returns the total number of topics associated with this model, including those nested multiple levels deep.
	 *
	 * @since 4.21.0
	 * @deprecated 4.24.0
	 *
	 * @return int
	 */
	public function get_topics_number(): int {
		/**
		 * Filters topics number associated with this model.
		 *
		 * @since 4.21.0
		 *
		 * @param int         $number Number of topics.
		 * @param Course|Step $model  Model with topics.
		 *
		 * @return int Number of nested topics.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_topics_number",
			$this->get_steps_number(
				LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC )
			),
			$this
		);
	}
}
