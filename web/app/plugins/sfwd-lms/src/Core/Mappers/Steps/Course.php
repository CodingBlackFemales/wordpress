<?php
/**
 * The course steps mapper.
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
 * The course steps mapper.
 *
 * @since 4.6.0
 */
class Course extends Mapper {
	/**
	 * The model.
	 *
	 * @since 4.6.0
	 *
	 * @var Models\Course
	 */
	protected $model;

	/**
	 * The flag indicating whether to include sections. Defaults to false.
	 *
	 * @since 4.6.0
	 *
	 * @var bool
	 */
	protected $with_sections = false;

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param Models\Course $model The model.
	 */
	public function __construct( Models\Course $model ) {
		parent::__construct( $model );

		$this->set_sub_steps_page_size(
			learndash_get_course_topics_per_page( $this->model->get_id() )
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

		// Calculate the number of lessons & the last page containing lessons.
		$lessons_number   = $this->model->get_lessons_number(); // TODO: Here $lessons_number will contain the wrong number if some of the lessons are not published (they will be filtered later in ::get_lessons(). So we need to decide how we want to handle this case.
		$last_lesson_page = (int) ceil( $lessons_number / $page_size );

		if ( $current_page > $last_lesson_page ) {
			// Calculate the quiz page based on the current page and the last lesson page.
			$quiz_page = $current_page - $last_lesson_page;

			// Calculate the offset caused by lessons.
			$offset_caused_by_lessons = 0 !== $lessons_number % $page_size
				? $page_size - ( $lessons_number % $page_size )
				: 0;

			$quizzes = $this->model->get_quizzes(
				$page_size,
				( $quiz_page - 1 ) * $page_size + $offset_caused_by_lessons
			);

			return Mappers\Steps\Quiz::convert_models_into_steps( $quizzes );
		}

		// Fetch lessons for the current page.
		$lessons = $this->model->get_lessons( $page_size, ( $current_page - 1 ) * $page_size );

		// Calculate the remaining quizzes to fill the current page (if any).
		$remaining_quizzes_number = $page_size - count( $lessons );

		// If there are remaining quizzes, fetch quizzes to fill the page.
		$quizzes = $remaining_quizzes_number > 0 ? $this->model->get_quizzes( $remaining_quizzes_number ) : [];

		$steps = Mappers\Steps\Lesson::convert_models_into_steps( $lessons );
		$steps = $steps->merge( Mappers\Steps\Quiz::convert_models_into_steps( $quizzes ) );

		if ( $this->with_sub_steps ) {
			$steps = $this->add_sub_steps( $steps, $lessons );
		}

		if ( $this->with_sections ) {
			$steps = $this->add_sections( $steps );
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
		$lessons = $this->model->get_lessons();

		$steps = Mappers\Steps\Lesson::convert_models_into_steps( $lessons );
		$steps = $steps->merge( Mappers\Steps\Quiz::convert_models_into_steps( $this->model->get_quizzes() ) );

		if ( $this->with_sub_steps ) {
			$steps = $this->add_sub_steps( $steps, $lessons );
		}

		if ( $this->with_sections ) {
			$steps = $this->add_sections( $steps );
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
		return $this->model->get_lessons_number() + $this->model->get_quizzes_number();
	}

	/**
	 * If called, the mapper will include sections in the steps.
	 *
	 * @since 4.6.0
	 *
	 * @return self
	 */
	public function with_sections(): self {
		$this->with_sections = true;

		return $this;
	}

	/**
	 * If called, the mapper will not include sections in the steps.
	 *
	 * @since 4.6.0
	 *
	 * @return self
	 */
	public function without_sections(): self {
		$this->with_sections = false;

		return $this;
	}

	/**
	 * Gets steps for the given lessons.
	 *
	 * @param Steps\Steps     $steps   Steps.
	 * @param Models\Lesson[] $lessons The lessons.
	 *
	 * @return Steps\Steps
	 */
	protected function add_sub_steps( Steps\Steps $steps, array $lessons ): Steps\Steps {
		if ( empty( $lessons ) ) {
			return $steps;
		}

		foreach ( $lessons as $lesson ) {
			$steps = $steps->merge( $this->get_sub_steps( $lesson, 1 ) );
		}

		return $steps;
	}

	/**
	 * Gets sub steps for the given step.
	 *
	 * @since 4.6.0
	 *
	 * @param int|Models\Lesson $step The step ID or the model.
	 * @param int               $page The current page.
	 *
	 * @return Steps\Steps
	 */
	public function get_sub_steps( $step, int $page ): Steps\Steps {
		$model = is_int( $step ) ? Models\Lesson::find( $step ) : $step;

		if ( false === $model instanceof Models\Lesson ) {
			return new Steps\Steps();
		}

		$steps_mapper = new Mappers\Steps\Lesson( $model );

		return $steps_mapper->paginated( $page, $this->sub_steps_page_size );
	}

	/**
	 * Inserts sections into steps.
	 *
	 * @since 4.6.0
	 *
	 * @param Steps\Steps $steps Steps.
	 *
	 * @return Steps\Steps
	 */
	protected function add_sections( Steps\Steps $steps ): Steps\Steps {
		/**
		 * Sections.
		 *
		 * @var object[] $sections Sections.
		 */
		$sections = learndash_course_get_sections( $this->model->get_id() );

		if ( empty( $sections ) ) {
			return $steps;
		}

		// Map sections.

		$mapped_sections = [];

		foreach ( $sections as $section ) {
			$section = (array) $section;

			/**
			 * Section.
			 *
			 * @var array{ ID: int, post_title: string, steps: array<int, int> } $section Section.
			 */
			if ( empty( $section['steps'] ) ) {
				// We don't include sections without steps.
				continue;
			}

			$step_id = $section['steps'][0];

			$section_step = new Steps\Step( $section['ID'], $section['post_title'] );
			$section_step->set_is_section( true );

			$mapped_sections[ $step_id ] = $section_step;
		}

		if ( empty( $mapped_sections ) ) {
			return $steps;
		}

		// Insert sections into steps.

		$steps_with_sections = [];

		foreach ( $steps->all() as $step ) {
			if ( array_key_exists( $step->get_id(), $mapped_sections ) ) {
				$steps_with_sections[] = $mapped_sections[ $step->get_id() ];
			}

			$steps_with_sections[] = $step;
		}

		return new Steps\Steps( $steps_with_sections );
	}

	/**
	 * Maps the current model to a step.
	 *
	 * @since 4.6.0
	 *
	 * @return Steps\Step
	 */
	protected function to_step(): Steps\Step {
		$step = new Steps\Step( $this->model->get_id(), $this->model->get_title(), $this->model->get_permalink() );

		$step->set_contents( $this->get_contents() );
		$step->set_progress( $this->model->get_progress_percentage( wp_get_current_user() ) );

		$step->set_icon( 'course' );
		$step->set_type_label( LearnDash_Custom_Label::get_label( 'course' ) );
		$step->set_sub_steps_page_size( $this->sub_steps_page_size > 0 ? $this->sub_steps_page_size : $this->total() );

		return $step;
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

		$lesson_count = $this->model->get_lessons_number();

		if ( $lesson_count > 0 ) {
			$label = sprintf(
				// translators: placeholder: Number of lessons, Lesson|Lessons label.
				esc_html_x( '%1$d %2$s', 'placeholder: Number of lessons, Lesson|Lessons label', 'learndash' ),
				$lesson_count,
				esc_html(
					_n(
						LearnDash_Custom_Label::get_label( 'lesson' ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle -- Translation is handled by LearnDash_Custom_Label::get_label().
						LearnDash_Custom_Label::get_label( 'lessons' ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralPlural -- Translation is handled by LearnDash_Custom_Label::get_label().
						$lesson_count,
						'learndash'
					)
				)
			);

			$contents[] = [
				'label' => $label,
				'icon'  => 'lesson',
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
}
