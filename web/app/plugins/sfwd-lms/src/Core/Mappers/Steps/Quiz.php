<?php
/**
 * The quiz steps mapper.
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

use LearnDash\Core\Models;
use LearnDash\Core\Template\Steps;
use LearnDash_Custom_Label;

// TODO: Test.

/**
 * The topic steps mapper.
 *
 * @since 4.6.0
 */
class Quiz extends Mapper {
	/**
	 * The model.
	 *
	 * @since 4.6.0
	 *
	 * @var Models\Quiz
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

		// TODO: Return the questions here in the future.
		return new Steps\Steps();
	}

	/**
	 * Gets all steps.
	 *
	 * @since 4.6.0
	 *
	 * @return Steps\Steps
	 */
	public function all(): Steps\Steps {
		// TODO: Return the questions here in the future.
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
		// TODO: Return the questions number here in the future.
		return 0;
	}

	/**
	 * Maps the current model to a step.
	 *
	 * @since 4.6.0
	 *
	 * @return Steps\Step
	 */
	protected function to_step(): Steps\Step {
		$course         = $this->model->get_course();
		$step_parent_id = $course ? learndash_course_get_single_parent_step( $course->get_id(), $this->model->get_id() ) : 0;

		$step = new Steps\Step(
			$this->model->get_id(),
			$this->model->get_title(),
			$this->model->get_permalink(),
			$step_parent_id
		);

		$step->set_progress( $this->model->get_progress_percentage( wp_get_current_user() ) );

		$step->set_icon( 'quiz' );
		$step->set_type_label( LearnDash_Custom_Label::get_label( 'quiz' ) );

		return $step;
	}

	/**
	 * Gets sub steps for the given step.
	 *
	 * TODO: Return the questions here in the future.
	 *
	 * @since 4.6.0
	 *
	 * @param int|Models\Model $step The step ID or the model.
	 * @param int              $page The current page.
	 *
	 * @return Steps\Steps
	 */
	public function get_sub_steps( $step, int $page ): Steps\Steps {
		return new Steps\Steps();
	}
}
