<?php
/**
 * Trait for models that can retrieve a number of child topics.
 *
 * @since 4.21.0
 * @deprecated 4.24.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models\Traits;

use LDLMS_Post_Types;

// TODO: Deprecate properly.

/**
 * Trait for models that can retrieve a number of child topics.
 *
 * @since 4.21.0
 * @deprecated 4.24.0
 */
trait Has_Topics_Number {
	use Has_Steps;

	/**
	 * Returns the total number of topics associated with this model, including those nested multiple levels deep.
	 *
	 * @since 4.21.0
	 * @deprecated 4.24.0
	 *
	 * @return int
	 */
	public function get_topics_number(): int {
		// TODO: Deprecate properly.
		/** This filter is documented in src/Core/Models/Traits/Has_Topics.php */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_topics_number",
			$this->get_steps_number(
				LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC )
			),
			$this
		);
	}
}
