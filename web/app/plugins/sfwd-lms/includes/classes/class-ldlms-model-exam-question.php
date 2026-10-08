<?php
/**
 * Class to extend LDLMS_Model_Post to LDLMS_Model_Exam_Question.
 *
 * @since 4.0.0
 * @package LearnDash\Exam
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LDLMS_Model_Post' ) ) && ( ! class_exists( 'LDLMS_Model_Exam_Question' ) ) ) {
	/**
	 * Class for LearnDash Exam Question.
	 *
	 * @since 4.0.0
	 * @uses LDLMS_Model
	 */
	class LDLMS_Model_Exam_Question extends LDLMS_Model_Post {

		/**
		 * Question block array.
		 *
		 * @since 4.0.0
		 *
		 * @var array
		 */
		protected $question_block = array();

		/**
		 * Class constructor.
		 *
		 * @since 4.0.0
		 *
		 * @param array $question_block Question block array.
		 *
		 * @throws LDLMS_Exception_NotFound When post not loaded.
		 *
		 * @return mixed instance of class or exception.
		 */
		public function __construct( $question_block = array() ) {
			$this->question_block = $question_block;

			$this->init();

			return $this;
		}

		/**
		 * Initialize question.
		 *
		 * @since 4.0.0
		 */
		private function init() {

			$this->question_block['attrs']['question_valid']           = $this->validate_question_block();
			$this->question_block['attrs']['question_graded']          = false;
			$this->question_block['attrs']['question_grade']           = false;
			$this->question_block['attrs']['learndash_question_model'] = $this;

			if ( ( isset( $this->question_block['innerBlocks'] ) ) && ( is_array( $this->question_block['innerBlocks'] ) ) ) {
				foreach ( $this->question_block['innerBlocks'] as &$question_block_inner ) {
					$question_block_inner['attrs']['learndash_exam_model']     = $this->question_block['attrs']['learndash_exam_model'];
					$question_block_inner['attrs']['learndash_question_model'] = $this;

					// It the answer block does not have the 'answer' attrs we grab from parent.
					if ( 'learndash/ld-question-answers-block' === $question_block_inner['blockName'] ) {
						if ( ( isset( $question_block_inner['attrs']['answers'] ) ) && ( ! empty( $question_block_inner['attrs']['answers'] ) ) ) {
							foreach ( $question_block_inner['attrs']['answers'] as $question_block_answer_idx => &$question_block_answer ) {
								$question_block_answer['answer_idx']           = $question_block_answer_idx;
								$question_block_answer['answer_grade']         = false;
								$question_block_answer['student_answer_value'] = null;
							}
						}
					}
				}
			}

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
				case 'question_graded':
					if ( isset( $this->question_block['attrs']['question_graded'] ) ) {
						$return_val = (bool) $this->question_block['attrs']['question_graded'];
					}
					break;

				case 'get_grade':
				case 'question_grade':
					if ( isset( $this->question_block['attrs']['question_grade'] ) ) {
						$return_val = (bool) $this->question_block['attrs']['question_grade'];
					}
					break;

				case 'question_idx':
					if ( isset( $this->question_block['attrs']['question_idx'] ) ) {
						$return_val = (int) $this->question_block['attrs']['question_idx'];
					}
					break;

				case 'question_number':
					if ( isset( $this->question_block['attrs']['question_number'] ) ) {
						$return_val = (int) $this->question_block['attrs']['question_number'];
					}
					break;

				case 'question_type':
					if ( isset( $this->question_block['attrs']['question_type'] ) ) {
						$return_val = $this->question_block['attrs']['question_type'];
					}
					break;

				case 'question_title':
					if ( isset( $this->question_block['attrs']['question_title'] ) ) {
						$return_val = $this->question_block['attrs']['question_title'];
					}
					break;

				case 'is_valid':
				case 'question_valid':
					if ( isset( $this->question_block['attrs']['question_valid'] ) ) {
						$return_val = (bool) $this->question_block['attrs']['question_valid'];
					} else {
						$return_val = false;
					}
					break;

				case 'get_block':
					$return_val = $this->question_block;
					break;

				case 'exam_model':
					if ( isset( $this->question_block['attrs']['learndash_exam_model'] ) ) {
						$return_val = $this->question_block['attrs']['learndash_exam_model'];
					}
					break;

				default:
					break;
			}

			return $return_val;
		}

		/**
		 * Get the Question classes.
		 *
		 * @since 4.0.0
		 *
		 * @param string $return_type     Return type 'array' (default) or 'string'.
		 *
		 * @return array of classes.
		 */
		public function get_question_classes( $return_type = 'array' ) {
			$question_classes = array( 'ld-exam-question' );

			$question_classes = array_unique( $question_classes );

			if ( 'string' === $return_type ) {
				if ( ! empty( $question_classes ) ) {
					return implode( ' ', $question_classes );
				}
				return '';
			}

			return $question_classes;
		}

		/**
		 * Get the Question Answer classes.
		 *
		 * @since 4.0.0
		 *
		 * @param array  $question_answer Question answer array.
		 * @param string $return_type     Return type 'array' (default) or 'string'.
		 *
		 * @return array of classes.
		 */
		public function get_answer_classes( $question_answer = array(), $return_type = 'array' ) {
			$question_answer_classes = array( 'ld-exam-question-answer' );

			$question_answer_classes = array_unique( $question_answer_classes );

			if ( 'string' === $return_type ) {
				if ( ! empty( $question_answer_classes ) ) {
					return implode( ' ', $question_answer_classes );
				}
				return '';
			}

			return $question_answer_classes;
		}


		/**
		 * Validate Question block.
		 *
		 * @since 4.0.0
		 * @return boolean True if valid.
		 */
		protected function validate_question_block() {
			if ( ! is_array( $this->question_block ) ) {
				return false;
			}

			if ( ! isset( $this->question_block['attrs']['question_idx'] ) ) {
				return false;
			}

			if ( ! isset( $this->question_block['attrs']['question_number'] ) ) {
				return false;
			}

			if ( ! isset( $this->question_block['attrs']['exam_id'] ) ) {
				return false;
			}

			if ( ! isset( $this->question_block['attrs']['learndash_exam_model'] ) ) {
				return false;
			}

			// If the question doesn't have a title.
			if ( ( ! isset( $this->question_block['attrs']['question_title'] ) ) || ( empty( $this->question_block['attrs']['question_title'] ) ) ) {
				return false;
			}

			// If the question doesn't have a type.
			if ( ( ! isset( $this->question_block['attrs']['question_type'] ) ) || ( empty( $this->question_block['attrs']['question_type'] ) ) ) {
				return false;
			}

			// If the question doesn't have inner blocks. Inner blocks are where the answers are stored.
			if ( ( ! isset( $this->question_block['innerBlocks'] ) ) || ( ! is_array( $this->question_block['innerBlocks'] ) ) || ( empty( $this->question_block['innerBlocks'] ) ) ) {
				return false;
			}

			// Find the inner block containing the answers.
			$question_block_answers = false;
			foreach ( $this->question_block['innerBlocks'] as &$question_block_inner ) {
				if ( 'learndash/ld-question-answers-block' === $question_block_inner['blockName'] ) {
					$question_block_answers = $question_block_inner;
					break;
				}
			}
			if ( false === $question_block_answers ) {
				return false;
			}

			// Validate the answers.
			if ( ( ! isset( $question_block_answers['attrs']['answers'] ) ) || ( ! is_array( $question_block_answers['attrs']['answers'] ) ) || ( empty( $question_block_answers['attrs']['answers'] ) ) ) {
				return false;
			}

			// Ensure at least one 'correct' answer is set.
			$question_block_correct_answer = false;
			foreach ( $question_block_answers['attrs']['answers'] as $answer_idx => $answer_block ) {
				if ( ( isset( $answer_block['answer_correct'] ) ) && ( true === (bool) $answer_block['answer_correct'] ) ) {
					$question_block_correct_answer = true;
					break;
				}
			}
			if ( false === $question_block_correct_answer ) {
				return false;
			}

			// All good.
			return true;
		}

		/**
		 * Show the Exam Question front.
		 *
		 * @since 4.0.0
		 *
		 * @return string HTML Exam content.
		 */
		public function get_front_content() {
			$question_output = '';

			if ( $this->is_valid ) {
				$question_output = render_block( $this->question_block );
			}

			return $question_output;
		}

		/**
		 * Grade Exam Question from user submitted answers.
		 *
		 * @since 4.0.0
		 * @param array $student_submit_data User submitted answers.
		 * @return bool true is correct.
		 */
		public function question_grade( $student_submit_data = array() ) {
			$question_grade = '';

			$this->question_block['attrs']['question_graded'] = false;
			$this->question_block['attrs']['question_grade']  = false;

			return $this->question_block['attrs']['question_grade'];
		}

		/** The methods below here are static and shared between all instances of this class */

		/**
		 * Get the question types.
		 *
		 * @since 4.0.0
		 *
		 * @return array Array for question types.
		 */
		private static function get_types() {
			return array(
				'single'   => array(
					'key'   => 'single',
					'label' => esc_html__( 'Single Choice', 'learndash' ),
					'model' => 'LDLMS_Model_Exam_Question_Single',
				),
				'multiple' => array(
					'key'   => 'multiple',
					'label' => esc_html__( 'Multiple Choice', 'learndash' ),
					'model' => 'LDLMS_Model_Exam_Question_Multiple',
				),
			);
		}

		/**
		 * Get the question type set.
		 *
		 * @since 4.0.0
		 *
		 * @param string $question_type question type slug.
		 * @return array Array for question type or null.
		 */
		public static function get_type_set( $question_type = '' ) {
			$question_types = self::get_types();
			if ( ( ! empty( $question_type ) ) && ( isset( $question_types[ $question_type ] ) ) ) {
				return $question_types[ $question_type ];
			}

			return null;
		}

		/**
		 * Get the model for question type.
		 *
		 * @since 4.0.0
		 *
		 * @param string $question_type question type slug.
		 * @return string Model for question type or null.
		 */
		public static function get_model_by_type( $question_type = '' ) {
			$question_types = self::get_types();
			if ( ( ! empty( $question_type ) ) && ( isset( $question_types[ $question_type ] ) ) ) {
				return $question_types[ $question_type ]['model'];
			}

			return null;
		}

		/**
		 * Get the label for question type.
		 *
		 * @since 4.0.0
		 *
		 * @param string $question_type question type slug.
		 * @return string Label question type or null.
		 */
		public static function get_label_by_type( $question_type = '' ) {
			$question_types = self::get_types();
			if ( ( ! empty( $question_type ) ) && ( isset( $question_types[ $question_type ] ) ) ) {
				return $question_types[ $question_type ]['label'];
			}

			return null;
		}

		// End of functions.
	}
}

require_once 'exam-question-types/class-ldlms-model-exam-question-single.php';
require_once 'exam-question-types/class-ldlms-model-exam-question-multiple.php';
