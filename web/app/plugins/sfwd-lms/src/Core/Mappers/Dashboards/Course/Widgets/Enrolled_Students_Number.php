<?php
/**
 * Enrolled students number widget.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Mappers\Dashboards\Course\Widgets;

use LearnDash\Core\Template\Dashboards\Widgets\Interfaces;
use LearnDash\Core\Template\Dashboards\Widgets\Traits\Supports_Post;
use LearnDash\Core\Template\Dashboards\Widgets\Types\Value;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\DB\DB;

/**
 * Enrolled students number widget.
 *
 * @since 4.9.0
 */
class Enrolled_Students_Number extends Value implements Interfaces\Requires_Post {
	use Supports_Post;

	/**
	 * Loads required data.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	protected function load_data(): void {
		$this->set_label(
			__( 'Enrolled Students', 'learndash' )
		);

		$this->set_value(
			$this->get_enrolled_students_number()
		);
	}

	/**
	 * Returns the number of enrolled students.
	 *
	 * @since 4.9.0
	 *
	 * @return int
	 */
	private function get_enrolled_students_number(): int {
		$course_groups_ids = learndash_get_course_groups( $this->get_post()->ID );

		$query = DB::table( 'usermeta' )
			->select( 'user_id' )
			->where( 'meta_key', 'course_' . $this->get_post()->ID . '_access_from' );

		if ( ! empty( $course_groups_ids ) ) {
			$query->orWhereIn(
				'meta_key',
				array_map(
					function ( $course_group_id ) {
						return "learndash_group_users_{$course_group_id}";
					},
					$course_groups_ids
				)
			);
		}

		$query->groupBy( 'user_id' );

		$count = DB::get_var(
			'SELECT COUNT(*) FROM (' . $query->getSQL() . ') AS unique_users'
		);

		return Cast::to_int( $count );
	}
}
