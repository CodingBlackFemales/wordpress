<?php
/**
 * LearnDash validation trait for groups with start or end date.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Validations\Traits;

use LearnDash\Core\Models\Group;
use LearnDash\Core\Validations\Validators\DTO\Action;

/**
 * Trait for groups with start or end date.
 *
 * @since 4.8.0
 */
trait Groups_With_Start_Or_End_Date {
	/**
	 * Check if the groups' list has multiple groups, with at least one of them with a start or end date.
	 *
	 * @since 4.8.0
	 *
	 * @param array<int> $group_ids List of group ids.
	 *
	 * @return Group|null The first conflicting group with a start or end date, or null if none found.
	 */
	protected function get_first_conflicting_group_with_start_or_end_date( array $group_ids ): ?Group {
		if ( count( $group_ids ) <= 1 ) {
			return null; // One or zero groups don't conflict.
		}

		$groups = Group::find_many( $group_ids );

		foreach ( $groups as $group ) {
			if (
				$group->get_product()->get_start_date()
				|| $group->get_product()->get_end_date()
			) {
				return $group;
			}
		}

		return null;
	}

	/**
	 * Check if the group contains at least one course that belongs to multiple groups.
	 *
	 * @param int $group_id The group id.
	 *
	 * @return bool True if the group contains at least one course that belongs to multiple groups, false otherwise.
	 */
	protected function contains_course_that_belongs_to_multiple_groups( int $group_id ): bool {
		$group_courses_ids = learndash_get_group_courses_list( $group_id );

		foreach ( $group_courses_ids as $course_id ) {
			$group_ids = learndash_get_course_groups( $course_id );

			if ( count( $group_ids ) > 1 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the actions for the start or end date validation fields to be displayed in the WP frontend.
	 *
	 * @since 4.8.0
	 *
	 * @return array<Action> The actions for the start or end date validation fields.
	 */
	protected function get_actions_for_start_or_end_date_validation_fields(): array {
		return [
			new Action(
				[
					'url'   => 'https://www.learndash.com/support/docs/users-groups/groups/group-cohorts',
					'label' => esc_html__( 'See the documentation', 'learndash' ),
				]
			),
		];
	}
}
