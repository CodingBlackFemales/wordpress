<?php
/**
 * LearnDash Admin Topic Cloning.
 *
 * @since 4.2.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'LearnDash_Admin_Cloning' ) && ! class_exists( 'Learndash_Admin_Topic_Cloning' ) ) {
	/**
	 * Class LearnDash Admin Topic Cloning.
	 *
	 * @since 4.2.0
	 */
	class Learndash_Admin_Topic_Cloning extends Learndash_Admin_Cloning {
		/**
		 * Returns the post type slug for cloning.
		 *
		 * @since 4.2.0
		 *
		 * @return string The Topic post type slug.
		 */
		public function get_cloning_object(): string {
			return 'topic';
		}

		/**
		 * Forces the cloning of the topic immediately.
		 * Topic cloning is fast and does not require a cron job.
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
		 * Clones the Topic.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Post $ld_object The LearnDash WP_Post topic.
		 * @param array   $args      The copy arguments.
		 *
		 * @return int The new topic ID.
		 */
		public function clone( WP_Post $ld_object, array $args = array() ) {
			// creating the new topic post.
			$new_topic_id = $this->clone_post_fully( $ld_object, $args );

			// update the topic meta according to the LD logic.
			$this->course_meta_updates( $ld_object, $new_topic_id, $args );

			return $new_topic_id;
		}

		/**
		 * Updates the topic meta according to the LD logic.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Post $topic        The topic object.
		 * @param int     $new_topic_id The new topic ID.
		 * @param array   $args         The copy arguments.
		 *
		 * @return void
		 */
		private function course_meta_updates( WP_Post $topic, int $new_topic_id, array $args ): void {
			$topic_meta = get_post_meta( $topic->ID );
			if ( is_array( $topic_meta ) ) {
				foreach ( $topic_meta as $meta_key => $meta_value ) {
					// topic settings meta.
					if ( '_sfwd-topic' === $meta_key ) {
						$topic_settings = maybe_unserialize( $meta_value[0] );
						if ( isset( $topic_settings['sfwd-topic_course'] ) ) {
							$topic_settings['sfwd-topic_course'] = ! empty( $args['new_course_id'] ) ? $args['new_course_id'] : 0;
						}
						if ( isset( $topic_settings['sfwd-topic_lesson'] ) ) {
							$topic_settings['sfwd-topic_lesson'] = ! empty( $args['new_lesson_id'] ) ? $args['new_lesson_id'] : 0;
						}
						update_post_meta( $new_topic_id, $meta_key, $topic_settings );
					}

					// course_id and lesson_id meta.
					if ( 'course_id' === $meta_key ) {
						if ( ! empty( $args['new_course_id'] ) ) {
							update_post_meta( $new_topic_id, $meta_key, $args['new_course_id'] );
						} else {
							delete_post_meta( $new_topic_id, $meta_key );
						}
					}
					if ( 'lesson_id' === $meta_key ) {
						if ( ! empty( $args['new_lesson_id'] ) ) {
							update_post_meta( $new_topic_id, $meta_key, $args['new_lesson_id'] );
						} else {
							delete_post_meta( $new_topic_id, $meta_key );
						}
					}

					// shared steps metadata: clear meta.
					$ld_course_prefix = 'ld_course';
					if ( substr( $meta_key, 0, strlen( $ld_course_prefix ) ) === $ld_course_prefix ) {
						delete_post_meta( $new_topic_id, $meta_key );
					}
				}
			}
		}
	}
}
