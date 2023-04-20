<?php
/**
 * LearnDash Factory Post Class.
 * This is a factory class used to instantiate LD custom post type related data.
 *
 * @since 2.5.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LDLMS_Factory_Post' ) ) && ( class_exists( 'LDLMS_Factory' ) ) ) {
	/**
	 * Class for LearnDash Factory Post.
	 *
	 * @since 2.5.0
	 * @uses LDLMS_Factory
	 */
	class LDLMS_Factory_Post extends LDLMS_Factory {

		/**
		 * Get a Course.
		 *
		 * @param int|object $course Either course_id integer or WP_Post instance.
		 * @param bool       $reload To force reload of instance.
		 *
		 * @return object|null Instance of `LDLMS_Model_Course` or null
		 */
		public static function course( $course = null, $reload = false ) {
			if ( ! empty( $course ) ) {
				$model = 'LDLMS_Model_Course';

				$course_id = 0;
				if ( ( is_a( $course, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'course' ) === $course->post_type ) ) {
					$course_id = absint( $course->ID );
				} else {
					$course_id = absint( $course );
				}

				if ( ! empty( $course_id ) ) {
					if ( true === $reload ) {
						self::remove_instance( $model, $course_id );
					}
					return self::add_instance( $model, $course_id, $course_id );
				}
			}

			return null;
		}

		/**
		 * Get a Lesson.
		 *
		 * @param int|object $lesson Either lesson_id integer or WP_Post instance.
		 * @param bool       $reload To force reload of instance.
		 *
		 * @return object|null Instance of `LDLMS_Model_Lesson` or null
		 */
		public static function lesson( $lesson = null, $reload = false ) {
			if ( ! empty( $lesson ) ) {
				$model = 'LDLMS_Model_Lesson';

				$lesson_id = 0;
				if ( ( is_a( $lesson, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'lesson' ) === $lesson->post_type ) ) {
					$lesson_id = absint( $lesson->ID );
				} else {
					$lesson_id = absint( $lesson );
				}

				if ( ! empty( $lesson_id ) ) {
					if ( true === $reload ) {
						self::remove_instance( $model, $lesson_id );
					}
					return self::add_instance( $model, $lesson_id, $lesson_id );
				}
			}

			return null;
		}

		/**
		 * Get Course.
		 *
		 * @param int|object $course Either course_id integer or WP_Post instance.
		 */
		public static function get_course( $course ) {}

		/**
		 * Get Course Lessons.
		 *
		 * @param int|object $course Either course_id integer or WP_Post instance.
		 * @param int|object $lesson Either lesson_id integer or WP_Post instance.
		 *
		 * @return array An array of course lessons
		 */
		public static function get_course_lessons( $course = null, $lesson = null ) {
			if ( ! empty( $course ) ) {
				$course = self::get_course( $course );
				if ( $course ) {
					$lesson_id = 0;

					if ( ( is_a( $lesson, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'lesson' ) === $lesson->post_type ) ) {
						$lesson_id = absint( $lesson->ID );
					} else {
						$lesson_id = absint( $lesson );
					}

					$course_lesson = $course->get_lesson( $lesson_id );

					return $course_lesson;
				}
			}

			return array();
		}

		/**
		 * Get a Quiz Questions.
		 *
		 * @param int|object $quiz Either quiz_id integer or WP_Post instance.
		 * @param bool       $reload To force reload of instance.
		 *
		 * @return object|null Instance of `LDLMS_Quiz_Questions` or null
		 */
		public static function quiz_questions( $quiz = null, $reload = false ) {
			if ( ! empty( $quiz ) ) {
				$model = 'LDLMS_Quiz_Questions';

				$quiz_id = 0;

				if ( ( is_a( $quiz, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'quiz' ) === $quiz->post_type ) ) {
					$quiz_id = absint( $quiz->ID );
				} else {
					$quiz_id = absint( $quiz );
				}

				if ( ! empty( $quiz_id ) ) {
					if ( true === $reload ) {
						self::remove_instance( $model, $quiz_id );
					}
					return self::add_instance( $model, $quiz_id, $quiz_id );
				}
			}

			return null;
		}

		/**
		 * Get a Course Steps.
		 *
		 * @param int|object $course Either course_id integer or WP_Post instance.
		 * @param bool       $reload To force reload of instance.
		 *
		 * @return object|null Instance of `LDLMS_Course_Steps` or null
		 */
		public static function course_steps( $course = null, $reload = false ) {
			if ( ! empty( $course ) ) {
				$model = 'LDLMS_Course_Steps';

				$course_id = 0;

				if ( ( is_a( $course, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'course' ) === $course->post_type ) ) {
					$course_id = absint( $course->ID );
				} else {
					$course_id = absint( $course );
				}

				if ( ! empty( $course_id ) ) {
					if ( true === $reload ) {
						self::remove_instance( $model, $course_id );
					}
					return self::add_instance( $model, $course_id, $course_id );
				}
			}

			return null;
		}

		/**
		 * Get a Exam.
		 *
		 * @since 4.0.0
		 *
		 * @param int|object $exam   Either exam_id integer or WP_Post instance.
		 * @param array      $atts   Array of attributes (course_id, user_id).
		 * @param bool       $reload To force reload of instance.
		 *
		 * @return object|null Instance of `LDLMS_Model_Exam` or null
		 */
		public static function exam( $exam = null, $atts = array(), $reload = false ) {
			if ( ! empty( $exam ) ) {
				$model = 'LDLMS_Model_Exam';

				$exam_id = 0;
				if ( ( is_a( $exam, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'exam' ) === $exam->post_type ) ) {
					$exam_id = absint( $exam->ID );
				} else {
					$exam_id = absint( $exam );
				}

				if ( ! empty( $exam_id ) ) {
					$model_key = $exam_id;
					if ( ( is_array( $atts ) ) && ( ! empty( $atts ) ) ) {
						ksort( $atts );
						$model_key = $exam_id . '-' . md5( serialize( $atts ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
					}

					if ( true === $reload ) {
						self::remove_instance( $model, $model_key );
					}
					return self::add_instance( $model, $model_key, $exam_id, $atts );
				}
			}

			return null;
		}

		/**
		 * Get a Exam Question.
		 *
		 * @since 4.0.0
		 *
		 * @param array   $exam_question_block Array of question attributes.
		 * @param boolean $reload              To force reload of instance.
		 *
		 * @return object|null Instance of `LDLMS_Model_Exam_Question` or null
		 */
		public static function exam_question( $exam_question_block = array(), $reload = false ) {
			if ( ! empty( $exam_question_block ) ) {
				if ( isset( $exam_question_block['attrs']['question_type'] ) ) {
					$exam_question_key = $exam_question_block['attrs']['question_type'] . '-' . ( isset( $exam_question_block['attrs']['exam_id'] ) ? $exam_question_block['attrs']['exam_id'] : '0' ) . '-' . ( isset( $exam_question_block['attrs']['question_idx'] ) ? $exam_question_block['attrs']['question_idx'] : '0' );

					$model = LDLMS_Model_Exam_Question::get_model_by_type( $exam_question_block['attrs']['question_type'] );
					if ( $model ) {
						if ( true === $reload ) {
							self::remove_instance( $model, $exam_question_key );
						}
						return self::add_instance( $model, $exam_question_key, $exam_question_block );
					}
				}
			}

			return null;
		}

		// End of functions.
	}
}
