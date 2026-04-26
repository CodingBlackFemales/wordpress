<?php
/**
 * This class provides the easy way to operate a course.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Models;

use LDLMS_Post_Types;
use LearnDash\Core\Utilities\Cast;
use WP_User;

/**
 * Course model class.
 *
 * @since 4.6.0
 */
class Course extends Post {
	use Traits\Has_Materials;
	use Traits\Has_Quizzes;
	use Traits\Has_Topics;
	use Traits\Has_Steps;

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
		 * @since 4.21.0
		 *
		 * @param Product $product Product model.
		 * @param Course  $course  Course model.
		 *
		 * @return Product Course product model.
		 */
		return apply_filters(
			'learndash_model_course_product',
			Product::create_from_post( $this->get_post() ),
			$this
		);
	}

	/**
	 * Returns lessons that are a child of this course.
	 *
	 * @since 4.21.0
	 *
	 * @param int $limit  Optional. Limit. Default 0.
	 * @param int $offset Optional. Offset. Default 0.
	 *
	 * @return Lesson[]
	 */
	public function get_lessons( int $limit = 0, int $offset = 0 ): array {
		/**
		 * Lessons
		 *
		 * @var Lesson[] $lessons
		 */
		$lessons = $this->get_steps(
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON ),
			$limit,
			$offset
		);

		foreach ( $lessons as $lesson ) {
			$lesson->set_course( $this ); // This is used to optimize subsequent calls to $lesson->get_course().
		}

		/**
		 * Filters lessons that are a child of this course.
		 *
		 * @since 4.21.0
		 *
		 * @param Lesson[] $lessons Lessons.
		 * @param int      $limit   Limit. Default 0.
		 * @param int      $offset  Offset. Default 0.
		 * @param Course   $course  Course model.
		 *
		 * @return Lesson[] Lessons.
		 */
		return apply_filters(
			'learndash_model_course_lessons',
			$lessons,
			$limit,
			$offset,
			$this
		);
	}

	/**
	 * Returns the total number of lessons associated with a course.
	 *
	 * @since 4.21.0
	 *
	 * @return int
	 */
	public function get_lessons_number(): int {
		/**
		 * Filters lessons number associated with a course.
		 *
		 * @since 4.21.0
		 *
		 * @param int    $number Number of lessons.
		 * @param Course $course Course model.
		 *
		 * @return int Number of nested lessons.
		 */
		return apply_filters(
			'learndash_model_course_lessons_number',
			$this->get_steps_number(
				LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON )
			),
			$this
		);
	}

	/**
	 * Returns true if a course has steps, otherwise false.
	 *
	 * @since 4.21.0
	 *
	 * @return bool
	 */
	public function has_steps(): bool {
		/**
		 * Filters whether a course has steps.
		 *
		 * @since 4.21.0
		 *
		 * @param bool   $has_steps Whether a course has steps.
		 * @param Course $course    Course model.
		 *
		 * @return bool Whether a course has steps.
		 */
		return apply_filters(
			'learndash_model_course_has_steps',
			$this->get_lessons_number() > 0 || $this->get_quizzes_number() > 0,
			$this
		);
	}

	/**
	 * Returns true if a course has awards, otherwise false.
	 *
	 * @since 4.21.0
	 *
	 * @return bool
	 */
	public function has_awards(): bool {
		/**
		 * Filters whether a course has awards.
		 *
		 * @since 4.21.0
		 *
		 * @param bool   $has_awards Whether a course has awards.
		 * @param Course $course     Course model.
		 *
		 * @return bool Whether a course has awards.
		 */
		return apply_filters(
			'learndash_model_course_has_awards',
			$this->get_award_points() > 0 || $this->get_award_certificate(),
			$this
		);
	}

	/**
	 * Returns a certificate award or null if not set.
	 *
	 * @since 4.21.0
	 *
	 * @return Certificate|null
	 */
	public function get_award_certificate(): ?Certificate {
		$certificate_id = Cast::to_int(
			$this->getAttribute( '_ld_certificate' )
		);

		/**
		 * Filters a course certificate award.
		 *
		 * @since 4.21.0
		 *
		 * @param Certificate|null $certificate Certificate model or null if not found.
		 * @param Course           $course      Course model.
		 *
		 * @return Certificate|null Filters a course certificate award.
		 */
		return apply_filters(
			'learndash_model_course_award_certificate',
			Certificate::find( $certificate_id ),
			$this
		);
	}

	/**
	 * Returns points award.
	 *
	 * @since 4.21.0
	 *
	 * @return float
	 */
	public function get_award_points(): float {
		/**
		 * Filters course points award.
		 *
		 * @since 4.21.0
		 *
		 * @param float  $points Points award.
		 * @param Course $course Course model.
		 *
		 * @return float Points award.
		 */
		return apply_filters(
			'learndash_model_course_award_points',
			learndash_format_course_points(
				$this->getAttribute( 'course_points', 0.0 )
			),
			$this
		);
	}

	/**
	 * Returns a certificate link for a user.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return string
	 */
	public function get_certificate_link( $user = null ): string {
		$user    = $this->map_user( $user );
		$user_id = $user instanceof WP_User ? $user->ID : $user;

		/**
		 * Filters a course certificate link.
		 *
		 * @since 4.21.0
		 *
		 * @param string      $url    Course certificate link.
		 * @param Course      $course Course model.
		 * @param WP_User|int $user   The WP_User by default or the user ID if a user ID was passed explicitly to the filter's caller.
		 *
		 * @return string Course certificate link.
		 */
		return apply_filters(
			'learndash_model_course_certificate_link',
			learndash_get_course_certificate_link( $this->get_id(), $user_id ),
			$this,
			$user
		);
	}

	/**
	 * Returns true if a course has requirements, otherwise false.
	 *
	 * @since 4.21.0
	 *
	 * @return bool
	 */
	public function has_requirements(): bool {
		$has_requirements = $this->get_requirement_points() > 0;

		if ( ! $has_requirements ) {
			$requirement_prerequisite = $this->get_requirement_prerequisites();

			$has_requirements = is_array( $requirement_prerequisite ) && ! empty( $requirement_prerequisite['ids'] );
		}

		/**
		 * Filters whether a course has requirements.
		 *
		 * @since 4.21.0
		 *
		 * @param bool   $has_requirements Whether a course has requirements.
		 * @param Course $course           Course model.
		 *
		 * @return bool Whether a course has requirements.
		 */
		return apply_filters(
			'learndash_model_course_has_requirements',
			$has_requirements,
			$this
		);
	}

	/**
	 * Returns prerequisites requirement.
	 *
	 * @since 4.21.0
	 *
	 * @return array{type: 'all'|'any', ids: int[]}|null Type can be 'all' or 'any'.
	 */
	public function get_requirement_prerequisites(): ?array {
		$is_enabled = learndash_get_course_prerequisite_enabled( $this->get_id() );
		$course_ids = learndash_get_course_prerequisite( $this->get_id() );

		if (
			$is_enabled
			&& ! empty( $course_ids )
		) {
			$requirement = [
				'type' => strtolower( learndash_get_course_prerequisite_compare( $this->get_id() ) ),
				'ids'  => $course_ids,
			];
		} else {
			$requirement = null;
		}

		/**
		 * Filters prerequisite requirement.
		 *
		 * @since 4.21.0
		 *
		 * @param array{type: 'all'|'any', ids: int[]}|null $requirement Prerequisite requirement.
		 *                                                               Type can be 'all' or 'any'.
		 *                                                               Ids are currently course IDs.
		 *                                                               Null if prerequisite requirement is not fully set (type and courses) or not enabled.
		 * @param bool                                      $is_enabled  Whether prerequisite requirement is enabled.
		 * @param Course                                    $course      Course model.
		 *
		 * @return array{type: 'all'|'any', ids: int[]}|null Prerequisite requirement.
		 */
		return apply_filters(
			'learndash_model_course_requirement_prerequisites',
			$requirement,
			$is_enabled,
			$this
		);
	}

	/**
	 * Returns points requirement.
	 *
	 * @since 4.21.0
	 *
	 * @return float Points requirement or 0.0 if isn't set.
	 */
	public function get_requirement_points(): float {
		$is_enabled = learndash_get_course_points_enabled( $this->get_id() );

		$points = $is_enabled
			? Cast::to_float( learndash_get_course_points_access( $this->get_id() ) )
			: 0.0;
		$points = learndash_format_course_points( $points );

		/**
		 * Filters course points requirement.
		 *
		 * @since 4.21.0
		 *
		 * @param float  $points     Points requirement. 0.0 if not set.
		 * @param bool   $is_enabled Whether points requirement is enabled.
		 * @param Course $course     Course model.
		 *
		 * @return float Points requirement.
		 */
		return apply_filters(
			'learndash_model_course_requirement_points',
			$points,
			$is_enabled,
			$this
		);
	}

	/**
	 * Returns whether a course has been completed by a user.
	 *
	 * @since 4.24.0
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return bool
	 */
	public function is_complete( $user = null ): bool {
		$user    = $this->map_user( $user );
		$user_id = $user instanceof WP_User ? $user->ID : $user;

		/**
		 * Filters whether a course has been completed by a user.
		 *
		 * @since 4.24.0
		 *
		 * @param bool   $is_complete Whether a course has been completed by a user.
		 * @param int    $user_id     The user ID.
		 * @param Course $course      The course model.
		 *
		 * @return bool Whether a course has been completed by a user.
		 */
		return apply_filters(
			'learndash_model_course_is_complete',
			learndash_course_completed( $user_id, $this->get_id() ),
			$user_id,
			$this
		);
	}

	/**
	 * Returns whether linear progression is enabled for the course.
	 * Always returns false if the provided user is not logged in.
	 *
	 * @since 4.24.0
	 *
	 * @param WP_User|int|null $user The user ID or WP_User. If null or empty, the current user is used.
	 *
	 * @return bool
	 */
	public function is_linear_progression_enabled( $user = null ): bool {
		$user    = $this->map_user( $user );
		$user_id = $user instanceof WP_User ? $user->ID : $user;

		$current_user                  = wp_get_current_user();
		$is_linear_progression_enabled = false;

		// If the provided user is logged in, check if linear progression is enabled.
		if (
			$user_id > 0
			&& $current_user->ID === $user_id
		) {
			$is_linear_progression_enabled = learndash_lesson_progression_enabled( $this->get_id() );
		}

		/**
		 * Filters whether linear progression is enabled for the course.
		 *
		 * @since 4.24.0
		 *
		 * @param bool   $is_linear_progression_enabled Whether linear progression is enabled for the course.
		 * @param Course $course                        The course model.
		 * @param WP_User|int $user                     The user ID or WP_User. If null or empty, the current user is used.
		 *
		 * @return bool Whether linear progression is enabled for the course.
		 */
		return apply_filters(
			'learndash_model_course_is_linear_progression_enabled',
			$is_linear_progression_enabled,
			$this,
			$user
		);
	}
}
