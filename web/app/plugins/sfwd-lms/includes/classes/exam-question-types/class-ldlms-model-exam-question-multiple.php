<?php
/**
 * Class to extend LDLMS_Model_Exam_Question to LDLMS_Model_Exam_Question_Multiple.
 *
 * @since 4.0.0
 * @package LearnDash\Exam
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LDLMS_Model_Exam_Question' ) ) && ( ! class_exists( 'LDLMS_Model_Exam_Question_Multiple' ) ) ) {
	/**
	 * Class for LearnDash Exam Question Multiple.
	 *
	 * @since 4.0.0
	 * @uses LDLMS_Model_Exam_Question
	 */
	class LDLMS_Model_Exam_Question_Multiple extends LDLMS_Model_Exam_Question {
		/**
		 * Grade Exam Question from user submitted answers.
		 *
		 * @since 4.0.0
		 * @param array $student_submit_data User submitted answers.
		 * @return bool true is correct.
		 */
		public function question_grade( $student_submit_data = array() ) {
			$question_grade = '';

			if ( true === $this->is_valid ) {

				if ( ( isset( $this->question_block['innerBlocks'] ) ) && ( is_array( $this->question_block['innerBlocks'] ) ) && ( ! empty( $this->question_block['innerBlocks'] ) ) ) {
					foreach ( $this->question_block['innerBlocks'] as &$question_block_inner ) {
						// It the answer block does not have the 'answer' attrs we grab from parent.
						if ( 'learndash/ld-question-answers-block' === $question_block_inner['blockName'] ) {
							foreach ( $question_block_inner['attrs']['answers'] as $answer_idx => &$answer ) {
								if ( ! isset( $answer['answer_correct'] ) ) {
									$answer['answer_correct'] = false;
								}

								if ( isset( $student_submit_data['answers'][ $this->question_idx ][ $answer_idx ] ) ) {
									$answer['student_answer_value'] = (bool) $student_submit_data['answers'][ $this->question_idx ][ $answer_idx ];
								} else {
									$answer['student_answer_value'] = '';
								}

								if ( ( true === (bool) $answer['answer_correct'] ) && ( (bool) $answer['answer_correct'] === (bool) $answer['student_answer_value'] ) ) {
									$answer['answer_grade'] = true;
								} else {
									$answer['answer_grade'] = false;
								}
							}

							$question_grade = false;
							if ( ! empty( $question_block_inner['attrs']['answers'] ) ) {
								$question_grade = true;
								foreach ( $question_block_inner['attrs']['answers'] as $answer_idx => &$answer ) {
									if ( true === (bool) $answer['answer_correct'] ) {

										if ( '' === $answer['student_answer_value'] ) {
											$question_grade = false;
											break;
										}
									} else {
										if ( '' !== $answer['student_answer_value'] ) {
											$question_grade = false;
											break;
										}
									}
								}
							}

							break;
						}
					}
				}

				$this->question_block['attrs']['question_graded'] = true;
				$this->question_block['attrs']['question_grade']  = $question_grade;
			} else {
				$this->question_block['attrs']['question_graded'] = false;
				$this->question_block['attrs']['question_grade']  = false;
			}

			return $this->question_block['attrs']['question_grade'];
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
			// We don't pass the `$return_type` argument to the parent method because we want an array returned.
			$question_classes = parent::get_question_classes();

			$question_classes[] = ' ld-exam-question-type-' . $this->question_type;

			if ( true === $this->exam_model->is_graded ) {
				if ( true === (bool) $this->get_grade ) {
					$question_classes[] = ' ld-exam-question-correct';
				} else {
					$question_classes[] = ' ld-exam-question-incorrect';
				}
			}

			$question_classes = array_unique( $question_classes );

			/**
			 * Filter Question classes for type Multiple (multiple).
			 *
			 * @since 4.0.0
			 *
			 * @param array  $question_classes Array of Question classes.
			 * @param array  $question_type    Question type slug.
			 * @param object $question_model   LDLMS_Model_Exam_Question instance.
			 *
			 * @return array of classes.
			 */
			$question_answer_classes = (array) apply_filters( 'learndash_question_classes', $question_classes, $this->question_type, $this );

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
			$question_answer_classes = parent::get_answer_classes( $question_answer );

			if ( true === $this->exam_model->is_graded ) {
				if ( ( isset( $question_answer['answer_correct'] ) ) && ( true === (bool) $question_answer['answer_correct'] ) ) {
					$question_answer_classes[] = 'ld-exam-question-answer-correct';

					if ( ( isset( $question_answer['answer_grade'] ) ) && ( true === (bool) $question_answer['answer_grade'] ) ) {
						$question_answer_classes[] = 'ld-exam-question-answer-student-correct';
					}
				} else {
					if ( ( isset( $question_answer['student_answer_value'] ) ) && ( '' !== $question_answer['student_answer_value'] ) ) {
						$question_answer_classes[] = 'ld-exam-question-answer-incorrect';
						if ( ( isset( $question_answer['answer_grade'] ) ) && ( true !== (bool) $question_answer['answer_grade'] ) ) {
							$question_answer_classes[] = 'ld-exam-question-answer-student-incorrect';
						}
					}
				}

				if ( ( isset( $question_answer['student_answer_value'] ) ) && ( '' !== $question_answer['student_answer_value'] ) ) {
					$question_answer_classes[] = 'ld-exam-question-answer-student-selected';
				} else {
					$question_answer_classes[] = 'ld-exam-question-answer-student-not-selected';
				}
			}

			$question_answer_classes = array_unique( $question_answer_classes );

			/**
			 * Filter Question Answer classes for type Multiple (multiple).
			 *
			 * @since 4.0.0
			 *
			 * @param array  $question_answer_classes Question answer classes.
			 * @param array  $question_type           Question type slug.
			 * @param array  $question_answer         Question answer array.
			 * @param object $question_model          LDLMS_Model_Exam_Question instance.
			 *
			 * @return array of classes.
			 */
			$question_answer_classes = (array) apply_filters( 'learndash_question_answer_classes', $question_answer_classes, $this->question_type, $question_answer, $this );

			if ( 'string' === $return_type ) {
				if ( ! empty( $question_answer_classes ) ) {
					return implode( ' ', $question_answer_classes );
				}
				return '';
			}

			return $question_answer_classes;
		}

		// End of functions.
	}
}
