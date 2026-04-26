<?php
/**
 * Enrolled students progress widget.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Mappers\Dashboards\Course\Widgets;

use LearnDash\Core\Template\Dashboards\Widgets\Interfaces;
use LearnDash\Core\Template\Dashboards\Widgets\Traits\Supports_Post;
use LearnDash\Core\Template\Dashboards\Widgets\Types\Value_Comparison;
use StellarWP\Learndash\StellarWP\DB\DB;
use StellarWP\Learndash\StellarWP\DB\QueryBuilder\JoinQueryBuilder;

/**
 * Enrolled students progress widget.
 *
 * @since 4.9.0
 */
class Enrollments_Progress extends Value_Comparison implements Interfaces\Requires_Post {
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
			__( 'Enrollments', 'learndash' )
		);

		$this->set_value(
			$this->get_enrolled_students_number_in_interval( 7 ) // past 7 days.
		);

		$this->set_previous_value(
			$this->get_enrolled_students_number_in_interval( 14, 7 ) // between 14 and 7 days ago.
		);
	}

	/**
	 * Returns the number of enrolled students in the course between the interval specified in days.
	 *
	 * @since 4.9.0
	 *
	 * @param int $start_in_days_ago Start of the interval in days.
	 * @param int $end_in_days_ago   End of the interval in days. Default 0 (today).
	 *
	 * @return int
	 */
	private function get_enrolled_students_number_in_interval( int $start_in_days_ago, int $end_in_days_ago = 0 ): int {
		$directly_enrolled_students    = $this->get_directly_enrolled_students_in_interval( $start_in_days_ago, $end_in_days_ago );
		$group_based_enrolled_students = $this->get_group_based_enrolled_students_in_interval( $start_in_days_ago, $end_in_days_ago );

		return count(
			array_unique(
				array_merge(
					$directly_enrolled_students,
					$group_based_enrolled_students
				)
			)
		);
	}

	/**
	 * Returns the directly enrolled students' IDs in the course between the interval specified in days.
	 *
	 * @since 4.9.0
	 *
	 * @param int $start_in_days_ago Start of the interval in days.
	 * @param int $end_in_days_ago   End of the interval in days. Default 0 (today).
	 *
	 * @return array<int>
	 */
	private function get_directly_enrolled_students_in_interval( int $start_in_days_ago, int $end_in_days_ago = 0 ): array {
		$start_param = ! empty( $start_in_days_ago ) ? 'NOW() - INTERVAL ' . $start_in_days_ago . ' DAY' : 'NOW()';
		$end_param   = ! empty( $end_in_days_ago ) ? 'NOW() - INTERVAL ' . $end_in_days_ago . ' DAY' : 'NOW()';

		$users_sql = DB::table( 'users' )
			->select( 'ID' )
			->join(
				function ( JoinQueryBuilder $builder ) {
					$builder
						->leftJoin( 'usermeta', 'enrolled_at' )
						->on( 'ID', 'enrolled_at.user_id' )
						->andOn( 'enrolled_at.meta_key', "learndash_course_{$this->get_post()->ID}_enrolled_at", true );
				}
			)
			->whereRaw( "WHERE enrolled_at.meta_value BETWEEN UNIX_TIMESTAMP($start_param) AND UNIX_TIMESTAMP($end_param)" )
			->getSQL();

		return DB::get_col( $users_sql );
	}

	/**
	 * Returns the students' IDs enrolled in the course via groups between the interval specified in days.
	 *
	 * @since 4.9.0
	 *
	 * @param int $start_in_days_ago Start of the interval in days.
	 * @param int $end_in_days_ago   End of the interval in days. Default 0 (today).
	 *
	 * @return array<int>
	 */
	private function get_group_based_enrolled_students_in_interval( int $start_in_days_ago, int $end_in_days_ago = 0 ): array {
		$course_groups_ids = learndash_get_course_groups( $this->get_post()->ID );

		if ( empty( $course_groups_ids ) ) {
			return [];
		}

		$start_param = ! empty( $start_in_days_ago ) ? 'NOW() - INTERVAL ' . $start_in_days_ago . ' DAY' : 'NOW()';
		$end_param   = ! empty( $end_in_days_ago ) ? 'NOW() - INTERVAL ' . $end_in_days_ago . ' DAY' : 'NOW()';

		$enrolled_students_sql = DB::table( 'usermeta' )
			->select( 'user_id' )
			->distinct()
			->whereIn(
				'meta_key',
				array_map(
					function( $course_group_id ) {
						return "learndash_group_{$course_group_id}_enrolled_at";
					},
					$course_groups_ids
				)
			)
			->whereRaw( "AND meta_value BETWEEN UNIX_TIMESTAMP($start_param) AND UNIX_TIMESTAMP($end_param)" )
			->getSQL();

		return DB::get_col( $enrolled_students_sql );
	}
}
