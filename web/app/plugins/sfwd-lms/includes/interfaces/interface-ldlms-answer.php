<?php
/**
 * Interface for every answer type.
 *
 * This would be used in REST API quiz-statistics/<id>/questions
 * endpoint in order to get answer data from various question types.
 *
 * @since 3.3.0
 * @package Learndash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface LDLMS_Answer
 *
 * @package Learndash
 */
if ( ! interface_exists( 'LDLMS_Answer' ) ) {

	interface LDLMS_Answer {

		/**
		 * All necessary actions for the object like
		 * adding hooks, calling internal methods etc.
		 *
		 * @since 3.3.0
		 *
		 * @return mixed
		 */
		public function setup();

		/**
		 * Answer key. questionID + position.
		 * Example: '12-2'
		 *
		 * @since 3.3.0
		 *
		 * @param string $pos position of the answer in answer set.
		 *
		 * @return mixed
		 */
		public function get_answer_key( $pos );

		/**
		 * Get answers data in the form of array.
		 *
		 * @since 3.3.0
		 *
		 * @return array
		 */
		public function get_answers();

		/**
		 * Get submitted answers data in form of array.
		 *
		 * @since 3.3.0
		 *
		 * @return array
		 */
		public function get_student_answers();

	}
}
