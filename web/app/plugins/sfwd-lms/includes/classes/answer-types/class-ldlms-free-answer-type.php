<?php
/**
 * Class for getting answers and student nodes for `free` type questions.
 *
 * @since 3.3.0
 * @package Learndash\Question\Free
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LDLMS_Free_Answer' ) ) {

	/**
	 * Class LDLMS_Sort_Answer
	 *
	 * @package Learndash
	 */
	class LDLMS_Free_Answer extends LDLMS_Base_Answer_Type {
		/**
		 * Parsed list of answers for a question.
		 *
		 * @var array
		 */
		private $parsed_answers;

		/**
		 * LDLMS_Cloze_Answer constructor.
		 *
		 * @param WpProQuiz_Model_Question          $question        Question model object.
		 * @param string                            $student_answers Submitted answers' list.
		 * @param WpProQuiz_Model_StatisticRefModel $stat_ref_model  Statistic reference model.
		 */
		public function __construct( WpProQuiz_Model_Question $question, $student_answers = null, WpProQuiz_Model_StatisticRefModel $stat_ref_model = null ) {
			parent::__construct( $question, $student_answers, $stat_ref_model );

			$this->parsed_answers = $this->parse_answers();
		}

		/**
		 * Override setup method in parent.
		 *
		 * @return void
		 */
		public function setup() {
			parent::setup();

			remove_filter( 'learndash_rest_statistic_answer_node_data', array( $this, 'maybe_add_points' ), 10 );
			add_filter( 'learndash_rest_statistic_answer_node_data', array( $this, 'student_answers_value_key' ), 30, 5 );
		}

		/**
		 * Get answers data in the form of array.
		 *
		 * @return array
		 */
		public function get_answers() {
			$answers = array();

			$answer_label_set = array();

			$answer_key = $this->get_answer_key( 0 );
			foreach ( $this->parsed_answers as $key => $answer_set ) {

				$answer_set_key = $answer_key . '-' . $key;

				if ( ( isset( $answer_set['label'] ) ) && ( ! empty( $answer_set['label'] ) ) ) {

					$answer_label_set[ $answer_set_key ] = array(
						'label' => $answer_set['label'],
					);

					if ( $this->question->isAnswerPointsActivated() ) {
						$points = 1;
						if ( isset( $answer_set['points'] ) ) {
							$points = $answer_set['points'];
						}
						$answer_label_set[ $answer_set_key ]['points'] = $points;
					}
				}
			}

			$answers[ $answer_key ] = array(
				'values' => $answer_label_set,
			);

			return $answers;
		}

		/**
		 * Get student's answers' response.
		 *
		 * @return array
		 */
		public function get_student_answers() {
			$answers = array();

			$question_answer_sets = $this->get_answers();

			foreach ( $this->student_answers as $student_answer_key => $student_answer ) {
				$answers[ $student_answer_key ] = array(
					'answer_key' => $this->get_answer_key( $student_answer_key ),
					'answer'     => $student_answer,
					'correct'    => false,
				);

				foreach ( $question_answer_sets as $question_answer_set_key => $question_answer_set ) {
					if ( ( ! isset( $question_answer_set['values'] ) ) || ( empty( $question_answer_set['values'] ) ) ) {
						continue;
					}
					foreach ( $question_answer_set['values'] as $answer_set_key => $answer_set ) {
						if ( ( ! isset( $answer_set['label'] ) ) || ( '' === $answer_set['label'] ) ) {
							continue;
						}

						/**
						 * Filters whether to convert quiz question free to lowercase or not.
						 *
						 * @since 3.5.0
						 *
						 * @param bool   $convert_to_lower Whether to convert quiz question free to lower case.
						 * @param object $question         WpProQuiz_Model_Question Question Model instance.
						*/
						if ( apply_filters( 'learndash_quiz_question_free_answers_to_lowercase', true, $this->question ) ) {
							if ( function_exists( 'mb_strtolower' ) ) {
								$student_answer_filtered   = mb_strtolower( $student_answer );
								$answer_set_label_filtered = mb_strtolower( $answer_set['label'] );
							} else {
								$student_answer_filtered   = strtolower( $student_answer );
								$answer_set_label_filtered = strtolower( $answer_set['label'] );
							}
						}

						if ( $student_answer_filtered == $answer_set_label_filtered ) {
							$answers[ $student_answer_key ]['correct']   = true;
							$answers[ $student_answer_key ]['value_key'] = $answer_set_key;

							if ( $this->question->isAnswerPointsActivated() ) {
								if ( isset( $answer_set['points'] ) ) {
									$answers[ $student_answer_key ]['points'] = $answer_set['points'];
								}
							}
							break;
						}
					}
				}
			}

			return $answers;
		}

		/**
		 * Add the `value_key` to answer data.
		 * Also, if label is not required, omit it.
		 *
		 * @param array                       $answer_data       Answer Data.
		 * @param string                      $answer_type       Type of answer node.
		 * @param WpProQuiz_Model_AnswerTypes $answer_type_model Answer type model object.
		 * @param int                         $question_id       Question ID.
		 * @param int                         $key               Position of answer.
		 *
		 * @return array
		 */
		public function student_answers_value_key( array $answer_data, $answer_type, $answer_type_model, $question_id, $key = 0 ) {
			if ( $question_id !== $this->question->getId() ) {
				return $answer_data;
			}

			switch ( $answer_type ) {

				case 'answer':
					unset( $answer_data['label'] );
					unset( $answer_data['points'] );
					break;
			}

			return $answer_data;
		}

		/**
		 * Parse the answer in array form from the answer markup.
		 *
		 * @return array List of parsed answers.
		 */
		private function parse_answers() {
			$answer = array();
			foreach ( $this->answer_data as $index => $answer_data ) {
				$question_answer_data = learndash_question_free_get_answer_data( $answer_data, $this->question );
				if ( isset( $question_answer_data['correct'] ) ) {
					foreach ( $question_answer_data['correct'] as $answer_idx => $answer_labels ) {
						$answer_points = array( 1 );
						if ( isset( $question_answer_data['points'][ $answer_idx ] ) ) {
							$answer_points = $question_answer_data['points'][ $answer_idx ];
						}
						$answer[] = array(
							'label'  => $answer_labels,
							'points' => $answer_points,
						);
					}
				}
			}
			return $answer;
		}
	}
}
