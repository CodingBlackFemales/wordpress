<?php
/**
 * The topic steps mapper.
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
use LearnDash_Custom_Label;

// TODO: Test.

/**
 * The topic steps mapper.
 *
 * @since 4.6.0
 */
class Topic extends Mapper {

	/**
	 * The model.
	 *
	 * @since 4.6.0
	 *
	 * @var Models\Topic
	 */
	protected $model;

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param Models\Topic $model The model.
	 */
	public function __construct( Models\Topic $model ) {
		parent::__construct( $model );

		$course = $this->model->get_course();

		$this->set_sub_steps_page_size(
			learndash_get_course_topics_per_page( $course ? $course->get_id() : 0 )
		);
	}

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

		return Mappers\Steps\Quiz::convert_models_into_steps(
			$this->model->get_quizzes( $page_size, ( $current_page - 1 ) * $page_size )
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
		return Mappers\Steps\Quiz::convert_models_into_steps( $this->model->get_quizzes() );
	}

	/**
	 * Returns the total number of direct steps.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function total(): int {
		return $this->model->get_quizzes_number();
	}

	/**
	 * Returns the contents.
	 *
	 * @since 4.6.0
	 *
	 * @return array{ label: string, icon: string }[]
	 */
	protected function get_contents(): array {
		$contents = [];

		$quiz_count = $this->model->get_quizzes_number();

		if ( $quiz_count > 0 ) {
			// TODO: This code is repeating in all mappers, we need to move it to the separate mapper or something like this.
			$label = sprintf(
				// translators: placeholder: Number of quizzes, Quiz|Quizzes label.
				esc_html_x( '%1$d %2$s', 'placeholder: Number of quizzes, Quiz|Quizzes label', 'learndash' ),
				$quiz_count,
				esc_html(
					_n(
						LearnDash_Custom_Label::get_label( 'quiz' ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle -- Translation is handled by LearnDash_Custom_Label::get_label().
						LearnDash_Custom_Label::get_label( 'quizzes' ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralPlural -- Translation is handled by LearnDash_Custom_Label::get_label().
						$quiz_count,
						'learndash'
					)
				)
			);

			$contents[] = [
				'label' => $label,
				'icon'  => 'quiz',
			];
		}

		return $contents;
	}

	/**
	 * Maps the current model to a step.
	 *
	 * @since 4.6.0
	 *
	 * @return Steps\Step
	 */
	protected function to_step(): Steps\Step {
		$step_parent_id = (int) learndash_get_lesson_id( $this->model->get_id() );

		$step = new Steps\Step(
			$this->model->get_id(),
			$this->model->get_title(),
			$this->model->get_permalink(),
			$step_parent_id
		);

		$step->set_contents( $this->get_contents() );
		$step->set_steps_number( $this->total() );
		$step->set_progress( $this->model->get_progress_percentage( wp_get_current_user() ) );

		$step->set_icon( 'lesson' );
		$step->set_type_label( LearnDash_Custom_Label::get_label( 'topic' ) );
		$step->set_sub_steps_page_size( $this->sub_steps_page_size > 0 ? $this->sub_steps_page_size : $this->total() );

		return $step;
	}

	/**
	 * Gets sub steps for the given step ID.
	 *
	 * @since 4.6.0
	 *
	 * @param int|Models\Course $step The step ID or the model.
	 * @param int               $page The current page.
	 *
	 * @return Steps\Steps
	 */
	public function get_sub_steps( $step, int $page ): Steps\Steps {
		$course = is_int( $step ) ? Models\Course::find( $step ) : $step;

		if ( false === $course instanceof Models\Course ) {
			return new Steps\Steps();
		}

		$steps_mapper = new Mappers\Steps\Course( $course );

		return $steps_mapper->paginated( $page, $this->sub_steps_page_size );
	}
}
