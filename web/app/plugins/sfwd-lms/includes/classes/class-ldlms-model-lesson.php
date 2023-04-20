<?php
/**
 * Class to extend LDLMS_Model_Post to LDLMS_Model_Lesson.
 *
 * @since 2.5.0
 * @package LearnDash\Lesson
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LDLMS_Model_Lesson' ) ) && ( class_exists( 'LDLMS_Model_Post' ) ) ) {
	/**
	 * Class for LearnDash Model Lesson.
	 *
	 * @since 3.2.0
	 * @uses LDLMS_Model_Post
	 */
	class LDLMS_Model_Lesson extends LDLMS_Model_Post {

		/**
		 * Class constructor.
		 *
		 * @since 3.2.0
		 *
		 * @param int $lesson_id Lesson Post ID to load.
		 *
		 * return mixed instance of class or exception.
		 */
		public function __construct( $lesson_id = 0 ) {
			$this->post_type = learndash_get_post_type_slug( 'lesson' );
			$this->initialize( $lesson_id );
		}

		/**
		 * Initialize post.
		 *
		 * @since 3.2.0
		 *
		 * @param int $lesson_id Lesson Post ID to load.
		 */
		public function initialize( $lesson_id ) {
			if ( ! empty( $lesson_id ) ) {
				$lesson = get_post( $lesson_id );
				if ( ( is_a( $lesson, 'WP_Post' ) ) && ( $lesson->post_type === $this->post_type ) ) {
					$this->post_id = absint( $lesson_id );
					$this->post    = $lesson;
				}
			}
		}

		/**
		 * Checks if lesson is sample.
		 *
		 * @since 3.2.0
		 *
		 * @return boolean Returns true if the post is sample otherwise false.
		 */
		public function is_sample() {
			$is_sample = false;

			if ( empty( $this->post_id ) ) {
				return $is_sample;
			}

			if ( learndash_get_setting( $this->post_id, 'sample_lesson' ) ) {
				$is_sample = true;
			}

			/**
			 * Filters whether the lesson is a sample lesson or not.
			 *
			 * @param boolean            $is_sample Whether the lesson is a sample lesson or not.
			 * @param WP_Post|array|null $post      Post Object.
			 */
			return apply_filters( 'learndash_lesson_is_sample', $is_sample, $this->post );
		}
	}
}
