<?php
/**
 * This class provides the easy way to operate a course.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

namespace LearnDash\Core\Models;

use LDLMS_Post_Types;
use LearnDash\Core\Models\Traits\Has_Quizzes;
use LearnDash\Core\Models\Traits\Has_Materials;
use WP_User;

/**
 * Course model class.
 *
 * @since 4.6.0
 */
class Course extends Post implements Interfaces\Product {
	use Has_Quizzes {
		get_quizzes as get_quizzes_from_trait;
		get_quizzes_number as get_quizzes_number_from_trait;
	}
	use Has_Materials;

	/**
	 * Returns allowed post types.
	 *
	 * @since 4.6.0
	 *
	 * @return string[]
	 */
	public static function get_allowed_post_types(): array {
		return array(
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ),
		);
	}

	/**
	 * Returns instructors.
	 *
	 * @since 4.6.0
	 *
	 * @return Instructor[]
	 */
	public function get_instructors(): array {
		/**
		 * Filters course instructors.
		 *
		 * @since 4.6.0
		 *
		 * @param Instructor[] $instructors Instructors.
		 * @param Course       $course      Course model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_course_instructors',
			$this->memoize(
				function(): array {
					$post_author = get_userdata( (int) $this->get_post()->post_author );

					if ( ! $post_author ) {
						return [];
					}

					return [
						Instructor::create_from_user( $post_author ),
					];
				}
			),
			$this
		);
	}

	/**
	 * Returns a product model based on the course.
	 *
	 * @since 4.6.0
	 *
	 * @return Product
	 */
	public function get_product(): Product {
		/**
		 * Filters a course product.
		 *
		 * @since 4.6.0
		 *
		 * @param Product $product Product model.
		 * @param Course  $course  Course model.
		 *
		 * @return Product Course product model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_course_product',
			$this->memoize(
				function(): Product {
					$product = Product::create_from_post( $this->get_post() );

					if ( $this->memoization_is_enabled() ) {
						$product->enable_memoization();
					}

					return $product;
				}
			),
			$this
		);
	}

	/**
	 * Returns related lessons models.
	 *
	 * @since 4.6.0
	 *
	 * @param int $limit  Optional. Limit. Default is 0 which will be changed with LD settings.
	 * @param int $offset Optional. Offset. Default 0.
	 *
	 * @return Lesson[]
	 */
	public function get_lessons( int $limit = 0, int $offset = 0 ): array {
		/**
		 * Filters course lessons.
		 *
		 * @since 4.6.0
		 *
		 * @param Lesson[] $lessons Lessons.
		 * @param Course   $course  Course model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_course_lessons',
			$this->memoize(
				function() use ( $limit, $offset ): array {
					// TODO: This must be refactored to the direct call to the instance with disabling steps objects loading.
					$lesson_ids = learndash_course_get_children_of_step(
						$this->get_id(),
						0,
						LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON )
					);

					// TODO: Lesson model has a similar logic, maybe refactor.

					// TODO: Here we need to remove those lessons that a user can't read. We need to refactor it, it can be an additional call to filter by statuses, but a loop is too inefficient.
					// It was done in the legacy code with the following method:
					// protected function can_user_read_step( $step_post_id = 0 ) {
					//
					// if ( ! empty( $lesson_ids ) ) {
					// foreach ( $lesson_ids as $lesson_id => $lesson_post ) {
					// if ( ! $this->can_user_read_step( $lesson_post->ID ) ) {
					// unset( $lessons[ $lesson_id ] );
					// }
					// }
					// }.

					$lesson_ids = array_slice( $lesson_ids, $offset, $limit > 0 ? $limit : null );

					return Lesson::find_many( $lesson_ids );
				}
			),
			$this
		);
	}

	/**
	 * Returns related quizzes models.
	 *
	 * @since 4.6.0
	 *
	 * @param int $limit  Optional. Limit. Default is 0 which will be changed with LD settings.
	 * @param int $offset Optional. Offset. Default 0.
	 *
	 * @return Quiz[]
	 */
	public function get_quizzes( int $limit = 0, int $offset = 0 ): array {
		/**
		 * Filters course quizzes.
		 *
		 * @since 4.6.0
		 *
		 * @param Quiz[] $quizzes Quizzes.
		 * @param Course $course  Course model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_course_quizzes',
			$this->get_quizzes_from_trait( $limit, $offset ),
			$this
		);
	}

	/**
	 * Returns a certificate link for a user.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_User $user User.
	 *
	 * @return string
	 */
	public function get_certificate_link( WP_User $user ): string {
		/**
		 * Filters a course certificate link.
		 *
		 * @since 4.6.0
		 *
		 * @param string  $url    Course certificate link.
		 * @param Course  $course Course model.
		 * @param WP_User $user   User.
		 *
		 * @return string Course certificate link.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_course_certificate_link',
			$this->memoize(
				function() use ( $user ): string {
					return learndash_get_course_certificate_link( $this->get_id(), $user->ID );
				}
			),
			$this,
			$user
		);
	}

	/**
	 * Returns a status slug for a user.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_User $user User.
	 *
	 * @return string
	 */
	public function get_status_slug( WP_User $user ): string {
		/**
		 * Filters a course status slug.
		 *
		 * @since 4.6.0
		 *
		 * @param string  $slug   Course status slug.
		 * @param Course  $course Course model.
		 * @param WP_User $user   User.
		 *
		 * @return string Course status slug.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_course_status_slug',
			$this->memoize(
				function() use ( $user ): string {
					return learndash_course_status( $this->get_id(), $user->ID, true );
				}
			),
			$this,
			$user
		);
	}

	/**
	 * Returns the total number of steps.
	 *
	 * TODO: Maybe this method is not needed.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function get_total_steps_number(): int {
		/**
		 * Filters a number of course steps.
		 *
		 * @since 4.6.0
		 *
		 * @param int    $steps_number Course steps number.
		 * @param Course $course       Course model.
		 *
		 * @return int Course steps number.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_course_steps_number_total',
			$this->memoize(
				function(): int {
					return learndash_get_course_steps_count( $this->get_id() );
				}
			),
			$this
		);
	}

	/**
	 * Returns the total number of related lessons.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function get_lessons_number(): int {
		/**
		 * Filters course lessons number.
		 *
		 * @since 4.6.0
		 *
		 * @param int    $number Number of lessons.
		 * @param Course $course Course model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_course_lessons_number',
			$this->memoize(
				function(): int {
					return count(
						// TODO: Inefficient, we need to refactor it to the direct call to the instance with disabling steps objects loading.
						learndash_course_get_children_of_step(
							$this->get_id(),
							0,
							LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON )
						)
					);
				}
			),
			$this
		);
	}

	/**
	 * Returns the total number of related quizzes.
	 *
	 * @since 4.6.0
	 *
	 * @param bool $with_nested Optional. Whether to include nested quizzes. Default false.
	 *
	 * @return int
	 */
	public function get_quizzes_number( bool $with_nested = false ): int {
		/**
		 * Filters course quizzes number.
		 *
		 * @since 4.6.0
		 *
		 * @param int    $number Number of quizzes.
		 * @param Course $course Course model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_course_quizzes_number',
			$this->get_quizzes_number_from_trait( $with_nested ),
			$this
		);
	}

	/**
	 * Returns the number of steps a user has completed.
	 *
	 * TODO: Maybe this method is not needed.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_User $user User.
	 *
	 * @return int
	 */
	public function get_completed_steps_number( WP_User $user ): int {
		/**
		 * Filters a number of course steps.
		 *
		 * @since 4.6.0
		 *
		 * @param int    $steps_number Course steps number.
		 * @param Course $course       Course model.
		 *
		 * @return int Course steps number.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_course_steps_number_completed',
			$this->memoize(
				function() use ( $user ): int {
					// TODO: Probably inefficient, check.
					return learndash_course_get_completed_steps( $user->ID, $this->get_id() );
				}
			),
			$this
		);
	}

	/**
	 * Returns the progress percentage for a user.
	 *
	 * TODO: Maybe this method is not needed.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_User $user User.
	 *
	 * @return int
	 */
	public function get_progress_percentage( WP_User $user ): int {
		/**
		 * Filters course progress percentage.
		 *
		 * @since 4.6.0
		 *
		 * @param int    $progress_percentage Course progress percentage.
		 * @param Course $course              Course model.
		 *
		 * @return int Course progress percentage.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_course_progress_percentage',
			$this->memoize(
				function() use ( $user ): int {
					$steps_total_number = $this->get_total_steps_number();

					if ( $steps_total_number === 0 ) {
						return 0;
					}

					$steps_completed_number = $this->get_completed_steps_number( $user );

					if ( $steps_completed_number >= $steps_total_number ) {
						return 100;
					}

					return intval( $this->get_completed_steps_number( $user ) * 100 / $steps_total_number );
				}
			),
			$this
		);
	}
}
