<?php
/**
 * LearnDash progress bar class.
 *
 * @since 4.24.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Progression;

use LearnDash\Core\Models\DTO;

/**
 * A class to handle the progress bar.
 *
 * @since 4.24.0
 */
class Bar {
	/**
	 * The total number of steps.
	 *
	 * @since 4.24.0
	 *
	 * @var int
	 */
	protected int $total_step_count = 0;

	/**
	 * The number of completed steps.
	 *
	 * @since 4.24.0
	 *
	 * @var int
	 */
	protected int $completed_step_count = 0;

	/**
	 * The last activity DTO.
	 *
	 * @since 4.24.0
	 *
	 * @var DTO\Last_Activity|null
	 */
	protected ?DTO\Last_Activity $last_activity = null;

	/**
	 * Whether the context is complete. Default null.
	 * If null, the progress bar completion will be calculated based on the total and completed steps.
	 *
	 * @since 4.24.0
	 *
	 * @var bool
	 */
	protected ?bool $is_complete = null;

	/**
	 * The label used for the progress bar.
	 *
	 * @since 4.24.0
	 *
	 * @var string
	 */
	protected string $label = '';

	/**
	 * Returns whether the progress bar should be shown.
	 *
	 * @since 4.24.0
	 *
	 * @return bool
	 */
	public function should_show(): bool {
		/**
		 * Filters whether the progress bar should be shown.
		 *
		 * @since 4.24.0
		 *
		 * @param bool $should_show  Whether the progress bar should be shown.
		 * @param Bar  $progress_bar The progress bar instance.
		 *
		 * @return bool
		 */
		return apply_filters(
			'learndash_progression_bar_should_show',
			$this->get_total_step_count() > 0 || $this->is_complete(),
			$this
		);
	}

	/**
	 * Returns the total number of steps.
	 *
	 * @since 4.24.0
	 *
	 * @return int
	 */
	public function get_total_step_count(): int {
		return $this->total_step_count;
	}

	/**
	 * Sets the total number of steps.
	 *
	 * @since 4.24.0
	 *
	 * @param int $total_step_count The total number of steps.
	 *
	 * @return self
	 */
	public function set_total_step_count( int $total_step_count ): self {
		$this->total_step_count = $total_step_count;

		return $this;
	}

	/**
	 * Returns the number of completed steps.
	 *
	 * @since 4.24.0
	 *
	 * @return int
	 */
	public function get_completed_step_count(): int {
		return $this->completed_step_count;
	}

	/**
	 * Sets the number of completed steps.
	 *
	 * @since 4.24.0
	 *
	 * @param int $completed_step_count The number of completed steps.
	 *
	 * @return self
	 */
	public function set_completed_step_count( int $completed_step_count ): self {
		$this->completed_step_count = $completed_step_count;

		return $this;
	}

	/**
	 * Returns the completion percentage.
	 *
	 * @since 4.24.0
	 *
	 * @return float
	 */
	public function get_completion_percentage(): float {
		if ( $this->get_total_step_count() === 0 ) {
			if ( $this->is_complete() ) {
				return 100;
			}

			return 0;
		}

		return round(
			( $this->get_completed_step_count() / $this->get_total_step_count() ) * 100,
			2
		);
	}

	/**
	 * Returns the last activity for the model.
	 *
	 * @since 4.24.0
	 *
	 * @return ?DTO\Last_Activity Last activity DTO. Null if no activity found.
	 */
	public function get_last_activity(): ?DTO\Last_Activity {
		return $this->last_activity;
	}

	/**
	 * Sets the last activity for the model.
	 *
	 * @since 4.24.0
	 *
	 * @param ?DTO\Last_Activity $last_activity The last activity DTO.
	 *
	 * @return self
	 */
	public function set_last_activity( ?DTO\Last_Activity $last_activity ): self {
		$this->last_activity = $last_activity;

		return $this;
	}

	/**
	 * Returns the label used for the progress bar.
	 *
	 * @since 4.24.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Sets the label used for the progress bar.
	 *
	 * @since 4.24.0
	 *
	 * @param string $label The label.
	 *
	 * @return self
	 */
	public function set_label( string $label ): self {
		$this->label = $label;

		return $this;
	}

	/**
	 * Returns whether the progress bar should be considered complete.
	 *
	 * @since 4.24.0
	 *
	 * @return bool
	 */
	public function is_complete(): bool {
		if ( is_bool( $this->is_complete ) ) {
			$is_complete = $this->is_complete;
		} else {
			$is_complete = $this->get_total_step_count() > 0
				&& $this->get_completed_step_count() === $this->get_total_step_count();
		}

		/**
		 * Filters whether the progress bar should be considered complete.
		 *
		 * @since 4.24.0
		 *
		 * @param bool $is_complete  Whether the progress bar should be considered complete.
		 * @param Bar  $progress_bar The progress bar instance.
		 *
		 * @return bool
		 */
		return apply_filters(
			'learndash_progression_bar_is_complete',
			$is_complete,
			$this
		);
	}

	/**
	 * Sets whether the progress bar should be considered complete.
	 * This is only needed if you need to force the progress bar to be considered complete.
	 * Set to null to restore the default calculation.
	 *
	 * @since 4.24.0
	 *
	 * @param ?bool $is_complete Whether the progress bar should be considered complete. Set to null to restore the default calculation.
	 *
	 * @return self
	 */
	public function set_complete( ?bool $is_complete ): self {
		$this->is_complete = $is_complete;

		return $this;
	}
}
