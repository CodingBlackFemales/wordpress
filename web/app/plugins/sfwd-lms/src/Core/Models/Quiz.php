<?php
/**
 * This class provides the easy way to operate a quiz.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models;

use LDLMS_Post_Types;

/**
 * Quiz model class.
 *
 * @since 4.6.0
 */
class Quiz extends Step {
	use Traits\Has_Materials;

	/**
	 * Returns allowed post types.
	 *
	 * @since 4.6.0
	 *
	 * @return string[]
	 */
	public static function get_allowed_post_types(): array {
		return [
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ),
		];
	}
}
