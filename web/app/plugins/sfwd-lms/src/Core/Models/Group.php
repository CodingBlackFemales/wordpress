<?php
/**
 * This class provides the easy way to operate a group.
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
use LearnDash\Core\Models\Traits\Has_Materials;
use WP_User;

/**
 * Group model class.
 *
 * @since 4.6.0
 */
class Group extends Post implements Interfaces\Product {
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
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::GROUP ),
		);
	}

	/**
	 * Returns a product model based on the group.
	 *
	 * @since 4.6.0
	 *
	 * @return Product
	 */
	public function get_product(): Product {
		/**
		 * Filters a group product.
		 *
		 * @since 4.6.0
		 *
		 * @param Product $product Product model.
		 * @param Group   $group   Group model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_group_product',
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
	 * Returns instructors.
	 *
	 * @since 4.6.0
	 *
	 * @return Instructor[]
	 */
	public function get_instructors(): array {
		/**
		 * Filters group instructors.
		 *
		 * @since 4.6.0
		 *
		 * @param Instructor[] $instructors Instructors.
		 * @param Group        $group       Group model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_group_instructors',
			$this->memoize(
				function(): array {
					$instructors = [];

					$limit  = 20;
					$offset = 0;

					do {
						$courses = $this->get_courses( $limit, $offset );

						foreach ( $courses as $course ) {
							foreach ( $course->get_instructors() as $instructor ) {
								$instructors[ $instructor->get_id() ] = $instructor;
							}
						}

						$offset += $limit;
					} while ( ! empty( $courses ) );

					return array_values( $instructors );
				}
			),
			$this
		);
	}

	/**
	 * Returns related courses models.
	 *
	 * @since 4.6.0
	 *
	 * @param int $limit  Optional. Limit. Default is 0 which will be changed with LD settings.
	 * @param int $offset Optional. Offset. Default 0.
	 *
	 * @return Course[]
	 */
	public function get_courses( int $limit = 0, int $offset = 0 ): array {
		$query_args = [
			'offset' => $offset,
		];

		if ( $limit !== 0 ) {
			$query_args['per_page'] = $limit;
		}

		/**
		 * Filters group courses.
		 *
		 * @since 4.6.0
		 *
		 * @param Course[] $courses Courses.
		 * @param Group    $group   Group model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_group_courses',
			$this->memoize(
				function() use ( $query_args ): array {
					return Course::find_many(
						learndash_get_group_courses_list( $this->get_id(), $query_args )
					);
				}
			),
			$this
		);
	}

	/**
	 * Returns the total number of related courses.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function get_courses_number(): int {
		/**
		 * Filters group courses number.
		 *
		 * @since 4.6.0
		 *
		 * @param int   $number Number of courses.
		 * @param Group $group  Group model.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_group_courses_number',
			$this->memoize(
				function(): int {
					return count(
						learndash_group_enrolled_courses( $this->get_id() )
					);
				}
			),
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
		 * Filters a group certificate link.
		 *
		 * @since 4.6.0
		 *
		 * @param string  $url   Group certificate link.
		 * @param Group   $group Group model.
		 * @param WP_User $user  User.
		 *
		 * @return string Group certificate link.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_group_certificate_link',
			$this->memoize(
				function() use ( $user ): string {
					return learndash_get_group_certificate_link( $this->get_id(), $user->ID );
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
		 * Filters a group status slug.
		 *
		 * @since 4.6.0
		 *
		 * @param string  $slug  Group status slug.
		 * @param Group   $group Group model.
		 * @param WP_User $user  User.
		 *
		 * @return string Course status slug.
		 *
		 * @ignore
		 */
		return apply_filters(
			'learndash_model_group_status_slug',
			$this->memoize(
				function() use ( $user ): string {
					return learndash_get_user_group_status( $this->get_id(), $user->ID, true );
				}
			),
			$this,
			$user
		);
	}
}
