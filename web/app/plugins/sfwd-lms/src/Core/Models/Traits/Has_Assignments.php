<?php
/**
 * Trait for a Step model that can have assignments.
 *
 * @since 4.24.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models\Traits;

use LearnDash\Core\Models;
use LearnDash\Core\Utilities\Cast;
use WP_User;

/**
 * Trait for a Step model that can have assignments.
 *
 * @since 4.24.0
 *
 * @mixin Models\Step
 */
trait Has_Assignments {
	/**
	 * Returns the assignments for a step.
	 * If a user is provided, only the assignments for that user are returned. Otherwise, all assignments for the step are returned.
	 *
	 * Add tests.
	 *
	 * @since 4.24.0
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or 0, all step related assignments are returned. If a user ID is provided, only the assignments for that user are returned.
	 *
	 * @return Models\Assignment[]
	 */
	public function get_assignments( $user = null ): array {
		$user    = $this->map_user( $user, true );
		$user_id = $user instanceof WP_User ? $user->ID : $user;

		$course = $this->get_course();

		$assignments = [];

		if ( $course ) {
			$meta = [
				'course_id' => $course->get_id(),
				'lesson_id' => $this->get_id(),
			];

			// If we want to get the assignments for a specific user.
			if ( $user_id > 0 ) {
				$meta['user_id'] = $user_id;
			}

			$assignments = Models\Assignment::find_many_by_meta( $meta );
		}

		/**
		 * Filters model assignments.
		 *
		 * @since 4.24.0
		 *
		 * @param Models\Assignment[] $assignments Assignments.
		 * @param Models\Step         $model       Post model with assignments.
		 * @param WP_User|int|null    $user        User.
		 *
		 * @return Models\Assignment[] Assignments.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_assignments",
			$assignments,
			$this,
			$user
		);
	}

	/**
	 * Returns the assignments number for a step.
	 * If a user is provided, only the assignments for that user are returned. Otherwise, all assignments for the step are returned.
	 *
	 * It could be more efficient with a custom query, but we don't expect many assignments per user now. We can switch to a custom query if needed.
	 *
	 * @since 4.24.0
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return int
	 */
	public function get_assignments_number( $user = null ): int {
		/**
		 * Filters the number of assignments for a user.
		 *
		 * @since 4.24.0
		 *
		 * @param int              $number The number of assignments.
		 * @param Models\Step      $model  The model.
		 * @param WP_User|int|null $user   The user ID or WP_User or null. Null by default.
		 *
		 * @return int The number of assignments.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_assignments_number",
			count( $this->get_assignments( $user ) ),
			$this,
			$user
		);
	}

	/**
	 * Returns the number of approved assignments for a user.
	 * If a user is provided, only the assignments for that user are returned. Otherwise, all assignments for the step are returned.
	 *
	 * It could be more efficient with a custom query, but we don't expect many assignments per user now. We can switch to a custom query if needed.
	 *
	 * @since 4.24.0
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return int
	 */
	public function get_approved_assignments_number( $user = null ): int {
		$number = count(
			array_filter(
				$this->get_assignments( $user ),
				fn ( Models\Assignment $assignment ) => $assignment->is_approved()
			)
		);

		/**
		 * Filters the number of approved assignments for a user.
		 *
		 * @since 4.24.0
		 *
		 * @param int              $number The number of approved assignments.
		 * @param Models\Step      $model  The model.
		 * @param WP_User|int|null $user   The user ID or WP_User or null. Null by default.
		 *
		 * @return int The number of approved assignments.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_approved_assignments_number",
			$number,
			$this,
			$user
		);
	}

	/**
	 * Returns the number of assignments still allowed to be submitted by a user.
	 *
	 * It calculates the number of assignments still allowed to be submitted by a user based on the maximum number of assignments allowed for the model,
	 * and the number of assignments already submitted by the user.
	 *
	 * If the user has already submitted the maximum number of assignments, the number of assignments still allowed to be submitted by the user is 0.
	 *
	 * If the maximum number of assignments is not set, the number of assignments still allowed to be submitted by the user is 0.
	 *
	 * @since 4.24.0
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return int The number of assignments still allowed to be submitted by a user.
	 */
	public function get_submittable_assignments_number( $user = null ): int {
		$max_assignments_number = $this->get_maximum_assignments_number();

		$submittable_assignments_number = $max_assignments_number > 0
			? $max_assignments_number - $this->get_assignments_number( $user )
			: 0;

		// An extra check in case a setting is set to a different value after the assignments are submitted.
		if ( $submittable_assignments_number < 0 ) {
			$submittable_assignments_number = 0;
		}

		/**
		 * Filters the number of assignments still allowed to be submitted by a user.
		 *
		 * @since 4.24.0
		 *
		 * @param int              $submittable_assignments_number The number of assignments still allowed to be submitted by a user.
		 * @param Models\Step      $model                          Post model with assignments.
		 * @param WP_User|int|null $user                           The user ID or WP_User or null. Null by default.
		 *
		 * @return int The number of assignments still allowed to be submitted by a user.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_submittable_assignments_number",
			$submittable_assignments_number,
			$this,
			$user
		);
	}

	/**
	 * Returns the maximum number of assignments for a model.
	 *
	 * @since 4.24.0
	 *
	 * @return int The maximum number of assignments for a model.
	 */
	public function get_maximum_assignments_number(): int {
		/**
		 * Filters the maximum number of assignments for a model.
		 *
		 * @since 4.24.0
		 *
		 * @param int         $maximum_assignments_number The maximum number of assignments for a model.
		 * @param Models\Step $model                      Post model with assignments.
		 *
		 * @return int The maximum number of assignments for a model.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_maximum_assignments_number",
			Cast::to_int(
				$this->get_setting( 'assignment_upload_limit_count' )
			),
			$this
		);
	}

	/**
	 * Returns whether the model requires assignments.
	 *
	 * @since 4.24.0
	 *
	 * @return bool
	 */
	public function requires_assignments(): bool {
		/**
		 * Filters whether the model requires assignments.
		 *
		 * @since 4.24.0
		 *
		 * @param bool        $requires_assignments Whether the model requires assignments.
		 * @param Models\Step $model                Post model with assignments.
		 *
		 * @return bool Whether the model requires assignments.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_requires_assignments",
			'on' === $this->get_setting( 'lesson_assignment_upload' ),
			$this
		);
	}

	/**
	 * Returns whether the model has points enabled.
	 *
	 * @since 4.24.0
	 *
	 * @return bool
	 */
	public function has_assignment_points_enabled(): bool {
		/**
		 * Filters whether the model has assignment points enabled.
		 *
		 * @since 4.24.0
		 *
		 * @param bool        $has_assignment_points_enabled Whether the model has assignment points enabled.
		 * @param Models\Step $model                         Post model with assignments.
		 *
		 * @return bool Whether the model has assignment points enabled.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_has_assignment_points_enabled",
			$this->requires_assignments() && 'on' === $this->get_setting( 'lesson_assignment_points_enabled' ),
			$this
		);
	}

	/**
	 * Returns the maximum points for an assignment.
	 *
	 * @since 4.24.0
	 *
	 * @return ?float The maximum points for an assignment or null if assignments are not enabled.
	 */
	public function get_assignment_points_maximum(): ?float {
		$max_points = null;

		if (
			$this->requires_assignments()
			&& $this->has_assignment_points_enabled()
		) {
			$max_points = Cast::to_float(
				$this->get_setting( 'lesson_assignment_points_amount' )
			);
		}

		/**
		 * Filters the maximum points for an assignment.
		 *
		 * @since 4.24.0
		 *
		 * @param float|null  $max_points The maximum points for an assignment. Null if assignments or points are not enabled.
		 * @param Models\Step $model      Post model with assignments.
		 *
		 * @return float|null The maximum points for an assignment.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_assignment_points_maximum",
			$max_points,
			$this
		);
	}

	/**
	 * Returns the maximum upload size for an assignment.
	 *
	 * @since 4.24.0
	 *
	 * @return int The maximum upload size for an assignment.
	 */
	public function get_assignment_file_size_limit_in_bytes(): int {
		$max_upload_size_setting_value = Cast::to_string( $this->get_setting( 'assignment_upload_limit_size' ) );

		$max_upload_size_in_bytes = ! empty( $max_upload_size_setting_value )
			? learndash_return_bytes_from_shorthand( $max_upload_size_setting_value )
			: wp_max_upload_size();

		$max_upload_size_in_bytes = Cast::to_int( min( $max_upload_size_in_bytes, wp_max_upload_size() ) );

		/**
		 * Filters the maximum upload size for an assignment.
		 *
		 * @since 4.24.0
		 *
		 * @param int         $max_upload_size_in_bytes The maximum upload size for an assignment. In bytes.
		 * @param Models\Step $model                    Post model with assignments.
		 *
		 * @return int The maximum upload size for an assignment.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_assignment_upload_limit_size",
			$max_upload_size_in_bytes,
			$this
		);
	}

	/**
	 * Returns supported assignment file types.
	 *
	 * @since 4.24.0
	 *
	 * @return string[] The assignment file types supported by the model.
	 */
	public function get_supported_assignment_file_types(): array {
		$assignment_file_types_setting_value = $this->get_setting( 'assignment_upload_limit_extensions' );

		if (
			! is_array( $assignment_file_types_setting_value )
			&& ! is_string( $assignment_file_types_setting_value )
		) {
			$assignment_file_types_setting_value = [];
		}

		$assignment_file_types = ! empty( $assignment_file_types_setting_value )
			? learndash_validate_extensions( $assignment_file_types_setting_value )
			: [];

		$allowed_file_extensions = learndash_get_allowed_upload_file_extensions( false );
		$supported_file_types    = $allowed_file_extensions;

		// If file types are set, we only support the ones that are allowed.
		if ( ! empty( $assignment_file_types ) ) {
			$supported_file_types = array_intersect(
				$assignment_file_types,
				$allowed_file_extensions
			);
		}

		/**
		 * Filters the supported assignment file types.
		 *
		 * @since 4.24.0
		 *
		 * @param string[]    $supported_file_types The supported assignment file types.
		 * @param Models\Step $model                Post model with assignments.
		 *
		 * @return string[] The supported assignment file types.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_supported_assignment_file_types",
			array_values( $supported_file_types ),
			$this
		);
	}

	/**
	 * Returns the supported assignment file mime types.
	 *
	 * @since 4.24.0
	 *
	 * @return array<string, string> The supported assignment file mime types with the file extension as the key.
	 */
	public function get_supported_assignment_file_mime_types(): array {
		/**
		 * Filters the supported assignment file mime types.
		 *
		 * @since 4.24.0
		 *
		 * @param string[]    $supported_file_mime_types The supported assignment file mime types.
		 * @param Models\Step $model                     Post model with assignments.
		 *
		 * @return array<string, string> The supported assignment file mime types with the file extension as the key.
		 */
		return apply_filters(
			"learndash_model_{$this->get_post_type_key()}_supported_assignment_file_mime_types",
			learndash_get_allowed_upload_file_extensions( true, $this->get_supported_assignment_file_types() ),
			$this
		);
	}
}
