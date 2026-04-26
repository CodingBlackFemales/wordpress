<?php
/**
 * LearnDash step progression class.
 *
 * @since 4.24.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Progression;

use LDLMS_Post_Types;
use LearnDash\Core\Mappers\Models\Step_Mapper;
use LearnDash\Core\Models\Step as Step_Model;

/**
 * A class to handle step progression.
 *
 * @since 4.24.0
 */
class Step {
	/**
	 * The Step model.
	 *
	 * @since 4.24.0
	 *
	 * @var Step_Model
	 */
	protected Step_Model $step;

	/**
	 * The user ID.
	 *
	 * @since 4.24.0
	 *
	 * @var int
	 */
	protected int $user_id;

	/**
	 * The previous step.
	 *
	 * @since 4.24.0
	 *
	 * @var ?Step_Model
	 */
	private ?Step_Model $previous;

	/**
	 * The next step.
	 *
	 * @since 4.24.0
	 *
	 * @var ?Step_Model
	 */
	private ?Step_Model $next;

	/**
	 * Whether the step has just been completed. Default to false.
	 *
	 * @since 4.24.0
	 *
	 * @var bool
	 */
	private bool $is_just_completed = false;

	/**
	 * The URL to which a user should be redirected after the step has just been completed. Default to empty string.
	 *
	 * @since 4.24.0
	 *
	 * @var string
	 */
	private string $url_after_completion = '';

	/**
	 * The next step after the step has just been completed.
	 *
	 * @since 4.24.0
	 *
	 * @var ?Step_Model
	 */
	private ?Step_Model $next_step_after_completion;

	/**
	 * Constructor.
	 *
	 * @param Step_Model $step    The Step model.
	 * @param int        $user_id The user ID.
	 *
	 * @since 4.24.0
	 */
	public function __construct( Step_Model $step, int $user_id ) {
		$this->step    = $step;
		$this->user_id = $user_id;

		$course = $this->step->get_course();

		$this->previous = $step->get_previous();
		$this->next     = $step->get_next();

		if ( $course ) {
			// Grab the step completion transient data, which is used to determine whether the step has just been completed.

			$step_completion_transient_data = learndash_get_step_completed_transient_data(
				$this->step->get_id(),
				$course->get_id(),
				$this->user_id
			);

			if ( ! empty( $step_completion_transient_data ) ) {
				$this->is_just_completed          = true;
				$this->url_after_completion       = $step_completion_transient_data['next_step_url'];
				$this->next_step_after_completion = Step_Mapper::create( $step_completion_transient_data['next_step_id'] );
			}
		}
	}

	/**
	 * Returns whether the step has just been completed.
	 *
	 * @since 4.24.0
	 *
	 * @return bool
	 */
	public function is_just_completed(): bool {
		/**
		 * Filters whether the step has just been completed.
		 *
		 * @since 4.24.0
		 *
		 * @param bool       $is_just_completed Whether the step has just been completed.
		 * @param Step_Model $step              The Step model.
		 * @param int        $user_id           The user ID.
		 *
		 * @return bool
		 */
		return apply_filters(
			'learndash_template_progression_step_is_just_completed',
			$this->is_just_completed,
			$this->step,
			$this->user_id
		);
	}

	/**
	 * Returns whether the course is completed.
	 *
	 * @since 4.24.0
	 *
	 * @return bool
	 */
	public function is_course_completed(): bool {
		$is_course_completed = false;

		$course = $this->step->get_course();

		if ( $course ) {
			$is_course_completed = $course->is_complete( $this->user_id );
		}

		/**
		 * Filters whether the course is completed.
		 *
		 * @since 4.24.0
		 *
		 * @param bool       $is_course_completed Whether the course is completed.
		 * @param Step_Model $step                The Step model.
		 * @param int        $user_id             The user ID.
		 *
		 * @return bool
		 */
		return apply_filters(
			'learndash_template_progression_step_is_course_completed',
			$is_course_completed,
			$this->step,
			$this->user_id
		);
	}

