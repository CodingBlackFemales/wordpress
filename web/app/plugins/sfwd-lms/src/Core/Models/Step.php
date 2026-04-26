<?php
/**
 * This base class provides the easy way to interact with a Course Step.
 *
 * @since 4.21.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models;

use LDLMS_Post_Types;
use LearnDash\Core\Mappers\Models\Step_Mapper;
use LearnDash_Settings_Section;
use WP_User;

/**
 * Step model base class.
 *
 * @since 4.21.0
 */
abstract class Step extends Post {
	/**
	 * Returns a course step permalink.
	 *
	 * @since 4.21.0
	 *
	 * @return string
	 */
	public function get_permalink(): string {
		$nested_urls_enabled = 'yes' === LearnDash_Settings_Section::get_section_setting(
			'LearnDash_Settings_Section_Permalinks',
			'nested_urls'
		);

		if ( $nested_urls_enabled ) {
			$course = $this->get_course();

			if ( $course ) {
				return (string) learndash_get_step_permalink( $this->get_id(), $course->get_id() );
			}
		}

		return (string) get_permalink( $this->get_id() );
	}

	/**
	 * Returns the parent step of the current step.
	 *
	 * @since 4.24.0
	 *
	 * @return Step|null
	 */
	public function get_parent_step(): ?self {
		$parent_step = null;

		$course = $this->get_course();

		if ( $course ) {
			$parent_step_id = learndash_course_get_single_parent_step( $course->get_id(), $this->get_id() );

			if ( $parent_step_id ) {
				$parent_step = Step_Mapper::create( (int) $parent_step_id );
			}
		}

		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_parent_step",
			$parent_step,
			$this
		);
	}

	/**
	 * Returns the related course of the step or null if the step is not associated with a course.
	 *
	 * @since 4.21.0
	 *
	 * @return Course|null
	 */
	public function get_course(): ?Course {
		$cached_course = $this->getAttribute( LDLMS_Post_Types::COURSE, false );

		if (
			$cached_course instanceof Course
			|| is_null( $cached_course )
		) {
			return $cached_course;
		}

		$course = Course::find(
			(int) learndash_get_course_id( $this->get_id() )
		);

		/**
		 * Filters a course step's course.
		 *
		 * @since 4.21.0
		 *
		 * @param Course|null $course Course model.
		 * @param Step        $step   Course step model.
		 *
		 * @return Course|null Course model or null if not found.
		 */
		$course = apply_filters(
			"learndash_model_{$this->get_post_type_key()}_course",
			$course,
			$this
		);

		$this->set_course( $course );

		return $course;
	}

	/**
	 * Returns the ID of the course associated with the step. 0 if the step is not associated with a course.
	 *
	 * @since 4.24.0
	 *
	 * @return int Course ID or 0 if the step is not associated with a course.
	 */
	public function get_course_id(): int {
		$course = $this->get_course();

		return $course ? $course->get_id() : 0;
	}

	/**
	 * Sets the related course of the step.
	 *
	 * @since 4.21.0
	 *
	 * @param Course|null $course Course model or null.
	 *
	 * @return void
	 */
	public function set_course( ?Course $course ): void {
		$this->setAttribute( LDLMS_Post_Types::COURSE, $course );
	}

	/**
	 * Returns the previous step.
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @since 4.24.0
	 *
	 * @return Step|null Previous step or null if the step is not associated with a course or if it's the first step.
	 */
	public function get_previous( $user = null ): ?Step {
		$user    = $this->map_user( $user );
		$user_id = $user instanceof WP_User ? $user->ID : $user;

		$previous_step = null;

		$previous_step_id = learndash_previous_post_link( null, 'id', $this->get_post() );

		if ( $previous_step_id ) {
			$previous_step = Step_Mapper::create( (int) $previous_step_id );
		}

		/**
		 * Filters the previous step.
		 *
		 * @since 4.24.0
		 *
		 * @param Step|null $previous_step Previous step or null.
		 * @param Step      $step          Step model.
		 * @param int       $user_id       The user ID.
		 *
		 * @return Step|null Previous step or null.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_previous_step",
			$previous_step,
			$this,
			$user_id
		);
	}

	/**
	 * Returns the next step.
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @since 4.24.0
	 *
	 * @return Step|null Next step or null if the step is not associated with a course or if it's the last step.
	 */
	public function get_next( $user = null ): ?Step {
		$user    = $this->map_user( $user );
		$user_id = $user instanceof WP_User ? $user->ID : $user;

		$next_step = null;

		$next_step_id = learndash_next_post_link( null, 'id', $this->get_post() );

		if ( $next_step_id ) {
			$next_step = Step_Mapper::create( (int) $next_step_id );
		}

		/**
		 * Filters the next step.
		 *
		 * @since 4.24.0
		 *
		 * @param Step|null $next_step Next step or null.
		 * @param Step      $step      Step model.
		 * @param int       $user_id   The user ID.
		 *
		 * @return Step|null Next step or null.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_next_step",
			$next_step,
			$this,
			$user_id
		);
	}

	/**
	 * Returns a flag whether a step is completed.
	 *
	 * @since 4.21.0
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return bool
	 */
	public function is_complete( $user = null ): bool {
		$user    = $this->map_user( $user );
		$user_id = $user instanceof WP_User ? $user->ID : $user;

		$course = $this->get_course();

		$is_complete = $course && learndash_user_progress_is_step_complete(
			$user_id,
			$course->get_id(),
			$this->get_id(),
		);

		/**
		 * Filters whether the step is completed.
		 *
		 * @since 4.21.0
		 *
		 * @param bool        $is_complete Whether the step is completed.
		 * @param Step        $step        Step model.
		 * @param WP_User|int $user        The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return bool Whether the step is completed.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_is_complete",
			$is_complete,
			$this,
			$user
		);
	}

	/**
	 * Returns whether the step takes place in an external setting.
	 *
	 * @since 4.21.0
	 *
	 * @return bool
	 */
	public function is_external(): bool {
		$is_external = learndash_course_steps_is_external( $this->get_id() );

		/**
		 * Filters whether the step takes place in an external setting.
		 *
		 * @since 4.21.0
		 *
		 * @param bool $is_external Whether the step takes place in an external setting.
		 * @param Step $step        Step model.
		 *
		 * @return bool Whether the step takes place in an external setting.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_is_external",
			$is_external,
			$this
		);
	}

	/**
	 * Returns whether the step is offered virtually.
	 *
	 * @since 4.21.0
	 *
	 * @return bool
	 */
	public function is_virtual(): bool {
		$external_type = strtolower( learndash_course_steps_get_external_type( $this->get_id() ) );

		$is_virtual = $external_type === 'virtual';

		/**
		 * Filters whether the step is offered virtually.
		 *
		 * @since 4.21.0
		 *
		 * @param bool $is_attendance_required Whether the step is offered virtually.
		 * @param Step $step                   Step model.
		 *
		 * @return bool Whether the step is offered virtually.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_is_virtual",
			$is_virtual,
			$this
		);
	}

	/**
	 * Returns whether the step is offered in-person.
	 *
	 * @since 4.21.0
	 *
	 * @return bool
	 */
	public function is_in_person(): bool {
		$external_type = strtolower( learndash_course_steps_get_external_type( $this->get_id() ) );

		$is_in_person = $external_type === 'in-person';

		/**
		 * Filters whether the step is offered in-person.
		 *
		 * @since 4.21.0
		 *
		 * @param bool $is_attendance_required Whether the step is offered in-person.
		 * @param Step $step                   Step model.
		 *
		 * @return bool Whether the step is offered in-person.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_is_in_person",
			$is_in_person,
			$this
		);
	}

	/**
	 * Returns whether the step requires attendance.
	 *
	 * @since 4.21.0
	 *
	 * @return bool
	 */
	public function is_attendance_required(): bool {
		$is_attendance_required = learndash_course_steps_is_external_attendance_required( $this->get_id() );

		/**
		 * Filters whether attendance is required.
		 *
		 * @since 4.21.0
		 *
		 * @param bool $is_attendance_required Whether attendance is required.
		 * @param Step $step                   Step model.
		 *
		 * @return bool Whether attendance is required.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_is_attendance_required",
			$is_attendance_required,
			$this
		);
	}

	/**
	 * Returns the timestamp when the step is available.
	 *
	 * @since 4.21.0
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return int|null Unix timestamp or null if the step is always available.
	 */
	public function get_available_on_date( $user = null ): ?int {
		$user    = $this->map_user( $user );
		$user_id = $user instanceof WP_User ? $user->ID : $user;

		$available_on = null;

		$course = $this->get_course();

		if (
			$course
			&& ! learndash_can_user_bypass(
				$user_id,
				'learndash_course_lesson_not_available',
				[
					'step_id' => $this->get_id(),
					'step'    => $this->get_post(),
				]
			)
		) {
			// Get all parent step IDs.
			// We need to reverse the array to start from the immediate parent step to the root step.

			$step_ids = array_reverse( learndash_course_get_all_parent_step_ids( $course->get_id(), $this->get_id() ) );
			$step_ids = array_merge( [ $this->get_id() ], $step_ids );

			// Loop through all parent steps and the current step to find the first step with a defined availability date.

			foreach ( $step_ids as $step_id ) {
				$available_on = (int) ld_lesson_access_from( $step_id, $user_id, $course->get_id() );

				if ( $available_on > 0 ) {
					break;
				}
			}

			// If the step is always available, set the availability date to null.

			if ( $available_on <= 0 ) {
				$available_on = null;
			}
		}

		/**
		 * Filters the timestamp when the step is available.
		 *
		 * @since 4.21.0
		 *
		 * @param int|null    $available_on Unix timestamp when the step is available or null if the step is always available.
		 * @param Step        $step         Step model.
		 * @param WP_User|int $user         The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return int|null Unix timestamp when the step is available or null if the step is always available.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_available_on_date",
			$available_on,
			$this,
			$user
		);
	}

	/**
	 * Returns a flag whether a step is a sample.
	 *
	 * @since 4.21.0
	 *
	 * @return bool
	 */
	public function is_sample(): bool {
		/**
		 * Filters whether the step is a sample.
		 *
		 * @since 4.21.0
		 *
		 * @param bool $is_sample Whether the step is a sample.
		 * @param Step $step      Step model.
		 *
		 * @return bool Whether the step is a sample.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_is_sample",
			learndash_is_sample( $this->get_id() ),
			$this
		);
	}

	/**
	 * Returns true if a user has access to this Course Step, false otherwise.
	 *
	 * @since 4.24.0
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return bool
	 */
	public function user_has_access( $user = null ): bool {
		$user    = $this->map_user( $user );
		$user_id = $user instanceof WP_User ? $user->ID : $user;

		$has_access = sfwd_lms_has_access( $this->get_id(), $user_id );

		/**
		 * Filters whether a user has access to a Course Step.
		 *
		 * @since 4.24.0
		 *
		 * @param bool        $has_access True if a user has access, false otherwise.
		 * @param Step        $step       Step model.
		 * @param WP_User|int $user       The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return bool True if a user has access, false otherwise.
		 */
		return apply_filters( "learndash_model_{$this->get_post_type_key()}_user_has_access", $has_access, $this, $user );
	}

	/**
	 * Returns whether the Course Step content should be visible.
	 *
	 * @since 4.24.0
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return bool
	 */
	public function is_content_visible( $user = null ): bool {
		$user    = $this->map_user( $user );
		$user_id = $user instanceof WP_User ? $user->ID : $user;
		$course  = $this->get_course();

		$lesson_progression_enabled = false;
		$bypass_prerequisites       = false;

		$is_content_visible = ! $this->get_available_on_date( $user )
			&& $this->is_sample();

		if ( $course ) {
			$lesson_progression_enabled = learndash_lesson_progression_enabled( $course->get_id() );
			$bypass_prerequisites       = learndash_can_user_bypass(
				$user_id,
				'learndash_prerequities_bypass', // cspell:disable-line -- prerequities are prerequisites.
				[
					'course_id' => $course->get_id(),
					'step_id'   => $this->get_id(),
				]
			);
		}

		// Logic specific to existing users.
		if (
			$user_id > 0
			&& $this->user_has_access( $user )
			&& ! $this->get_available_on_date( $user )
		) {
			if (
				$course
				&& learndash_user_progress_is_step_complete( $user_id, $course->get_id(), $this->get_id() )
			) {
				// If the step has already been completed, they should be allowed to view it.
				$is_content_visible = true;
			} elseif (
				$course
				&& $lesson_progression_enabled
			) {
				// If Lesson Progression is enabled, check if this Course Step is the next step to be completed.
				$previous_incomplete_step_id = learndash_user_progress_get_previous_incomplete_step( $user_id, $course->get_id(), $this->get_id() );

				if ( $previous_incomplete_step_id === $this->get_id() ) {
					// If the step supports video progression, check if the content is visible for video progression.
					if ( method_exists( $this, 'is_content_visible_for_video_progression' ) ) {
						$is_content_visible = $this->is_content_visible_for_video_progression( $user );
					} else {
						$is_content_visible = true; // This is the current incomplete step.
					}
				}

				/** This filter is documented in includes/class-ld-cpt-instance.php */
				$is_content_visible = apply_filters(
					'learndash_previous_step_completed',
					$is_content_visible,
					$this->get_id(),
					$user_id
				);
			} else {
				// If we're not checking against progression, assume they can view the content since they have access.
				$is_content_visible = true;
			}
		}

		// Non-existing/logged out users.
		if (
			$user_id === 0
			&& $this->user_has_access( $user )
			&& ! $this->get_available_on_date( $user )
		) {
			$is_content_visible = true;
		}

		// If bypassing requirements is allowed, ensure the content is visible.
		if ( $bypass_prerequisites ) {
			$is_content_visible = true;
		}

		/**
		 * Filters whether the Course Step content should be visible.
		 *
		 * @since 4.24.0
		 *
		 * @param bool        $is_content_visible True if the content should be visible, false otherwise.
		 * @param Step        $step               Step model.
		 * @param WP_User|int $user               The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return bool True if the content should be visible, false otherwise.
		 */
		return apply_filters( "learndash_model_{$this->get_post_type_key()}_is_content_visible", $is_content_visible, $this, $user );
	}
}
