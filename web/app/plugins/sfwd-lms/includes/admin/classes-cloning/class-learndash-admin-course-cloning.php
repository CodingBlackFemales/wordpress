<?php
/**
 * LearnDash Admin Course Cloning.
 *
 * @since 4.2.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'LearnDash_Admin_Cloning' ) && ! class_exists( 'Learndash_Admin_Course_Cloning' ) ) {
	/**
	 * Class LearnDash Admin Course Cloning.
	 *
	 * @since 4.2.0
	 */
	class Learndash_Admin_Course_Cloning extends Learndash_Admin_Cloning {
		/**
		 * Returns the post type slug for cloning.
		 *
		 * @since 4.2.0
		 *
		 * @return string The course post type slug.
		 */
		public function get_cloning_object(): string {
			return 'course';
		}

		/**
		 * Changing the action row label for the course as courses are copied entirely.
		 *
		 * @since 4.2.0
		 *
		 * @return string The cloning action row label.
		 */
		protected function get_cloning_row_label(): string {
			return __( 'Clone', 'learndash' );
		}

		/**
		 * Forces the cloning of the course immediately if shared steps is enabled.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Post $ld_object The LearnDash WP_Post object.
		 *
		 * @return boolean
		 */
		protected function run_clone_immediately( WP_Post $ld_object ): bool {
			return LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) === 'yes';
		}

		/**
		 * Clones the course.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Post $ld_object The LearnDash WP_Post course.
		 * @param array   $args      The copy arguments.
		 *
		 * @return int The new course ID.
		 */
		public function clone( WP_Post $ld_object, array $args = array() ) {
			$shared_steps = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) === 'yes';

			// creating the new course post.
			$new_course_id = $this->clone_post_fully( $ld_object, $args );

			// update the course meta according to the LD logic.
			$this->course_meta_updates( $ld_object, $new_course_id, $shared_steps );

			// copy related objects.
			// if shared steps is disabled, prepare to copy related objects.
			if ( ! $shared_steps ) {
				$lesson_cloning_class = new Learndash_Admin_Lesson_Cloning();
				$topic_cloning_class  = new Learndash_Admin_Topic_Cloning();
				$quiz_cloning_class   = new Learndash_Admin_Quiz_Cloning();
			}

			// lessons.
			$lessons = learndash_course_get_lessons(
				$ld_object->ID,
				array(
					'return_type' => 'WP_Post',
					'per_page'    => 0,
				)
			);
			if ( is_array( $lessons ) && ! empty( $lessons ) ) {
				$new_lesson_id = 0;
				foreach ( $lessons as $lesson ) {
					if ( ! $shared_steps ) {
						$new_lesson_id = $lesson_cloning_class->clone(
							$lesson,
							array_replace(
								$args,
								array(
									'copy_name'     => $lesson->post_title,
									'new_course_id' => $new_course_id,
								)
							)
						);
					} else {
						// add shared course metadata.
						update_post_meta( $lesson->ID, "ld_course_$new_course_id", $new_course_id );
					}

					// topics.
					$topics = learndash_course_get_topics(
						$ld_object->ID,
						$lesson->ID,
						array(
							'return_type' => 'WP_Post',
							'per_page'    => 0,
						)
					);

					if ( is_array( $topics ) && ! empty( $topics ) ) {
						$new_topic_id = 0;
						foreach ( $topics as $topic ) {
							if ( ! $shared_steps ) {
								$new_topic_id = $topic_cloning_class->clone(
									$topic,
									array_replace(
										$args,
										array(
											'copy_name' => $topic->post_title,
											'new_course_id' => $new_course_id,
											'new_lesson_id' => $new_lesson_id,
										)
									)
								);
							} else {
								// add shared course metadata.
								update_post_meta( $topic->ID, "ld_course_$new_course_id", $new_course_id );
							}

							// Get Topic's Quizzes.
							$topic_quizzes = learndash_course_get_quizzes(
								$ld_object->ID,
								$topic->ID,
								array(
									'return_type' => 'WP_Post',
									'per_page'    => 0,
								)
							);

							if ( is_array( $topic_quizzes ) && ! empty( $topic_quizzes ) ) {
								foreach ( $topic_quizzes as $topic_quiz ) {
									if ( ! $shared_steps ) {
										$quiz_cloning_class->clone(
											$topic_quiz,
											array_replace(
												$args,
												array(
													'copy_name'     => $topic_quiz->post_title,
													'new_course_id' => $new_course_id,
													'new_lesson_id' => $new_topic_id,
												)
											)
										);
									} else {
										// add shared course metadata.
										update_post_meta( $topic_quiz->ID, "ld_course_$new_course_id", $new_course_id );
									}
								}
							} // end topic quizzes.
						}
					} // end topics.

					// Get lesson's quizzes.
					$lesson_quizzes = learndash_course_get_quizzes(
						$ld_object->ID,
						$lesson->ID,
						array(
							'return_type' => 'WP_Post',
							'per_page'    => 0,
						)
					);

					if ( is_array( $lesson_quizzes ) && ! empty( $lesson_quizzes ) ) {
						foreach ( $lesson_quizzes as $lesson_quiz ) {
							if ( ! $shared_steps ) {
								$quiz_cloning_class->clone(
									$lesson_quiz,
									array_replace(
										$args,
										array(
											'copy_name' => $lesson_quiz->post_title,
											'new_course_id' => $new_course_id,
											'new_lesson_id' => $new_lesson_id,
										)
									)
								);
							} else {
								// add shared course metadata.
								update_post_meta( $lesson_quiz->ID, "ld_course_$new_course_id", $new_course_id );
							}
						}
					} // end lesson quizzes.
				}
			} // end lessons.

			// Get a list of course (global) quizzes.
			$global_quizzes = learndash_course_get_quizzes(
				$ld_object->ID,
				$ld_object->ID,
				array(
					'return_type' => 'WP_Post',
					'per_page'    => 0,
				)
			);

			if ( is_array( $global_quizzes ) && ! empty( $global_quizzes ) ) {
				foreach ( $global_quizzes as $global_quiz ) {
					if ( ! $shared_steps ) {
						$quiz_cloning_class->clone(
							$global_quiz,
							array_replace(
								$args,
								array(
									'copy_name'     => $global_quiz->post_title,
									'new_course_id' => $new_course_id,
								)
							)
						);
					} else {
						// add shared course metadata.
						update_post_meta( $global_quiz->ID, "ld_course_$new_course_id", $new_course_id );
					}
				}
			} // end global quizzes.

			if ( ! $shared_steps ) {
				// set a flag to rebuild the ld_course_steps.
				learndash_course_set_steps_dirty( $new_course_id );
			}

			return $new_course_id;
		}

		/**
		 * Update the course meta according to the LD logic.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Post $course        The course object.
		 * @param int     $new_course_id The new course ID.
		 * @param bool    $shared_steps  Whether to share steps or not.
		 *
		 * @return void
		 */
		private function course_meta_updates( WP_Post $course, int $new_course_id, bool $shared_steps ): void {
			// remove challenge exam while we do not support cloning them.
			learndash_update_setting( $new_course_id, 'exam_challenge', '' );

			$course_meta = get_post_meta( $course->ID );
			if ( is_array( $course_meta ) ) {
				foreach ( $course_meta as $meta_key => $meta_value ) {
					// update group meta.
					$group_enrolled_prefix = 'learndash_group_enrolled';
					if ( substr( $meta_key, 0, strlen( $group_enrolled_prefix ) ) === $group_enrolled_prefix ) {
						update_post_meta( $new_course_id, $meta_key, time() );
					}
				}
			}

			if ( ! $shared_steps ) {
				// delete course steps meta.
				delete_post_meta( $new_course_id, 'ld_course_steps' );
			} else {
				// update course on the metadata.
				$ld_course_steps              = get_post_meta( $new_course_id, 'ld_course_steps', true );
				$ld_course_steps['course_id'] = $new_course_id;
				update_post_meta( $new_course_id, 'ld_course_steps', $ld_course_steps );
			}
		}
	}
}