	/**
	 * Returns the previous incomplete step.
	 *
	 * @since 4.24.0
	 *
	 * @return ?Step_Model The previous incomplete step or null if there is no previous incomplete step.
	 */
	public function get_previous_incomplete_step(): ?Step_Model {
		$previous_incomplete_step = null;

		$course = $this->step->get_course();

		if ( $course ) {
			$previous_incomplete_step_id = learndash_user_progress_get_previous_incomplete_step(
				$this->user_id,
				$course->get_id(),
				$this->step->get_id()
			);

			$previous_incomplete_step = $previous_incomplete_step_id
				? Step_Mapper::create( $previous_incomplete_step_id )
				: null;
		}

		/**
		 * Filters the previous incomplete step.
		 *
		 * @since 4.24.0
		 *
		 * @param ?Step_Model $previous_incomplete_step The previous incomplete step.
		 * @param Step_Model  $step                     The Step model.
		 * @param int         $user_id                  The user ID.
		 *
		 * @return ?Step_Model
		 */
		return apply_filters(
			'learndash_template_progression_step_previous_incomplete_step',
			$previous_incomplete_step,
			$this->step,
			$this->user_id
		);
	}

	/**
	 * Returns the label for the next button.
	 *
	 * @since 4.24.0
	 *
	 * @return string
	 */
	public function get_next_label(): string {
		/**
		 * Filters the label for the next button.
		 *
		 * @since 4.24.0
		 *
		 * @param string     $label   The label for the next button.
		 * @param Step_Model $step    The Step model.
		 * @param int        $user_id The user ID.
		 *
		 * @return string
		 */
		return apply_filters(
			'learndash_template_progression_step_next_label',
			$this->get_next_step_button_label(),
			$this->step,
			$this->user_id
		);
	}

	/**
	 * Returns the short label for the next button.
	 *
	 * @since 4.24.0
	 *
	 * @return string
	 */
	public function get_next_short_label(): string {
		/**
		 * Filters the short label for the next button.
		 *
		 * @since 4.24.0
		 *
		 * @param string     $label   The short label for the next button.
		 * @param Step_Model $step    The Step model.
		 * @param int        $user_id The user ID.
		 *
		 * @return string
		 */
		return apply_filters(
			'learndash_template_progression_step_next_short_label',
			$this->get_next_step_button_label( true ),
			$this->step,
			$this->user_id
		);
	}

	/**
	 * Returns the URL for the next button.
	 *
	 * @since 4.24.0
	 *
	 * @return string
	 */
	public function get_next_url(): string {
		$url = '';

		if ( $this->is_just_completed() ) {
			// If the step has just been completed, return the URL after completion.
			$url = $this->url_after_completion;
		} elseif ( $this->next ) {
			// If the next step exists, return the next step URL if the user has access to it.
			$url = $this->next->is_content_visible( $this->user_id )
				? $this->next->get_permalink()
				: '';
		} else {
			// It's the last step. Let's redirect to the course URL.
			$url = $this->get_back_to_course_url();
		}

		/**
		 * Filters the URL for the next button.
		 *
		 * @since 4.24.0
		 *
		 * @param string     $url     The URL for the next button.
		 * @param Step_Model $step    The Step model.
		 * @param int        $user_id The user ID.
		 *
		 * @return string
		 */
		return apply_filters(
			'learndash_template_progression_step_next_url',
			$url,
			$this->step,
			$this->user_id
		);
	}

	/**
	 * Returns the label for the previous button.
	 *
	 * @since 4.24.0
	 *
	 * @return string
	 */
	public function get_previous_label(): string {
		$label = $this->previous
			? learndash_get_label_course_step_previous( $this->previous->get_post_type() )
			: $this->get_back_to_course_label();

		/**
		 * Filters the label for the previous button.
		 *
		 * @since 4.24.0
		 *
		 * @param string     $label   The label for the previous button.
		 * @param Step_Model $step    The Step model.
		 * @param int        $user_id The user ID.
		 *
		 * @return string
		 */
		return apply_filters(
			'learndash_template_progression_step_previous_label',
			$label,
			$this->step,
			$this->user_id
		);
	}

