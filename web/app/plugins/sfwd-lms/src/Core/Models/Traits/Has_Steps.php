<?php
/**
 * Trait for models that may have course steps attached.
 *
 * @since 4.21.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models\Traits;

use LDLMS_Course_Steps;
use LDLMS_DB;
use LDLMS_Factory_Post;
use LDLMS_Post_Types;
use LearnDash\Core\Mappers\Models\Step_Mapper;
use LearnDash\Core\Models\Course;
use LearnDash\Core\Models\Lesson;
use LearnDash\Core\Models\Quiz;
use LearnDash\Core\Models\Step;
use LearnDash\Core\Models\Topic;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\DB\DB;
use WP_Post_Type;
use WP_User;
use LearnDash\Core\Models\DTO;

/**
 * Trait for models that may have course steps attached.
 *
 * @since 4.21.0
 */
trait Has_Steps {
	/**
	 * The user to control steps visibility. Default null, to not restrict visibility.
	 *
	 * @since 4.21.0
	 *
	 * @var WP_User|null
	 */
	private ?WP_User $steps_visibility_user = null;

	/**
	 * Sets the user to control steps visibility.
	 *
	 * @since 4.21.0
	 *
	 * @param WP_User $user The user.
	 *
	 * @return void
	 */
	public function limit_steps_visibility_to_user( WP_User $user ): void {
		$this->steps_visibility_user = $user;
	}

	/**
	 * Returns related step models of a specific step post type.
	 *
	 * @since 4.21.0
	 *
	 * @param string $step_post_type The step post type.
	 * @param int    $limit          Limit. Default 0.
	 * @param int    $offset         Offset. Default 0.
	 * @param bool   $with_nested    Whether to include nested steps. Default false.
	 *
	 * @return Step[]
	 */
	protected function get_steps(
		string $step_post_type,
		int $limit = 0,
		int $offset = 0,
		bool $with_nested = false
	): array {
		$step_model_class = $this->map_step_model_class_from_post_type( $step_post_type );

		if ( is_null( $step_model_class ) ) {
			return [];
		}

		return $step_model_class::find_many(
			$this->get_step_post_ids( $step_post_type, $limit, $offset, $with_nested )
		);
	}

	/**
	 * Returns the total number of related steps of a specific step post type.
	 *
	 * @since 4.21.0
	 *
	 * @param string $step_post_type The step post type.
	 * @param bool   $with_nested    Whether to include nested steps. Default true.
	 *
	 * @return int
	 */
	protected function get_steps_number( string $step_post_type, bool $with_nested = true ): int {
		return count(
			$this->get_step_post_ids( $step_post_type, 0, 0, $with_nested )
		);
	}

	/**
	 * Returns the last activity for a step, including its child steps.
	 *
	 * @since 4.24.0
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return DTO\Last_Activity|null Last activity DTO. Null if no activity found.
	 */
	public function get_last_activity( $user = null ): ?DTO\Last_Activity {
		$user    = $this->map_user( $user );
		$user_id = $user instanceof WP_User ? $user->ID : $user;

		$course    = $this instanceof Course ? $this : $this->get_course();
		$course_id = 0;

		if ( $course ) {
			$course_id = $course->get_id();
		}

		$child_step_ids         = [];
		$page_size              = 100;
		$course_step_post_types = LDLMS_Post_Types::get_post_types( 'course_steps' );

		foreach ( $course_step_post_types as $course_step_post_type ) {
			$offset = 0;

			$post_type_post_ids = $this->get_step_post_ids( $course_step_post_type, $page_size, $offset, true );

			while ( ! empty( $post_type_post_ids ) ) {
				$child_step_ids = array_merge(
					$child_step_ids,
					$post_type_post_ids
				);

				$offset += $page_size;

				$post_type_post_ids = $this->get_step_post_ids( $course_step_post_type, $page_size, $offset, true );
			}
		}

		$post_ids = array_merge(
			[ $this->get_id() ],
			$child_step_ids
		);

		$last_activity_row = DB::table(
			DB::raw( LDLMS_DB::get_table_name( 'user_activity' ) )
		)
		->select(
			[ 'activity_completed', 'completed_timestamp' ],
			[ 'activity_started', 'started_timestamp' ],
			'course_id',
			'post_id'
		)
		->where( 'user_id', $user_id )
		->where( 'course_id', $course_id )
		->whereIn( 'post_id', $post_ids )
		->whereIn( 'activity_type', [ 'lesson', 'topic', 'quiz' ] )
		->where( 'activity_completed', 0, '>' ) // Ensure we only return completed activities.
		->orderBy( 'activity_completed', 'DESC' )
		->limit( 1 )
		->get();

		$last_activity = null;

		if ( ! empty( $last_activity_row ) ) {
			$last_activity = DTO\Last_Activity::create( (array) $last_activity_row );
		}

		/**
		 * Filters the last activity for a step, including its child steps.
		 *
		 * @since 4.24.0
		 *
		 * @param DTO\Last_Activity|null $last_activity Last activity DTO. Null if no activity found.
		 * @param Step|Course            $model         The model.
		 * @param WP_User|int            $user          The user ID or WP_User. If null or empty, the current user is used.
		 *
		 * @return DTO\Last_Activity|null Last activity DTO. Null if no activity found.
		 */
		return apply_filters( "learndash_model_{$this->get_post_type_key()}_last_activity", $last_activity, $this, $user );
	}

