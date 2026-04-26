<?php
/**
 * Trait for models that support video progression.
 *
 * @since 4.24.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models\Traits;

use LearnDash\Core\Models\Step;
use WP_User;

/**
 * Trait for models that support video progression.
 *
 * @since 4.24.0
 */
trait Supports_Video_Progression {
	/**
	 * Returns whether the step's video has been watched.
	 *
	 * @since 4.24.0
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return ?bool Whether the step's video has been watched or null if the step does not require watching a video.
	 */
	public function is_video_watched( $user = null ): ?bool {
		$user    = $this->map_user( $user );
		$user_id = $user instanceof WP_User ? $user->ID : $user;
		$course  = $this->get_course();

		$is_video_watched = null;

		if ( $course ) {
			if ( $this->requires_watching_video() ) {
				$is_video_watched = learndash_video_complete_for_step( $this->get_id(), $course->get_id(), $user_id );
			}
		}

		/**
		 * Filters whether the step's video has been watched.
		 *
		 * @since 4.24.0
		 *
		 * @param ?bool   $is_video_watched Whether the step's video has been watched or null if the step does not require watching a video.
		 * @param Step    $step             Step model.
		 * @param WP_User|int $user         The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return ?bool Whether the step's video has been watched or null if the step does not require watching a video.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_is_video_watched",
			$is_video_watched,
			$this,
			$user
		);
	}

	/**
	 * Returns whether the step requires watching a video after sub-steps.
	 *
	 * @since 4.24.0
	 *
	 * @return ?bool Whether the step requires watching a video after sub-steps or null if the step does not require watching a video.
	 */
	public function requires_watching_video_after_sub_steps(): ?bool {
		$requires_watching_video_after_sub_steps = null;

		if ( $this->requires_watching_video() ) {
			$step_settings = $this->get_settings();

			$requires_watching_video_after_sub_steps = isset( $step_settings['lesson_video_shown'] )
				&& 'AFTER' === $step_settings['lesson_video_shown'];
		}

		/**
		 * Filters whether the step requires watching a video after sub-steps.
		 *
		 * @since 4.24.0
		 *
		 * @param ?bool   $requires_watching_video_after_sub_steps Whether the step requires watching a video after sub-steps or null if the step does not require watching a video.
		 * @param Step    $step                        Step model.
		 *
		 * @return ?bool Whether the step requires watching a video after sub-steps or null if the step does not require watching a video.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_requires_watching_video_after_sub_steps",
			$requires_watching_video_after_sub_steps,
			$this
		);
	}

	/**
	 * Returns whether the step requires watching a video before sub-steps.
	 *
	 * @since 4.24.0
	 *
	 * @return ?bool Whether the step requires watching a video before sub-steps or null if the step does not require watching a video.
	 */
	public function requires_watching_video_before_sub_steps(): ?bool {
		$requires_watching_video_before_sub_steps = null;

		if ( $this->requires_watching_video() ) {
			$step_settings = $this->get_settings();

			$requires_watching_video_before_sub_steps = isset( $step_settings['lesson_video_shown'] )
				&& 'BEFORE' === $step_settings['lesson_video_shown'];
		}

		/**
		 * Filters whether the step requires watching a video before sub-steps.
		 *
		 * @since 4.24.0
		 *
		 * @param ?bool   $requires_watching_video_before_sub_steps Whether the step requires watching a video before sub-steps or null if the step does not require watching a video.
		 * @param Step    $step                        Step model.
		 *
		 * @return ?bool Whether the step requires watching a video before sub-steps or null if the step does not require watching a video.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_requires_watching_video_before_sub_steps",
			$requires_watching_video_before_sub_steps,
			$this
		);
	}

	/**
	 * Returns whether the step's content is visible for video progression.
	 *
	 * @since 4.24.0
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return bool Whether the step's content is visible for video progression.
	 */
	protected function is_content_visible_for_video_progression( $user ): bool {
		$parent_step = $this->get_parent_step();

		if ( ! $parent_step ) {
			return true;
		}

		if (
			method_exists( $parent_step, 'requires_watching_video_before_sub_steps' )
			&& method_exists( $parent_step, 'is_video_watched' )
			&& $parent_step->requires_watching_video_before_sub_steps()
		) {
			// If the parent step requires watching a video before completing sub-steps, returns whether the user has watched the video.
			return $parent_step->is_video_watched( $user );
		}

		// Check the grandparent step.

		$grandparent_step = $parent_step->get_parent_step();

		return ! $grandparent_step // No grandparent step.
			// Grandparent step does not support video progression.
			|| ! method_exists( $grandparent_step, 'requires_watching_video_before_sub_steps' )
			|| ! method_exists( $grandparent_step, 'is_video_watched' )
			// Grandparent step does not require watching a video before completing sub-steps.
			|| ! $grandparent_step->requires_watching_video_before_sub_steps()
			// Grandparent step requires watching a video before completing sub-steps and the user has watched the video.
			|| $grandparent_step->is_video_watched( $user );
	}

	/**
	 * Returns whether the step requires watching a video.
	 *
	 * @since 4.24.0
	 *
	 * @return bool Whether the step requires watching a video.
	 */
	private function requires_watching_video(): bool {
		$step_settings = $this->get_settings();

		return isset( $step_settings['lesson_video_enabled'] )
			&& 'on' === $step_settings['lesson_video_enabled']
			&& isset( $step_settings['lesson_video_url'] )
			&& ! empty( $step_settings['lesson_video_url'] )
			&& isset( $step_settings['lesson_video_shown'] );
	}
}
