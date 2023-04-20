<?php
/**
 * @link       https://wisdmlabs.com
 * @since      1.0.0
 * @package    Ld_Content_Cloner
 * @subpackage Ld_Content_Cloner/includes
 * @author     WisdmLabs <info@wisdmlabs.com>
 */

namespace LdccCourseClone;

class LdccCourse {

	/**
	 *
	 * @since    1.0.0
	 */

	public function __construct() {
	}
	/**
	 * IR Multiinstructor for shared steps doesn't return lessons list if instructor is not primary author because of author param added in Instructor Role plugin.
	 * This filter will fix this issue so that all the instructors will be able to clone the complete course.
	 *
	 * @param  [type] $query [description]
	 * @return [type]        [description]
	 */
	public function allowCourseAccessToInstructors( $query ) {
		$course_id = filter_input( INPUT_POST, 'course_id', FILTER_VALIDATE_INT );
		if ( empty( $course_id ) ) {
			return $query;
		}
		$course_nonce = filter_input( INPUT_POST, 'course' );
		if ( empty( $course_nonce ) ) {
			return $query;
		}
		$nonce_check = wp_verify_nonce( $course_nonce, 'dup_course_' . $course_id );
		if ( false === $nonce_check ) {
			return $query;
		}
		$query->set( 'author__in', array() );
		return $query;
	}

