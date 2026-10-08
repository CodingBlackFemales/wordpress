<?php
/**
 * Report export learner enumeration.
 *
 * @since 5.1.10
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports\Export;

use LDLMS_DB;
use LearnDash\Core\Utilities\Cast;
use LearnDash_Settings_Section;
use StellarWP\Learndash\StellarWP\DB\DB;
use StellarWP\Learndash\StellarWP\DB\QueryBuilder\QueryBuilder;

/**
 * Enumerates the distinct learners that have activity of a given type.
 *
 * Report exports historically enumerated users via a role-based `WP_User_Query`
 * (`learndash_get_report_user_ids()`), which is capped and times out on sites with a large user
 * base. This reads the distinct `user_id`s already present in the activity table instead — the
 * exact set an export needs.
 *
 * @since 5.1.10
 */
final class User_Enumeration {
	/**
	 * Administrator user IDs excluded from report enumeration, resolved once and cached
	 * for the lifetime of this instance.
	 *
	 * @since 5.1.10
	 *
	 * @var int[]|null
	 */
	private ?array $admin_user_ids = null;

	/**
	 * Returns every distinct learner ID that has activity of the given types, ascending.
	 *
	 * The result is unbounded: the caller holds the ID list and slices it per chunk. That costs
	 * ~48 bytes per learner, so a full-site list can grow into several megabytes — larger than a
	 * single chunk's row data — but it avoids re-scanning the activity table once per chunk.
	 *
	 * @since 5.1.10
	 *
	 * @param string[] $activity_types Activity types to match, e.g. ['quiz'] or ['course'].
	 *
	 * @return int[] Ascending distinct user IDs; empty when none match.
	 */
	public function get_all_activity_user_ids( array $activity_types ): array {
		$query = DB::table( DB::raw( LDLMS_DB::get_table_name( 'user_activity' ) ) )
			->distinct()
			->select( 'user_id' )
			->whereIn( 'activity_type', $activity_types )
			->orderBy( 'user_id', 'ASC' );

		$this->exclude_admin_users( $query );

		return array_map( [ Cast::class, 'to_int' ], DB::get_col( $query->getSQL() ) );
	}

	/**
	 * Adds the admin-exclusion WHERE clause to a query builder, unless the site is
	 * configured to include admin users in reports.
	 *
	 * @since 5.1.10
	 *
	 * @param QueryBuilder $query Query builder to constrain in place.
	 *
	 * @return void
	 */
	private function exclude_admin_users( QueryBuilder $query ): void {
		if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'reports_include_admin_users' ) ) {
			return;
		}

		$admin_user_ids = $this->get_admin_user_ids();

		if ( empty( $admin_user_ids ) ) {
			return;
		}

		$query->whereNotIn( 'user_id', $admin_user_ids );
	}

	/**
	 * Resolves and caches the administrator user IDs to exclude from report enumeration.
	 *
	 * Reads the capabilities usermeta directly (bounded to the handful of admin accounts a
	 * site has) rather than a role-based `WP_User_Query`, which returns nothing at scale.
	 *
	 * @since 5.1.10
	 *
	 * @return int[] Administrator user IDs.
	 */
	private function get_admin_user_ids(): array {
		if ( null !== $this->admin_user_ids ) {
			return $this->admin_user_ids;
		}

		global $wpdb;

		$query = DB::table( DB::raw( $wpdb->usermeta ) )
			->select( 'user_id' )
			->where( 'meta_key', $wpdb->prefix . 'capabilities' )
			->whereLike( 'meta_value', '%administrator%' );

		$this->admin_user_ids = array_map( [ Cast::class, 'to_int' ], DB::get_col( $query->getSQL() ) );

		return $this->admin_user_ids;
	}
}
