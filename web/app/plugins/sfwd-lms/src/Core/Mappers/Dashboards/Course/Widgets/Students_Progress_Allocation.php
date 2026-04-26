<?php
/**
 * Students progress allocation widget.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Mappers\Dashboards\Course\Widgets;

use LDLMS_DB;
use LearnDash\Core\Template\Dashboards\Widgets\Interfaces;
use LearnDash\Core\Template\Dashboards\Widgets\Traits\Supports_Post;
use LearnDash\Core\Template\Dashboards\Widgets\Types\DTO\Values_Item;
use LearnDash\Core\Template\Dashboards\Widgets\Types\Values;
use LearnDash\Core\Utilities\Cast;
use Learndash_DTO_Validation_Exception;
use StellarWP\Learndash\StellarWP\DB\DB;
use StellarWP\Learndash\StellarWP\DB\QueryBuilder\QueryBuilder;
use StellarWP\Learndash\StellarWP\DB\QueryBuilder\WhereQueryBuilder;

/**
 * Students progress allocation widget.
 *
 * @since 4.9.0
 */
class Students_Progress_Allocation extends Values implements Interfaces\Requires_Post {
	use Supports_Post;

	/**
	 * Number of students enrolled in the course. Default 0.
	 *
	 * @since 4.9.0
	 *
	 * @var int
	 */
	private $students_number_total = 0;

	/**
	 * Number of students who completed the course. Default 0.
	 *
	 * @since 4.9.0
	 *
	 * @var int
	 */
	private $students_number_completed = 0;

	/**
	 * Number of students who are in progress. Default 0.
	 *
	 * @since 4.9.0
	 *
	 * @var int
	 */
	private $students_number_in_progress = 0;

	/**
	 * Number of students who have not started the course. Default 0.
	 *
	 * @since 4.9.0
	 *
	 * @var int
	 */
	private $students_number_not_started = 0;

	/**
	 * Loads required data.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	protected function load_data(): void {
		$this->load_progress_allocation();

		$items_data = [
			[
				'label'     => __( 'Completed', 'learndash' ),
				'value'     => $this->get_percentage( $this->students_number_completed ) . '%',
				'sub_label' => sprintf(
					// translators: %s: number of students.
					_n( '%s student', '%s students', $this->students_number_completed, 'learndash' ),
					$this->students_number_completed
				),
			],
			[
				'label'     => __( 'In Progress', 'learndash' ),
				'value'     => $this->get_percentage( $this->students_number_in_progress ) . '%',
				'sub_label' => sprintf(
					// translators: %s: number of students.
					_n( '%s student', '%s students', $this->students_number_in_progress, 'learndash' ),
					$this->students_number_in_progress
				),
			],
			[
				'label'     => __( 'Not Started', 'learndash' ),
				'value'     => $this->get_percentage( $this->students_number_not_started ) . '%',
				'sub_label' => sprintf(
					// translators: %s: number of students.
					_n( '%s student', '%s students', $this->students_number_not_started, 'learndash' ),
					$this->students_number_not_started
				),
			],
		];

		$items = [];

		foreach ( $items_data as $item ) {
			try {
				$items[] = new Values_Item( $item );
			} catch ( Learndash_DTO_Validation_Exception $e ) {
				continue;
			}
		}

		$this->set_items( $items );
	}

	/**
	 * Loads the progress allocation data.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	private function load_progress_allocation(): void {
		$course_groups_ids = learndash_get_course_groups( $this->get_post()->ID );

		$this->students_number_total = $this->get_enrolled_students_number( $course_groups_ids );

		if ( 0 === $this->students_number_total ) {
			return;
		}

		$this->students_number_completed   = $this->get_students_number_completed( $course_groups_ids );
		$this->students_number_in_progress = $this->get_students_number_in_progress( $course_groups_ids );
		$this->students_number_not_started = $this->students_number_total - $this->students_number_completed - $this->students_number_in_progress;
	}

	/**
	 * Returns the percentage based on the total number of students.
	 *
	 * @since 4.9.0
	 *
	 * @param int $value The value to calculate the percentage.
	 *
	 * @return float
	 */
	private function get_percentage( int $value ): float {
		if ( 0 === $this->students_number_total ) {
			return 0;
		}

		return round( ( $value / $this->students_number_total ) * 100, 2 );
	}

	/**
	 * Returns the enrolled students number.
	 *
	 * @since 4.12.0
	 *
	 * @param int[] $course_groups_ids The group IDs.
	 *
	 * @return int
	 */
	private function get_enrolled_students_number( array $course_groups_ids ): int {
		$query = DB::table( 'usermeta' )
			->select( 'user_id' )
			->where( 'meta_key', 'course_' . $this->get_post()->ID . '_access_from' );

		if ( ! empty( $course_groups_ids ) ) {
			$query->orWhereIn(
				'meta_key',
				array_map(
					function( $group_id ) {
						return "learndash_group_users_{$group_id}";
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

	/**
	 * Returns the number of students who completed the course.
	 *
	 * @since 4.12.0
	 *
	 * @param int[] $course_groups_ids The group IDs.
	 *
	 * @return int
	 */
	private function get_students_number_completed( array $course_groups_ids ): int {
		return DB::table( DB::raw( $this->get_activity_table_name() ) )
			->where( 'course_id', Cast::to_string( $this->get_post()->ID ) )
			->whereIn(
				'user_id',
				function ( QueryBuilder $builder ) use ( $course_groups_ids ) {
					$builder
						->select( 'user_id' )
						->from( 'usermeta' )
						->where( 'meta_key', 'course_' . $this->get_post()->ID . '_access_from' );

					if ( ! empty( $course_groups_ids ) ) {
						$builder->orWhereIn(
							'meta_key',
							array_map(
								function ( $course_group_id ) {
									return "learndash_group_users_{$course_group_id}";
								},
								$course_groups_ids
							)
						);
					}

					$builder->groupBy( 'user_id' );
				}
			)
			->where( 'activity_type', 'course' )
			->where( 'activity_completed', '0', '>' )
			->count();
	}

	/**
	 * Returns the number of students who are in progress.
	 *
	 * @since 4.12.0
	 *
	 * @param int[] $course_groups_ids The group IDs.
	 *
	 * @return int
	 */
	private function get_students_number_in_progress( array $course_groups_ids ): int {
		return DB::table( DB::raw( $this->get_activity_table_name() ) )
			->where( 'course_id', Cast::to_string( $this->get_post()->ID ) )
			->whereIn(
				'user_id',
				function ( QueryBuilder $builder ) use ( $course_groups_ids ) {
					$builder
						->select( 'user_id' )
						->from( 'usermeta' )
						->where( 'meta_key', 'course_' . $this->get_post()->ID . '_access_from' );

					if ( ! empty( $course_groups_ids ) ) {
						$builder->orWhereIn(
							'meta_key',
							array_map(
								function ( $course_group_id ) {
									return "learndash_group_users_{$course_group_id}";
								},
								$course_groups_ids
							)
						);
					}

					$builder->groupBy( 'user_id' );
				}
			)
			->where( 'activity_type', 'course' )
			->where( 'activity_started', '0', '>' )
			->where(
				function ( WhereQueryBuilder $builder ) {
					$builder->where( 'activity_completed', '0' )
						->orWhereIsNull( 'activity_completed' );
				}
			)
			->whereRaw( 'AND activity_updated != activity_started' )
			->count();
	}

	/**
	 * Returns the user activity table name.
	 *
	 * @since 4.12.0
	 *
	 * @return string
	 */
	private function get_activity_table_name(): string {
		return LDLMS_DB::get_table_name( 'user_activity' );
	}
}
