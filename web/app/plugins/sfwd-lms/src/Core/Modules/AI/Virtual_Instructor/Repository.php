<?php
/**
 * Repository class file for virtual instructor.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor;

use LDLMS_Post_Types;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\DB\DB;
use StellarWP\Learndash\StellarWP\DB\QueryBuilder\WhereQueryBuilder;
use WP_Post;

/**
 * Repository class for virtual instructor.
 *
 * @since 4.13.0
 */
class Repository {
	/**
	 * Get virtual instructor post by course ID.
	 *
	 * @since 4.13.0
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return ?WP_Post
	 */
	public static function get_by_course_id( int $course_id ): ?WP_Post {
		$group_ids = learndash_get_course_groups( $course_id );

		$id = self::get_by_specific_course_setting( $course_id )
			?? self::get_by_specific_group_setting( $group_ids )
			?? self::get_by_global_group_setting( $group_ids )
			?? self::get_by_global_course_setting();

		if ( $id === null ) {
			return null;
		}

		$post = get_post( $id );

		if ( ! $post instanceof WP_Post ) {
			return null;
		}

		return $post;
	}

	/**
	 * Get virtual instructor ID by virtual instructor specific associated course setting.
	 *
	 * @since 4.13.0
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return ?int
	 */
	private static function get_by_specific_course_setting( int $course_id ): ?int {
		$id = DB::get_var(
			DB::table( 'posts', 'posts' )
				->select( 'posts.ID' )
				->leftJoin( 'postmeta', 'posts.ID', 'postmeta.post_id', 'postmeta' )
				->where( 'posts.post_type', learndash_get_post_type_slug( LDLMS_Post_Types::VIRTUAL_INSTRUCTOR ) )
				->where( 'posts.post_status', 'publish' )
				->where(
					function ( WhereQueryBuilder $builder ) use ( $course_id ) {
						$builder
							// $course_id is in the list of course ids.
							->where( 'postmeta.meta_key', 'course_ids' )
							// Wrap $course_id in ':$id;' to return exact match results.
							->whereLike(
								'postmeta.meta_value',
								Cast::to_string(
									DB::prepare( ':%d;', $course_id )
								)
							);
					}
				)
				->orderBy( 'posts.post_date', 'DESC' )
				->getSQL()
		);

		return $id ? (int) $id : null;
	}

	/**
	 * Get virtual instructor ID by virtual instructor specific associated group setting.
	 *
	 * @since 4.13.0
	 *
	 * @param array<int> $group_ids Group IDs a course belong to.
	 *
	 * @return ?int
	 */
	private static function get_by_specific_group_setting( array $group_ids ): ?int {
		if ( empty( $group_ids ) ) {
			return null;
		}

		$id = DB::get_var(
			DB::table( 'posts', 'posts' )
				->select( 'posts.ID' )
				->leftJoin( 'postmeta', 'posts.ID', 'postmeta.post_id', 'postmeta' )
				->where( 'posts.post_type', learndash_get_post_type_slug( LDLMS_Post_Types::VIRTUAL_INSTRUCTOR ) )
				->where( 'posts.post_status', 'publish' )
				->where(
					function ( WhereQueryBuilder $builder ) use ( $group_ids ) {
						// $course_id belongs to at least a group.

						foreach ( $group_ids as $group_id ) {
							$builder
								// $group_id is in the list of group ids.
								->where( 'postmeta.meta_key', 'group_ids' )
								// Wrap $course_id in ':$id;' to return exact match results.
								->whereLike(
									'postmeta.meta_value',
									Cast::to_string(
										DB::prepare( ':%d;', $group_id )
									)
								);
						}
					}
				)
				->orderBy( 'posts.post_date', 'DESC' )
				->getSQL()
		);

		return $id ? (int) $id : null;
	}

	/**
	 * Get virtual instructor ID by virtual instructor global associated groups setting.
	 *
	 * @since 4.13.0
	 *
	 * @param array<int> $group_ids Group IDs a course belong to.
	 *
	 * @return ?int
	 */
	private static function get_by_global_group_setting( array $group_ids ): ?int {
		if ( empty( $group_ids ) ) {
			return null;
		}

		$id = DB::get_var(
			DB::table( 'posts', 'posts' )
				->select( 'posts.ID' )
				->leftJoin( 'postmeta', 'posts.ID', 'postmeta.post_id', 'postmeta' )
				->where( 'posts.post_type', learndash_get_post_type_slug( LDLMS_Post_Types::VIRTUAL_INSTRUCTOR ) )
				->where( 'posts.post_status', 'publish' )
				// Virtual instructor is applied to all groups.
				->where( 'postmeta.meta_key', 'apply_to_all_groups' )
				->where( 'postmeta.meta_value', 'on' )
				->orderBy( 'posts.post_date', 'DESC' )
				->getSQL()
		);

		return $id ? (int) $id : null;
	}

	/**
	 * Get virtual instructor ID by virtual instructor global associated courses setting.
	 *
	 * @since 4.13.0
	 *
	 * @return ?int
	 */
	private static function get_by_global_course_setting(): ?int {
		$id = DB::get_var(
			DB::table( 'posts', 'posts' )
				->select( 'posts.ID' )
				->leftJoin( 'postmeta', 'posts.ID', 'postmeta.post_id', 'postmeta' )
				->where( 'posts.post_type', learndash_get_post_type_slug( LDLMS_Post_Types::VIRTUAL_INSTRUCTOR ) )
				->where( 'posts.post_status', 'publish' )
				->where( 'postmeta.meta_key', 'apply_to_all_courses' )
				->where( 'postmeta.meta_value', 'on' )
				->orderBy( 'posts.post_date', 'DESC' )
				->getSQL()
		);

		return $id ? (int) $id : null;
	}
}
