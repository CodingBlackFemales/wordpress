<?php
/**
 * LearnDash Import Quiz Questions
 *
 * This file contains functions to handle import of the LearnDash Quiz Questions
 *
 * @package LearnDash\Import
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LearnDash_Import_Quiz_Question' ) ) && ( class_exists( 'LearnDash_Import_Post' ) ) ) {
	/**
	 * Class to import LearnDash Quiz Questions
	 */
	class LearnDash_Import_Quiz_Question extends LearnDash_Import_Post {

		/**
		 * Version
		 *
		 * @var string Version.
		 */
		private $version = '1.0';

		/**
		 * Constructor
		 */
		public function __construct() {
		}

		/**
		 * Get Questions
		 *
		 * @return array
		 */
		public function startQuizQuestionSet() {
			$pro_quiz_question_import = new WpProQuiz_Model_Question();

			return $pro_quiz_question_import->get_object_as_array();
		}

		/**
		 * Save Questions
		 *
		 * @param array $quiz_question_data Array of quiz question data.
		 */
		public function saveQuizQuestionSet( $quiz_question_data = array() ) {
			if ( ! empty( $quiz_question_data ) ) {

				// Called to ensure we have a working Question Set ( WpProQuiz_Model_Question ).
				$pro_quiz_question_import = new WpProQuiz_Model_Question();
				$pro_quiz_question_import->set_array_to_object( $quiz_question_data );

				$quiz_question_mapper = new WpProQuiz_Model_QuestionMapper();
				$new_question         = $quiz_question_mapper->save( $pro_quiz_question_import );
				if ( is_a( $new_question, 'WpProQuiz_Model_Question' ) ) {
					return $new_question->getId();
				}
			}
		}

		/**
		 * Get Question Answer Types
		 *
		 * @return array
		 */
		public function startQuizQuestionAnswerTypesSet() {
			$pro_quiz_question_answer_types_import = new WpProQuiz_Model_AnswerTypes();

			return $pro_quiz_question_answer_types_import->get_object_as_array();
		}

		// End of functions.
	}
}
