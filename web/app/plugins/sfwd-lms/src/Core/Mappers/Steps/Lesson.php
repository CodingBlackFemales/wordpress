<?php
/**
 * The lesson steps mapper.
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
use LearnDash\Core\Template\Views;
use LearnDash_Custom_Label;

// TODO: Test.

/**
 * The lesson steps mapper.
 *
 * @since 4.6.0
 */
class Lesson extends Mapper {
	/**
	 * The model.
	 *
	 * @since 4.6.0
	 *
	 * @var Models\Lesson
	 */
	protected $model;

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param Models\Lesson $model The model.
	 */
	public function __construct( Models\Lesson $model ) {
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

		// Calculate the number of topics & the last page containing topics.
		$topics_number   = $this->model->get_topics_number(); // TODO: It can be a wrong number due to the same reasons as in other Views.
		$last_topic_page = (int) ceil( $topics_number / $page_size );

		// It's a case where only quizzes are shown.
		if ( $current_page > $last_topic_page ) {
			// Calculate the quiz page based on the current page and the last lesson page.
			$quiz_page = $current_page - $last_topic_page;

			// Calculate the offset caused by topics.
			$offset_caused_by_topics = 0 !== $topics_number % $page_size
				? $page_size - ( $topics_number % $page_size )
				: 0;

			$quizzes = $this->model->get_quizzes(
				$page_size,
				( $quiz_page - 1 ) * $page_size + $offset_caused_by_topics
			);

			return Mappers\Steps\Quiz::convert_models_into_steps( $quizzes );
		}

		// Fetch topics for the current page.
		$topics = $this->model->get_topics( $page_size, ( $current_page - 1 ) * $page_size );

		// Calculate the remaining quizzes to fill the current page (if any).
		$remaining_quizzes_number = $page_size - count( $topics );

		// If there are remaining quizzes, fetch quizzes to fill the page.
		$quizzes = $remaining_quizzes_number > 0 ? $this->model->get_quizzes( $remaining_quizzes_number ) : [];

		$steps = Mappers\Steps\Topic::convert_models_into_steps( $topics );
		$steps = $steps->merge( Mappers\Steps\Quiz::convert_models_into_steps( $quizzes ) );

		if ( $this->with_sub_steps ) {
			$steps = $this->add_sub_steps( $steps, $topics );
		}

		return $steps;
	}

	/**
	 * Gets all steps.
	 *
	 * @since 4.6.0
	 *
	 * @return Steps\Steps
	 */
	public function all(): Steps\Steps {
		$topics = $this->model->get_topics();

		$steps = Mappers\Steps\Topic::convert_models_into_steps( $topics );
		$steps = $steps->merge( Mappers\Steps\Quiz::convert_models_into_steps( $this->model->get_quizzes() ) );

		if ( $this->with_sub_steps ) {
			$steps = $this->add_sub_steps( $steps, $topics );
		}

		return $steps;
	}

	/**
	 * Returns the total number of direct steps.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function total(): int {
		return $this->model->get_topics_number() + $this->model->get_quizzes_number();
	}

	/**
	 * Gets steps for the given topics.
	 *
	 * @param Steps\Steps    $steps  Steps.
	 * @param Models\Topic[] $topics Topics.
	 *
	 * @return Steps\Steps
	 */
	protected function add_sub_steps( Steps\Steps $steps, array $topics ): Steps\Steps {
		if ( empty( $topics ) ) {
			return $steps;
		}

		foreach ( $topics as $topic ) {
			$steps = $steps->merge( $this->get_sub_steps( $topic, 1 ) );
		}

		return $steps;
	}

	/**
	 * Returns the contents.
	 *
	 * @since 4.6.0
	 *
	 * @return array{ label: string, icon: string }[]
	 */
	protected function get_contents(): array {
		if ( 0 === $this->total() ) {
			return [];
		}

		$contents = [];

		$topic_count = $this->model->get_topics_number();

		if ( $topic_count > 0 ) {
			$label = sprintf(
				// translators: placeholder: Number of topics, Topic|Topics label.
				esc_html_x( '%1$d %2$s', 'placeholder: Number of topics, Topic|Topics label', 'learndash' ),
				$topic_count,
				esc_html(
					_n(
						LearnDash_Custom_Label::get_label( 'topic' ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle -- Translation is handled by LearnDash_Custom_Label::get_label().
						LearnDash_Custom_Label::get_label( 'topics' ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralPlural -- Translation is handled by LearnDash_Custom_Label::get_label().
						$topic_count,
						'learndash'
					)
				)
			);

			$contents[] = [
				'label' => $label,
				'icon'  => 'topic',
			];
		}

		$quiz_count = $this->model->get_quizzes_number();

		if ( $quiz_count > 0 ) {
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
		$step_parent_id = $this->model->get_course() ? $this->model->get_course()->get_id() : 0;

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
		$step->set_type_label( LearnDash_Custom_Label::get_label( 'lesson' ) );
		$step->set_sub_steps_page_size( $this->sub_steps_page_size > 0 ? $this->sub_steps_page_size : $this->total() );

		return $step;
	}

	/**
	 * Gets sub steps for the given step.
	 *
	 * @since 4.6.0
	 *
	 * @param int|Models\Topic $step The step ID or the model.
	 * @param int              $page The current page.
	 *
	 * @return Steps\Steps
	 */
	public function get_sub_steps( $step, int $page ): Steps\Steps {
		$model = is_int( $step ) ? Models\Topic::find( $step ) : $step;

		if ( false === $model instanceof Models\Topic ) {
			return new Steps\Steps();
		}

		$steps_mapper = new Mappers\Steps\Topic( $model );

		return $steps_mapper->paginated( $page, $this->sub_steps_page_size );
	}
}
