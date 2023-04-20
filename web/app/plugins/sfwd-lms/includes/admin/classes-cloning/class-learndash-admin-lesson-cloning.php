<?php
/**
 * LearnDash Admin Lesson Cloning.
 *
 * @since 4.2.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'LearnDash_Admin_Cloning' ) && ! class_exists( 'Learndash_Admin_Lesson_Cloning' ) ) {
	/**
	 * Class LearnDash Admin Lesson Cloning.
	 *
	 * @since 4.2.0
	 */
	class Learndash_Admin_Lesson_Cloning extends Learndash_Admin_Cloning {
		/**
		 * Gets the post type slug for cloning.
		 *
		 * @since 4.2.0
		 *
		 * @return string The lesson post type slug.
		 */
		public function get_cloning_object(): string {
			return 'lesson';
		}

		/**
		 * Forces the cloning of the lesson immediately.
		 * Lesson cloning is fast and does not require a cron job.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Post $ld_object The LearnDash WP_Post object.
		 *
		 * @return boolean
		 */
		protected function run_clone_immediately( WP_Post $ld_object ): bool {
			return true;
		}

		/**
		 * Clones the lesson.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Post $ld_object The LearnDash WP_Post lesson.
		 * @param array   $args      The copy arguments.
		 *
		 * @return int The new lesson ID.
		 */
		public function clone( WP_Post $ld_object, array $args = array() ) {
			// creating the new lesson post.
			$new_lesson_id = $this->clone_post_fully( $ld_object, $args );

			// update the lesson meta according to the LD logic.
			$this->course_meta_updates( $ld_object, $new_lesson_id, $args );

			return $new_lesson_id;
		}

		/**
		 * Copies relevant lesson meta.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Post $lesson        The lesson object.
		 * @param int     $new_lesson_id The new lesson ID.
		 * @param array   $args          The copy arguments.
		 *
		 * @return void
		 */
		private function course_meta_updates( WP_Post $lesson, int $new_lesson_id, array $args ): void {
			$lesson_meta = get_post_meta( $lesson->ID );
			if ( is_array( $lesson_meta ) ) {
				foreach ( $lesson_meta as $meta_key => $meta_value ) {
					// lesson settings meta.
					if ( '_sfwd-lessons' === $meta_key ) {
						$lesson_settings = maybe_unserialize( $meta_value[0] );
						if ( isset( $lesson_settings['sfwd-lessons_course'] ) ) {
							$lesson_settings['sfwd-lessons_course'] = ! empty( $args['new_course_id'] ) ? $args['new_course_id'] : 0;
						}
						update_post_meta( $new_lesson_id, $meta_key, $lesson_settings );
					}

					// course_id meta.
					if ( 'course_id' === $meta_key ) {
						if ( ! empty( $args['new_course_id'] ) ) {
							update_post_meta( $new_lesson_id, $meta_key, $args['new_course_id'] );
						} else {
							delete_post_meta( $new_lesson_id, $meta_key );
						}
					}

					// shared steps metadata: clear meta.
					$ld_course_prefix = 'ld_course';
					if ( substr( $meta_key, 0, strlen( $ld_course_prefix ) ) === $ld_course_prefix ) {
						delete_post_meta( $new_lesson_id, $meta_key );
					}
				}
			}
		}
	}
}