	public static function createDuplicateCourse() {
		$course_id           = filter_input( INPUT_POST, 'course_id', FILTER_VALIDATE_INT );
		$course_nonce        = filter_input( INPUT_POST, 'course' );
		$nonce_check         = wp_verify_nonce( $course_nonce, 'dup_course_' . $course_id );
		$ld_builder_settings = filter_input( INPUT_POST, 'ld_builder_settings', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( $nonce_check === false ) {
			echo json_encode( array( 'error' => __( 'Security check failed.', 'ld-content-cloner' ) ) );
			die();
		}

		if ( ( ! isset( $course_id ) ) || ! ( get_post_type( $course_id ) == 'sfwd-courses' ) ) {
			echo json_encode( array( 'error' => __( 'The current post is not a Course and hence could not be cloned.', 'ld-content-cloner' ) ) );
			die();
		}

		$course_post = get_post( $course_id, ARRAY_A );
		$course_post = \LDCC_Course\LDCC_Course::stripPostData( $course_post );

		// Create a new Course
		$new_course_id = wp_insert_post( wp_slash( $course_post ), true );
		/**
		 * This action will run after course clone post is created.
		 *
		 * @since 1.2.8 [<description>]
		 */
		do_action( 'ldcc_course_clone_post_created', $new_course_id, $course_id );
		if ( ! is_wp_error( $new_course_id ) ) {
			$ld_course_builder      = isset( $ld_builder_settings['course_builder'] ) ? $ld_builder_settings['course_builder'] : '';
			$ld_shared_course_steps = isset( $ld_builder_settings['shared_steps_course'] ) ? $ld_builder_settings['shared_steps_course'] : '';
			self::setMeta( 'course', $course_id, $new_course_id, array(), $ld_course_builder, $ld_shared_course_steps );

			$send_result = self::ldccCourseSharedSteps( $course_id, $new_course_id );
			echo json_encode( $send_result );
		} else {
			echo json_encode( array( 'error' => __( 'Some error occurred. The Course could not be cloned.', 'ld-content-cloner' ) ) );
		}

		die();
	}

	public static function ldccCourseSharedSteps( $course_id, $new_course_id ) {
		$course_steps       = get_post_meta( $course_id, 'ld_course_steps' );
			$course_steps_h = array();
		if ( ! empty( $course_steps ) ) {
			// New check to see if using older or newer LD required after LD 3.4
			if ( array_key_exists( 'steps', $course_steps[0] ) ) {
				$course_steps_h = $course_steps[0]['steps']['h'];
			} else {
				$course_steps_h = $course_steps[0]['h'];
			}
			$c_data = self::getLDCourseStepsArray( $course_steps_h, $new_course_id );
		} else {
			$c_data = self::createLDCourseStepsArray( $course_id, $new_course_id );
		}
			$send_result = array(
				'success' => array(
					'old_course_id' => $course_id,
					'new_course_id' => $new_course_id,
					'c_data'        => $c_data,
				),
			);
			return $send_result;
	}

	public static function getLDCourseStepsArray( $course_steps, $new_course_id ) {
		$lessons_list = array();
		$quizzes_list = array();
		$h_c_quiz     = array();
		$h_lesson     = array();
		$lessons      = $course_steps['sfwd-lessons'];
		foreach ( $lessons as $lesson_id => $l_content ) {
			$h_quiz         = array();
			$h_topic        = array();
			$new_lesson_id  = wp_insert_post(
				array(
					'post_title'   => 'Copy',
					'post_content' => '',
					'post_type'    => 'sfwd-lessons',
				)
			);
			$lessons_list[] = array( $lesson_id, get_the_title( $lesson_id ), $new_lesson_id, null );
			$topics         = $l_content['sfwd-topic'];
			foreach ( $topics as $topic_id => $content ) {
				$h_t_quiz       = array();
				$new_topic_id   = wp_insert_post(
					array(
						'post_title'   => 'Copy',
						'post_content' => '',
						'post_type'    => 'sfwd-topic',
					)
				);
				$lessons_list[] = array( $topic_id, get_the_title( $topic_id ), $new_topic_id, $new_lesson_id );
				$t_quizzes      = $content['sfwd-quiz'];
				foreach ( $t_quizzes as $quiz_id => $content ) {
					$new_quiz_id                               = wp_insert_post(
						array(
							'post_title'   => 'Copy',
							'post_content' => '',
							'post_type'    => 'sfwd-quiz',
						)
					);
					$quizzes_list[]                            = array( $quiz_id, get_the_title( $quiz_id ), $new_quiz_id, $new_topic_id );
					$h_t_quiz[ $new_topic_id ][ $new_quiz_id ] = array();
				}
				if ( ! isset( $h_t_quiz[ $new_topic_id ] ) ) {
					$h_t_quiz[ $new_topic_id ] = array();
				}
				$h_topic[ $new_lesson_id ][ $new_topic_id ]['sfwd-quiz'] = $h_t_quiz[ $new_topic_id ];
			}
			$l_quizzes = $l_content['sfwd-quiz'];
			foreach ( $l_quizzes as $quiz_id => $content ) {
				$new_l_quiz_id                              = wp_insert_post(
					array(
						'post_title'   => 'Copy',
						'post_content' => '',
						'post_type'    => 'sfwd-quiz',
					)
				);
				$quizzes_list[]                             = array( $quiz_id, get_the_title( $quiz_id ), $new_l_quiz_id, $new_lesson_id );
				$h_quiz[ $new_lesson_id ][ $new_l_quiz_id ] = array();
			}
			if ( ! isset( $h_topic[ $new_lesson_id ] ) ) {
				$h_topic[ $new_lesson_id ] = array();
			}

			if ( ! isset( $h_quiz[ $new_lesson_id ] ) ) {
				$h_quiz[ $new_lesson_id ] = array();
			}
			$h_lesson[ $new_lesson_id ]['sfwd-topic'] = $h_topic[ $new_lesson_id ];
			$h_lesson[ $new_lesson_id ]['sfwd-quiz']  = $h_quiz[ $new_lesson_id ];
		}
			$quizzes = $course_steps['sfwd-quiz'];
		foreach ( $quizzes as $quiz_id => $content ) {
			$new_c_quiz_id              = wp_insert_post(
				array(
					'post_title'   => 'Copy',
					'post_content' => '',
					'post_type'    => 'sfwd-quiz',
				)
			);
			$quizzes_list[]             = array( $quiz_id, get_the_title( $quiz_id ), $new_c_quiz_id, null );
			$h_c_quiz[ $new_c_quiz_id ] = array();
		}
		$h_course['sfwd-lessons'] = $h_lesson;
		$h_course['sfwd-quiz']    = $h_c_quiz;
		if ( ! isset( $_SESSION ) ) {
			session_start();
		}
		$_SESSION['course_association'][ $new_course_id ] = $h_course;
		self::getLDCourseSteps( $h_course, $new_course_id );
		return array(
			'lesson' => $lessons_list,
			'quiz'   => $quizzes_list,
		);
	}

	// .get entire ld_course_steps array from h subarray
	public static function getLDCourseSteps( $h_course, $new_course_id ) {
		$courseStepsClass = new \LDLMS_Course_Steps( $new_course_id );
		if ( ! empty( $h_course ) ) {
			$courseStepsClass->set_steps( $h_course );
		}
	}

	public static function createLDCourseStepsArray( $course_id, $new_course_id ) {
		$lessons_list = array();
		$quizzes_list = array();
		$h_c_quiz     = array();
		$h_course     = $h_lesson = $h_topic = $h_quiz = $h_c_quiz = $h_t_quiz = array();
		$lessons      = learndash_get_course_lessons_list( $course_id, null, array( 'num' => 0 ) );
		foreach ( $lessons as $lesson ) {
			$h_quiz         = array();
			$h_topic        = array();
			$lesson_id      = $lesson['post']->ID;
			$new_lesson_id  = wp_insert_post( wp_slash( array( 'post_type' => 'sfwd-lessons' ) ) );
			$lessons_list[] = array( $lesson_id, $lesson['post']->post_title, $new_lesson_id, null );
			$topics         = learndash_get_topic_list( $lesson_id, $course_id );
			foreach ( $topics as $topic ) {
				$h_t_quiz       = array();
				$topic_id       = $topic->ID;
				$new_topic_id   = wp_insert_post( wp_slash( array( 'post_type' => 'sfwd-topic' ) ) );
				$lessons_list[] = array( $topic_id, $topic->post_title, $new_topic_id, $new_lesson_id );
				$t_quizzes      = learndash_get_lesson_quiz_list( $topic_id, '', $course_id );
				foreach ( $t_quizzes as $t_quiz ) {
					$quiz_id                                   = $t_quiz['post']->ID;
					$new_quiz_id                               = wp_insert_post( wp_slash( array( 'post_type' => 'sfwd-quiz' ) ) );
					$quizzes_list[]                            = array( $quiz_id, $t_quiz['post']->post_title, $new_quiz_id, $new_topic_id );
					$h_t_quiz[ $new_topic_id ][ $new_quiz_id ] = array();
				}
				if ( ! isset( $h_t_quiz[ $new_topic_id ] ) ) {
					$h_t_quiz[ $new_topic_id ] = array();
				}
				$h_topic[ $new_lesson_id ][ $new_topic_id ]['sfwd-quiz'] = $h_t_quiz[ $new_topic_id ];
			}
			$l_quizzes = learndash_get_lesson_quiz_list( $lesson_id, '', $course_id );
			foreach ( $l_quizzes as $l_quiz ) {
				$quiz_id                                    = $l_quiz['post']->ID;
				$new_l_quiz_id                              = wp_insert_post( wp_slash( array( 'post_type' => 'sfwd-quiz' ) ) );
				$quizzes_list[]                             = array( $quiz_id, $l_quiz['post']->post_title, $new_l_quiz_id, $new_lesson_id );
				$h_quiz[ $new_lesson_id ][ $new_l_quiz_id ] = array();
			}
			if ( ! isset( $h_topic[ $new_lesson_id ] ) ) {
				$h_topic[ $new_lesson_id ] = array();
			}

			if ( ! isset( $h_quiz[ $new_lesson_id ] ) ) {
				$h_quiz[ $new_lesson_id ] = array();
			}
			$h_lesson[ $new_lesson_id ]['sfwd-topic'] = $h_topic[ $new_lesson_id ];
			$h_lesson[ $new_lesson_id ]['sfwd-quiz']  = $h_quiz[ $new_lesson_id ];
		}
		$quizzes = learndash_get_course_quiz_list( $course_id );
		foreach ( $quizzes as $c_quiz ) {
			$quiz_id                    = $c_quiz['post']->ID;
			$new_c_quiz_id              = wp_insert_post( wp_slash( array( 'post_type' => 'sfwd-quiz' ) ) );
			$quizzes_list[]             = array( $quiz_id, get_the_title( $quiz_id ), $new_c_quiz_id, null );
			$h_c_quiz[ $new_c_quiz_id ] = array();
		}
		$h_course['sfwd-lessons'] = $h_lesson;
		$h_course['sfwd-quiz']    = $h_c_quiz;
		if ( ! isset( $_SESSION ) ) {
			session_start();
		}
		$_SESSION['course_association'][ $new_course_id ] = $h_course;
		self::getLDCourseSteps( $h_course, $new_course_id );
		return array(
			'lesson' => $lessons_list,
			'quiz'   => $quizzes_list,
		);
	}

	public static function createDuplicateLesson() {
		$lesson_id           = filter_input( INPUT_POST, 'lesson_id', FILTER_VALIDATE_INT );
		$new_lesson_id       = filter_input( INPUT_POST, 'new_lesson_id', FILTER_VALIDATE_INT );
		$topic_lesson_id     = filter_input( INPUT_POST, 'topic_lesson_id', FILTER_VALIDATE_INT );
		$ld_builder_settings = filter_input( INPUT_POST, 'ld_builder_settings', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( ( ! isset( $lesson_id ) ) || ( ! ( get_post_type( $lesson_id ) == 'sfwd-lessons' ) && ! ( get_post_type( $lesson_id ) == 'sfwd-topic' ) ) ) {
			echo json_encode( array( 'error' => __( 'The current post is not a Lesson or topic and hence could not be cloned.', 'ld-content-cloner' ) ) );
			die();
		}
		$old_course_id = filter_input( INPUT_POST, 'old_course_id', FILTER_VALIDATE_INT );
		$course_id     = filter_input( INPUT_POST, 'course_id', FILTER_VALIDATE_INT );
		if ( ( ! isset( $course_id ) ) || ! ( get_post_type( $course_id ) == 'sfwd-courses' ) ) {
			echo json_encode( array( 'error' => __( 'The course ID provided with is incorrect for the lesson.', 'ld-content-cloner' ) ) );
			die();
		}
		$lesson_post = get_post( $lesson_id, ARRAY_A );
		$old_id      = $lesson_post['ID'];
		// $lesson_post = self::stripPostData($lesson_post);
		$exclude_remove = array( 'post_content', 'post_title', 'post_status', 'post_type', 'comment_status', 'ping_status' );
		foreach ( $lesson_post as $lpkey => $lpvalue ) {
			if ( ! in_array( $lpkey, $exclude_remove ) ) {
				unset( $lesson_post[ $lpkey ] );
			}
			unset( $lpvalue );
		}
		$lesson_post['ID'] = $new_lesson_id;
		/**
		 * This filter is used to change the copy word used for cloned modules
		 *
		 * @since 1.2.8
		 * @var integer $new_lesson_id The new ID before Post update.
		 * @var integer $old_id The ID of the source module being cloned.
		 */
		$new_module_slug           = apply_filters( 'ldcc_duplicate_slug', 'Copy', $new_lesson_id, $old_id );
		$lesson_post['post_title'] = $lesson_post['post_title'] . ' ' . $new_module_slug;
		$new_lesson_id             = wp_update_post( wp_slash( $lesson_post ), true );
		if ( ! isset( $_SESSION ) ) {
			session_start();
		}
		if ( ! isset( $_SESSION['course_association'] ) || ! is_array( $_SESSION['course_association'] ) ) {
			wp_send_json_error( new \WP_Error( '001', __( 'Some error occurred. The Lesson was not fully cloned.', 'ld-content-cloner' ) ) );
			die();
		}
		$h_course = filter_var( $_SESSION['course_association'][ $course_id ], FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );// Multidimensional array containing course hierarchy.
		self::getLDCourseSteps( $h_course, $course_id );
		/**
		 * This action will run after lesson/topic clone post is created.
		 *
		 * @since 1.2.8 [<description>]
		 */
		do_action( 'ldcc_lesson_or_topic_clone_post_created', $new_lesson_id, $old_id );
		if ( ! is_wp_error( $new_lesson_id ) ) {
			$other_data = array(
				'course_id'       => $course_id,
				'old_course_id'   => $old_course_id,
				'topic_lesson_id' => $topic_lesson_id,
			);
			self::setMeta(
				'lesson',
				$lesson_id,
				$new_lesson_id,
				$other_data,
				$ld_builder_settings['course_builder'],
				$ld_builder_settings['shared_steps_course']
			);

			$send_result = array( 'success' => array() );
		} else {
			$send_result = array( 'error' => __( 'Some error occurred. The Lesson was not fully cloned.', 'ld-content-cloner' ) );
		}
		echo json_encode( $send_result );
		die();
	}

	public static function duplicateQuiz( $quiz_id = 0, $lesson_id = 0, $course_id = 0 ) {
		// duplicate quiz post
		$send_response = false;
		if ( $quiz_id == 0 || $quiz_id == '' ) {
			$quiz_id             = filter_input( INPUT_POST, 'quiz_id', FILTER_VALIDATE_INT );
			$new_quiz_id         = filter_input( INPUT_POST, 'new_quiz_id', FILTER_VALIDATE_INT );
			$old_course_id       = filter_input( INPUT_POST, 'old_course_id', FILTER_VALIDATE_INT );
			$course_id           = filter_input( INPUT_POST, 'course_id', FILTER_VALIDATE_INT );
			$lesson_id           = filter_input( INPUT_POST, 'lesson_id', FILTER_VALIDATE_INT );
			$ld_builder_settings = filter_input( INPUT_POST, 'ld_builder_settings', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
			$send_response       = true;
		}
		$quiz_post = get_post( $quiz_id, ARRAY_A );
		$old_id    = $quiz_post['ID'];
		// $lesson_post = self::stripPostData($lesson_post);
		$exclude_remove = array( 'post_content', 'post_title', 'post_status', 'post_type', 'comment_status', 'ping_status' );
		foreach ( $quiz_post as $qpkey => $qpvalue ) {
			if ( ! in_array( $qpkey, $exclude_remove ) ) {
				unset( $quiz_post[ $qpkey ] );
			}
			unset( $qpvalue );
		}
		$quiz_post['ID'] = $new_quiz_id;
		/**
		 * This filter is used to change the copy word used for cloned modules
		 *
		 * @since 1.2.8
		 * @var integer $new_quiz_id The new ID before Post update.
		 * @var integer $old_id The ID of the source module being cloned.
		 */
		$new_module_slug         = apply_filters( 'ldcc_duplicate_slug', 'Copy', $new_quiz_id, $old_id );
		$quiz_post['post_title'] = $quiz_post['post_title'] . ' ' . $new_module_slug;

		$new_quiz_id = wp_update_post( wp_slash( $quiz_post ), true );
		if ( ! isset( $_SESSION ) ) {
			session_start();
		}
		if ( ! isset( $_SESSION['course_association'] ) || ! is_array( $_SESSION['course_association'] ) ) {
			wp_send_json_error( new \WP_Error( '001', __( 'Some error occurred. The Quiz was not fully cloned.', 'ld-content-cloner' ) ) );
			die();
		}
		$h_course = filter_var( $_SESSION['course_association'][ $course_id ], FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );// Multidimensional array containing course hierarchy.
		
		self::getLDCourseSteps( $h_course, $course_id );
		/**
		 * This action will run after quiz clone post is created.
		 *
		 * @since 1.2.8 [<description>]
		 */
		do_action( 'ldcc_quiz_clone_post_created', $new_quiz_id, $old_id );
		if ( ! is_wp_error( $new_quiz_id ) ) {
			$ld_quiz_data_old = get_post_meta( $quiz_id, '_sfwd-quiz', true );
			if ( empty( $pro_quiz_id_old = $ld_quiz_data_old['sfwd-quiz_quiz_pro'] ) ) {
				$pro_quiz_id_old = get_post_meta( $quiz_id, 'quiz_pro_id', true );
			}

			$wp_pro_quiz_id = self::ldccQuizBuilder( $new_quiz_id, $pro_quiz_id_old, $old_id );
			self::setMeta(
				'quiz',
				$quiz_id,
				$new_quiz_id,
				array(
					'lesson_id'       => $lesson_id,
					'course_id'       => $course_id,
					'old_course_id'   => $old_course_id,
					'quiz_pro_id'     => $wp_pro_quiz_id,
					'quiz_pro_id_old' => $pro_quiz_id_old,
				),
				$ld_builder_settings['course_builder'],
				$ld_builder_settings['shared_steps_course']
			);
			$questions       = self::ldccGetQuizQuestions( $quiz_id, $pro_quiz_id_old );
			$returnQuestions = self::ldccQuestionBuilder( $wp_pro_quiz_id, $questions );

			if ( $ld_builder_settings['quiz_builder'] == '' ) {
			} elseif ( $ld_builder_settings['quiz_builder'] == 'yes' && $ld_builder_settings['shared_steps_quiz'] == 'yes' ) {
				self::ldccQuestionBuilderEnabled( $returnQuestions, $new_quiz_id );
			} elseif ( $ld_builder_settings['quiz_builder'] == 'yes' && $ld_builder_settings['shared_steps_quiz'] == '' ) {
				self::ldccQuestionBuilderEnabled( $returnQuestions, $new_quiz_id );
			}

			// Get quiz question data from SQL in sorted order
			global $wpdb;
			$table_prefix = $wpdb->prefix;
			$query        = 'SELECT id FROM ' . $table_prefix . 'learndash_pro_quiz_question WHERE quiz_id = ' . $wp_pro_quiz_id . ' ORDER BY sort ASC';

			// Sorted quiz question result data.
			$data = $wpdb->get_results( $query, ARRAY_A );

			// Creating sorted question array data
			foreach ( $data as $qusestion_post ) {
				$question_post_id              = \learndash_get_question_post_by_pro_id( $qusestion_post['id'] );
				$sortdata[ $question_post_id ] = $qusestion_post['id'];
			}

			// Updating sort order for questions after post quiz question creation.
			update_post_meta( $new_quiz_id, 'ld_quiz_questions', $sortdata );

			$send_result = array( 'success' => array() );
		} else {
			$send_result = array( 'error' => __( 'Some error occurred. The Quiz was not fully cloned.', 'ld-content-cloner' ) );
		}
		if ( $send_response ) {
			echo json_encode( $send_result );
			die();
		}
	}

	public static function ldccQuestionBuilderEnabled( $questions, $new_quiz_id ) {
		$question_post_ids = array();
		foreach ( $questions as $question_pro_id ) {
			$question_pro_mapper = new \WpProQuiz_Model_QuestionMapper();
			$question_post_ids[] = self::ldccCreateQuestionPost( $question_pro_id, $question_pro_mapper, $new_quiz_id );
		}
		$course_id = filter_input( INPUT_POST, 'course_id', FILTER_VALIDATE_INT );
		if ( ! isset( $_SESSION['course_association'] ) ) {
			session_start();
		}
		if ( ! isset( $_SESSION['course_association'] ) || ! is_array( $_SESSION['course_association'] ) ) {
			wp_send_json_error( new \WP_Error( '001', __( 'Some error occurred. The Quiz was not fully cloned.', 'ld-content-cloner' ) ) );
			die();
		}
		$h_course = filter_var( $_SESSION['course_association'][ $course_id ], FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );// Multidimensional array containing course hierarchy.
		self::getLDCourseSteps( $h_course, $course_id );
	}

	public static function ldccCreateQuestionPost( $question_pro_id, $question_pro_mapper, $new_quiz_id ) {
		$question_pro = $question_pro_mapper->fetch( $question_pro_id );

		$question_post_array = array(
			'post_type'    => learndash_get_post_type_slug( 'question' ),
			'post_title'   => $question_pro->getTitle(),
			'post_content' => $question_pro->getQuestion(),
			'post_status'  => 'publish',
		);
		$question_post_id    = learndash_get_question_post_by_pro_id( $question_pro_id );
		if ( ! $question_post_id ) {
			$question_post_id = wp_insert_post( wp_slash( $question_post_array ) );
		}
		if ( ! is_wp_error( $question_post_id ) ) {
			do_action( 'ldcc_question_clone_post_created', $question_post_id );

			learndash_proquiz_sync_question_fields( $question_post_id, $question_pro );
			// if (!$shared) {
				learndash_update_setting( $question_post_id, 'quiz', absint( $new_quiz_id ) );
			// }
			add_post_meta( $question_post_id, 'ld_quiz_' . absint( $new_quiz_id ), absint( $new_quiz_id ), true );
			// learndash_set_question_quizzes_dirty($question_post_id);
			return $question_post_id;
		}
	}

	public static function ldccGetQuizQuestions( $quiz_id, $pro_quiz_id ) {
		$questionMapper = new \WpProQuiz_Model_QuestionMapper();
		$questions      = $questionMapper->fetchAll( $pro_quiz_id );
		$questionArray  = array();
		foreach ( $questions as $qu ) {
			$questionArray[] = $qu->getId();
		}
		if ( function_exists( 'learndash_get_quiz_questions' ) ) {
			$question_post_ids = learndash_get_quiz_questions( $quiz_id );
			$question_pro_ids  = array();
			if ( ! empty( $question_post_ids ) ) {
				$question_pro_ids = array_filter( array_values( $question_post_ids ) );
			}
			$questionArray = array_unique(
				array_merge(
					$questionArray,
					$question_pro_ids
				)
			);
		}
		return $questionArray;
	}

	public static function ldccQuizBuilder( $new_quiz_id, $pro_quiz_id, $old_id ) {
		$ld_quiz_data = get_post_meta( $new_quiz_id, '_sfwd-quiz', true );
		if ( empty( $ld_quiz_data ) ) {
			$ld_quiz_data = array();
		}
		global $wpdb;
		if ( class_exists( '\LDLMS_DB' ) ) {
			$_tableMaster       = \LDLMS_DB::get_table_name( 'quiz_master', 'wpproquiz' );
			$_tablePrerequisite = \LDLMS_DB::get_table_name( 'quiz_prerequisite', 'wpproquiz' );
			$_tableForm         = \LDLMS_DB::get_table_name( 'quiz_form', 'wpproquiz' );
		} else {
			$_prefix            = $wpdb->prefix . 'wp_pro_quiz_';
			$_tableMaster       = $_prefix . 'master';
			$_tablePrerequisite = $_prefix . 'prerequisite';
			$_tableForm         = $_prefix . 'form';
		}

		// fetch and create in top quiz master table ( wp_pro_quiz_master )
		$pq_query = "SELECT * FROM $_tableMaster WHERE id = %d;";

		$pro_quiz = $wpdb->get_row( $wpdb->prepare( $pq_query, $pro_quiz_id ), ARRAY_A );

		unset( $pro_quiz['id'] );

		$pro_quiz['name'] .= ' ' . apply_filters( 'ldcc_duplicate_slug', 'Copy', $new_quiz_id, $old_id );

		$format = array( '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d' );

		$ins_result = $wpdb->insert( $_tableMaster, $pro_quiz, $format );

		$wp_pro_quiz_id = 0;

		if ( $ins_result !== false ) {
			$wp_pro_quiz_id                     = $wpdb->insert_id;
			$ld_quiz_data['sfwd-quiz_quiz_pro'] = $wp_pro_quiz_id;
			update_post_meta( $new_quiz_id, '_sfwd-quiz', \wdm_recursively_slash_strings( $ld_quiz_data ) );
			// fetch and create in pre-requisites table ( wp_pro_quiz_prerequisite )
			$pqr_query    = "SELECT * FROM $_tablePrerequisite WHERE prerequisite_quiz_id = %d;";
			$pror_quizzes = $wpdb->get_results( $wpdb->prepare( $pqr_query, $pro_quiz_id ), ARRAY_A );
			if ( ! empty( $pror_quizzes ) ) {
				foreach ( $pror_quizzes as $pror_quiz ) {
					$pror_quiz['prerequisite_quiz_id'] = $wp_pro_quiz_id;
					$ins_result                        = $wpdb->insert( $_tablePrerequisite, $pror_quiz, array( '%s', '%s' ) );
				}
			}

			// copy custom fields in quiz
			$frm_query   = "SELECT * FROM $_tableForm WHERE quiz_id = %d;";
			$frm_quizzes = $wpdb->get_results( $wpdb->prepare( $frm_query, $pro_quiz_id ), ARRAY_A );
			if ( ! empty( $frm_quizzes ) ) {
				foreach ( $frm_quizzes as $frm_quiz ) {
					unset( $frm_quiz['form_id'] );
					$frm_quiz['quiz_id'] = $wp_pro_quiz_id;
					$wpdb->insert( $_tableForm, $frm_quiz, array( '%d', '%s', '%d', '%d', '%d', '%s' ) );
				}
			}
		}
		return $wp_pro_quiz_id;
	}

	public static function ldccQuestionBuilder( $wp_pro_quiz_id, $questionArr ) {
		if ( ! empty( $questionArr ) ) {
			$returnQuestions = \LDCC_Course\LDCC_Course::copy_questions( $wp_pro_quiz_id, $questionArr );
		}
		return $returnQuestions;
	}

	public static function setMeta( $post_type, $old_post_id, $new_post_id, $other_data = array(), $course_builder = '', $shared_steps_course = '' ) {
		$exclude_post_meta = array( '_edit_last', '_edit_lock', 'activity_id', 'ir_shared_instructor_ids' );

		$exclude_post_meta = apply_filters( 'LDCC_exclude_post_meta_keys', $exclude_post_meta, $old_post_id, $new_post_id );

		if ( empty( $old_post_id ) || empty( $new_post_id ) ) {
			return false;
		}
		if ( $post_type == 'course' ) {
			\LDCC_Course\LDCC_Course::updateCourseMeta( $old_post_id, $new_post_id );
			array_push( $exclude_post_meta, '_sfwd-courses', 'ld_course_steps', 'ld_course_steps_dirty' );
		} elseif ( $post_type == 'lesson' ) {
			$old_course_id = $other_data['old_course_id'];
			\LDCC_Course\LDCC_Course::updateLessonMeta( $old_post_id, $new_post_id, $other_data, $shared_steps_course );
			array_push( $exclude_post_meta, '_sfwd-lessons', 'course_id', 'course_' . $old_course_id . '_lessons_list', 'ld_course_' . $old_course_id, 'lesson_id', '_sfwd-topic' );
		} elseif ( $post_type == 'quiz' ) {
			$old_course_id   = $other_data['old_course_id'];
			$quiz_pro_id_old = $other_data['quiz_pro_id_old'];
			\LDCC_Course\LDCC_Course::updateQuizMeta( $old_post_id, $new_post_id, $other_data, $shared_steps_course );

			array_push( $exclude_post_meta, '_sfwd-quiz', 'course_id', 'lesson_id', 'ld_course_' . $old_course_id, 'ld_quiz_questions', 'quiz_pro_id', 'quiz_pro_id_' . $quiz_pro_id_old, 'quiz_pro_primary_' . $quiz_pro_id_old );
		}
		$old_post_meta = get_post_meta( $old_post_id );
		if ( ! empty( $old_post_meta ) ) {
			foreach ( $old_post_meta as $key => $value ) {
				if ( ! in_array( $key, $exclude_post_meta ) ) {
					update_post_meta( $new_post_id, $key, \wdm_recursively_slash_strings( get_post_meta( $old_post_id, $key, true ) ) );
				}
			}
		}
		unset( $value );
		unset( $course_builder );
		return true;
	}
}
