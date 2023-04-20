<?php
/**
 * Class for getting answers and student nodes for `fill in the blank` type questions.
 *
 * @since 3.3.0
 * @package Learndash\Question\Cloze
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LDLMS_Cloze_Answer' ) ) {

	/**
	 * Class LDLMS_Sort_Answer
	 *
	 * @package Learndash
	 */
	class LDLMS_Cloze_Answer extends LDLMS_Base_Answer_Type {

		/**
		 * This type of question will have answer_text in response object.
		 *
		 * @var bool
		 */
		public $has_answer_text = true;

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
		 * Override parent function call.
		 */
		public function setup() {
			parent::setup();
			add_filter( 'learndash_rest_statistic_answer_node_data', array( $this, 'student_answers_value_key' ), 30, 5 );
		}

		/**
		 * Get answers data in the form of array.
		 *
		 * @return array
		 */
		public function get_answers() {
			$answers = array();

			foreach ( $this->parsed_answers as $key => $answer_set ) {
				$answer_key = $this->get_answer_key( $key );

				if ( ( isset( $answer_set['label'] ) ) && ( is_array( $answer_set['label'] ) ) && ( ! empty( $answer_set['label'] ) ) ) {
					$answer_label_set = array();

					foreach ( $answer_set['label'] as $label_idx => $label_val ) {
						$answer_set_key = $answer_key . '-' . $label_idx;

						$answer_label_set[ $answer_set_key ] = array(
							'label' => $label_val,
						);

						if ( $this->question->isAnswerPointsActivated() ) {
							$points = 1;
							if ( isset( $answer_set['points'][ $label_idx ] ) ) {
								$points = $answer_set['points'][ $label_idx ];
							}
							$answer_label_set[ $answer_set_key ]['points'] = $points;
						}
					}
					$answers[ $answer_key ] = array(
						'values' => $answer_label_set,
					);
				}
			}

			return $answers;
		}

		/**
		 * Get student's answers' response.
		 *
		 * @return array
		 */
		public function get_student_answers() {
			$answers = array();

			foreach ( $this->student_answers as $key => $answer ) {
				$answers[ $key ] = array(
					'answer_key' => $this->get_answer_key( $key ),
					'answer'     => $answer,
					'correct'    => false,
				);

				if ( ( isset( $this->parsed_answers[ $key ]['label'] ) ) && ( is_array( $this->parsed_answers[ $key ]['label'] ) ) && ( ! empty( $this->parsed_answers[ $key ]['label'] ) ) ) {
					if ( apply_filters( 'learndash_quiz_question_cloze_answers_to_lowercase', true ) ) {
						if ( function_exists( 'mb_strtolower' ) ) {
							$user_answer_formatted = mb_strtolower( $answer );
						} else {
							$user_answer_formatted = strtolower( $answer );
						}
					} else {
						$user_answer_formatted = $answer;
					}

					$correct_idx = array_search( $user_answer_formatted, $this->parsed_answers[ $key ]['label'] );
					if ( false !== $correct_idx ) {
						$answers[ $key ]['correct']   = true;
						$answers[ $key ]['value_key'] = $this->get_answer_key( $key ) . '-' . $correct_idx;

						if ( $this->question->isAnswerPointsActivated() ) {
							if ( isset( $this->parsed_answers[ $key ]['points'][ $correct_idx ] ) ) {
								$answers[ $key ]['points'] = $this->parsed_answers[ $key ]['points'][ $correct_idx ];
							}
						}
					}
				}
			}

			return $answers;
		}

		/**
		 * If individual answers points are activated, add points field
		 * to each answer.
		 *
		 * @param array  $answer_data Answer Data.
		 * @param string $answer_type Type of answer node.
		 * @param array  $answer      Answer info.
		 * @param int    $question_id Question ID.
		 *
		 * @return array
		 */
		public function maybe_add_points( array $answer_data, $answer_type, $answer, $question_id ) {
			return $answer_data;
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
					$labels = (array) $answer_data['label'];

					foreach ( $labels as $pos => $label ) {
						$index = $this->get_answer_key( (string) $key );
						$index = $index . '-' . $pos;

						$values[ $index ] = array(
							'label' => $label,
						);

						if ( $this->question->isAnswerPointsActivated() ) {
							$values[ $index ]['points'] = $answer_data['points'];
						}
					}

					$answer_data['values'] = $values;
					break;

				case 'student':
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
				$question_answer_data = learndash_question_cloze_fetch_data( $answer_data->getAnswer() );
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