	/**
	 * Returns the related Step Post IDs.
	 *
	 * @since 4.21.0
	 *
	 * @param string $post_type   The step post type.
	 * @param int    $limit       Limit. Default 0.
	 * @param int    $offset      Offset. Default 0.
	 * @param bool   $with_nested Whether to include nested steps. Default false.
	 *
	 * @return int[]
	 */
	private function get_step_post_ids(
		string $post_type,
		int $limit = 0,
		int $offset = 0,
		bool $with_nested = false
	): array {
		if ( $this instanceof Step ) {
			$course = $this->get_course();

			if ( ! $course ) {
				return [];
			}

			$course_id = $course->get_id();
			$parent_id = $this->get_id();
		} elseif ( is_a( $this, Course::class ) ) { // We need to use is_a() here as class that extends Step will never be a Course.
			$course_id = $this->get_id();
			$parent_id = $course_id;
		} else {
			return [];
		}

		$legacy_course_steps_handler = LDLMS_Factory_Post::course_steps( $course_id );

		if ( ! $legacy_course_steps_handler instanceof LDLMS_Course_Steps ) {
			return [];
		}

		$post_ids = $legacy_course_steps_handler->get_children_steps(
			$parent_id,
			$post_type,
			'ids',
			$with_nested
		);

		if (
			/**
			 * Filters whether we should filter steps by the current user's visibility.
			 *
			 * @since 4.21.0
			 *
			 * @param bool        $filter_by_visibility Whether to filter steps by visibility.
			 * @param string      $post_type            The step post type.
			 * @param int         $limit                Limit.
			 * @param int         $offset               Offset.
			 * @param bool        $with_nested          Whether to include nested steps.
			 * @param Course|Step $model                The model.
			 *
			 * @return bool
			 */
			apply_filters(
				"learndash_model_{$this->get_post_type_key()}_steps_filter_by_visibility",
				true,
				$post_type,
				$limit,
				$offset,
				$with_nested,
				$this
			)
		) {
			$post_ids = $this->filter_by_visibility(
				$post_ids,
				$post_type
			);
		}

		$post_ids = array_map( 'intval', $post_ids );

		return array_slice( $post_ids, $offset, $limit > 0 ? $limit : null );
	}

	/**
	 * Filters the given Post IDs based on the set user's visibility.
	 * The set user defaults to the logged in user, but it can be changed using limit_steps_visibility_to_user().
	 *
	 * When a User Object is set:
	 *  - If they are an Admin, we don't restrict visibility.
	 *  - For existing Users, we can restrict visibility using the `read_post` capability.
	 *  - For non-existing or not logged in Users, we instead restrict visibility
	 *    based on Post Status, only allowing 'publish' Posts.
	 *
	 * @since 4.21.0
	 *
	 * @param int[]  $post_ids  Post IDs that we're filtering.
	 * @param string $post_type Post Type we're filtering results for.
	 *
	 * @return int[]
	 */
	private function filter_by_visibility( array $post_ids, string $post_type ): array {
		// Default to the current user for visibility.
		if ( ! $this->steps_visibility_user ) {
			$this->limit_steps_visibility_to_user( wp_get_current_user() );
		}

		$user_id = $this->steps_visibility_user instanceof WP_User
			? $this->steps_visibility_user->ID
			: null;

		if (
			empty( $post_ids )
			// User ID is null only when a User Object is not set. A User Object is set even for not logged in users.
			|| $user_id === null
			|| learndash_is_admin_user( $user_id )
		) {
			return $post_ids;
		}

		// For existing Users, we can restrict visibility using the `read_post` capability.
		if ( $user_id > 0 ) {
			$post_type_object = get_post_type_object( $post_type );

			$capability = 'read_post';
			if ( $post_type_object instanceof WP_Post_Type ) {
				$capability = $post_type_object->cap->read_post;
			}

			return array_filter(
				$post_ids,
				function ( $post_id ) use ( $user_id, $capability ) {
					return user_can( $user_id, $capability, $post_id );
				}
			);
		}

		/**
		 * For non-existing or not logged in Users (User ID 0), we have to restrict visibility based on Post Status.
		 *
		 * We use array_intersect() to ensure that the order of the results in preserved.
		 */
		return array_intersect(
			$post_ids,
			DB::get_col(
				DB::table( 'posts' )
					->select( 'ID' )
					->where( 'post_type', $post_type )
					->where( 'post_status', 'publish' )
					->whereIn( 'ID', $post_ids )
					->getSQL()
			)
		);
	}

	/**
	 * Maps a step model class from a post type.
	 *
	 * @since 4.21.0
	 *
	 * @param string $post_type The post type.
	 *
	 * @return class-string|null
	 */
	private function map_step_model_class_from_post_type( string $post_type ): ?string {
		switch ( $post_type ) {
			case LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON ):
				return Lesson::class;
			case LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ):
				return Quiz::class;
			case LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC ):
				return Topic::class;
			default:
				return null;
		}
	}
}
