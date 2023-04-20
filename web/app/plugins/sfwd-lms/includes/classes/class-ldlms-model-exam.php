<?php
/**
 * Class to extend LDLMS_Model_Post to LDLMS_Model_Exam.
 *
 * @since 4.0.0
 * @package LearnDash\Exam
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LDLMS_Model_Post' ) ) && ( ! class_exists( 'LDLMS_Model_Exam' ) ) ) {
	/**
	 * Class for LearnDash Exam.
	 *
	 * @since 4.0.0
	 * @uses LDLMS_Model
	 */
	class LDLMS_Model_Exam extends LDLMS_Model_Post {

		/**
		 * User ID.
		 *
		 * @since 4.0.0
		 * @var int $user_id User ID.
		 */
		private $user_id = 0;

		/**
		 * Course ID.
		 *
		 * @since 4.0.0
		 * @var int $course_id Course ID.
		 */
		private $course_id = 0;

		/**
		 * Exam Is Graded flag.
		 *
		 * @since 4.0.0
		 * @var bool $exam_is_graded True if exam has been graded. False if not.
		 */
		private $exam_is_graded = false;

		/**
		 * Exam Grade.
		 *
		 * @since 4.0.0
		 * @var bool $exam_grad True if student passed exam. False if not.
		 */
		private $exam_grade = false;

		/**
		 * Question Blocks.
		 *
		 * @since 4.0.0
		 * @var array $question_blocks Array of question blocks.
		 */
		private $question_blocks = array();

		/**
		 * Question Models.
		 *
		 * @since 4.0.0
		 * @var array $question_models Array of question models.
		 */
		private $question_models = array();

		/**
		 * User exam submit data.
		 *
		 * @since 4.0.0
		 * @var array $student_submit_data Array of user submit data.
		 */
		private $student_submit_data = array();

		/**
		 * Class constructor.
		 *
		 * @since 4.0.0
		 *
		 * @param int   $post_id Exam Post ID to load.
		 * @param array $atts    Array of attributes.
		 *
		 * @throws LDLMS_Exception_NotFound When post not loaded.
		 *
		 * @return mixed instance of class or exception.
		 */
		public function __construct( $post_id = 0, $atts = array() ) {
			$this->post_type = learndash_get_post_type_slug( 'exam' );

			if ( ! $this->init( $post_id, $atts ) ) {
				throw new LDLMS_Exception_NotFound();
			} else {
				return $this;
			}
		}

		/**
		 * Initialize post.
		 *
		 * @since 4.0.0
		 *
		 * @param int   $post_id Exam Post ID to load.
		 * @param array $atts    Array of attributes.
		 *
		 * @return bool True if post was loaded. False otherwise.
		 */
		private function init( $post_id = 0, $atts = array() ) {
			$post_id = absint( $post_id );
			if ( ! empty( $post_id ) ) {
				$post = get_post( $post_id );
				if ( ( is_a( $post, 'WP_Post' ) ) && ( $post->post_type === $this->post_type ) ) {
					$this->post_id = $post_id;
					$this->post    = $post;

					if ( isset( $atts['course_id'] ) ) {
						$this->course_id = absint( $atts['course_id'] );
					} else {
						$this->course_id = 0;
					}

					if ( isset( $atts['user_id'] ) ) {
						$this->user_id = absint( $atts['user_id'] );
					} else {
						$this->user_id = 0;
					}

					return true;
				}
			}
			return false;
		}

		/**
		 * Generic getter function to access misc class properties.
		 *
		 * @since 4.0.0
		 * @param string $property Property to access.
		 * @return mixed Value of property or null.
		 */
		public function __get( $property = '' ) {
			$return_val = null;

			switch ( $property ) {
				case 'is_graded':
					$return_val = (bool) $this->is_graded();
					break;

				case 'get_grade':
					$return_val = (bool) $this->get_grade();
					break;

				case 'exam_id':
					$return_val = $this->post_id;
					break;

				case 'course_id':
					$return_val = $this->course_id;
					break;

				case 'user_id':
					$return_val = $this->user_id;
					break;

				case 'questions_count':
					$return_val = (int) $this->get_questions_count();
					break;

				case 'form_nonce':
					$return_val = $this->get_nonce_value();
					break;

				default:
					break;
			}

			return $return_val;
		}

		/**
		 * Class utility function to generate the Exam nonce key.
		 *
		 * @since 4.0.0
		 *
		 * @return string Nonce key.
		 */
		protected function get_nonce_key() {
			$nonce_key = 'ld-exam-' . absint( $this->post_id ) . '-' . absint( $this->user_id ) . '-' . absint( $this->course_id );

			return $nonce_key;
		}

		/**
		 * Class utility function to generate the Exam nonce value.
		 *
		 * @uses wp_create_nonce(), get_nonce_key().
		 *
		 * @since 4.0.0
		 *
		 * @return string Nonce key.
		 */
		protected function get_nonce_value() {
			$nonce_value = '';

			$nonce_key = $this->get_nonce_key();
			if ( ! empty( $nonce_key ) ) {
				$nonce_value = wp_create_nonce( $nonce_key );
			}

			return $nonce_value;
		}

		/**
		 * Public function to check if Exam is graded.
		 *
		 * @since 4.0.0
		 * @return bool True if exam is graded. False if not.
		 */
		public function is_graded() {
			return $this->exam_is_graded;
		}

		/**
		 * Public function to get the Exam grade.
		 *
		 * @since 4.0.0
		 * @return bool True if exam is passed. False if failed. Null if Exam not graded
		 */
		public function get_grade() {
			if ( $this->is_graded() ) {
				return $this->exam_grade;
			}
			return null;
		}

		/**
		 * Public function to get the number of valid exam questions.
		 *
		 * @since 4.0.0
		 * @return int Returns the count of valid questions.
		 */
		public function get_questions_count() {
			$questions_total = 0;
			foreach ( $this->question_models as $question_model ) {
				if ( true === $question_model->is_valid ) {
					$questions_total++;
				}
			}

			return $questions_total;
		}

		/**
		 * Get Exam result message after grading.
		 *
		 * @since 4.0.0
		 *
		 * @param bool $format True to return formatted message. False to return raw message.
		 * @return string Exam result message.
		 */
		public function get_result_message( $format = true ) {
			$result_message = '';
			if ( true === $this->exam_is_graded ) {
				if ( true === $this->exam_grade ) {
					$result_message = learndash_get_setting( $this->post_id, 'message_passed' );
				} else {
					$result_message = learndash_get_setting( $this->post_id, 'message_failed' );
				}

				// Clear if only empty paragraph.
				if ( ( '<p></p>' === $result_message ) ) {
					$result_message = '';
				}

				if ( ( ! empty( $result_message ) ) && ( true === $format ) ) {
					$result_message = do_shortcode( $result_message );
					$result_message = wpautop( $result_message );
				}
			}

			return $result_message;
		}

		/**
		 * Process the Exam submit.
		 *
		 * @since 4.0.0
		 *
		 * @return bool True is passed. False otherwise.
		 */
		public function process_exam_submit() {
			$this->exam_is_graded = false;
			$this->exam_grade     = false;

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( ( isset( $_POST['exam-nonce'] ) ) && ( ! empty( $_POST['exam-nonce'] ) ) && ( wp_verify_nonce( $_POST['exam-nonce'], $this->get_nonce_key() ) ) ) {

				$this->student_submit_data = array();

				if ( ! isset( $_POST['exam_id'] ) ) {
					return $this->exam_grade;
				}
				if ( absint( $_POST['exam_id'] ) !== $this->post_id ) {
					return $this->exam_grade;
				}
				$this->student_submit_data['exam_id'] = absint( $_POST['exam_id'] );

				if ( ! isset( $_POST['course_id'] ) ) {
					return $this->exam_grade;
				}
				if ( absint( $_POST['course_id'] ) !== $this->course_id ) {
					return $this->exam_grade;
				}
				$this->student_submit_data['course_id'] = absint( $_POST['course_id'] );

				if ( ! isset( $_POST['user_id'] ) ) {
					return $this->exam_grade;
				}
				if ( absint( $_POST['user_id'] ) !== $this->user_id ) {
					return $this->exam_grade;
				}
				$this->student_submit_data['user_id'] = absint( $_POST['user_id'] );

				if ( isset( $_POST['ld-exam-question-answer'] ) ) {
					$this->student_submit_data['answers'] = wp_unslash( $_POST['ld-exam-question-answer'] );
				} else {
					$this->student_submit_data['answers'] = array();
				}

				if ( isset( $_POST['exam_started'] ) ) {
					// The 'exam_started' timestamp is set in the Exam JS includes microtime.
					$this->student_submit_data['started'] = absint( $_POST['exam_started'] ) / 1000;
				} else {
					$this->student_submit_data['started'] = 0;
				}
				$this->student_submit_data['ended'] = time();

				$this->exam_is_graded = true;
				$this->exam_questions_grading();
				$this->exam_grade = $this->exam_grading_from_questions();

				$this->set_exam_activity();
				$this->set_course_complete();
			}

			return $this->exam_grade;
		}

		/**
		 * Exam grade questions answers
		 *
		 * @since 4.0.0
		 */
		protected function exam_questions_grading() {
			$this->load_question_models_from_post_content();

			if ( ! empty( $this->question_models ) ) {
				foreach ( $this->question_models as $question_model ) {
					$question_model->question_grade( $this->student_submit_data );
				}
			}

		}

		/**
		 * Process Exam grading from Questions.
		 *
		 * Calculate the overall Exam grade from the collection of Question grades.
		 *
		 * @since 4.0.0
		 */
		public function exam_grading_from_questions() {
			$this->exam_grade = false;
			$this->load_question_models_from_post_content();
			if ( ! empty( $this->question_models ) ) {
				$questions_correct = 0;
				$questions_total   = 0;

				foreach ( $this->question_models as $question_model ) {
					if ( true !== $question_model->is_valid ) {
						continue;
					}

					$questions_total++;

					if ( ( true === $question_model->is_graded ) && ( true === $question_model->get_grade ) ) {
						$questions_correct++;
					}
				}

				if ( ( $questions_total > 0 ) && ( $questions_correct === $questions_total ) ) {
					$this->exam_grade = true;
				}
			}

			return $this->exam_grade;
		}

		/**
		 * Get Exam Result Message Button Parameters.
		 *
		 * Grabs the user defined values for the button that is displayed in the result message when an exam is graded
		 *
		 * @since 4.0.0
		 *
		 * @return array $button_params array of button parameters 'button_label' and 'redirect_url'.
		 */
		public function get_result_button_params() {
			$exam_result_button = array();

			$exam_status_slug = 'not_taken';
			if ( true === $this->exam_is_graded ) {
				if ( true === $this->exam_grade ) {
					$exam_status_slug = 'passed';

					$exam_result_button['button_label'] = learndash_get_setting( $this->post->ID, 'exam_passed_button_label' );
					$exam_result_button['redirect_url'] = learndash_get_setting( $this->post->ID, 'exam_passed_redirect_url' );

					if ( empty( $exam_result_button['button_label'] ) ) {
						$exam_result_button['button_label'] = esc_html__( 'Proceed', 'learndash' );
					}

					if ( empty( $exam_result_button['redirect_url'] ) ) {
						$exam_challenge_course_passed = learndash_get_setting( $this->post_id, 'exam_challenge_course_passed' );
						if ( empty( $exam_challenge_course_passed ) ) {
							$exam_challenge_course_show = learndash_get_setting( $this->post_id, 'exam_challenge_course_show' );
							if ( ! empty( $exam_challenge_course_show ) ) {
								$exam_challenge_course_passed = $exam_challenge_course_show;
							}
						}
						if ( ( ! empty( $exam_challenge_course_passed ) ) && ( is_post_publicly_viewable( $exam_challenge_course_passed ) ) ) {
							$exam_result_button['redirect_url'] = get_permalink( $exam_challenge_course_passed );
						}
					}
				} else {
					$exam_status_slug = 'failed';

					$exam_result_button['button_label'] = learndash_get_setting( $this->post->ID, 'exam_failed_button_label' );
					$exam_result_button['redirect_url'] = learndash_get_setting( $this->post->ID, 'exam_failed_redirect_url' );

					if ( empty( $exam_result_button['button_label'] ) ) {
						$exam_result_button['button_label'] = esc_html__( 'Proceed', 'learndash' );
					}

					if ( empty( $exam_result_button['redirect_url'] ) ) {
						$exam_challenge_course_show = learndash_get_setting( $this->post_id, 'exam_challenge_course_show' );
						if ( ( ! empty( $exam_challenge_course_show ) ) && ( is_post_publicly_viewable( $exam_challenge_course_show ) ) ) {
							$exam_result_button['redirect_url'] = get_permalink( $exam_challenge_course_show );
						}
					}
				}

				/**
				 * Filters the Exam Result button label and URL.
				 *
				 * @since 4.0.0
				 *
				 * @param array $exam_result_button {
				 *    @type string $button_label Button label.
				 *    @type string $redirect_url Button URL.
				 * } An array of attributes.
				 * @param int    $exam_id            Exam Post ID.
				 * @param string $exam_status        Exam Status slug.
				*/
				$exam_result_button = apply_filters(
					'learndash_exam_challenge_to_course_passed_redirect',
					$exam_result_button,
					$this->post->ID,
					$exam_status_slug
				);

			} else {
				// If the exam is not graded then there are not values to set here.
				$exam_result_button['button_label'] = '';
				$exam_result_button['redirect_url'] = '';
			}

			return $exam_result_button;
		}

		/**
		 * Show the Exam front.
		 *
		 * @since 4.0.0
		 *
		 * @return string HTML Exam content.
		 */
		public function get_front_content() {
			$exam_output = '';

			if ( ! is_a( $this->post, 'WP_Post' ) ) {
				return $exam_output;
			}

			$questions_output = '';

			$this->load_question_models_from_post_content();
			if ( ! empty( $this->question_models ) ) {
				foreach ( $this->question_models as $question_model ) {
					$questions_output .= $question_model->get_front_content();
				}
			}

			if ( ! empty( $questions_output ) ) {
				$exam_output .= SFWD_LMS::get_template(
					'exam/partials/exam_result_message.php',
					array(
						'learndash_exam_model' => $this,
					)
				);

				$exam_output .= SFWD_LMS::get_template(
					'exam/partials/exam_header.php',
					array(
						'learndash_exam_model' => $this,
					)
				);

				$exam_output .= SFWD_LMS::get_template(
					'exam/partials/exam_questions.php',
					array(
						'questions_content'    => $questions_output,
						'learndash_exam_model' => $this,
					)
				);

				$exam_output .= SFWD_LMS::get_template(
					'exam/partials/exam_footer.php',
					array(
						'learndash_exam_model' => $this,
					)
				);

				// We replace (not append) the existing $content since we pass it into the template args.
				$exam_output = SFWD_LMS::get_template(
					'exam/exam_wrapper.php',
					array(
						'exam_content'         => $exam_output,
						'learndash_exam_model' => $this,
					)
				);
			}

			return $exam_output;
		}

		/**
		 * Get Question Models LDLMS_Model_Exam_Question from post content.
		 *
		 * @since 4.0.0
		 *
		 * @return array Array of Question models LDLMS_Model_Exam_Question.
		 */
		public function load_question_models_from_post_content() {
			static $questions_loaded = false;

			if ( false === $questions_loaded ) {
				$this->question_blocks = array();

				$questions_loaded = true;
				$content_blocks   = parse_blocks( $this->post->post_content );
				foreach ( $content_blocks as  &$content_block ) {
					if ( 'learndash/ld-exam' === $content_block['blockName'] ) {
						if ( ( isset( $content_block['innerBlocks'] ) ) && ( is_array( $content_block['innerBlocks'] ) ) && ( ! empty( $content_block['innerBlocks'] ) ) ) {
							$question_block_idx = 0;
							foreach ( $content_block['innerBlocks'] as &$exam_inner_block ) {
								if ( 'learndash/ld-exam-question' === $exam_inner_block['blockName'] ) {
									$exam_inner_block['attrs']['question_idx']         = $question_block_idx;
									$exam_inner_block['attrs']['question_number']      = absint( $question_block_idx + 1 );
									$exam_inner_block['attrs']['exam_id']              = $this->post->ID;
									$exam_inner_block['attrs']['learndash_exam_model'] = $this;

									$ld_exam_question_object = LDLMS_Factory_Post::exam_question( $exam_inner_block );
									if ( ( $ld_exam_question_object ) && ( is_a( $ld_exam_question_object, 'LDLMS_Model_Exam_Question' ) ) ) {
										$this->question_models[ $question_block_idx ] = $ld_exam_question_object;
									}

									$question_block_idx++;
								}
							}
						}
					}
				}
			}
			return $this->question_models;
		}

		/**
		 * Set the Activity record and meta for the exam.
		 *
		 * @since 4.0.0
		 *
		 * @return int The inserted/updated activity ID for the Exam or 0 if activity not found.
		 */
		protected function set_exam_activity() {
			$exam_meta = $this->prepare_exam_activity_meta();

			$activity_args = array(
				'course_id'          => $this->course_id,
				'user_id'            => $this->user_id,
				'post_id'            => $this->post_id,
				'activity_type'      => 'exam',
				'activity_status'    => $this->exam_grade,
				'activity_started'   => $this->student_submit_data['started'],
				'activity_completed' => $this->student_submit_data['ended'],
				'activity_updated'   => $this->student_submit_data['ended'],
				'activity_meta'      => $exam_meta,
			);
			$exam_activity = learndash_get_user_activity( $activity_args, true );

			if ( ( is_a( $exam_activity, 'LDLMS_Model_Activity' ) ) && ( property_exists( $exam_activity, 'activity_id' ) ) && ( ! empty( $exam_activity->activity_id ) ) ) {
				return $exam_activity->activity_id;
			}

			return 0;
		}

		/**
		 * Prepare the Activity meta for the exam.
		 *
		 * @since 4.0.0
		 *
		 * @return array Exam meta data.
		 */
		protected function prepare_exam_activity_meta() {
			$exam_meta = array(
				'exam_id'                => $this->post_id,
				'course_id'              => $this->course_id,
				'user_id'                => $this->user_id,
				'exam_post_modified_gmt' => $this->post->post_modified_gmt,
				'exam_is_graded'         => (int) $this->is_graded(),
				'exam_grade'             => (int) $this->get_grade(),
				'questions_count'        => 0,
				'questions_correct'      => 0,
				'questions_percentage'   => 0,
				'ld_version'             => LEARNDASH_VERSION,
			);

			$exam_questions_meta = array();

			$this->load_question_models_from_post_content();
			if ( ! empty( $this->question_models ) ) {
				$exam_meta['questions_count'] = 0;

				foreach ( $this->question_models as $question_model ) {
					if ( true !== $question_model->is_valid ) {
						continue;
					}

					$exam_meta['questions_count']++;

					$question_block_meta = array(
						'question_type'    => $question_model->question_type,
						'question_idx'     => $question_model->question_idx,
						'question_grade'   => $question_model->get_grade,
						'question_answers' => array(),
						'student_answers'  => isset( $this->student_submit_data['answers'][ $question_model->question_idx ] ) ? $this->student_submit_data['answers'][ $question_model->question_idx ] : array(),
					);

					if ( true === $question_block_meta['question_grade'] ) {
						$exam_meta['questions_correct']++;
					}

					$question_block = $question_model->get_block;
					if ( ( isset( $question_block['innerBlocks'] ) ) && ( is_array( $question_block['innerBlocks'] ) ) && ( ! empty( $question_block['innerBlocks'] ) ) ) {
						foreach ( $question_block['innerBlocks'] as &$question_block_inner ) {
							if ( 'learndash/ld-question-answers-block' === $question_block_inner['blockName'] ) {
								if ( isset( $question_block_inner['attrs']['answers'] ) ) {
									$question_block_meta['question_answers'] = $question_block_inner['attrs']['answers'];
									break;
								}
							}
						}
					}

					$exam_questions_meta[ $question_model->question_idx ] = $question_block_meta;
				}

				if ( ( $exam_meta['questions_count'] > 0 ) && ( $exam_meta['questions_correct'] > 0 ) ) {
					$exam_meta['questions_percentage'] = ( $exam_meta['questions_correct'] / $exam_meta['questions_count'] ) * 100;
				}

				$exam_meta['questions'] = $exam_questions_meta;

			}

			return $exam_meta;
		}

		/**
		 * Set the Exam Course as completed if the Exam was passed.
		 *
		 * @since 4.0.0
		 *
		 * @return bool True is course is set as complete. False otherwise.
		 */
		protected function set_course_complete(): bool {
			if ( true === $this->exam_grade ) {
				$exam_challenge_passed = learndash_get_setting( $this->post_id, 'exam_challenge_course_passed' );
				if ( empty( $exam_challenge_passed ) ) {
					$exam_challenge_passed = $this->course_id;
				}

				if ( ! empty( $exam_challenge_passed ) ) {
					$course_access = sfwd_lms_has_access( $exam_challenge_passed, $this->user_id );
					if ( true !== $course_access ) {
						ld_update_course_access( $this->user_id, $exam_challenge_passed );
					}

					if ( ! learndash_course_completed( $this->user_id, $exam_challenge_passed ) ) {
						return learndash_user_course_complete_all_steps( $this->user_id, $exam_challenge_passed );
					}
				}
			}

			return false;
		}
	}
}
