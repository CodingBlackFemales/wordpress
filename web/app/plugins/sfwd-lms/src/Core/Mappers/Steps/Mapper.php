<?php
/**
 * The steps mapper.
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

// TODO: Test.

/**
 * The steps mapper.
 *
 * @phpstan-consistent-constructor
 *
 * @since 4.6.0
 */
abstract class Mapper {
	/**
	 * The model.
	 *
	 * @since 4.6.0
	 *
	 * @var Models\Post
	 */
	protected $model;

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param Models\Post $model The model.
	 */
	public function __construct( Models\Post $model ) {
		$this->model = $model;
	}

	/**
	 * Maps the steps for the given page.
	 * Currently, 1 level depth is supported.
	 *
	 * @since 4.6.0
	 *
	 * @param int $current_page The current page.
	 * @param int $page_size    The page size.
	 *
	 * @return Steps\Steps
	 */
	abstract public function paginated( int $current_page, int $page_size ): Steps\Steps;

	/**
	 * Maps all the steps.
	 * Currently, 1 level depth is supported.
	 *
	 * @since 4.6.0
	 *
	 * @return Steps\Steps
	 */
	abstract public function all(): Steps\Steps;

	/**
	 * Returns the total number of direct steps.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	abstract public function total(): int;

	/**
	 * Returns the step object for the given model.
	 *
	 * @since 4.6.0
	 *
	 * @return Steps\Step
	 */
	abstract protected function to_step(): Steps\Step;

	/**
	 * Gets sub steps for the given step.
	 *
	 * @since 4.6.0
	 *
	 * @param int|Models\Model $step The step. Can be either a step ID or a model.
	 * @param int              $page The current page.
	 *
	 * @return Steps\Steps
	 */
	abstract public function get_sub_steps( $step, int $page ): Steps\Steps;

	/**
	 * The flag indicating whether to include sub steps. Defaults to false.
	 *
	 * @since 4.6.0
	 *
	 * @var bool
	 */
	protected $with_sub_steps = false;

	/**
	 * The page size for sub steps. Defaults to 0.
	 *
	 * @since 4.6.0
	 *
	 * @var int
	 */
	protected $sub_steps_page_size = 0;

	/**
	 * If called, the mapper will include sub steps in the steps.
	 *
	 * @since 4.6.0
	 *
	 * @return self
	 */
	public function with_sub_steps(): self {
		$this->with_sub_steps = true;

		return $this;
	}

	/**
	 * If called, the mapper will not include sub steps in the steps.
	 *
	 * @since 4.6.0
	 *
	 * @return self
	 */
	public function without_sub_steps(): self {
		$this->with_sub_steps = false;

		return $this;
	}

	/**
	 * Returns the page size for sub steps.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function get_sub_steps_page_size(): int {
		return $this->sub_steps_page_size;
	}

	/**
	 * Sets the page size for sub steps.
	 *
	 * @since 4.6.0
	 *
	 * @param int $page_size The page size.
	 *
	 * @return self
	 */
	public function set_sub_steps_page_size( int $page_size ): self {
		$this->sub_steps_page_size = $page_size;

		return $this;
	}

	/**
	 * Maps an array of models into Steps.
	 * Every mapper has its own implementation of to_step().
	 *
	 * @since 4.6.0
	 *
	 * @param Models\Post[] $models Array of models.
	 *
	 * @return Steps\Steps
	 */
	public static function convert_models_into_steps( array $models ): Steps\Steps {
		$steps = new Steps\Steps();

		foreach ( $models as $model ) {
			$steps_handler = new static( $model );

			$steps->add( $steps_handler->to_step() );
		}

		return $steps;
	}
}
