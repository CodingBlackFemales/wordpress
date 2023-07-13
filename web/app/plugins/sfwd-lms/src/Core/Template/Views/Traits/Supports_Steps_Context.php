<?php
/**
 * Trait for objects that supports steps context.
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

namespace LearnDash\Core\Template\Views\Traits;

use LearnDash\Core\Models;
use LearnDash\Core\Models\Interfaces;

// TODO: Add hooks later when everything is ready.

/**
 * Trait for objects that supports steps context.
 *
 * @since 4.6.0
 */
trait Supports_Steps_Context {
	/**
	 * Max depth. Default 2.
	 *
	 * @since 4.6.0
	 *
	 * @var int
	 */
	protected $steps_walker_max_depth = 2;

	/**
	 * Builds the context for the steps.
	 *
	 * @since 4.6.0
	 *
	 * @param Models\Post $model The model.
	 *
	 * @return array<string, mixed>
	 */
	protected static function build_steps_context( Models\Post $model ): array {
		$is_enrolled = false;

		if ( $model instanceof Interfaces\Product ) {
			$is_enrolled = $model->get_product()->user_has_access( wp_get_current_user() );
		} elseif ( $model instanceof Interfaces\Course_Step ) { // @phpstan-ignore-line -- it can be used in other classes.
			$course = $model->get_course();
			if ( $course ) {
				$is_enrolled = $course->get_product()->user_has_access( wp_get_current_user() );
			}
		}

		return [
			'is_enrolled' => $is_enrolled,
		];
	}
}
