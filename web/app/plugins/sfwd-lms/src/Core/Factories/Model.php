<?php
/**
 * A factory class for creating models from posts.
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

namespace LearnDash\Core\Factories;

use InvalidArgumentException;
use LDLMS_Post_Types;
use LearnDash\Core\Models\Course;
use LearnDash\Core\Models\Group;
use LearnDash\Core\Models\Lesson;
use LearnDash\Core\Models\Post;
use LearnDash\Core\Models\Quiz;
use LearnDash\Core\Models\Topic;
use WP_Post;

// TODO: Add tests.

/**
 * A factory class for creating models from posts.
 *
 * @since 4.6.0
 */
class Model {
	/**
	 * Creates a model from a post based on the post type.
	 *
	 * @since 4.6.0
	 *
	 * @param WP_Post $post Post.
	 *
	 * @throws InvalidArgumentException If the post type is invalid.
	 *
	 * @return Post
	 */
	public static function create_from_post( WP_Post $post ): Post {
		switch ( LDLMS_Post_Types::get_post_type_key( $post->post_type ) ) {
			case LDLMS_Post_Types::COURSE:
				return Course::create_from_post( $post );
			case LDLMS_Post_Types::GROUP:
				return Group::create_from_post( $post );
			case LDLMS_Post_Types::LESSON:
				return Lesson::create_from_post( $post );
			case LDLMS_Post_Types::TOPIC:
				return Topic::create_from_post( $post );
			case LDLMS_Post_Types::QUIZ:
				return Quiz::create_from_post( $post );
			default:
				throw new InvalidArgumentException( 'Invalid post type' );
		}
	}
}
