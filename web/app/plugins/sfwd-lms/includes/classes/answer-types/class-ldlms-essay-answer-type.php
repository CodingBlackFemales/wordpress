<?php
/**
 * Class for getting answers and student nodes for `essay` type questions.
 *
 * @since 3.3.0
 * @package Learndash\Question\Essay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LDLMS_Essay_Answer' ) ) {

	/**
	 * Class LDLMS_Essay_Answer
	 *
	 * @package Learndash
	 */
	class LDLMS_Essay_Answer extends LDLMS_Base_Answer_Type {

		/**
		 * Override parent setup call.
		 */
		public function setup() {
			parent::setup();
			add_filter( 'learndash_quiz_statistics_question_rest_response', array( $this, 'add_links' ), 10, 2 );
		}

		/**
		 * Get answers data in the form of array.
		 *
		 * @return array
		 */
		public function get_answers() {
			$answers = array();

			foreach ( $this->answer_data as $pos => $answer ) {
				$answers[ $this->get_answer_key( (string) $pos ) ] = array(
					'essay_type'    => $answer->getGradedType(),
					'essay_grading' => $answer->getGradingProgression(),
				);
			}

			return $answers;
		}

		/**
		 * Get student's answers' response.
		 *
		 * @return array
		 */
		public function get_student_answers() {
			$student_answers = array(
				'answer_key' => $this->get_answer_key( '0' ),
				'essay'      => $this->student_answers->graded_id,
				'status'     => get_post_status( $this->student_answers->graded_id ),
			);

			if ( in_array( $student_answers['status'], array( 'graded' ), true ) ) {
				$student_answers['points'] = $this->answer_data[0]->getPoints();
			}

			return $student_answers;
		}

		/**
		 * Add links to response object.
		 *
		 * @param stdClass                 $response Response object.
		 * @param WpProQuiz_Model_Question $question Question object.
		 *
		 * @return stdClass
		 */
		public function add_links( stdClass $response, WpProQuiz_Model_Question $question ) {
			if ( $this->question->getId() === $question->getId() ) {
				$obj       = get_post_type_object( learndash_get_post_type_slug( 'essay' ) );
				$rest_base = ! empty( $obj->rest_base ) ? $obj->rest_base : $obj->name;
				$post_id   = $this->student_answers->graded_id;

				$links = array(
					'post' => array(
						array(
							'href'       => get_rest_url( null, trailingslashit( '/ldlms/v2/' . $rest_base ) . $post_id ),
							'embeddable' => true,
						),
					),
				);

				$response->_links = $links;
			}

			return $response;
		}
	}
}