	/**
	 * Returns the short label for the previous button.
	 *
	 * @since 4.24.0
	 *
	 * @return string
	 */
	public function get_previous_short_label(): string {
		$label = $this->previous
			? __( 'Previous', 'learndash' )
			: __( 'Back', 'learndash' );

		/**
		 * Filters the short label for the previous button.
		 *
		 * @since 4.24.0
		 *
		 * @param string     $label   The short label for the previous button.
		 * @param Step_Model $step    The Step model.
		 * @param int        $user_id The user ID.
		 *
		 * @return string
		 */
		return apply_filters(
			'learndash_template_progression_step_previous_short_label',
			$label,
			$this->step,
			$this->user_id
		);
	}

	/**
	 * Returns the URL for the previous button.
	 *
	 * @since 4.24.0
	 *
	 * @return string
	 */
	public function get_previous_url(): string {
		$url = $this->previous
			? $this->previous->get_permalink()
			: $this->get_back_to_course_url();

		/**
		 * Filters the URL for the previous button.
		 *
		 * @since 4.24.0
		 *
		 * @param string     $url     The URL for the previous button.
		 * @param Step_Model $step    The Step model.
		 * @param int        $user_id The user ID.
		 *
		 * @return string
		 */
		return apply_filters(
			'learndash_template_progression_step_previous_url',
			$url,
			$this->step,
			$this->user_id
		);
	}

	/**
	 * Returns the label for the back to course button.
	 *
	 * @since 4.24.0
	 *
	 * @return string
	 */
	public function get_back_to_course_label(): string {
		$label = learndash_get_label_course_step_back( learndash_get_post_type_slug( LDLMS_Post_Types::COURSE ) );

		/**
		 * Filters the label for the back to course button.
		 *
		 * @since 4.24.0
		 *
		 * @param string     $label   The label for the back to course button.
		 * @param Step_Model $step    The Step model.
		 * @param int        $user_id The user ID.
		 *
		 * @return string
		 */
		return apply_filters(
			'learndash_template_progression_step_back_to_course_label',
			$label,
			$this->step,
			$this->user_id
		);
	}

	/**
	 * Returns the URL for the back to course button.
	 *
	 * @since 4.24.0
	 *
	 * @return string
	 */
	public function get_back_to_course_url(): string {
		$course = $this->step->get_course();

		$url = $course ? $course->get_permalink() : '';

		/**
		 * Filters the URL for the back to course button.
		 *
		 * @since 4.24.0
		 *
		 * @param string     $url     The URL for the back to course button.
		 * @param Step_Model $step    The Step model.
		 * @param int        $user_id The user ID.
		 *
		 * @return string
		 */
		return apply_filters( 'learndash_template_progression_step_back_to_course_url', $url, $this->step, $this->user_id );
	}

	/**
	 * Returns the label for the next step button.
	 *
	 * @since 4.24.0
	 *
	 * @param bool $short Whether to return the short label.
	 *
	 * @return string
	 */
	private function get_next_step_button_label( bool $short = false ): string {
		$course = $this->step->get_course();

		// If the step is not part of a course, there is no next step.

		if ( ! $course ) {
			return '';
		}

		// If the step has just been completed, use the label based on the course completion status.

		if ( $this->is_just_completed() ) {
			if ( $this->is_course_completed() ) {
				return $short
					? __( 'Finish', 'learndash' )
					: sprintf(
						// translators: placeholder: Course label.
						esc_html__( 'Finish %s', 'learndash' ),
						learndash_get_custom_label( LDLMS_Post_Types::COURSE )
					);
			} elseif ( $this->next_step_after_completion ) {
				return $short
					? __( 'Next', 'learndash' )
					: learndash_get_label_course_step_next( $this->next_step_after_completion->get_post_type() );
			}
		}

		// If there is a next step, return the Next label.

		if ( $this->next ) {
			return $short
				? __( 'Next', 'learndash' )
				: learndash_get_label_course_step_next( $this->next->get_post_type() );
		}

		// It's the last step. Let's return the Back to Course label.

		return $short ? __( 'Back', 'learndash' ) : $this->get_back_to_course_label();
	}
}
