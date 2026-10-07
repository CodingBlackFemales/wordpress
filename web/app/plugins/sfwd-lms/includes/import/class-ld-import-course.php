<?php
/**
 * LearnDash Import Courses CPT
 *
 * This file contains functions to handle import of the LearnDash CPT Course
 *
 * @package LearnDash\Import
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LearnDash_Import_Course' ) ) && ( class_exists( 'LearnDash_Import_Post' ) ) ) {
	/**
	 * Class to import courses.
	 */
	class LearnDash_Import_Course extends LearnDash_Import_Post {
		/**
		 * Version
		 *
		 * @var string Version.
		 */
		private $version = '1.0';

		/**
		 * Destination Post Type
		 *
		 * @var string $dest_post_type
		 */
		protected $dest_post_type = 'sfwd-courses';

		/**
		 * Source Post Type
		 *
		 * @var string $source_post_type
		 */
		protected $source_post_type = 'sfwd-courses';

		/**
		 * Destination Taxonomy
		 *
		 * @var string $dest_taxonomy
		 */
		protected $dest_taxonomy = 'ld_course_category';

		/**
		 * Duplicate post
		 *
		 * @param integer $source_post_id Post ID to copy.
		 * @param boolean $force_copy     Whether to force the copy. Default false.
		 *
		 * @return WP_Post
		 */
		public function duplicate_post( $source_post_id = 0, $force_copy = false ) {
			$new_post = parent::duplicate_post( $source_post_id, $force_copy );

			return $new_post;
		}

		/**
		 * Duplicate Post's taxonomies
		 *
		 * @param WP_Term $source_term    WP_Term to duplicate.
		 * @param boolean $create_parents Whether to create parent taxonomies. Default false.
		 *
		 * @return WP_Term
		 */
		public function duplicate_post_tax_term( $source_term, $create_parents = false ) {
			$new_term = parent::duplicate_post_tax_term( $source_term, $create_parents );

			return $new_term;
		}

		/**
		 * Set post prerequisites
		 *
		 * Prerequisite only support by Courses. (well and quizzes)
		 * This function also enables course prerequisite
		 *
		 * @param int $dest_post_id         Destination Post ID.
		 * @param int $prerequisite_post_id Prerequisite Post ID.
		 */
		public function set_post_prerequisite( $dest_post_id = 0, $prerequisite_post_id = 0 ) {
			if ( ( ! empty( $dest_post_id ) ) && ( ! empty( $prerequisite_post_id ) ) ) {
				$this->set_course_prerequisite_enabled( $dest_post_id, true );

				$prerequisite_posts   = learndash_get_course_prerequisite( $dest_post_id );
				$prerequisite_posts[] = $prerequisite_post_id;
				$this->set_course_prerequisite( $dest_post_id, $prerequisite_posts );
			}
		}

		/**
		 * Enable Course Prerequisites
		 *
		 * @param int  $course_id  Course ID.
		 * @param bool $enabled    Whether prerequisites are enabled. Default true.
		 *
		 * @return bool
		 */
		public function set_course_prerequisite_enabled( $course_id, $enabled = true ) {
			if ( true === $enabled ) {
				$enabled = 'on';
			}

			if ( 'on' != $enabled ) {
				$enabled = '';
			}

			return learndash_update_setting( $course_id, 'course_prerequisite_enabled', $enabled );
		}

		/**
		 * Set Course Prerequisites
		 *
		 * @param int   $course_id            Course ID.
		 * @param array $course_prerequisites Array of course prerequisites.
		 *
		 * @return bool
		 */
		public function set_course_prerequisite( $course_id = 0, $course_prerequisites = array() ) {
			if ( ! empty( $course_id ) ) {
				if ( ( ! empty( $course_prerequisites ) ) && ( is_array( $course_prerequisites ) ) ) {
					$course_prerequisites = array_unique( $course_prerequisites );
				}

				return learndash_update_setting( $course_id, 'course_prerequisite', (array) $course_prerequisites );
			}
			return false;
		}

		/**
		 * Enroll User
		 *
		 * @param int $user_id              User ID.
		 * @param int $course_id            Course ID.
		 * @param int $enroll_timestamp_gmt Enrollment time stamp.
		 */
		public function enroll_user( $user_id = 0, $course_id = 0, $enroll_timestamp_gmt = 0 ) {
			if ( ( ! empty( $user_id ) ) && ( ! empty( $course_id ) ) ) {
				if ( empty( $enroll_timestamp_gmt ) ) {
					$enroll_timestamp_gmt = time();
				}

				$user_course_access_time = get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );
				if ( empty( $user_course_access_time ) ) {
					update_user_meta( $user_id, 'course_' . $course_id . '_access_from', $enroll_timestamp_gmt );
				}
			}
		}

		/**
		 * Add user progress
		 *
		 * @param int   $user_id   User ID.
		 * @param int   $course_id Course ID.
		 * @param array $args      Array of arguments.
		 * @param bool  $force     Whether to force the update. Default false.
		 */
		public function add_user_progress( $user_id = 0, $course_id = 0, $args = array(), $force = false ) {
			if ( ( ! empty( $user_id ) ) && ( ! empty( $course_id ) ) ) {
				$user_id   = intval( $user_id );
				$course_id = intval( $course_id );

				$user = get_user_by( 'ID', $user_id );
				if ( is_a( $user, 'WP_User' ) ) {
					$user_course_meta = get_user_meta( $user_id, '_sfwd-course_progress', true );
					if ( ( false === $user_course_meta ) || ( ! is_array( $user_course_meta ) ) ) {
						$user_course_meta = array();
					}

					$_changed = false;

					if ( ! isset( $user_course_meta[ $course_id ] ) ) {
						$user_course_meta[ $course_id ] = array();
						$_changed                       = true;
					}

					if ( ! empty( $args ) ) {
						foreach ( $args as $key => $val ) {

							if ( ( ! isset( $user_course_meta[ $course_id ][ $key ] ) ) || ( true === $force ) ) {
								$user_course_meta[ $course_id ][ $key ] = $val;
								$_changed                               = true;
							}
						}
					}

					if ( true === $_changed ) {
						update_user_meta( $user_id, '_sfwd-course_progress', $user_course_meta );
					}
				}
			}
		}

		// End of functions.
	}
}
