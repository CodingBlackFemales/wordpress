<?php
/**
 * Class to extend LDLMS_Model_Post to LDLMS_Model_Question.
 *
 * @since 3.4.0
 * @package LearnDash\Question
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LDLMS_Model_Post' ) ) && ( ! class_exists( 'LDLMS_Model_Question' ) ) ) {
	/**
	 * Class for LearnDash Model Question.
	 *
	 * @since 3.4.0
	 * @uses LDLMS_Model_Post
	 */
	class LDLMS_Model_Question extends LDLMS_Model_Post {

		/**
		 * Initialize post.
		 *
		 * @since 3.4.0
		 *
		 * @param int $question_id Question Post ID to load.
		 */
		public function __construct( $question_id = 0 ) {
			$this->post_type = learndash_get_post_type_slug( 'question' );
			$this->load( $question_id );
		}

		/**
		 * Load question
		 *
		 * @param int $question_id Question ID.
		 */
		public function load( $question_id ) {}

		// End of functions.
	}
}
