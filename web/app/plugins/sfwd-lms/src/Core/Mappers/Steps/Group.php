<?php
/**
 * The group steps mapper.
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

namespace LearnDash\Core\Mappers\Steps;

use LearnDash\Core\Mappers;
use LearnDash\Core\Models;
use LearnDash\Core\Template\Steps;

// TODO: Test.

/**
 * The group steps mapper.
 *
 * @since 4.6.0
 */
class Group extends Mapper {
	/**
	 * The model.
	 *
	 * @since 4.6.0
	 *
	 * @var Models\Group
	 */
	protected $model;

	/**
	 * Maps the steps for the given page.
	 *
	 * @param int $current_page The current page.
	 * @param int $page_size    The page size.
	 *
	 * @return Steps\Steps
	 */
	public function paginated( int $current_page, int $page_size ): Steps\Steps {
		if ( $page_size <= 0 ) {
			return $this->all();
		}

		return Mappers\Steps\Course::convert_models_into_steps(
			$this->model->get_courses( $page_size, ( $current_page - 1 ) * $page_size )
		);
	}

	/**
	 * Gets all steps.
	 *
	 * @since 4.6.0
	 *
	 * @return Steps\Steps
	 */
	public function all(): Steps\Steps {
		return Mappers\Steps\Course::convert_models_into_steps( $this->model->get_courses() );
	}

	/**
	 * Gets sub steps for the given step ID.
	 * Always returns an empty steps object.
	 *
	 * @since 4.6.0
	 *
	 * @param int|Models\Model $step The step ID or the model.
	 * @param int              $page The current page.
	 *
	 * @return Steps\Steps
	 */
	public function get_sub_steps( $step, int $page ): Steps\Steps {
		// TODO: If we want to implement it, it requires additional logic, as child models don't know about the course id.
		return new Steps\Steps();
	}

	/**
	 * Returns the total number of direct steps.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function total(): int {
		return $this->model->get_courses_number();
	}

	/**
	 * Maps the current model to a step.
	 *
	 * @since 4.6.0
	 *
	 * @return Steps\Step
	 */
	protected function to_step(): Steps\Step {
		return new Steps\Step( $this->model->get_id(), $this->model->get_title(), $this->model->get_permalink() );
	}
}
