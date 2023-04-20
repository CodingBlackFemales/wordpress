<?php
/**
 * Class for getting answers and student nodes for `sort` type questions.
 *
 * This class will be used to part answers for `sort` and `matrix_sort` type
 * question's answers.
 *
 * @since 3.3.0
 * @package Learndash\Question\Sort
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LDLMS_Sort_Answer' ) ) {

	/**
	 * Class LDLMS_Sort_Answer
	 *
	 * @package Learndash
	 */
	class LDLMS_Sort_Answer extends LDLMS_Base_Answer_Type {
		/**
		 * Get answers data in the form of array.
		 *
		 * @return array
		 */
		public function get_answers() {
			$answers = array();
			foreach ( $this->answer_data as $position => $data ) {

				$answers[ $this->get_answer_key( (string) $position ) ] = array(
					'label' => $data->getAnswer(),
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
				 * @param int    $position         Position of the answer.
				 */
				$answers[ $this->get_answer_key( (string) $position ) ] = apply_filters(
					'learndash_rest_statistic_answer_node_data',
					$answers[ $this->get_answer_key( (string) $position ) ],
					'answer',
					$data,
					$this->question->getId(),
					$position
				);
			}

			return $answers;
		}

		/**
		 * Get students answers' response.
		 *
		 * @return array
		 */
		public function get_student_answers() {

			$answers = array();

			foreach ( $this->student_answers as $answered_position => $answer ) {

				foreach ( $this->answer_data as $original_position => $data ) {
					$answer_hash = md5( $this->stat_ref_model->getUserId() . $this->question->getId() . $original_position );

					if ( $answer === $answer_hash ) {

						$answers[] = array(
							'answer_key' => $this->get_answer_key( $answered_position ),
							'answer'     => $this->get_answer_key( $original_position ),
							'correct'    => (bool) ( (int) $answered_position === (int) $original_position ),
						);

						$answers[ count( $answers ) - 1 ] = apply_filters(
							'learndash_rest_statistic_answer_node_data',
							$answers[ count( $answers ) - 1 ],
							'student',
							$data,
							$this->question->getId(),
							$answered_position
						);
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
					unset( $answer_data['points'] );
					break;
				case 'student':
					/**
					 * Currently there is only one to one association, so the value key will
					 * point to 1st element only.
					 */
					$answer_data['value_key'] = $answer_data['answer_key'] . '-' . 0;
					break;
			}

			return $answer_data;
		}

	}
}
