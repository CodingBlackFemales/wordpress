<?php
/**
 * Step_Mapper class for mapping post IDs to the correct Step model.
 *
 * @since 4.24.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Mappers\Models;

use LearnDash\Core\Models\Step;
use InvalidArgumentException;
use LDLMS_Post_Types;
use LearnDash\Core\Models\Exam;
use LearnDash\Core\Models\Lesson;
use LearnDash\Core\Models\Quiz;
use LearnDash\Core\Models\Topic;
use WP_Post;

/**
 * Step_Mapper class for mapping post IDs to the correct Step model.
 *
 * @since 4.24.0
 */
class Step_Mapper {
	/**
	 * Creates the correct Step model for a given post ID.
	 *
	 * @since 4.24.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return Step|null
	 */
	public static function create( int $post_id ): ?Step {
		if ( $post_id <= 0 ) {
			return null;
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return null;
		}

		$step_models_map = self::get_step_models_map();

		if ( ! isset( $step_models_map[ $post->post_type ] ) ) {
			return null;
		}

		try {
			return $step_models_map[ $post->post_type ]::create_from_post( $post );
		} catch ( InvalidArgumentException $e ) {
			return null;
		}
	}

	/**
	 * Returns the map of post types to their corresponding step models.
	 *
	 * @since 4.24.0
	 *
	 * @return array<string, class-string<Step>>
	 */
	private static function get_step_models_map(): array {
		$step_models_map = [
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON ) => Lesson::class,
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC ) => Topic::class,
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ) => Quiz::class,
			LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::EXAM ) => Exam::class,
		];

		/**
		 * Filters the step models map.
		 *
		 * @since 4.24.0
		 *
		 * @param array<string, class-string<Step>> $step_models_map The step models map.
		 */
		$step_models_map = apply_filters( 'learndash_mapper_models_step_map', $step_models_map );

		// Validate the step models map.

		foreach ( $step_models_map as $post_type => $class ) {
			if ( ! is_subclass_of( $class, Step::class ) ) {
				// Remove the invalid class from the map.
				unset( $step_models_map[ $post_type ] );
			}
		}

		return $step_models_map;
	}
}
