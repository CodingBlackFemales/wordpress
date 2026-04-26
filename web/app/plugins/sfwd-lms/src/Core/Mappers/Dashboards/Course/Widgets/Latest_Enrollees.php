<?php
/**
 * Latest enrollees widget.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Mappers\Dashboards\Course\Widgets;

use LearnDash\Core\Template\Dashboards\Widgets\Interfaces;
use LearnDash\Core\Template\Dashboards\Widgets\Traits\Supports_Post;
use LearnDash\Core\Template\Dashboards\Widgets\Types\Users;
use StellarWP\Learndash\StellarWP\DB\DB;
use StellarWP\Learndash\StellarWP\DB\QueryBuilder\JoinQueryBuilder;

/**
 * Enrolled students widget.
 *
 * @since 4.9.0
 */
class Latest_Enrollees extends Users implements Interfaces\Requires_Post {
	use Supports_Post;

	/**
	 * Returns the limit of latest enrollees to display.
	 *
	 * @since 4.9.0
	 *
	 * @return int
	 */
	protected static function get_users_limit(): int {
		/**
		 * Filters the limit of latest enrollees to display.
		 *
		 * @since 4.9.0
		 *
		 * @param int $users_limit The limit of latest enrollees to display.
		 *
		 * @return int
		 */
		return apply_filters( 'learndash_dashboard_widget_course_latest_enrollees_users_limit', 7 );
	}

	/**
	 * Loads required data.
	 *
	 * @since 4.9.0
	 *
	 * @return void
	 */
	protected function load_data(): void {
		$this->set_custom_label_property( 'enrolled_at' );

		$latest_enrollees = $this->get_latest_enrollees( self::get_users_limit() );

		if ( empty( $latest_enrollees ) ) {
			return;
		}

		$users = get_users(
			[
				'fields'  => [ 'ID', 'display_name', 'user_email' ],
				'include' => array_keys( $latest_enrollees ),
				'orderby' => 'include',
			]
		);

		foreach ( $users as $user ) {
			$user->{$this->get_custom_label_property()} = learndash_adjust_date_time_display(
				$latest_enrollees[ $user->ID ]
			);
		}

		$this->set_users( $users );
	}

	/**
	 * Returns the latest enrollee's user IDs and their enrolment dates.
	 *
	 * @since 4.9.0
	 *
	 * @param int $users_limit The limit of users to return.
	 *
	 * @return array<int,int> [user_id => enrolment_date]
	 */
	private function get_latest_enrollees( int $users_limit ): array {
		$group_based_latest_enrollees = $this->get_group_based_latest_enrollees( $users_limit );
		$directly_latest_enrollees    = $this->get_directly_latest_enrollees( $users_limit );

		// Order by the enrolment date and limit the result to the maximum number of users.

		$latest_enrollees = $group_based_latest_enrollees;

		foreach ( $directly_latest_enrollees as $user_id => $enrolled_at ) {
			// If the user is already on the list, we need to ensure we are using the latest enrolment date.
			if (
				! isset( $latest_enrollees[ $user_id ] )
				|| $enrolled_at < $latest_enrollees[ $user_id ]
			) {
				$latest_enrollees[ $user_id ] = $enrolled_at;
			}
		}

		arsort( $latest_enrollees );

		return array_slice( $latest_enrollees, 0, $users_limit, true );
	}

	/**
	 * Returns the latest enrollee's user IDs with direct access and their enrolment dates.
	 *
	 * @since 4.9.0
	 *
	 * @param int $users_limit The limit of users to return.
	 *
	 * @return array<int,int> [user_id => enrolment_date]
	 */
	private function get_directly_latest_enrollees( int $users_limit ): array {
		$query_result = DB::table( 'usermeta' )
			->select( 'user_id as ID', 'MAX(meta_value) as enrolled_at' )
			->where( 'meta_key', "learndash_course_{$this->get_post()->ID}_enrolled_at" )
			->orWhere( 'meta_key', "course_{$this->get_post()->ID}_access_from" )
			->groupBy( 'user_id' )
			->orderBy( 'enrolled_at', 'DESC' )
			->limit( $users_limit )
			->getAll();

		// Preparing the result.

		$latest_enrollees = [];

		foreach ( (array) $query_result as $row ) {
			$latest_enrollees[ (int) $row->ID ] = (int) $row->enrolled_at;
		}

		return $latest_enrollees;
	}

	/**
	 * Returns the latest enrollee's user IDs with access via group and their enrolment dates.
	 *
	 * @since 4.9.0
	 *
	 * @param int $users_limit The limit of users to return.
	 *
	 * @return array<int,int> [user_id => enrolment_date]
	 */
	private function get_group_based_latest_enrollees( int $users_limit ): array {
		$course_groups_ids = learndash_get_course_groups( $this->get_post()->ID );

		if ( empty( $course_groups_ids ) ) {
			return [];
		}

		$query_result = DB::table( 'usermeta' )
			->select( 'user_id as ID', 'MIN(meta_value) AS enrolled_at' )
			->whereIn(
				'meta_key',
				array_map(
					function( $course_group_id ) {
						return "learndash_group_{$course_group_id}_enrolled_at";
					},
					$course_groups_ids
				)
			)
			->groupBy( 'user_id' )
			->orderBy( 'meta_value', 'DESC' )
			->limit( $users_limit )
			->getAll();

		// Preparing the result.

		$latest_enrollees = [];

		foreach ( (array) $query_result as $row ) {
			$latest_enrollees[ (int) $row->ID ] = (int) $row->enrolled_at;
		}

		return $latest_enrollees;
	}

	/**
	 * Returns a widget empty state text. It is used when there is no data to show.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_empty_state_text(): string {
		return __( 'No one joined yet.', 'learndash' );
	}
}
