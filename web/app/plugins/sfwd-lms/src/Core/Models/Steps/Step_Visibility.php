<?php
/**
 * Filters course step IDs based on a user's visibility.
 *
 * @since 5.1.5
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models\Steps;

use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\DB\DB;
use WP_Post_Type;
use WP_User;

/**
 * Filters course step IDs based on a user's visibility.
 *
 * Unlike the per-post-type filtering historically done inside the Has_Steps trait,
 * this accepts a flat list of step IDs of mixed post types (e.g. lessons, topics and quizzes
 * mixed together in a linear course order) and keeps only the steps the user is allowed to see.
 *
 * This is the visibility filtering used by the step-related model methods. It is a plain stateless
 * utility (no object to construct) because it is just a basic filter.
 *
 * Visibility rules:
 *  - Admins are not restricted.
 *  - For existing Users, visibility is restricted using the `read_post` capability of each step's post type.
 *  - For not logged in Users (User ID 0), visibility is restricted based on Post Status, only allowing `publish` Posts.
 *
 * @since 5.1.5
 */
class Step_Visibility {
	/**
	 * Filters the given step IDs based on the user's visibility, preserving the original order.
	 *
	 * @since 5.1.5
	 *
	 * @param int[]        $step_ids Step IDs that we're filtering.
	 * @param WP_User|null $user     The user to control steps visibility. Defaults to the current user.
	 *
	 * @return int[]
	 */
	public static function filter( array $step_ids, ?WP_User $user = null ): array {
		// A User Object is set even for not logged in users (ID 0), so it is always available here.
		$user    = $user ?? wp_get_current_user();
		$user_id = $user->ID;

		if (
			empty( $step_ids )
			|| learndash_is_admin_user( $user_id )
		) {
			return $step_ids;
		}

		// For existing Users, we can restrict visibility using the `read_post` capability.
		if ( $user_id > 0 ) {
			return array_values(
				array_filter(
					$step_ids,
					fn( $step_id ) => self::user_can_read( $user_id, Cast::to_int( $step_id ) )
				)
			);
		}

		/**
		 * For not logged in Users (User ID 0), we restrict visibility based on Post Status.
		 *
		 * We use array_intersect() to ensure that the order of the results is preserved.
		 */
		return array_values(
			array_intersect(
				$step_ids,
				DB::get_col(
					DB::table( 'posts' )
						->select( 'ID' )
						->where( 'post_status', 'publish' )
						->whereIn( 'ID', $step_ids )
						->getSQL()
				)
			)
		);
	}

	/**
	 * Returns whether the user can read the given step, resolving the capability from the step's post type.
	 *
	 * @since 5.1.5
	 *
	 * @param int $user_id User ID.
	 * @param int $step_id Step post ID.
	 *
	 * @return bool
	 */
	private static function user_can_read( int $user_id, int $step_id ): bool {
		$post_type_object = get_post_type_object( Cast::to_string( get_post_type( $step_id ) ) );

		$capability = 'read_post';
		if ( $post_type_object instanceof WP_Post_Type ) {
			$capability = $post_type_object->cap->read_post;
		}

		return user_can( $user_id, $capability, $step_id );
	}
}
