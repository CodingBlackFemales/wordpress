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
use LearnDash\Core\Models\Course;
use LearnDash\Core\Models\DTO;
use LearnDash\Core\Models\Lesson;
use LearnDash\Core\Models\Quiz;
use LearnDash\Core\Models\Step;
use LearnDash\Core\Models\Steps\Step_Visibility;
use LearnDash\Core\Models\Topic;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\DB\DB;
use WP_User;

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
	 * Returns all step IDs of a course in a linear (flattened) order, filtered by the user's visibility.
	 *
	 * The linear order is always relative to a Course. When called from a Course, that course is used.
	 * When called from a step model (e.g. a Lesson or Topic), the course is resolved from the explicit
	 * `$course_id` argument, falling back to the step's own course. If no course can be resolved, an
	 * empty array is returned.
	 *
	 * Steps the user is not allowed to see (e.g. drafts for a regular student) are removed, and a step
	 * under a non-visible ancestor (e.g. a topic or quiz under a draft lesson) is removed too, so
	 * navigation and "Resume Course" never land on an inaccessible step.
	 *
	 * @since 5.1.5
	 *
	 * @param WP_User|int|null $user      The user to control steps visibility. If null or empty, the current user is used.
	 * @param int              $course_id Optional. The course to read the linear steps from. Defaults to the course
	 *                                    resolved from the model (the model itself when it is a Course).
	 *
	 * @return int[] Array of step IDs.
	 */
	public function get_linear_step_ids( $user = null, int $course_id = 0 ): array {
		$user    = $this->map_user( $user );
		$user_id = $user instanceof WP_User ? $user->ID : Cast::to_int( $user );

		// The linear order is always relative to a Course. Resolve which course to read from.
		if ( $course_id <= 0 ) {
			if ( $this instanceof Step ) {
				$course    = $this->get_course();
				$course_id = $course ? $course->get_id() : 0;
			} elseif ( is_a( $this, Course::class ) ) { // We need to use is_a() here as a class that extends Step will never be a Course.
				$course_id = $this->get_id();
			}
		}

		if ( $course_id <= 0 ) {
			return [];
		}

		$step_ids = [];

		// This handler is legacy. We should aim to refactor it out in the future.
		$legacy_course_steps_handler = LDLMS_Factory_Post::course_steps( $course_id );

		if ( $legacy_course_steps_handler instanceof LDLMS_Course_Steps ) {
			// Extracting post ids from the flattened steps, e.g. "sfwd-lessons:272".
			foreach ( $legacy_course_steps_handler->get_steps( 'l' ) as $step_with_post_type_prefix ) {
				[ , $step_id ] = explode(
					':',
					Cast::to_string( $step_with_post_type_prefix ) // Casting to be safe.
				);

				$step_id = Cast::to_int( $step_id );

				if ( $step_id > 0 ) {
					$step_ids[] = $step_id;
				}
			}

			$user_object = $user instanceof WP_User ? $user : get_user_by( 'id', $user_id );

			$step_ids = $this->filter_reachable_step_ids_in_linear_order(
				$step_ids,
				Step_Visibility::filter(
					$step_ids,
					$user_object instanceof WP_User ? $user_object : null
				),
				$legacy_course_steps_handler
			);
		}

		/**
		 * Filters the visibility-aware linear step IDs for a course.
		 *
		 * @since 5.1.5
		 *
		 * @param int[]       $step_ids Step IDs.
		 * @param Course|Step $model    The model the linear steps were requested from.
		 * @param int         $user_id  The user ID used to control steps visibility.
		 *
		 * @return int[] Step IDs.
		 */
		return apply_filters( "learndash_model_{$this->get_post_type_key()}_linear_step_ids", $step_ids, $this, $user_id );
	}

	/**
	 * Returns the reachable step IDs in Linear Course Progression order.
	 *
	 * This is needed on top of {@see Step_Visibility::filter()} for two reasons:
	 *
	 * 1. {@see Step_Visibility::filter()} only reports which steps are individually visible; it does
	 *    not enforce ancestor reachability. A step is only reachable when all of its ancestor steps
	 *    are visible too (e.g. a topic or quiz under a draft lesson must not be reachable), so the
	 *    ancestry has to be checked here.
	 * 2. {@see Step_Visibility::filter()} does not necessarily return the steps in the Linear Course
	 *    Progression order. This method preserves the order of the incoming `$step_ids`, which is the
	 *    flattened linear order, so navigation and "Resume Course" stay consistent.
	 *
	 * @since 5.1.5
	 *
	 * @param int[]              $step_ids         All step IDs in linear order.
	 * @param int[]              $visible_step_ids Step IDs that are visible to the user (a subset of $step_ids).
	 * @param LDLMS_Course_Steps $steps_handler    The course steps handler, used to read the already-loaded
	 *                                             ancestry map without per-step function calls.
	 *
	 * @return int[]
	 */
	private function filter_reachable_step_ids_in_linear_order( array $step_ids, array $visible_step_ids, LDLMS_Course_Steps $steps_handler ): array {
		$visible_lookup = array_flip( $visible_step_ids );

		// Build a complete step-id → parent-ids map in one pass over the reverse-lookup table
		// that is already held in memory by the course steps handler.
		// steps['r'] maps "post_type:id" => ["ancestor_type:id", ...] for every step,
		// populated once by build_steps() during the get_steps('l') call above.
		// This avoids repeated learndash_course_get_all_parent_step_ids() calls (each of which
		// would pay for a get_post_type() lookup, string splitting, and array_reverse).
		$parent_map = [];
		foreach ( $steps_handler->get_steps( 'r' ) as $step_key => $ancestor_keys ) {
			[ , $step_id_str ] = explode( ':', Cast::to_string( $step_key ) );
			$step_id           = Cast::to_int( $step_id_str );

			if ( $step_id > 0 ) {
				$parent_ids = [];
				foreach ( $ancestor_keys as $ancestor_key ) {
					[ , $parent_id_str ] = explode( ':', Cast::to_string( $ancestor_key ) );
					$parent_ids[]        = Cast::to_int( $parent_id_str );
				}
				$parent_map[ $step_id ] = $parent_ids;
			}
		}

		return array_values(
			array_filter(
				$step_ids,
				function ( $step_id ) use ( $visible_lookup, $parent_map ) {
					if ( ! isset( $visible_lookup[ $step_id ] ) ) {
						return false;
					}

					foreach ( $parent_map[ $step_id ] ?? [] as $parent_id ) {
						if ( ! isset( $visible_lookup[ $parent_id ] ) ) {
							return false;
						}
					}

					return true;
				}
			)
		);
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
			$post_ids = $this->filter_by_visibility( $post_ids );
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
	 * @since 5.1.5 Delegates to the static {@see Step_Visibility::filter()}, which supports mixed post types
	 *            and resolves the capability from each step's post type, so a post type no longer needs to
	 *            be passed in.
	 *
	 * @param int[] $post_ids Post IDs that we're filtering.
	 *
	 * @return int[]
	 */
	private function filter_by_visibility( array $post_ids ): array {
		// Default to the current user for visibility.
		if ( ! $this->steps_visibility_user ) {
			$this->limit_steps_visibility_to_user( wp_get_current_user() );
		}

		return Step_Visibility::filter( $post_ids, $this->steps_visibility_user );
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
