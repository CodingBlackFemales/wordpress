<?php
/**
 * Base class for answer types.
 *
 * This class will also be utilized for `single` and
 * `multiple` answer type questions.
 *
 * Class LDLMS_Base_Answer_Type
 *
 * @since 3.3.0
 * @package Learndash\Question
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LDLMS_Base_Answer_Type' ) ) {

	/**
	 * Class LDLMS_Base_Answer_Type
	 *
	 * @package Learndash
	 */
	class LDLMS_Base_Answer_Type implements LDLMS_Answer {

		/**
		 * Question model object.
		 *
		 * @var WpProQuiz_Model_Question
		 */
		protected $question;

		/**
		 * Answer data submitted by student.
		 *
		 * @var array
		 */
		protected $student_answers;

		/**
		 * Answer data for current question.
		 *
		 * @var array
		 */
		protected $answer_data;

		/**
		 * Model for statistics reference.
		 *
		 * @var WpProQuiz_Model_StatisticRefModel
		 */
		protected $stat_ref_model;

		/**
		 * LDLMS_Base_Answer_Type constructor.
		 *
		 * @param WpProQuiz_Model_Question          $question        Answer data for the question.
		 * @param string                            $student_answers Submitted answer data by student.
		 * @param WpProQuiz_Model_StatisticRefModel $stat_ref_model  Statistics ref model.
		 */
		public function __construct( WpProQuiz_Model_Question $question, $student_answers = null, WpProQuiz_Model_StatisticRefModel $stat_ref_model = null ) {

			$this->question        = $question;
			$this->student_answers = json_decode( $student_answers );
			$this->answer_data     = $this->question->getAnswerData();
			$this->stat_ref_model  = $stat_ref_model;
		}

		/**
		 * Add necessary hooks, call other setup actions.
		 */
		public function setup() {

			add_filter( 'learndash_quiz_statistics_question_rest_response', array( $this, 'add_answer_text' ), 10, 2 );
			add_filter( 'learndash_rest_statistic_answer_node_data', array( $this, 'maybe_add_points' ), 10, 4 );
			add_filter( 'learndash_rest_statistic_answer_node_data', array( $this, 'maybe_add_answer_values' ), 20, 5 );
		}

		/**
		 * Answer key. questionID + position.
		 * Example: '12-2'
		 *
		 * @param string $pos position of the answer in answer set.
		 *
		 * @return string
		 */
		public function get_answer_key( $pos = '' ) {

			return $this->question->getId() . '-' . $pos;
		}

		/**
		 * Get answers data in the form of array.
		 *
		 * @return array
		 */
		public function get_answers() {
			$answers = array();

			foreach ( $this->answer_data as $position => $data ) {

				$answers[ $this->get_answer_key( (string) $position ) ] = array(
					'label'   => $data->getAnswer(),
					'correct' => $data->isCorrect() ? true : false,
				);

				$answer_node_data = $answers[ $this->get_answer_key( (string) $position ) ];

				/**
				 * Filters the individual answer node.
				 *
				 * @since 3.3.0
				 *
				 * @param array  $answer_node_data The answer node.
				 * @param string $type             Whether the node is answer node or student answer node.
				 * @param mixed  $data             Individual answer data.
				 * @param int    $question_id      Question ID.
				 */
				$answer_node_data = apply_filters(
					'learndash_rest_statistic_answer_node_data',
					$answer_node_data,
					'answer',
					$data,
					$this->question->getId()
				);

				$answers[ $this->get_answer_key( (string) $position ) ] = $answer_node_data;
			}

			return $answers;
		}

		/**
		 * Get submitted answers data in form of array.
		 *
		 * @return array
		 */
		public function get_student_answers() {
			$answers = array();

			foreach ( $this->answer_data as $position => $data ) {

				$answers[] = array(
					'answer_key' => $this->get_answer_key( (string) $position ),
					'correct'    => $data->isCorrect() && isset( $this->student_answers[ $position ] ) && (bool) $this->student_answers[ $position ],
					'answer'     => isset( $this->student_answers[ $position ] ) ? (bool) ( (int) $this->student_answers[ $position ] ) : null,
				);

				/**
				 * Filters the individual answer node.
				 *
				 * @since 3.3.0
				 *
				 * @param array  $answer_node_data The answer node.
				 * @param string $type             Whether the node is answer node or student answer node.
				 * @param mixed  $data             Individual answer data.
				 * @param int    $question_id      Question ID.
				 */
				$answers[ $position ] = apply_filters(
					'learndash_rest_statistic_answer_node_data',
					$answers[ $position ],
					'student',
					$data,
					$this->question->getId()
				);
			}

			return $answers;
		}

		/**
		 * Add answer_text node to response object.
		 *
		 * @param stdClass                 $response Response object.
		 * @param WpProQuiz_Model_Question $question Question Model.
		 *
		 * @return stdClass Filtered response.
		 */
		public function add_answer_text( stdClass $response, WpProQuiz_Model_Question $question ) {

			if (
				$question->getId() === $this->question->getId() &&
				property_exists( $this, 'has_answer_text' ) &&
				true === $this->has_answer_text
			) {
				$answer_data           = $this->question->getAnswerData();
				$answer_data           = $answer_data && is_array( $answer_data ) ? $answer_data[0] : '';
				$response->answer_text = $answer_data instanceof WpProQuiz_Model_AnswerTypes ? $answer_data->getAnswer() : '';
			}

			return $response;
		}

		/**
		 * If individual answers points are activated, add points field
		 * to each answer.
		 *
		 * @param array                       $answer_data       Answer Data.
		 * @param string                      $answer_type       Type of answer node.
		 * @param WpProQuiz_Model_AnswerTypes $answer_type_model Answer type model object.
		 * @param int                         $question_id       Question ID.
		 *
		 * @return array
		 */
		public function maybe_add_points( array $answer_data, $answer_type, $answer_type_model, $question_id ) {

			if ( ( $question_id === $this->question->getId() ) && $this->question->isAnswerPointsActivated() && ( $answer_type_model instanceof WpProQuiz_Model_AnswerTypes ) ) {
				$points = $answer_type_model->getPoints();
				if ( isset( $answer_data['correct'] ) ) {
					$correct = $answer_data['correct'];
				} else {
					$correct = null;
				}
				if ( isset( $answer_data['answer'] ) ) {
					$answered = $answer_data['answer'];
				} else {
					$answered = null;
				}

				if ( 'student' === $answer_type ) {

					/**
					 * 1  When answer field is true and it is incorrect, points would be negative
					 * 2. When answer field is true and it is correct, points would be positive.
					 * 3. When answer field is false (Unanswered) and it is not correct/incorrect points would always be zero.
					 */
					$answer_data['points'] = ( $correct && $answered ) ? $points : ( ! $correct && $answered ? 0 - $points : 0 );

				} else {
					$answer_data['points'] = $points;
				}
			}

			return $answer_data;
		}

		/**
		 * Add the values in form of array for all possible answers.
		 *
		 * @param array                       $answer_data       Answer Data.
		 * @param string                      $answer_type       Type of answer node.
		 * @param WpProQuiz_Model_AnswerTypes $answer_type_model Answer type model object.
		 * @param int                         $question_id       Question ID.
		 * @param int                         $key               Position of answer.
		 *
		 * @return array
		 */
		public function maybe_add_answer_values( array $answer_data, $answer_type, $answer_type_model, $question_id, $key = 0 ) {
			if ( ! in_array(
				$this->question->getAnswerType(),
				array(
					'matrix_sort_answer',
				),
				true
			) ) {
				return $answer_data;
			}

			if ( $question_id === $this->question->getId() ) {
				$values = array();

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
						$value_key = '';
						$answers   = $this->get_answers();
						$answers   = array_values( $this->get_answers() );
						$answers   = array_values( $this->get_answers() );
						$values    = ! empty( $answers[ $key ]['values'] ) ? $answers[ $key ]['values'] : array();

						foreach ( $values as $index => $value ) {
							if ( 0 === strcmp( $value['label'], $answer_data['answer'] ) ) {
								$value_key = $index;
								break;
							}
						}

						$answer_data['value_key'] = $value_key;
				}
			}

			return $answer_data;
		}
	}
}
